
<?php
session_start();

if (empty($_SESSION["id_usuario"])) {
    header("Location: login.html");
    exit;
}

if (($_SESSION["rol_usuario"] ?? "") !== "Vendedor") {
    http_response_code(403);
    exit("Acceso denegado. Esta página es exclusiva del Vendedor.");
}

require_once __DIR__ . "/php/conexion.php";

$idTienda = (int)($_SESSION["id_tienda"] ?? 0);

if ($idTienda < 1) {
    http_response_code(403);
    exit("Tu cuenta no tiene una tienda asignada. Comunícate con el Gestor.");
}

if (empty($_SESSION["token_inventario"])) {
    $_SESSION["token_inventario"] = bin2hex(random_bytes(32));
}

$token = $_SESSION["token_inventario"];
$mensaje = "";
$tipoMensaje = "error";

function escapar($valor) {
    return htmlspecialchars((string)($valor ?? ""), ENT_QUOTES, "UTF-8");
}

function redireccionarConMensaje($codigo) {
    header("Location: inventario_tienda.php?mensaje=" . urlencode($codigo));
    exit;
}

$mensajes = [
    "actualizado" => ["Cantidad actualizada correctamente.", "ok"],
    "agregado" => ["Producto agregado al inventario de tu tienda.", "ok"],
    "duplicado" => ["Ese producto ya existe en el inventario de tu tienda. Actualiza su cantidad en la tabla.", "error"],
    "no_encontrado" => ["No se encontró el registro solicitado en tu tienda.", "error"],
    "sin_producto" => ["Selecciona un producto válido.", "error"],
    "cantidad" => ["La cantidad debe ser un número entero entre 0 y 1000000.", "error"],
    "token" => ["La solicitud expiró. Actualiza la página e inténtalo nuevamente.", "error"],
    "error" => ["No se pudo completar la operación. Revisa los datos e inténtalo nuevamente.", "error"]
];

