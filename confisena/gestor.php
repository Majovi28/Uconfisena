
<?php
session_start();

if (empty($_SESSION["id_usuario"])) {
    header("Location: login.html");
    exit;
}

if (($_SESSION["rol_usuario"] ?? "") !== "Gestor") {
    http_response_code(403);
    exit("Acceso denegado. Esta página es exclusiva del Gestor.");
}

require_once __DIR__ . "/php/conexion.php";

$tiendas = (int)$conexion
    ->query("SELECT COUNT(*) FROM tienda")
    ->fetchColumn();

$vendedores = (int)$conexion
    ->query(
        "SELECT COUNT(*)
         FROM usuario u
         INNER JOIN rol r ON r.id_rol = u.id_rol
         WHERE r.nombre_rol = 'Vendedor'"
    )
    ->fetchColumn();

$nombre = htmlspecialchars(
    $_SESSION["nombre_usuario"] ?? "Gestor",
    ENT_QUOTES,
    "UTF-8"
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Gestor - CONFISENA</title>
    <link rel="stylesheet" href="gestor.css">
    <style>
        .enlaces-gestor {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 20px;
            margin: 25px 0;
        }
        .enlaces-gestor .tarjeta {
            background: white;
            padding: 24px;
            border-radius: 12px;
            border: 1px solid #dfe5ee;
        }
        .enlaces-gestor a {
            display: inline-block;
            padding: 10px 15px;
            background: #123563;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 10px;
        }
        .estadisticas-gestor {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin: 25px 0;
        }
        .estadisticas-gestor div {
            flex: 1;
            min-width: 160px;
            background: white;
            border: 1px solid #dfe5ee;
            border-radius: 10px;
            padding: 20px;
        }
        .estadisticas-gestor strong {
            display: block;
            font-size: 28px;
            color: #123563;
        }
        .boton-salir {
            display: inline-block;
            background: #123563;
            color: white;
            border: 0;
            border-radius: 6px;
            padding: 10px 15px;
            text-decoration: none;
        }
    </style>
</head>
<body>

<header class="encabezado">
    <div class="logo">
        <h1>confisena</h1>
        <p>Transformamos la forma de comprar</p>
    </div>
    <div>
        <span><?= $nombre ?> · Gestor</span>
        <a class="boton-salir" href="cerrar_sesion.php">Cerrar sesión</a>
    </div>
</header>

<main class="contenedor">
    <section class="bienvenida">
        <h2>Panel del Gestor</h2>
        <p>Administra el registro de tiendas y vendedores de CONFISENA.</p>
    </section>

    <section class="estadisticas-gestor">
        <div>
            <strong><?= $tiendas ?></strong>
            Tiendas registradas
        </div>
        <div>
            <strong><?= $vendedores ?></strong>
            Vendedores registrados
        </div>
    </section>

    <section class="enlaces-gestor">
        <article class="tarjeta">
            <h3>Registrar tienda</h3>
            <p>Ingresa los datos de una nueva tienda autorizada.</p>
            <a href="registrar_tienda.php">Registrar tienda</a>
        </article>

        <article class="tarjeta">
            <h3>Registrar vendedor</h3>
            <p>Crea una cuenta para un vendedor y asígnalo a una tienda.</p>
            <a href="registrar_vendedor.php">Registrar vendedor</a>
        </article>

        <article class="tarjeta">
            <h3>Consultar tiendas</h3>
            <p>Revisa las tiendas registradas en el sistema.</p>
            <a href="consultar_tiendas.php">Ver tiendas</a>
        </article>

        <article class="tarjeta">
            <h3>Consultar vendedores</h3>
            <p>Consulta las cuentas de vendedores y sus tiendas.</p>
            <a href="consultar_vendedores.php">Ver vendedores</a>
        </article>
    </section>
</main>

<footer>
    <p>© 2026 CONFISENA - Transformamos la forma de comprar</p>
</footer>

</body>
</html>
