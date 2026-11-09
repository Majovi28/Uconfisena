
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
<link rel="stylesheet" href="gestor-compartido.css">
</head>
<body class="panel-admin panel-gestor">
<aside class="barra-lateral">
  <a class="marca" href="gestor.php"><span class="marca-simbolo">C</span><span>Confi<strong>sena</strong></span></a>
  <p class="etiqueta-menu">MENÚ PRINCIPAL</p>
  <nav class="navegacion">
    <a class="activo" href="gestor.php"><span>⌂</span> Inicio</a>
    <a href="consultar_tiendas.php"><span>▤</span> Tiendas</a>
    <a href="consultar_vendedores.php"><span>♙</span> Vendedores</a>
    <a href="reportes-gestor.html"><span>▥</span> Reportes</a>
  </nav>
  <a class="cerrar-lateral" href="cerrar_sesion.php"><span>↪</span> Cerrar sesión</a>
</aside>
<div class="zona-principal">
  <header class="barra-superior">
    <div class="saludo"><strong>Bienvenido, <?= $nombre ?></strong><span>Panel del Gestor</span></div>
    <span class="perfil-rol">Gestor</span>
  </header>
  <main class="contenido-panel">
    <section class="portada-panel">
      <div><p class="sobre-titulo">PLATAFORMA CONFISENA</p><h1>Gestiona con confianza.</h1><p>Administra tiendas, vendedores y reportes desde un solo lugar.</p></div>
      <div class="portada-inicial">C</div>
    </section>
    <section class="indicadores-panel">
      <article class="indicador"><span class="indicador-icono violeta">▤</span><div><span>Tiendas registradas</span><strong><?= $tiendas ?></strong></div></article>
      <article class="indicador"><span class="indicador-icono lavanda">♙</span><div><span>Vendedores registrados</span><strong><?= $vendedores ?></strong></div></article>
    </section>
    <section class="bloque-panel">
      <div class="titulo-bloque"><div><h2>Accesos rápidos</h2><p>Continúa con las tareas de gestión.</p></div></div>
      <div class="accesos-panel">
        <a class="acceso-panel acceso-violeta" href="registrar_tienda.php"><span class="acceso-icono">＋</span><span><strong>Registrar tienda</strong><small>Agregar una nueva tienda</small></span><b>›</b></a>
        <a class="acceso-panel acceso-lavanda" href="registrar_vendedor.php"><span class="acceso-icono">♙</span><span><strong>Registrar vendedor</strong><small>Crear y asignar una cuenta</small></span><b>›</b></a>
        <a class="acceso-panel acceso-lavanda" href="consultar_tiendas.php"><span class="acceso-icono">▤</span><span><strong>Consultar tiendas</strong><small>Revisar tiendas registradas</small></span><b>›</b></a>
        <a class="acceso-panel acceso-violeta" href="consultar_vendedores.php"><span class="acceso-icono">♧</span><span><strong>Consultar vendedores</strong><small>Ver vendedores y sus tiendas</small></span><b>›</b></a>
        <a class="acceso-panel acceso-coral" href="reportes-gestor.html"><span class="acceso-icono">▥</span><span><strong>Gestionar problemas</strong><small>Revisar reportes recibidos</small></span><b>›</b></a>
      </div>
    </section>
  </main>
  <footer class="pie-panel">CONFISENA · Plataforma de gestión y control</footer>
</div>
</body>
</html>