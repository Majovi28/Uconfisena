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
            FROM producto p
            INNER JOIN inventario i ON i.id_producto = p.id_producto
            INNER JOIN tienda t ON t.id_tienda = i.id_tienda
            LEFT JOIN categoria c ON c.id_categoria = p.id_categoria
            WHERE t.estado = 1
            ORDER BY p.nombre_producto, t.nombre_tienda";
    $filas = $conexion->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $productos = [];
    foreach ($filas as $fila) {
        $id = (int)$fila["id_producto"];
        if (!isset($productos[$id])) {
            $productos[$id] = [
                "id_producto" => $id,
                "nombre_producto" => $fila["nombre_producto"],
                "tallas" => $fila["tallas"],
                "descripcion" => $fila["descripcion"],
                "nombre_categoria" => $fila["nombre_categoria"],
                "tiendas" => []
            ];
        }
        $productos[$id]["tiendas"][] = [
            "id_inventario" => (int)$fila["id_inventario"],
            "id_tienda" => (int)$fila["id_tienda"],
            "nombre_tienda" => $fila["nombre_tienda"],
            "precio" => (float)$fila["precio"],
            "cantidad_disponible" => (int)$fila["cantidad_disponible"]
        ];
    }
    $productos = array_values($productos);
} catch (Throwable $ex) {
    error_log("Error catálogo CONFISENA: " . $ex->getMessage());
    $productos = [];
    $errorCatalogo = true;
}
?>
<!DOCTYPE html>
<html lang="es"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Catálogo | CONFISENA</title><link rel="stylesheet" href="stylecatalogo.css">
<style>
.tienda-producto{margin:10px 0;color:#4D5148}.selector-tienda{width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;margin:6px 0 12px}
.producto-card .producto-contenido{height:100%;display:flex;flex-direction:column}.producto-botones{margin-top:auto}
.producto-card[hidden]{display:none!important}
</style></head><body>
<header class="header"><div class="contenedor header-contenido">
<a href="index.html" class="logo"><span class="logo-principal">CONFI<span>SENA</span></span><small>Transformamos la forma de comprar</small></a>
<nav class="menu"><a href="index.html">Inicio</a><a href="catalogo.php" class="activo">Catálogo</a><a href="#productos">Productos</a><a href="#contacto">Contacto</a><a href="cerrar_sesion.php">Cerrar sesión</a></nav>
</div></header>
<main>
<section class="catalogo-encabezado"><div class="contenedor"><span class="etiqueta">CATÁLOGO CONFISENA</span><h1>Encuentra el uniforme que necesitas</h1><p>Consulta cada producto y elige la tienda donde deseas comprarlo o apartarlo.</p></div></section>
<section class="filtros-seccion"><div class="contenedor filtros">
<div class="buscador"><label for="buscar">Buscar producto o tienda</label><div class="buscador-input"><input type="search" id="buscar" placeholder="Nombre del producto o tienda..."></div></div>
<div class="filtro"><label for="ordenar">Ordenar por</label><select id="ordenar"><option value="destacados">Destacados</option><option value="menor">Precio menor a mayor</option><option value="mayor">Precio mayor a menor</option><option value="nombre">Nombre A - Z</option></select></div>
</div></section>
<section class="catalogo-seccion" id="productos"><div class="contenedor">
<div class="catalogo-superior"><h2>Productos disponibles</h2><span id="totalProductos"><?= count($productos) ?> productos</span></div>
<?php if (!empty($errorCatalogo)): ?><p>No fue posible cargar el catálogo. Intenta nuevamente.</p>
<?php elseif (!$productos): ?><p>Aún no hay productos registrados en el inventario de las tiendas.</p>
<?php else: ?><div class="productos-grid" id="productosGrid">
<?php foreach ($productos as $p): $id=(int)$p["id_producto"]; $nombre=(string)$p["nombre_producto"]; $tiendasJson=json_encode(array_column($p["tiendas"],"nombre_tienda"),JSON_UNESCAPED_UNICODE|JSON_HEX_APOS|JSON_HEX_QUOT); $primera=$p["tiendas"][0]; $stock=(int)$primera["cantidad_disponible"]; $tallas=preg_split("/[\\s,;]+/",trim((string)$p["tallas"]),-1,PREG_SPLIT_NO_EMPTY); ?>
<article class="producto-card" data-product-id="<?= $id ?>" data-nombre="<?= e($nombre) ?>" data-tiendas="<?= e(implode(" ",array_column($p["tiendas"],"nombre_tienda"))) ?>" data-precio="<?= (float)$primera["precio"] ?>">
<div class="producto-imagen"><span class="etiqueta-producto <?= $stock<=0 ? "agotado" : "" ?>"><?= $stock<=0 ? "Agotado" : "Disponible" ?></span><div class="imagen-placeholder"></div></div>
<div class="producto-contenido"><span class="categoria-producto"><?= e($p["nombre_categoria"] ?: "Sin categoría") ?></span><h3><?= e($nombre) ?></h3>
<p class="descripcion"><?= e($p["descripcion"]) ?></p>
<div class="producto-dato"><strong>Tallas:</strong><div class="tallas"><?php foreach($tallas as $t): ?><span><?= e($t) ?></span><?php endforeach; ?></div></div>
<div class="producto-dato"><label for="tienda-<?= $id ?>"><strong>Disponible en:</strong></label>
<select class="selector-tienda" id="tienda-<?= $id ?>" aria-label="Seleccionar tienda para <?= e($nombre) ?>">
<?php foreach($p["tiendas"] as $ti): ?><option value="<?= (int)$ti["id_inventario"] ?>" data-tienda-id="<?= (int)$ti["id_tienda"] ?>" data-nombre-tienda="<?= e($ti["nombre_tienda"]) ?>" data-precio="<?= (float)$ti["precio"] ?>" data-stock="<?= (int)$ti["cantidad_disponible"] ?>"><?= e($ti["nombre_tienda"]) ?> — <?= cop($ti["precio"]) ?> — <?= (int)$ti["cantidad_disponible"] ?> disponibles</option><?php endforeach; ?>
</select></div>
<div class="precio precio-dinamico"><?= cop($primera["precio"]) ?></div><div class="disponibilidad disponibilidad-dinamica <?= $stock<=0 ? "agotado-texto" : "disponible" ?>"><span class="punto"></span><span><?= $stock ?> unidades en la tienda seleccionada</span></div>
<div class="producto-botones"><a class="btn detalles" href="producto.php?id=<?= $id ?>&id_inventario=<?= (int)$primera["id_inventario"] ?>">Ver detalles</a>
<a class="btn comprar" href="pago.php?id_producto=<?= $id ?>&id_inventario=<?= (int)$primera["id_inventario"] ?>" <?= $stock<=0 ? 'aria-disabled="true" style="pointer-events:none;opacity:.5"' : "" ?>>Comprar</a>
<a class="btn apartar" href="apartado.php?id_producto=<?= $id ?>&id_inventario=<?= (int)$primera["id_inventario"] ?>" <?= $stock<=0 ? 'aria-disabled="true" style="pointer-events:none;opacity:.5"' : "" ?>>Apartar</a></div></div></article>
<?php endforeach; ?></div><p id="sinResultados" hidden>No se encontraron productos o tiendas con esa búsqueda.</p><?php endif; ?>
</div></section></main>
<footer class="footer" id="contacto"><div class="contenedor footer-grid"><div class="footer-col"><h3>CONFISENA</h3><p>Plataforma web de catálogo y venta de uniformes SENA.</p><p class="slogan">Transformamos la forma de comprar.</p></div><div class="footer-col"><h4>Enlaces</h4><a href="index.html">Inicio</a><a href="catalogo.php">Catálogo</a></div><div class="footer-col"><h4>Mi cuenta</h4><a href="cerrar_sesion.php">Cerrar sesión</a></div></div><div class="footer-bottom"><p>© 2026 CONFISENA. Todos los derechos reservados.</p></div></footer>
<script>
function guardarProducto(id,idInventario,nombre,precio){localStorage.setItem("id_producto",String(id));localStorage.setItem("id_inventario",String(idInventario));localStorage.setItem("producto",nombre);localStorage.setItem("precio",String(precio));localStorage.setItem("cantidad","1");}
document.querySelectorAll(".producto-card").forEach(card=>{
 const selector=card.querySelector(".selector-tienda"),precio=card.querySelector(".precio-dinamico"),disp=card.querySelector(".disponibilidad-dinamica span:last-child"),badge=card.querySelector(".etiqueta-producto");
 const detalles=card.querySelector(".detalles"),comprar=card.querySelector(".comprar"),apartar=card.querySelector(".apartar");
 const id=Number(card.dataset.productId)||Number(detalles.href.match(/[?&]id=(\d+)/)?.[1]);
 function actualizar(){
  const op=selector.selectedOptions[0],inv=op.value,store=op.dataset.tiendaId,stock=Number(op.dataset.stock),price=Number(op.dataset.precio);
  card.dataset.precio=String(price);precio.textContent="$"+price.toLocaleString("es-CO");disp.textContent=stock+" unidades en "+op.dataset.nombreTienda;
  badge.textContent=stock>0?"Disponible":"Agotado";badge.classList.toggle("agotado",stock<=0);
  detalles.href="producto.php?id="+id+"&id_inventario="+inv;
  comprar.href="pago.php?id_producto="+id+"&id_inventario="+inv;
  apartar.href="apartado.php?id_producto="+id+"&id_inventario="+inv;
  [comprar,apartar].forEach(a=>{a.style.pointerEvents=stock>0?"":"none";a.style.opacity=stock>0?"":"0.5";a.setAttribute("aria-disabled",stock>0?"false":"true");});
 }
 selector.addEventListener("change",actualizar);actualizar();
 [comprar,apartar].forEach(a=>a.addEventListener("click",event=>{const op=selector.selectedOptions[0];if(Number(op.dataset.stock)<=0){event.preventDefault();return;}guardarProducto(id,op.value,card.dataset.nombre,Number(op.dataset.precio));}));
});
const buscar=document.getElementById("buscar"),ordenar=document.getElementById("ordenar"),grid=document.getElementById("productosGrid"),total=document.getElementById("totalProductos"),sin=document.getElementById("sinResultados");
function filtrar(){if(!grid)return;const q=buscar.value.trim().toLocaleLowerCase("es"),cards=[...grid.querySelectorAll(".producto-card")];cards.forEach(card=>{card.hidden=!(card.dataset.nombre.toLocaleLowerCase("es").includes(q)||card.dataset.tiendas.toLocaleLowerCase("es").includes(q));});const visibles=cards.filter(x=>!x.hidden);if(ordenar.value==="menor")visibles.sort((a,b)=>+a.dataset.precio- +b.dataset.precio);if(ordenar.value==="mayor")visibles.sort((a,b)=>+b.dataset.precio- +a.dataset.precio);if(ordenar.value==="nombre")visibles.sort((a,b)=>a.dataset.nombre.localeCompare(b.dataset.nombre,"es"));visibles.forEach(x=>grid.appendChild(x));total.textContent=visibles.length+" productos";sin.hidden=visibles.length!==0;}
if(buscar&&ordenar){buscar.addEventListener("input",filtrar);ordenar.addEventListener("change",filtrar);}
</script></body></html>