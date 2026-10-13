
<?php
session_start();

if (empty($_SESSION["id_usuario"])) {
    header("Location: login.html");
    exit;
}

if (($_SESSION["rol_usuario"] ?? "") !== "Gerente General") {
    http_response_code(403);
    exit("Acceso denegado. Esta página es exclusiva del Gerente General.");
}

require_once __DIR__ . "/php/conexion.php";

function escapar($valor) {
    return htmlspecialchars(
        (string)($valor ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

function pesos($valor) {
    return "$" . number_format((float)$valor, 0, ",", ".");
}

function consultarCantidad($conexion, $sql) {
    return (int)$conexion->query($sql)->fetchColumn();
}

$nombre = $_SESSION["nombre_usuario"] ?? "Gerente General";

$error = "";
$tiendas = 0;
$usuarios = 0;
$vendedores = 0;
$productos = 0;
$unidadesInventario = 0;
$totalPedidos = 0;
$pedidosPendientes = 0;
$totalApartados = 0;
$apartadosPendientes = 0;
$pagosPendientes = 0;

$ultimosPedidos = [];
$ultimosApartados = [];
$tiendasRegistradas = [];
$inventarioBajo = [];

try {
    // Indicadores generales.
    $tiendas = consultarCantidad(
        $conexion,
        "SELECT COUNT(*) FROM tienda"
    );

    $usuarios = consultarCantidad(
        $conexion,
        "SELECT COUNT(*) FROM usuario"
    );

    $vendedores = consultarCantidad(
        $conexion,
        "SELECT COUNT(*)
         FROM usuario u
         INNER JOIN rol r ON r.id_rol = u.id_rol
         WHERE r.nombre_rol = 'Vendedor'"
    );

    $productos = consultarCantidad(
        $conexion,
        "SELECT COUNT(*) FROM producto"
    );

    $unidadesInventario = consultarCantidad(
        $conexion,
        "SELECT COALESCE(SUM(cantidad_disponible), 0)
         FROM inventario"
    );

    $totalPedidos = consultarCantidad(
        $conexion,
        "SELECT COUNT(*) FROM pedido"
    );

    $pedidosPendientes = consultarCantidad(
        $conexion,
        "SELECT COUNT(*)
         FROM pedido
         WHERE LOWER(estado_pedido) = 'pendiente'"
    );

    $totalApartados = consultarCantidad(
        $conexion,
        "SELECT COUNT(*) FROM apartado"
    );

    $apartadosPendientes = consultarCantidad(
        $conexion,
        "SELECT COUNT(*)
         FROM apartado
         WHERE LOWER(estado) = 'pendiente'"
    );

    $pagosPendientes = consultarCantidad(
        $conexion,
        "SELECT COUNT(*)
         FROM pago
         WHERE LOWER(estado) = 'pendiente'"
    );

    // Últimos pedidos de TODOS los usuarios.
    $consultaPedidos = $conexion->query(
        "SELECT
            p.id_pedido,
            p.fecha_pedido,
            p.fecha_entrega,
            p.estado_pedido,
            p.total,
            u.nombre,
            u.apellido,
            u.correo,
            (
                SELECT pg.estado
                FROM pago pg
                WHERE pg.id_pedido = p.id_pedido
                ORDER BY pg.id_pago DESC
                LIMIT 1
            ) AS estado_pago
         FROM pedido p
         LEFT JOIN usuario u
            ON u.id_usuario = p.id_usuario
         ORDER BY p.id_pedido DESC
         LIMIT 10"
    );

    $ultimosPedidos = $consultaPedidos->fetchAll(PDO::FETCH_ASSOC);

    // Últimos apartados de TODOS los usuarios.
    $consultaApartados = $conexion->query(
        "SELECT
            a.id_apartado,
            a.fecha_apartado,
            a.fecha_entrega,
            a.abono,
            a.saldo,
            a.estado,
            pr.nombre_producto,
            u.nombre,
            u.apellido
         FROM apartado a
         INNER JOIN producto pr
            ON pr.id_producto = a.id_producto
         LEFT JOIN usuario u
            ON u.id_usuario = a.id_usuario
         ORDER BY a.id_apartado DESC
         LIMIT 10"
    );

    $ultimosApartados = $consultaApartados->fetchAll(PDO::FETCH_ASSOC);

    // Tiendas registradas.
    $consultaTiendas = $conexion->query(
        "SELECT
            t.id_tienda,
            t.nombre_tienda,
            t.localidad,
            t.estado,
            (
                SELECT COUNT(*)
                FROM usuario u
                INNER JOIN rol r ON r.id_rol = u.id_rol
                WHERE u.id_tienda = t.id_tienda
                  AND r.nombre_rol = 'Vendedor'
            ) AS vendedores
         FROM tienda t
         ORDER BY t.id_tienda DESC
         LIMIT 10"
    );

    $tiendasRegistradas = $consultaTiendas->fetchAll(PDO::FETCH_ASSOC);

    // Productos con cinco unidades o menos.
    $consultaInventario = $conexion->query(
        "SELECT
            pr.nombre_producto,
            COALESCE(SUM(i.cantidad_disponible), 0) AS unidades
         FROM producto pr
         LEFT JOIN inventario i
            ON i.id_producto = pr.id_producto
         GROUP BY pr.id_producto, pr.nombre_producto
         HAVING COALESCE(SUM(i.cantidad_disponible), 0) <= 5
         ORDER BY unidades ASC, pr.nombre_producto ASC
         LIMIT 10"
    );

    $inventarioBajo = $consultaInventario->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    error_log("Error en el panel del gerente: " . $e->getMessage());
    $error = "No se pudo cargar toda la información. Revisa la base de datos y vuelve a actualizar la página.";
}

$nombreSeguro = escapar($nombre);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Supervisión general - CONFISENA</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f5f8;
            color: #263449;
        }

        header {
            background: #123563;
            color: #fff;
            padding: 20px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        header h1 {
            color: white;
            margin: 0;
            font-size: 28px;
        }

        header p {
            margin: 5px 0 0;
        }

        .usuario {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        .salir {
            display: inline-block;
            padding: 10px 14px;
            color: #123563;
            background: white;
            border-radius: 6px;
            text-decoration: none;
        }

        main {
            width: 94%;
            max-width: 1250px;
            margin: 30px auto;
        }

        h2, h3 {
            color: #123563;
        }

        .bienvenida {
            margin-bottom: 25px;
        }

        .bienvenida h2 {
            margin-bottom: 8px;
        }

        .subtitulo {
            color: #5b6678;
            line-height: 1.6;
        }

        .estadisticas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(175px, 1fr));
            gap: 16px;
            margin: 22px 0 35px;
        }

        .tarjeta-estadistica {
            background: white;
            padding: 20px;
            border: 1px solid #e0e6ee;
            border-radius: 10px;
        }

        .tarjeta-estadistica span {
            display: block;
            color: #596579;
            font-size: 14px;
        }

        .tarjeta-estadistica strong {
            display: block;
            font-size: 30px;
            color: #123563;
            margin: 10px 0;
        }

        .seccion {
            margin-top: 30px;
        }

        .tabla-contenedor {
            overflow-x: auto;
            background: white;
            border: 1px solid #e0e6ee;
            border-radius: 10px;
            padding: 12px;
        }

        table {
            width: 100%;
            min-width: 720px;
            border-collapse: collapse;
        }

        th, td {
            text-align: left;
            padding: 13px 12px;
            border-bottom: 1px solid #e8edf3;
            font-size: 14px;
        }

        th {
            background: #eaf0f8;
            color: #123563;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .vacio {
            padding: 18px;
            color: #596579;
        }

        .mensaje-error {
            background: white;
            color: #a61b1b;
            border: 1px solid #e0e6ee;
            border-radius: 8px;
            padding: 16px;
        }

        .nota {
            background: white;
            border: 1px solid #e0e6ee;
            border-radius: 10px;
            padding: 18px;
            line-height: 1.6;
        }

        footer {
            margin-top: 40px;
            text-align: center;
            padding: 24px;
            color: #596579;
        }

        @media (max-width: 600px) {
            header {
                align-items: flex-start;
            }

            .usuario {
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>

<header>
    <div>
        <h1>CONFISENA</h1>
        <p>Transformamos la forma de comprar</p>
    </div>

    <div class="usuario">
        <span>
            <?= $nombreSeguro ?> · Gerente General
        </span>

        <a class="salir" href="cerrar_sesion.php">
            Cerrar sesión
        </a>
    </div>
</header>

<main>

    <section class="bienvenida">
        <h2>Panel de supervisión general</h2>

        <p class="subtitulo">
            Consulta el funcionamiento general de CONFISENA:
            usuarios, tiendas, vendedores, productos, inventario,
            compras, apartados y pagos pendientes.
        </p>
    </section>

    <?php if ($error !== ""): ?>
        <div class="mensaje-error">
            <?= escapar($error) ?>
        </div>
    <?php endif; ?>

    <!-- INDICADORES GENERALES -->
    <section class="estadisticas">

        <article class="tarjeta-estadistica">
            <span>Usuarios registrados</span>
            <strong><?= $usuarios ?></strong>
        </article>

        <article class="tarjeta-estadistica">
            <span>Tiendas registradas</span>
            <strong><?= $tiendas ?></strong>
        </article>

        <article class="tarjeta-estadistica">
            <span>Vendedores registrados</span>
            <strong><?= $vendedores ?></strong>
        </article>

        <article class="tarjeta-estadistica">
            <span>Productos del catálogo</span>
            <strong><?= $productos ?></strong>
        </article>

        <article class="tarjeta-estadistica">
            <span>Unidades en inventario</span>
            <strong><?= $unidadesInventario ?></strong>
        </article>

        <article class="tarjeta-estadistica">
            <span>Total de pedidos</span>
            <strong><?= $totalPedidos ?></strong>
        </article>

        <article class="tarjeta-estadistica">
            <span>Pedidos pendientes</span>
            <strong><?= $pedidosPendientes ?></strong>
        </article>

        <article class="tarjeta-estadistica">
            <span>Total de apartados</span>
            <strong><?= $totalApartados ?></strong>
        </article>

        <article class="tarjeta-estadistica">
            <span>Apartados pendientes</span>
            <strong><?= $apartadosPendientes ?></strong>
        </article>

        <article class="tarjeta-estadistica">
            <span>Pagos pendientes</span>
            <strong><?= $pagosPendientes ?></strong>
        </article>

    </section>

    <!-- COMPRAS -->
    <section class="seccion">
        <h2>Últimos pedidos</h2>
        <p class="subtitulo">
            Seguimiento de las compras registradas por los usuarios.
        </p>

        <div class="tabla-contenedor">

            <?php if (empty($ultimosPedidos)): ?>
                <p class="vacio">Todavía no hay pedidos registrados.</p>
            <?php else: ?>

                <table>
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Cliente</th>
                            <th>Fecha</th>
                            <th>Entrega</th>
                            <th>Total del pedido</th>
                            <th>Estado del pedido</th>
                            <th>Estado del pago</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($ultimosPedidos as $pedido): ?>
                            <tr>
                                <td>#<?= (int)$pedido["id_pedido"] ?></td>

                                <td>
                                    <?= escapar(
                                        trim(
                                            ($pedido["nombre"] ?? "") . " " .
                                            ($pedido["apellido"] ?? "")
                                        ) ?: "Usuario"
                                    ) ?>
                                    <br>
                                    <small><?= escapar($pedido["correo"] ?? "") ?></small>
                                </td>

                                <td><?= escapar($pedido["fecha_pedido"]) ?></td>

                                <td>
                                    <?= escapar(
                                        $pedido["fecha_entrega"] ?: "Por confirmar"
                                    ) ?>
                                </td>

                                <td><?= pesos($pedido["total"]) ?></td>

                                <td><?= escapar($pedido["estado_pedido"]) ?></td>

                                <td><?= escapar($pedido["estado_pago"] ?? "Sin registro") ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php endif; ?>

        </div>
    </section>

    <!-- APARTADOS -->
    <section class="seccion">
        <h2>Últimos apartados</h2>
        <p class="subtitulo">
            Seguimiento de las reservas y sus saldos pendientes.
        </p>

        <div class="tabla-contenedor">

            <?php if (empty($ultimosApartados)): ?>
                <p class="vacio">Todavía no hay apartados registrados.</p>
            <?php else: ?>

                <table>
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Cliente</th>
                            <th>Producto</th>
                            <th>Fecha del apartado</th>
                            <th>Fecha de entrega</th>
                            <th>Abono registrado</th>
                            <th>Saldo pendiente</th>
                            <th>Estado</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($ultimosApartados as $apartado): ?>
                            <tr>
                                <td>#<?= (int)$apartado["id_apartado"] ?></td>

                                <td>
                                    <?= escapar(
                                        trim(
                                            ($apartado["nombre"] ?? "") . " " .
                                            ($apartado["apellido"] ?? "")
                                        ) ?: "Usuario"
                                    ) ?>
                                </td>

                                <td><?= escapar($apartado["nombre_producto"]) ?></td>

                                <td><?= escapar($apartado["fecha_apartado"]) ?></td>

                                <td>
                                    <?= escapar(
                                        $apartado["fecha_entrega"] ?: "Por confirmar"
                                    ) ?>
                                </td>

                                <td><?= pesos($apartado["abono"]) ?></td>

                                <td><?= pesos($apartado["saldo"]) ?></td>

                                <td><?= escapar($apartado["estado"]) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php endif; ?>

        </div>
    </section>

    <!-- TIENDAS -->
    <section class="seccion">
        <h2>Tiendas registradas</h2>
        <p class="subtitulo">
            Consulta las tiendas y la cantidad de vendedores asociados.
            Esta sección es únicamente de lectura.
        </p>

        <div class="tabla-contenedor">

            <?php if (empty($tiendasRegistradas)): ?>
                <p class="vacio">Todavía no hay tiendas registradas.</p>
            <?php else: ?>

                <table>
                    <thead>
                        <tr>
                            <th>Tienda</th>
                            <th>Localidad</th>
                            <th>Vendedores asociados</th>
                            <th>Estado</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($tiendasRegistradas as $tienda): ?>
                            <tr>
                                <td><?= escapar($tienda["nombre_tienda"]) ?></td>

                                <td><?= escapar($tienda["localidad"]) ?></td>

                                <td><?= (int)$tienda["vendedores"] ?></td>

                                <td>
                                    <?= (int)$tienda["estado"] === 1
                                        ? "Activa"
                                        : "Inactiva" ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php endif; ?>

        </div>
    </section>

    <!-- INVENTARIO -->
    <section class="seccion">
        <h2>Alertas de inventario</h2>
        <p class="subtitulo">
            Productos con cinco unidades disponibles o menos.
        </p>

        <div class="tabla-contenedor">

            <?php if (empty($inventarioBajo)): ?>
                <p class="vacio">
                    No hay productos con existencias bajas en este momento.
                </p>
            <?php else: ?>

                <table>
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Unidades disponibles</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($inventarioBajo as $producto): ?>
                            <tr>
                                <td><?= escapar($producto["nombre_producto"]) ?></td>
                                <td><?= (int)$producto["unidades"] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php endif; ?>

        </div>
    </section>

    <section class="seccion nota">
        <h3>Funciones del Gerente General</h3>
        <p>
            Este panel está destinado a supervisar la actividad de la plataforma.
            El Gerente General puede revisar los indicadores y los registros,
            pero no dispone de funciones para registrar tiendas ni vendedores
            desde este panel.
        </p>
    </section>

</main>

<footer>
    © 2026 CONFISENA - Transformamos la forma de comprar
</footer>

</body>
</html>
