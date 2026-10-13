
<?php
session_start();

if (empty($_SESSION["id_usuario"])) {
    header("Location: login.html");
    exit;
}

if (($_SESSION["rol_usuario"] ?? "") !== "Vendedor") {
    http_response_code(403);
    exit("Acceso denegado.");
}

require_once __DIR__ . "/php/conexion.php";

$idTienda = (int)($_SESSION["id_tienda"] ?? 0);

if ($idTienda < 1) {
    exit("Tu cuenta no tiene una tienda asignada.");
}

if (empty($_SESSION["token_apartados"])) {
    $_SESSION["token_apartados"] = bin2hex(random_bytes(32));
}

$token = $_SESSION["token_apartados"];
$mensaje = "";
$tipoMensaje = "error";
$apartados = [];
$tienda = null;
$puedeFiltrarPorTienda = false;

function escaparApartado($valor) {
    return htmlspecialchars((string)($valor ?? ""), ENT_QUOTES, "UTF-8");
}

function formatoPesosApartado($valor) {
    return "$" . number_format((float)$valor, 0, ",", ".");
}

try {
    $consultaTienda = $conexion->prepare(
        "SELECT id_tienda, nombre_tienda
         FROM tienda
         WHERE id_tienda = :id
         LIMIT 1"
    );

    $consultaTienda->execute(["id" => $idTienda]);
    $tienda = $consultaTienda->fetch(PDO::FETCH_ASSOC);

    if (!$tienda) {
        exit("No se encontró la tienda asignada a tu cuenta.");
    }

    // Detectar qué columnas existen para adaptarse al esquema actual.
    $columnas = $conexion
        ->query("SHOW COLUMNS FROM apartado")
        ->fetchAll(PDO::FETCH_COLUMN, 0);

    $tieneIdTienda = in_array("id_tienda", $columnas, true);
    $tieneNombreTienda = in_array("tienda_recogida", $columnas, true);
    $tieneCantidad = in_array("cantidad", $columnas, true);
    $tieneTalla = in_array("talla", $columnas, true);
    $tieneMetodo = in_array("metodo_pago", $columnas, true);

    $puedeFiltrarPorTienda = $tieneIdTienda || $tieneNombreTienda;

    // Definir la condición de pertenencia a la tienda.
    if ($tieneIdTienda) {
        $condicionTienda = "a.id_tienda = :tienda";
        $parametrosTienda = ["tienda" => $idTienda];
    } elseif ($tieneNombreTienda) {
        $condicionTienda = "a.tienda_recogida = :nombre_tienda";
        $parametrosTienda = [
            "nombre_tienda" => $tienda["nombre_tienda"]
        ];
    } else {
        $condicionTienda = "1 = 0";
        $parametrosTienda = [];
        $mensaje = "La tabla apartado no tiene una columna para identificar la tienda asignada.";
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $tokenEnviado = $_POST["token"] ?? "";
        $idApartado = filter_var(
            $_POST["id_apartado"] ?? null,
            FILTER_VALIDATE_INT
        );
        $nuevoEstado = trim($_POST["estado"] ?? "");

        $estadosPermitidos = [
            "Pendiente",
            "Preparado",
            "Entregado"
        ];

        if (!hash_equals($token, $tokenEnviado)) {
            $mensaje = "La solicitud expiró. Actualiza la página e inténtalo de nuevo.";

        } elseif (!$puedeFiltrarPorTienda) {
            $mensaje = "No se puede identificar con seguridad a qué tienda pertenece este apartado.";

        } elseif (!$idApartado || !in_array($nuevoEstado, $estadosPermitidos, true)) {
            $mensaje = "El apartado o el estado seleccionado no son válidos.";

        } else {
            $sqlActualizar = "UPDATE apartado a
                              SET a.estado = :estado
                              WHERE a.id_apartado = :id
                                AND " . $condicionTienda;

            $actualizar = $conexion->prepare($sqlActualizar);

            $parametros = [
                "estado" => $nuevoEstado,
                "id" => $idApartado
            ];

            $parametros = array_merge($parametros, $parametrosTienda);
            $actualizar->execute($parametros);

            if ($actualizar->rowCount() > 0) {
                header("Location: apartados_tienda.php?resultado=actualizado");
                exit;
            }

            $sqlVerificar = "SELECT a.id_apartado
                             FROM apartado a
                             WHERE a.id_apartado = :id
                               AND " . $condicionTienda;

            $verificar = $conexion->prepare($sqlVerificar);

            $parametrosVerificar = ["id" => $idApartado];
            $parametrosVerificar = array_merge(
                $parametrosVerificar,
                $parametrosTienda
            );

            $verificar->execute($parametrosVerificar);

            if ($verificar->fetch()) {
                header("Location: apartados_tienda.php?resultado=actualizado");
                exit;
            }

            $mensaje = "No se encontró ese apartado asignado a tu tienda.";
        }
    }

    if (($_GET["resultado"] ?? "") === "actualizado") {
        $mensaje = "El estado del apartado fue actualizado.";
        $tipoMensaje = "ok";
    }

    $exprCantidad = $tieneCantidad
        ? "a.cantidad"
        : "1 AS cantidad";

    $exprTalla = $tieneTalla
        ? "a.talla"
        : "NULL AS talla";

    $exprTienda = $tieneNombreTienda
        ? "a.tienda_recogida"
        : "t.nombre_tienda AS tienda_recogida";

    $exprMetodo = $tieneMetodo
        ? "a.metodo_pago"
        : "NULL AS metodo_pago";

    $sql = "SELECT
                a.id_apartado,
                a.fecha_apartado,
                a.fecha_entrega,
                a.fecha_salida,
                a.abono,
                a.saldo,
                a.estado,
                $exprCantidad,
                $exprTalla,
                $exprTienda,
                $exprMetodo,
                p.nombre_producto,
                u.nombre,
                u.apellido,
                u.correo
            FROM apartado a
            INNER JOIN producto p
                ON p.id_producto = a.id_producto
            LEFT JOIN usuario u
                ON u.id_usuario = a.id_usuario
            LEFT JOIN tienda t
                ON t.id_tienda = :id_tienda_consulta
            WHERE $condicionTienda
            ORDER BY a.id_apartado DESC";

    $consulta = $conexion->prepare($sql);

    $parametrosConsulta = [
        "id_tienda_consulta" => $idTienda
    ];

    $parametrosConsulta = array_merge(
        $parametrosConsulta,
        $parametrosTienda
    );

    $consulta->execute($parametrosConsulta);
    $apartados = $consulta->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    error_log("Error consultando apartados de tienda: " . $e->getMessage());
    $mensaje = "No fue posible consultar los apartados. Revisa la estructura de la tabla apartado.";
}

