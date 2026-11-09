<?php
session_start();
if (empty($_SESSION["id_usuario"])) { header("Location: login.html"); exit; }
require_once __DIR__ . "/php/conexion.php";
function e($v) { return htmlspecialchars((string)($v ?? ""), ENT_QUOTES, "UTF-8"); }
function cop($v) { return "$" . number_format((float)$v, 0, ",", "."); }
try {
    $sql = "SELECT p.id_producto, p.nombre_producto, p.tallas, p.descripcion,
                   c.nombre_categoria, i.id_inventario, i.id_tienda,
                   t.nombre_tienda, COALESCE(i.precio, p.precio) AS precio,
                   i.cantidad_disponible
            FROM inventario i
            INNER JOIN producto p ON p.id_producto = i.id_producto
            LEFT JOIN categoria c ON c.id_categoria = p.id_categoria
            INNER JOIN tienda t ON t.id_tienda = i.id_tienda
            WHERE t.estado = 1
            ORDER BY p.nombre_producto, t.nombre_tienda";
    $productos = $conexion->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $ex) {
    error_log("Error catálogo CONFISENA: " . $ex->getMessage());
    $productos = [];
    $errorCatalogo = true;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Catálogo | CONFISENA</title>
<link rel="stylesheet" href="stylecatalogo.css">
<style>
.tienda-producto{margin:8px 0;color:#4D5148}.tienda-producto strong{font-weight:700}
.producto-card{height:100%}.producto-card .producto-contenido{height:100%;display:flex;flex-direction:column}
.producto-botones{margin-top:auto}.producto-card[hidden]{display:none!important}
</style>
</head>
<body>
<header class="header"><div class="contenedor header-contenido">
<a href="index.html" class="logo"><span class="logo-principal">CONFI<span>SENA</span></span><small>Transformamos la forma de comprar</small></a>
<nav class="menu"><a href="index.html">Inicio</a><a href="catalogo.php" class="activo">Catálogo</a><a href="#productos">Productos</a><a href="#contacto">Contacto</a><a href="cerrar_sesion.php">Cerrar sesión</a></nav>
</div></header>
<main>
<section class="catalogo-encabezado"><div class="contenedor"><span class="etiqueta">CATÁLOGO CONFISENA</span><h1>Encuentra el uniforme que necesitas</h1><p>Consulta productos, precios y disponibilidad en cada tienda.</p></div></section>
<section class="filtros-seccion"><div class="contenedor filtros">
<div class="buscador"><label for="buscar">Buscar producto o tienda</label><div class="buscador-input"><input type="search" id="buscar" placeholder="Nombre del producto o tienda..."></div></div>
<div class="filtro"><label for="ordenar">Ordenar por</label><select id="ordenar"><option value="destacados">Destacados</option><option value="menor">Precio menor a mayor</option><option value="mayor">Precio mayor a menor</option><option value="nombre">Nombre A - Z</option></select></div>
</div></section>
<section class="catalogo-seccion" id="productos"><div class="contenedor">
<div class="catalogo-superior"><h2>Productos por tienda</h2><span id="totalProductos"><?= count($productos) ?> opciones</span></div>
<?php if (!empty($errorCatalogo)): ?><p>No fue posible cargar el catálogo. Intenta nuevamente.</p>
<?php elseif (!$productos): ?><p>Aún no hay productos asociados al inventario de las tiendas.</p>
<?php else: ?>
<div class="productos-grid" id="productosGrid">
<?php foreach ($productos as $p): $id=(int)$p["id_producto"]; $inv=(int)$p["id_inventario"]; $stock=(int)$p["cantidad_disponible"]; $agotado=$stock<=0; $nombre=(string)$p["nombre_producto"]; $precio=(float)$p["precio"]; ?>
<article class="producto-card" data-nombre="<?= e($nombre) ?>" data-tienda="<?= e($p["nombre_tienda"]) ?>" data-precio="<?= $precio ?>">
<div class="producto-imagen"><span class="etiqueta-producto <?= $agotado ? "agotado" : "" ?>"><?= $agotado ? "Agotado" : "Disponible" ?></span><div class="imagen-placeholder"></div></div>
<div class="producto-contenido"><span class="categoria-producto"><?= e($p["nombre_categoria"] ?: "Sin categoría") ?></span>
<h3><?= e($nombre) ?></h3><p class="tienda-producto"><strong>Tienda:</strong> <?= e($p["nombre_tienda"]) ?></p>
<p class="descripcion"><?= e($p["descripcion"]) ?></p>
<div class="producto-dato"><strong>Tallas:</strong><div class="tallas"><?php foreach (preg_split("/[\s,;]+/", trim((string)$p["tallas"]), -1, PREG_SPLIT_NO_EMPTY) as $t): ?><span><?= e($t) ?></span><?php endforeach; ?></div></div>
<div class="precio"><?= cop($precio) ?></div><div class="disponibilidad <?= $agotado ? "agotado-texto" : "disponible" ?>"><span class="punto"></span><?= $stock ?> unidades en esta tienda</div>
<div class="producto-botones"><a class="btn detalles" href="producto.php?id=<?= $id ?>&id_inventario=<?= $inv ?>">Ver detalles</a>
<?php if (!$agotado): ?>
<a class="btn comprar" href="pago.php?id_producto=<?= $id ?>&id_inventario=<?= $inv ?>" onclick="guardarProducto(<?= $id ?>,<?= $inv ?>,'<?= e($nombre) ?>',<?= $precio ?>)">Comprar</a>
<a class="btn apartar" href="apartado.php?id_producto=<?= $id ?>&id_inventario=<?= $inv ?>" onclick="guardarProducto(<?= $id ?>,<?= $inv ?>,'<?= e($nombre) ?>',<?= $precio ?>)">Apartar</a>
<?php else: ?><button class="btn comprar deshabilitado" disabled>Agotado</button><?php endif; ?></div></div></article>
<?php endforeach; ?>
</div><p id="sinResultados" hidden>No se encontraron productos o tiendas con esa búsqueda.</p>
<?php endif; ?></div></section>
</main>
<footer class="footer" id="contacto"><div class="contenedor footer-grid"><div class="footer-col"><h3>CONFISENA</h3><p>Plataforma web de catálogo y venta de uniformes SENA.</p><p class="slogan">Transformamos la forma de comprar.</p></div><div class="footer-col"><h4>Enlaces</h4><a href="index.html">Inicio</a><a href="catalogo.php">Catálogo</a></div><div class="footer-col"><h4>Mi cuenta</h4><a href="cerrar_sesion.php">Cerrar sesión</a></div></div><div class="footer-bottom"><p>© 2026 CONFISENA. Todos los derechos reservados.</p></div></footer>
<script>
function guardarProducto(id,idInventario,nombre,precio){localStorage.setItem("id_producto",String(id));localStorage.setItem("id_inventario",String(idInventario));localStorage.setItem("producto",nombre);localStorage.setItem("precio",String(precio));localStorage.setItem("cantidad","1");}
const buscar=document.getElementById("buscar"),ordenar=document.getElementById("ordenar"),grid=document.getElementById("productosGrid"),total=document.getElementById("totalProductos"),sin=document.getElementById("sinResultados");
function filtrar(){if(!grid)return;const q=buscar.value.trim().toLocaleLowerCase("es");const cards=[...grid.querySelectorAll(".producto-card")];cards.forEach(card=>{card.hidden=!(card.dataset.nombre.toLocaleLowerCase("es").includes(q)||card.dataset.tienda.toLocaleLowerCase("es").includes(q));});const visibles=cards.filter(x=>!x.hidden);if(ordenar.value==="menor")visibles.sort((a,b)=>+a.dataset.precio- +b.dataset.precio);if(ordenar.value==="mayor")visibles.sort((a,b)=>+b.dataset.precio- +a.dataset.precio);if(ordenar.value==="nombre")visibles.sort((a,b)=>a.dataset.nombre.localeCompare(b.dataset.nombre,"es"));visibles.forEach(x=>grid.appendChild(x));total.textContent=visibles.length+" opciones";sin.hidden=visibles.length!==0;}
if(buscar&&ordenar){buscar.addEventListener("input",filtrar);ordenar.addEventListener("change",filtrar);}
</script></body></html>