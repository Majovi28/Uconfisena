
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

$apartados = [];
$error = "";

try {
    $consulta = $conexion->prepare(
        "SELECT
            a.id_apartado,
            a.fecha_apartado,
            a.fecha_entrega,
            a.abono,
            a.saldo,
            a.estado,
            a.cantidad,
            a.talla,
            a.tienda_recogida,
            a.metodo_pago,
            p.nombre_producto
         FROM apartado a
         INNER JOIN producto p
            ON p.id_producto = a.id_producto
         WHERE a.id_usuario = :usuario
         ORDER BY a.fecha_apartado DESC"
    );

    $consulta->execute([
        "usuario" => (int)$_SESSION["id_usuario"]
    ]);

    $apartados = $consulta->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error consultando apartados: " . $e->getMessage());
    $error = "No fue posible consultar tus apartados. Revisa la base de datos e inténtalo nuevamente.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis apartados - CONFISENA</title>

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
        .apartado {
            background: white;
            border: 1px solid #e1e6ed;
            border-radius: 12px;
            padding: 22px;
            margin: 18px 0;
            box-shadow: 0 3px 12px #152b4410;
        }
        .cabecera {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            border-bottom: 1px solid #e2e7ee;
            padding-bottom: 14px;
        }
        .estado {
            display: inline-block;
            background: #e8eef7;
            color: #123563;
            border-radius: 20px;
            padding: 7px 12px;
            height: fit-content;
        }
        .valores {
            background: #f7f9fc;
            border-radius: 8px;
            padding: 12px 16px;
            margin-top: 15px;
        }
        .fila {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 7px 0;
        }
        .abono { color: #123563; font-weight: bold; }
        .saldo { font-weight: bold; }
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
        <a href="mis_pedidos.php">Mis pedidos</a>
        <a href="cerrar_sesion.php">Cerrar sesión</a>
    </nav>
</header>

<main>
    <h2>Mis apartados</h2>
    <p>Consulta los productos reservados, sus abonos y el saldo pendiente.</p>

    <a class="boton" href="catalogo.php">Ver catálogo</a>
    <a class="boton" href="mis_pedidos.php">Mis pedidos</a>

    <?php if ($error !== ""): ?>
        <div class="mensaje error"><?= escapar($error) ?></div>

    <?php elseif (empty($apartados)): ?>
        <div class="mensaje">
            Todavía no tienes apartados registrados.
        </div>

    <?php else: ?>

        <?php foreach ($apartados as $apartado): ?>
            <section class="apartado">
                <div class="cabecera">
                    <div>
                        <strong>
                            Apartado #<?= (int)$apartado["id_apartado"] ?>
                        </strong>

                        <p>
                            Fecha de apartado:
                            <?= escapar($apartado["fecha_apartado"]) ?>
                        </p>
                    </div>

                    <span class="estado">
                        <?= escapar($apartado["estado"]) ?>
                    </span>
                </div>

                <h3><?= escapar($apartado["nombre_producto"]) ?></h3>

                <p>Talla: <?= escapar($apartado["talla"] ?: "No registrada") ?></p>
                <p>Cantidad: <?= (int)$apartado["cantidad"] ?></p>

                <p>Tienda de recogida:
                    <?= escapar($apartado["tienda_recogida"] ?: "Por confirmar") ?>
                </p>

                <p>Fecha de entrega:
                    <?= escapar($apartado["fecha_entrega"] ?: "Por confirmar") ?>
                </p>

                <p>Método de pago seleccionado:
                    <?= escapar($apartado["metodo_pago"] ?: "No registrado") ?>
                </p>

                <div class="valores">
                    <div class="fila">
                        <span>Abono registrado:</span>
                        <span class="abono"><?= pesos($apartado["abono"]) ?></span>
                    </div>

                    <div class="fila">
                        <span>Saldo pendiente:</span>
                        <span class="saldo"><?= pesos($apartado["saldo"]) ?></span>
                    </div>
                </div>

                <p>
                    <small>
                        El estado del apartado y del pago se actualizará
                        cuando el proceso sea confirmado por el administrador.
                    </small>
                </p>
            </section>
        <?php endforeach; ?>

    <?php endif; ?>
</main>

<footer>
    © 2026 CONFISENA - Transformamos la forma de comprar
</footer>

</body>
</html>
