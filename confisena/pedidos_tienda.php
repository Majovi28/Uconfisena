
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

if (empty($_SESSION["token_pedidos"])) {
    $_SESSION["token_pedidos"] = bin2hex(random_bytes(32));
}

$token = $_SESSION["token_pedidos"];
$mensaje = "";
$tipoMensaje = "error";
$pedidos = [];

function escaparPedido($valor) {
    return htmlspecialchars((string)($valor ?? ""), ENT_QUOTES, "UTF-8");
}

function formatoPesosPedido($valor) {
    return "$" . number_format((float)$valor, 0, ",", ".");
}

try {
    $consultaTienda = $conexion->prepare(
        "SELECT nombre_tienda
         FROM tienda
         WHERE id_tienda = :id
         LIMIT 1"
    );
    $consultaTienda->execute(["id" => $idTienda]);
    $tienda = $consultaTienda->fetch(PDO::FETCH_ASSOC);

    if (!$tienda) {
        exit("No se encontró la tienda asignada a tu cuenta.");
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $tokenEnviado = $_POST["token"] ?? "";
        $idPedido = filter_var($_POST["id_pedido"] ?? null, FILTER_VALIDATE_INT);
        $nuevoEstado = trim($_POST["estado_pedido"] ?? "");

        $estadosPermitidos = [
            "Pendiente",
            "En preparación",
            "Listo para entrega",
            "Entregado"
        ];

        if (!hash_equals($token, $tokenEnviado)) {
            $mensaje = "La solicitud expiró. Actualiza la página e inténtalo de nuevo.";

        } elseif (!$idPedido || !in_array($nuevoEstado, $estadosPermitidos, true)) {
            $mensaje = "El pedido o el estado seleccionado no son válidos.";

        } else {
            // Solo se puede modificar un pedido que contiene algún producto
            // registrado en el inventario de esta tienda.
            $actualizar = $conexion->prepare(
                "UPDATE pedido
                 SET estado_pedido = :estado
                 WHERE id_pedido = :pedido
                   AND EXISTS (
                       SELECT 1
                       FROM detalle_pedido dp
                       INNER JOIN inventario i
                           ON i.id_producto = dp.id_producto
                       WHERE dp.id_pedido = pedido.id_pedido
                         AND i.id_tienda = :tienda
                   )"
            );

            $actualizar->execute([
                "estado" => $nuevoEstado,
                "pedido" => $idPedido,
                "tienda" => $idTienda
            ]);

            if ($actualizar->rowCount() > 0) {
                header("Location: pedidos_tienda.php?resultado=actualizado");
                exit;
            }

            // Diferenciar un pedido inexistente de uno cuyo estado ya era igual.
            $verificar = $conexion->prepare(
                "SELECT p.id_pedido
                 FROM pedido p
                 WHERE p.id_pedido = :pedido
                   AND EXISTS (
                       SELECT 1
                       FROM detalle_pedido dp
                       INNER JOIN inventario i
                           ON i.id_producto = dp.id_producto
                       WHERE dp.id_pedido = p.id_pedido
                         AND i.id_tienda = :tienda
                   )"
            );

            $verificar->execute([
                "pedido" => $idPedido,
                "tienda" => $idTienda
            ]);

            if ($verificar->fetch()) {
                header("Location: pedidos_tienda.php?resultado=actualizado");
                exit;
            }

            $mensaje = "No se encontró ese pedido entre los productos de tu tienda.";
        }
    }

    if (($_GET["resultado"] ?? "") === "actualizado") {
        $mensaje = "El estado del pedido fue actualizado.";
        $tipoMensaje = "ok";
    }

    $sql = "SELECT
                p.id_pedido,
                p.fecha_pedido,
                p.fecha_entrega,
                p.estado_pedido,
                p.total,
                u.nombre,
                u.apellido,
                u.correo,
                d.productos,
                d.cantidad_total,
                pg.metodo_pago,
                pg.estado_pago
            FROM pedido p
            LEFT JOIN usuario u
                ON u.id_usuario = p.id_usuario
            LEFT JOIN (
                SELECT
                    id_pedido,
                    GROUP_CONCAT(
                        DISTINCT nombre_producto
                        ORDER BY nombre_producto
                        SEPARATOR ', '
                    ) AS productos,
                    SUM(cantidad) AS cantidad_total
                FROM detalle_pedido
                GROUP BY id_pedido
            ) d ON d.id_pedido = p.id_pedido
            LEFT JOIN (
                SELECT pg1.id_pedido,
                       pg1.metodo_pago,
                       pg1.estado AS estado_pago
                FROM pago pg1
                INNER JOIN (
                    SELECT id_pedido, MAX(id_pago) AS ultimo_pago
                    FROM pago
                    GROUP BY id_pedido
                ) ult
                    ON ult.ultimo_pago = pg1.id_pago
            ) pg ON pg.id_pedido = p.id_pedido
            WHERE EXISTS (
                SELECT 1
                FROM detalle_pedido dp
                INNER JOIN inventario i
                    ON i.id_producto = dp.id_producto
                WHERE dp.id_pedido = p.id_pedido
                  AND i.id_tienda = :tienda
            )
            ORDER BY p.id_pedido DESC";

    $consulta = $conexion->prepare($sql);
    $consulta->execute(["tienda" => $idTienda]);
    $pedidos = $consulta->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    error_log("Error en pedidos de vendedor: " . $e->getMessage());
    $mensaje = "No fue posible consultar los pedidos. Revisa las tablas pedido, detalle_pedido, pago e inventario.";
}