$nombreTienda = escaparApartado($tienda["nombre_tienda"] ?? "");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apartados de mi tienda - CONFISENA</title>
    <link rel="stylesheet" href="tienda.css">

    <style>
        .pagina {
            width: 94%;
            max-width: 1250px;
            margin: 30px auto;
        }
        .panel {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 22px;
            margin-bottom: 22px;
        }
        h1, h2 { color: #172554; }
        .subtitulo { color: #64748b; line-height: 1.5; }
        .boton {
            display: inline-block;
            background: #2563eb;
            color: white;
            padding: 10px 14px;
            border: 0;
            border-radius: 7px;
            text-decoration: none;
            cursor: pointer;
        }
        .scroll { overflow-x: auto; margin-top: 15px; }
        table { border-collapse: collapse; width: 100%; min-width: 1000px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #e2e8f0; }
        th { color: #172554; background: #dbeafe; }
        select { padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px; margin-bottom: 7px; }
        .mensaje { padding: 14px; border-radius: 8px; background: white; margin-bottom: 18px; }
        .ok { color: #166534; }
        .error { color: #991b1b; }
        .vacio { padding: 18px; color: #64748b; }
        small { color: #64748b; }
    </style>
</head>
<body>

<header class="encabezado">
    <div class="logo">CONFISENA</div>
    <div class="informacion-vendedor">
        <span>Apartados de mi tienda</span>
        <strong><?= $nombreTienda ?></strong>
    </div>
    <a class="boton-cerrar" href="tienda.php">Volver al panel</a>
</header>

<main class="pagina">
    <section class="panel">
        <h1>Apartados</h1>
        <p class="subtitulo">
            Consulta los apartados asignados a tu tienda y actualiza
            su preparación o entrega.
        </p>
    </section>

    <?php if ($mensaje !== ""): ?>
        <div class="mensaje <?= escaparApartado($tipoMensaje) ?>">
            <?= escaparApartado($mensaje) ?>
        </div>
    <?php endif; ?>

    <section class="panel">
        <h2>Apartados registrados</h2>

        <?php if (!$puedeFiltrarPorTienda): ?>
            <p class="vacio">
                Para consultar los apartados por tienda, agrega la relación
                con la tienda en la tabla apartado.
            </p>

        <?php elseif (empty($apartados)): ?>
            <p class="vacio">
                No hay apartados asignados a tu tienda. La relación actual
                se verifica por el identificador de tienda, si existe, o
                por el nombre exacto de la tienda guardado en el apartado.
            </p>

        <?php else: ?>
            <div class="scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Cliente</th>
                            <th>Producto</th>
                            <th>Talla</th>
                            <th>Cantidad</th>
                            <th>Fecha del apartado</th>
                            <th>Entrega</th>
                            <th>Abono</th>
                            <th>Saldo</th>
                            <th>Estado</th>
                            <th>Actualizar estado</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($apartados as $a): ?>
                        <tr>
                            <td>#<?= (int)$a["id_apartado"] ?></td>

                            <td>
                                <?= escaparApartado(
                                    trim(($a["nombre"] ?? "") . " " . ($a["apellido"] ?? ""))
                                ) ?>
                                <br>
                                <small><?= escaparApartado($a["correo"] ?? "") ?></small>
                            </td>

                            <td><?= escaparApartado($a["nombre_producto"]) ?></td>
                            <td><?= escaparApartado($a["talla"] ?: "No registrada") ?></td>
                            <td><?= (int)$a["cantidad"] ?></td>
                            <td><?= escaparApartado($a["fecha_apartado"]) ?></td>
                            <td><?= escaparApartado($a["fecha_entrega"] ?: "Por confirmar") ?></td>
                            <td><?= formatoPesosApartado($a["abono"]) ?></td>
                            <td><?= formatoPesosApartado($a["saldo"]) ?></td>
                            <td><?= escaparApartado($a["estado"]) ?></td>

                            <td>
                                <form method="POST" action="apartados_tienda.php">
                                    <input type="hidden" name="token" value="<?= escaparApartado($token) ?>">
                                    <input type="hidden" name="id_apartado" value="<?= (int)$a["id_apartado"] ?>">

                                    <select name="estado" required>
                                        <?php foreach (["Pendiente", "Preparado", "Entregado"] as $estado): ?>
                                            <option
                                                value="<?= escaparApartado($estado) ?>"
                                                <?= $a["estado"] === $estado ? "selected" : "" ?>
                                            >
                                                <?= escaparApartado($estado) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <button class="boton" type="submit">
                                        Guardar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="panel">
        <p class="subtitulo">
            La actualización del apartado no confirma el pago del abono.
            El importe pagado debe verificarse por separado.
        </p>
    </section>
</main>

<footer style="text-align:center;padding:25px;color:#64748b;">
    © 2026 CONFISENA - Transformamos la forma de comprar
</footer>

</body>
</html>
