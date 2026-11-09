<?php
session_start();
if (empty($_SESSION["id_usuario"])) { header("Location: login.html"); exit; }
require_once __DIR__ . "/php/conexion.php";
function escapar($v) { return htmlspecialchars((string)($v ?? ""), ENT_QUOTES, "UTF-8"); }
function precioCOP($v) { return "$" . number_format((float)$v, 0, ",", "."); }
$idProducto = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
$idInventario = filter_input(INPUT_GET, "id_inventario", FILTER_VALIDATE_INT);
if (!$idProducto || $idProducto < 1 || !$idInventario || $idInventario < 1) { header("Location: catalogo.php"); exit; }
try {
    $sql = "SELECT p.id_producto, p.nombre_producto, p.tallas, p.descripcion,
                   c.nombre_categoria, i.id_inventario, i.id_tienda,
                   t.nombre_tienda, COALESCE(i.precio,p.precio) AS precio,
                   i.cantidad_disponible
            FROM producto p
            INNER JOIN inventario i ON i.id_producto=p.id_producto
            INNER JOIN tienda t ON t.id_tienda=i.id_tienda
            LEFT JOIN categoria c ON c.id_categoria=p.id_categoria
            WHERE p.id_producto=:producto AND i.id_inventario=:inventario AND t.estado=1
            LIMIT 1";
    $q=$conexion->prepare($sql);
    $q->execute(["producto"=>$idProducto,"inventario"=>$idInventario]);
    $producto=$q->fetch(PDO::FETCH_ASSOC);
    if (!$producto) { http_response_code(404); exit("El producto no está disponible en esa tienda. <a href='catalogo.php'>Volver al catálogo</a>"); }
} catch(Throwable $ex) {
    error_log("Error en ficha de producto: ".$ex->getMessage());
    http_response_code(500); exit("No fue posible cargar el producto.");
}
$tallas=preg_split("/[\\s,;]+/",trim((string)$producto["tallas"]),-1,PREG_SPLIT_NO_EMPTY);
$stock=(int)$producto["cantidad_disponible"];
$agotado=$stock<=0;
$nombreProducto=(string)$producto["nombre_producto"];
$precioProducto=(float)$producto["precio"];
$categoriaProducto=$producto["nombre_categoria"] ?: "Sin categoría";
$nombreTienda=(string)$producto["nombre_tienda"];
$codigoProducto="CF-PRD-".str_pad((string)$producto["id_producto"],4,"0",STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= escapar($nombreProducto) ?> | CONFISENA</title><link rel="stylesheet" href="sproducto.css"><script src="jproducto.js" defer></script>
<style>.tienda-producto{color:#4D5148;margin:10px 0;font-size:1rem}.producto-info .tienda-producto strong{font-weight:700}</style></head>
<body>
<header class="header"><div class="contenedor header-contenido"><a href="index.html" class="logo"><span class="logo-principal">CONFI<span>SENA</span></span><small>Transformamos la forma de comprar</small></a><nav class="menu"><a href="index.html">Inicio</a><a href="catalogo.php">Catálogo</a><a href="cerrar_sesion.php">Cerrar sesión</a></nav></div></header>
<main><div class="contenedor"><div class="migas"><a href="index.html">Inicio</a><span>/</span><a href="catalogo.php">Catálogo</a><span>/</span><span><?= escapar($nombreProducto) ?></span></div></div>
<section class="producto-seccion"><div class="contenedor producto" data-product-id="<?= (int)$producto["id_producto"] ?>" data-inventory-id="<?= (int)$producto["id_inventario"] ?>" data-product-name="<?= escapar($nombreProducto) ?>" data-product-price="<?= escapar($precioProducto) ?>" data-max-stock="<?= max(0,$stock) ?>" data-has-stock="1">
<div class="producto-imagen"><span class="estado"><?= $agotado ? "Agotado" : "Disponible" ?></span><div class="imagen-placeholder"></div></div>
<div class="producto-info"><span class="categoria"><?= escapar(strtoupper($categoriaProducto)) ?></span><h1><?= escapar($nombreProducto) ?></h1>
<p class="codigo">Código del producto: <?= escapar($codigoProducto) ?></p><p class="tienda-producto"><strong>Tienda:</strong> <?= escapar($nombreTienda) ?></p>
<div class="precio"><?= precioCOP($precioProducto) ?></div><p class="iva">Precio en pesos colombianos</p>
<div class="descripcion"><h2>Descripción</h2><p><?= escapar($producto["descripcion"] ?: "Producto institucional del SENA.") ?></p></div>
<div class="seleccion"><h2>Selecciona una talla</h2><div class="tallas">
<?php foreach($tallas as $i=>$talla): ?><button type="button" class="talla <?= $i===0 ? "seleccionada" : "" ?>" <?= $agotado ? "disabled" : "" ?>><?= escapar($talla) ?></button><?php endforeach; ?>
</div></div>
<div class="cantidad"><h2>Cantidad</h2><div class="contador"><button type="button" aria-label="Disminuir cantidad" <?= $agotado ? "disabled" : "" ?>>−</button><span>1</span><button type="button" aria-label="Aumentar cantidad" <?= $agotado ? "disabled" : "" ?>>+</button></div></div>
<div class="disponibilidad"><span class="punto"></span><strong><?= $stock ?> unidades disponibles en esta tienda</strong></div>
<div class="acciones"><?php if($agotado): ?><button type="button" class="boton comprar" disabled>Producto agotado</button><button type="button" class="boton apartar" disabled>No disponible</button><?php else: ?><a href="pago.php" class="boton comprar">Comprar ahora</a><a href="apartado.php" class="boton apartar">Apartar producto</a><?php endif; ?></div>
<div class="informacion"><div class="informacion-item"><div><strong>Tienda seleccionada</strong><p><?= escapar($nombreTienda) ?></p></div></div><div class="informacion-item"><div><strong>Compra segura</strong><p>Realiza tu proceso desde CONFISENA.</p></div></div></div>
</div></div></section></main>
<footer class="footer"><div class="contenedor footer-grid"><div class="footer-col"><h3>CONFISENA</h3><p>Plataforma web de catálogo y venta de uniformes SENA.</p><p class="slogan">Transformamos la forma de comprar.</p></div><div class="footer-col"><h4>Enlaces</h4><a href="index.html">Inicio</a><a href="catalogo.php">Catálogo</a></div></div><div class="footer-bottom"><p>© 2026 CONFISENA. Todos los derechos reservados.</p></div></footer>
</body></html>