$nombreTienda = escaparPedido($tienda["nombre_tienda"] ?? "");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos de mi tienda - CONFISENA</title>
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
        h1, h2, h3 { color: #172554; }
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
        table { border-collapse: collapse; width: 100%; min-width: 950px; }
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
        <span>Pedidos de mi tienda</span>
        <strong><?= $nombreTienda ?></strong>
    </div>
    <a class="boton-cerrar" href="tienda.php">Volver al panel</a>
</header>

<main class="pagina">
    <section class="panel">
        <h1>Pedidos</h1>
        <p class="subtitulo">
            Consulta los pedidos que incluyen productos de tu inventario
            y actualiza su estado de preparación o entrega.
        </p>
    </section>

    <?php if ($mensaje !== ""): ?>
        <div class="mensaje <?= escaparPedido($tipoMensaje) ?>">
            <?= escaparPedido($mensaje) ?>
        </div>
    <?php endif; ?>

    <section class="panel">
        <h2>Pedidos registrados</h2>

        <?php if (empty($pedidos)): ?>
            <p class="vacio">
                No hay pedidos relacionados con los productos de tu tienda.
            </p>
        <?php else: ?>
            <div class="scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Cliente</th>
                            <th>Productos</th>
                            <th>Cantidad</th>
                            <th>Fecha del pedido</th>
                            <th>Entrega</th>
                            <th>Total del pedido</th>
                            <th>Pago</th>
                            <th>Estado del pedido</th>
                            <th>Actualizar estado</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($pedidos as $p): ?>
                        <tr>
                            <td>#<?= (int)$p["id_pedido"] ?></td>

                            <td>
                                <?= escaparPedido(
                                    trim(($p["nombre"] ?? "") . " " . ($p["apellido"] ?? ""))
                                ) ?>
                                <br>
                                <small><?= escaparPedido($p["correo"] ?? "") ?></small>
                            </td>

                            <td><?= escaparPedido($p["productos"] ?? "Sin detalle") ?></td>
                            <td><?= (int)($p["cantidad_total"] ?? 0) ?></td>
                            <td><?= escaparPedido($p["fecha_pedido"]) ?></td>
                            <td><?= escaparPedido($p["fecha_entrega"] ?: "Por confirmar") ?></td>
                            <td><?= formatoPesosPedido($p["total"]) ?></td>

                            <td>
                                <?= escaparPedido($p["metodo_pago"] ?? "No registrado") ?>
                                <br>
                                <small>
                                    <?= escaparPedido($p["estado_pago"] ?? "Sin registro") ?>
                                </small>
                            </td>

                            <td><?= escaparPedido($p["estado_pedido"]) ?></td>

                            <td>
                                <form method="POST" action="pedidos_tienda.php">
                                    <input type="hidden" name="token" value="<?= escaparPedido($token) ?>">
                                    <input type="hidden" name="id_pedido" value="<?= (int)$p["id_pedido"] ?>">

                                    <select name="estado_pedido" required>
                                        <?php
                                        $estados = [
                                            "Pendiente",
                                            "En preparación",
                                            "Listo para entrega",
                                            "Entregado"
                                        ];

                                        foreach ($estados as $estado):
                                        ?>
                                            <option
                                                value="<?= escaparPedido($estado) ?>"
                                                <?= $p["estado_pedido"] === $estado ? "selected" : "" ?>
                                            >
                                                <?= escaparPedido($estado) ?>
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
            Importante: actualizar el estado del pedido no confirma el cobro.
            El pago permanece con el estado que tenga registrado en la base de datos.
        </p>
    </section>
</main>

<footer style="text-align:center;padding:25px;color:#64748b;">
    © 2026 CONFISENA - Transformamos la forma de comprar
</footer>

</body>
</html>
