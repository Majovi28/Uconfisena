
<?php
session_start();

if (empty($_SESSION["id_usuario"])) {
    header("Location: login.html");
    exit;
}

require_once __DIR__ . "/php/conexion.php";

function escapar($valor) {
    return htmlspecialchars((string)($valor ?? ""), ENT_QUOTES, "UTF-8");
}

function pesos($valor) {
    return "$" . number_format((float)$valor, 0, ",", ".");
}

$pedidos = [];
$error = "";

try {
    $consulta = $conexion->prepare(
        "SELECT id_pedido, fecha_pedido, fecha_entrega,
                estado_pedido, total
         FROM pedido
         WHERE id_usuario = :usuario
         ORDER BY fecha_pedido DESC"
    );

    $consulta->execute([
        "usuario" => (int)$_SESSION["id_usuario"]
    ]);

    $pedidos = $consulta->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error consultando pedidos: " . $e->getMessage());
    $error = "No fue posible consultar tus pedidos. Inténtalo nuevamente.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis pedidos - CONFISENA</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f5f8;
            color: #263449;
        }
        header {
            background: #123563;
            color: white;
            padding: 18px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        header h1 { margin: 0; text-transform: lowercase; }
        header p { margin: 5px 0 0; }
        nav { display: flex; flex-wrap: wrap; gap: 15px; }
        nav a { color: white; text-decoration: none; }
        nav a:hover { text-decoration: underline; }
        main { width: min(1050px, 92%); margin: 35px auto; }
        h2 { color: #123563; }
        .pedido {
            background: white;
            border-radius: 12px;
            padding: 22px;
            margin: 18px 0;
            box-shadow: 0 3px 12px #152b4410;
            border: 1px solid #e1e6ed;
        }
        .cabecera-pedido {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            border-bottom: 1px solid #e2e7ee;
            padding-bottom: 14px;
        }
        .estado {
            background: #e8eef7;
            color: #123563;
            border-radius: 20px;
            padding: 7px 12px;
            font-size: 14px;
            height: fit-content;
        }
        .detalle {
            padding: 12px 0;
            border-bottom: 1px solid #edf0f4;
        }
        .total { text-align: right; font-size: 20px; color: #123563; }
        .mensaje {
            background: white;
            border-radius: 10px;
            padding: 20px;
        }
        .error { color: #a61b1b; }
        .boton {
            display: inline-block;
            background: #123563;
            color: white;
            padding: 10px 16px;
            border-radius: 6px;
            text-decoration: none;
            margin: 5px 5px 5px 0;
        }
        footer {
            text-align: center;
            padding: 22px;
            color: #596579;
        }
    </style>
</head>
<body>

<header>
    <div>
        <h1>confisena</h1>
        <p>Transformamos la forma de comprar</p>
    </div>
    <nav>
        <a href="index.html">Inicio</a>
        <a href="catalogo.php">Catálogo</a>
        <a href="mis_apartados.php">Mis apartados</a>
        <a href="cerrar_sesion.php">Cerrar sesión</a>
    </nav>
</header>

<main>
    <h2>Mis pedidos</h2>
    <p>Consulta tus compras y el estado de cada pedido.</p>

    <a class="boton" href="catalogo.php">Seguir comprando</a>
    <a class="boton" href="mis_apartados.php">Consultar apartados</a>

    <?php if ($error !== ""): ?>
        <p class="mensaje error"><?= escapar($error) ?></p>

    <?php elseif (empty($pedidos)): ?>
        <div class="mensaje">
            Todavía no tienes pedidos registrados.
        </div>

    <?php else: ?>

        <?php foreach ($pedidos as $pedido): ?>
            <?php
            $consultaDetalle = $conexion->prepare(
                "SELECT nombre_producto, cantidad, total
                 FROM detalle_pedido
                 WHERE id_pedido = :pedido"
            );

            $consultaDetalle->execute([
                "pedido" => (int)$pedido["id_pedido"]
            ]);

            $detalles = $consultaDetalle->fetchAll(PDO::FETCH_ASSOC);

            $consultaPago = $conexion->prepare(
                "SELECT metodo_pago, estado
                 FROM pago
                 WHERE id_pedido = :pedido
                 ORDER BY id_pago DESC
                 LIMIT 1"
            );

            $consultaPago->execute([
                "pedido" => (int)$pedido["id_pedido"]
            ]);

            $pago = $consultaPago->fetch(PDO::FETCH_ASSOC);
            ?>

            <section class="pedido">
                <div class="cabecera-pedido">
                    <div>
                        <strong>Pedido #<?= (int)$pedido["id_pedido"] ?></strong>
                        <p>Fecha de compra:
                            <?= escapar($pedido["fecha_pedido"]) ?>
                        </p>
                    </div>

                    <span class="estado">
                        <?= escapar($pedido["estado_pedido"]) ?>
                    </span>
                </div>

                <h3>Productos</h3>

                <?php foreach ($detalles as $detalle): ?>
                    <div class="detalle">
                        <strong><?= escapar($detalle["nombre_producto"]) ?></strong>
                        <p>Cantidad: <?= (int)$detalle["cantidad"] ?></p>
                        <p>Subtotal: <?= pesos($detalle["total"]) ?></p>
                    </div>
                <?php endforeach; ?>

                <p>Fecha estimada de entrega:
                    <?= escapar($pedido["fecha_entrega"] ?: "Por confirmar") ?>
                </p>

                <p>Método de pago:
                    <?= escapar($pago["metodo_pago"] ?? "No registrado") ?>
                </p>

                <p>Estado del pago:
                    <?= escapar($pago["estado"] ?? "No registrado") ?>
                </p>

                <h3 class="total">
                    Total: <?= pesos($pedido["total"]) ?>
                </h3>
            </section>
        <?php endforeach; ?>

    <?php endif; ?>
</main>

<footer>
    © 2026 CONFISENA - Transformamos la forma de comprar
</footer>

</body>
</html>