if (isset($_GET["mensaje"])) {
    $codigo = $_GET["mensaje"];

    if (isset($mensajes[$codigo])) {
        [$mensaje, $tipoMensaje] = $mensajes[$codigo];
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tokenEnviado = $_POST["token"] ?? "";

    if (!hash_equals($token, $tokenEnviado)) {
        $mensaje = $mensajes["token"][0];

    } else {
        $accion = $_POST["accion"] ?? "";
        $cantidad = filter_var(
            $_POST["cantidad"] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($cantidad === false || $cantidad < 0 || $cantidad > 1000000) {
            $mensaje = $mensajes["cantidad"][0];

        } else {
            try {
                if ($accion === "actualizar") {
                    $idInventario = filter_var(
                        $_POST["id_inventario"] ?? null,
                        FILTER_VALIDATE_INT
                    );

                    if (!$idInventario || $idInventario < 1) {
                        $mensaje = $mensajes["no_encontrado"][0];

                    } else {
                        // La condición id_tienda impide modificar inventario ajeno.
                        $actualizar = $conexion->prepare(
                            "UPDATE inventario
                             SET cantidad_disponible = :cantidad,
                                 fecha_actualizacion = NOW()
                             WHERE id_inventario = :id
                               AND id_tienda = :tienda"
                        );

                        $actualizar->execute([
                            "cantidad" => $cantidad,
                            "id" => $idInventario,
                            "tienda" => $idTienda
                        ]);

                        if ($actualizar->rowCount() > 0) {
                            redireccionarConMensaje("actualizado");
                        }

                        $verificar = $conexion->prepare(
                            "SELECT id_inventario
                             FROM inventario
                             WHERE id_inventario = :id
                               AND id_tienda = :tienda"
                        );

                        $verificar->execute([
                            "id" => $idInventario,
                            "tienda" => $idTienda
                        ]);

                        if ($verificar->fetch()) {
                            // Puede que se haya guardado el mismo valor.
                            redireccionarConMensaje("actualizado");
                        } else {
                            $mensaje = $mensajes["no_encontrado"][0];
                        }
                    }

                } elseif ($accion === "agregar") {
                    $idProducto = filter_var(
                        $_POST["id_producto"] ?? null,
                        FILTER_VALIDATE_INT
                    );

                    if (!$idProducto || $idProducto < 1) {
                        $mensaje = $mensajes["sin_producto"][0];

                    } else {
                        $consultaProducto = $conexion->prepare(
                            "SELECT id_producto, nombre_producto, precio
                             FROM producto
                             WHERE id_producto = :id
                             LIMIT 1"
                        );

                        $consultaProducto->execute([
                            "id" => $idProducto
                        ]);

                        $producto = $consultaProducto->fetch(PDO::FETCH_ASSOC);

                        if (!$producto) {
                            $mensaje = $mensajes["sin_producto"][0];

                        } else {
                            $duplicado = $conexion->prepare(
                                "SELECT id_inventario
                                 FROM inventario
                                 WHERE id_producto = :producto
                                   AND id_tienda = :tienda
                                 LIMIT 1"
                            );

                            $duplicado->execute([
                                "producto" => $idProducto,
                                "tienda" => $idTienda
                            ]);

                            if ($duplicado->fetch()) {
                                $mensaje = $mensajes["duplicado"][0];

                            } else {
                                $insertar = $conexion->prepare(
                                    "INSERT INTO inventario
                                    (
                                        id_producto,
                                        cantidad_disponible,
                                        id_tienda,
                                        fecha_actualizacion,
                                        nombre_producto,
                                        proveedor,
                                        precio
                                    )
                                    VALUES
                                    (
                                        :producto,
                                        :cantidad,
                                        :tienda,
                                        NOW(),
                                        :nombre,
                                        :proveedor,
                                        :precio
                                    )"
                                );

                                $insertar->execute([
                                    "producto" => $idProducto,
                                    "cantidad" => $cantidad,
                                    "tienda" => $idTienda,
                                    "nombre" => $producto["nombre_producto"],
                                    "proveedor" => "Proveedor CONFISENA",
                                    "precio" => $producto["precio"]
                                ]);

                                redireccionarConMensaje("agregado");
                            }
                        }
                    }

                } else {
                    $mensaje = "La operación solicitada no es válida.";
                }

            } catch (PDOException $e) {
                error_log("Error de inventario de tienda: " . $e->getMessage());
                $mensaje = $mensajes["error"][0];
            }
        }
    }
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
        exit("La tienda asignada no existe.");
    }

    $consultaInventario = $conexion->prepare(
        "SELECT
            i.id_inventario,
            i.id_producto,
            i.cantidad_disponible,
            i.proveedor,
            i.fecha_actualizacion,
            p.nombre_producto,
            p.tallas,
            p.precio
         FROM inventario i
         INNER JOIN producto p
            ON p.id_producto = i.id_producto
         WHERE i.id_tienda = :tienda
         ORDER BY p.nombre_producto"
    );

    $consultaInventario->execute(["tienda" => $idTienda]);
    $inventario = $consultaInventario->fetchAll(PDO::FETCH_ASSOC);

    $consultaProductos = $conexion->prepare(
        "SELECT p.id_producto, p.nombre_producto, p.precio
         FROM producto p
         WHERE NOT EXISTS (
             SELECT 1
             FROM inventario i
             WHERE i.id_producto = p.id_producto
               AND i.id_tienda = :tienda
         )
         ORDER BY p.nombre_producto"
    );

    $consultaProductos->execute(["tienda" => $idTienda]);
    $productosDisponibles = $consultaProductos->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error consultando inventario de tienda: " . $e->getMessage());
    http_response_code(500);
    exit("No fue posible consultar el inventario de tu tienda.");
}

$nombreTienda = escapar($tienda["nombre_tienda"]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Inventario de mi tienda - CONFISENA</title>

    <link rel="stylesheet" href="tienda.css">

    <style>
        .pagina-inventario {
            width: 94%;
            max-width: 1200px;
            margin: 30px auto;
        }

        .panel-inventario {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 22px;
            margin-bottom: 24px;
        }

        .panel-inventario h1,
        .panel-inventario h2 {
            color: #172554;
            margin-bottom: 12px;
        }

        .panel-inventario p {
            color: #64748b;
            line-height: 1.5;
        }

        .tabla-scroll {
            overflow-x: auto;
            margin-top: 16px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            min-width: 760px;
        }

        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: #dbeafe;
            color: #172554;
        }

        .campo-cantidad {
            width: 115px;
            padding: 9px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
        }

        .campo-select {
            width: 100%;
            padding: 11px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            margin: 8px 0 14px;
        }

        .boton-inventario {
            border: 0;
            background: #2563eb;
            color: white;
            border-radius: 6px;
            padding: 10px 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .mensaje-inventario {
            background: white;
            border: 1px solid #e2e8f0;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .mensaje-inventario.ok {
            color: #176b42;
        }

        .mensaje-inventario.error {
            color: #a61b1b;
        }

        .texto-bajo {
            color: #a61b1b;
            font-weight: bold;
        }
    </style>
</head>

<body>

<header class="encabezado">
    <div class="logo">CONFISENA</div>

    <div class="informacion-vendedor">
        <span>Inventario de tienda</span>
        <strong><?= $nombreTienda ?></strong>
    </div>

    <a class="boton-cerrar" href="tienda.php">Volver al panel</a>
</header>

<main class="pagina-inventario">

    <section class="panel-inventario">
        <h1>Inventario de mi tienda</h1>
        <p>
            Consulta y administra las cantidades disponibles
            únicamente para tu tienda asignada.
        </p>
    </section>

    <?php if ($mensaje !== ""): ?>
        <div class="mensaje-inventario <?= escapar($tipoMensaje) ?>">
            <?= escapar($mensaje) ?>
        </div>
    <?php endif; ?>

    <section class="panel-inventario">

        <h2>Agregar un producto al inventario</h2>

        <?php if (empty($productosDisponibles)): ?>

            <p>Todos los productos del catálogo ya tienen un registro de inventario en tu tienda.</p>

        <?php else: ?>

            <form method="POST" action="inventario_tienda.php">

                <input
                    type="hidden"
                    name="token"
                    value="<?= escapar($token) ?>"
                >

                <input type="hidden" name="accion" value="agregar">

                <label for="id_producto">Producto del catálogo</label>

                <select
                    class="campo-select"
                    id="id_producto"
                    name="id_producto"
                    required
                >
                    <option value="">Selecciona un producto</option>

                    <?php foreach ($productosDisponibles as $producto): ?>
                        <option value="<?= (int)$producto["id_producto"] ?>">
                            <?= escapar($producto["nombre_producto"]) ?>
                            — $<?= number_format((float)$producto["precio"], 0, ",", ".") ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="cantidad_nueva">Cantidad inicial disponible</label>

                <input
                    class="campo-select"
                    type="number"
                    id="cantidad_nueva"
                    name="cantidad"
                    min="0"
                    max="1000000"
                    step="1"
                    value="10"
                    required
                >

                <button class="boton-inventario" type="submit">
                    Agregar al inventario
                </button>

            </form>

        <?php endif; ?>

    </section>

    <section class="panel-inventario">

        <h2>Productos registrados en mi tienda</h2>

        <?php if (empty($inventario)): ?>

            <p>
                Aún no hay productos asignados al inventario de esta tienda.
                Puedes agregar uno desde el formulario anterior.
            </p>

        <?php else: ?>

            <div class="tabla-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Tallas</th>
                            <th>Precio</th>
                            <th>Existencias</th>
                            <th>Proveedor</th>
                            <th>Última actualización</th>
                            <th>Acción</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($inventario as $fila): ?>

                            <tr>
                                <td>
                                    <?= escapar($fila["nombre_producto"]) ?>
                                </td>

                                <td><?= escapar($fila["tallas"]) ?></td>

                                <td>
                                    $<?= number_format(
                                        (float)$fila["precio"],
                                        0,
                                        ",",
                                        "."
                                    ) ?>
                                </td>

                                <td>
                                    <?php if ((int)$fila["cantidad_disponible"] <= 5): ?>
                                        <span class="texto-bajo">
                                            <?= (int)$fila["cantidad_disponible"] ?>
                                        </span>
                                    <?php else: ?>
                                        <?= (int)$fila["cantidad_disponible"] ?>
                                    <?php endif; ?>
                                </td>

                                <td><?= escapar($fila["proveedor"]) ?></td>

                                <td><?= escapar($fila["fecha_actualizacion"]) ?></td>

                                <td>
                                    <form method="POST" action="inventario_tienda.php">

                                        <input
                                            type="hidden"
                                            name="token"
                                            value="<?= escapar($token) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="accion"
                                            value="actualizar"
                                        >

                                        <input
                                            type="hidden"
                                            name="id_inventario"
                                            value="<?= (int)$fila["id_inventario"] ?>"
                                        >

                                        <input
                                            class="campo-cantidad"
                                            type="number"
                                            name="cantidad"
                                            value="<?= (int)$fila["cantidad_disponible"] ?>"
                                            min="0"
                                            max="1000000"
                                            step="1"
                                            required
                                            aria-label="Nueva cantidad para <?= escapar($fila["nombre_producto"]) ?>"
                                        >

                                        <button
                                            class="boton-inventario"
                                            type="submit"
                                        >
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

</main>

<footer style="text-align:center;padding:25px;color:#64748b;">
    © 2026 CONFISENA - Transformamos la forma de comprar
</footer>

</body>
</html>
