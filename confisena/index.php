<?php
require_once __DIR__ . "/php/conexion.php";
function inicioEscapar($v){return htmlspecialchars((string)($v ?? ""), ENT_QUOTES, "UTF-8");}
function inicioPrecio($v){return "$" . number_format((float)$v,0,",",".");}
$errorInicio=false;$tiendasInicio=[];$productosInicio=[];$totalTiendasInicio=0;$totalProductosInicio=0;
try {
$totalTiendasInicio=(int)$conexion->query("SELECT COUNT(*) FROM tienda WHERE estado=1")->fetchColumn();
$totalProductosInicio=(int)$conexion->query("SELECT COUNT(DISTINCT p.id_producto) FROM producto p INNER JOIN inventario i ON i.id_producto=p.id_producto INNER JOIN tienda t ON t.id_tienda=i.id_tienda WHERE t.estado=1 AND i.cantidad_disponible>0")->fetchColumn();
$tiendasInicio=$conexion->query("SELECT t.id_tienda,t.nombre_tienda,t.direccion,t.localidad,t.telefono,COUNT(DISTINCT CASE WHEN i.cantidad_disponible>0 THEN i.id_producto END) AS productos_disponibles FROM tienda t LEFT JOIN inventario i ON i.id_tienda=t.id_tienda WHERE t.estado=1 GROUP BY t.id_tienda,t.nombre_tienda,t.direccion,t.localidad,t.telefono ORDER BY t.nombre_tienda LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
$productosInicio=$conexion->query("SELECT p.id_producto,p.nombre_producto,p.descripcion,p.imagen,c.nombre_categoria,MIN(COALESCE(i.precio,p.precio)) AS precio,SUM(i.cantidad_disponible) AS stock FROM producto p INNER JOIN inventario i ON i.id_producto=p.id_producto INNER JOIN tienda t ON t.id_tienda=i.id_tienda LEFT JOIN categoria c ON c.id_categoria=p.id_categoria WHERE t.estado=1 AND i.cantidad_disponible>0 GROUP BY p.id_producto,p.nombre_producto,p.descripcion,p.imagen,c.nombre_categoria ORDER BY stock DESC,p.nombre_producto LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e){error_log("Error cargando inicio CONFISENA: ".$e->getMessage());$errorInicio=true;}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CONFISENA | Uniformes SENA</title>

    <link rel="stylesheet" href="style.css">
<style>.logo-inicio{display:flex;flex-direction:row;align-items:center;gap:12px}.logo-inicio img{width:66px;height:62px;object-fit:contain}.logo-texto{display:flex;flex-direction:column}.producto-imagen{height:230px;overflow:hidden;background:#F5F3EE}.producto-imagen img{width:100%;height:100%;object-fit:contain;padding:12px}.inicio-vacio{grid-column:1/-1;text-align:center;padding:25px;background:#fff;border-radius:12px}@media(max-width:600px){.logo-inicio img{width:54px;height:52px}}</style>
</head>

<body>

    <header class="header">

        <div class="contenedor header-contenido">


            <a href="index.html" class="logo">
                <span class="logo-principal">CONFI<span>SENA</span></span>
                <small>Transformamos la forma de comprar</small>
            </a>

            <nav class="menu">

                <a href="index.php" class="activo">Inicio</a>


                <a href="login.html">Iniciar sesión</a>

                <a href="login.html" class="boton-registro">
                    Registrarse
                </a>

            </nav>

        </div>

    </header>



    <main>

        <section class="hero">

            <div class="contenedor hero-contenido">


                <div class="hero-texto">

                    <span class="etiqueta">
                        PLATAFORMA DE UNIFORMES SENA
                    </span>

                    <h1>
                        Encuentra tus uniformes
                        <span>SENA</span>
                        de manera fácil y segura
                    </h1>

                    <p>
                        Consulta productos, precios, tallas y puntos de venta
                        autorizados desde un solo lugar.
                    </p>

                    <div class="hero-botones">

                        <a href="#catalogo" class="boton boton-principal">
                            Ver catálogo
                        </a>

                        <a href="#tiendas" class="boton boton-secundario">
                            Conocer tiendas
                        </a>

                    </div>

                </div>


                <div class="hero-visual">

                    <div class="tarjeta-uniforme">

                        <div class="icono-uniforme">
                            👕
                        </div>

                        <h3>
                            Uniformes SENA
                        </h3>

                        <p>
                            Consulta y compra tus productos
                            de forma rápida.
                        </p>

                        <div class="datos-uniforme">

                            <div>
                                <strong><?= $totalProductosInicio ?></strong>
                                <span>Productos</span>
                            </div>

                            <div>
                                <strong><?= $totalTiendasInicio ?></strong>
                                <span>Tiendas</span>
                            </div>

                            <div>
                                <strong>En línea</strong>
                                <span>Consulta</span>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </section>


        <section id="categorias" class="seccion categorias">

            <div class="contenedor">

                <div class="titulo-seccion">

                    <span>CATEGORÍAS</span>

                    <h2>
                        Encuentra lo que necesitas
                    </h2>

                    <p>
                        Explora las diferentes categorías de uniformes
                        y productos disponibles.
                    </p>

                </div>


                <div class="categorias-grid">

                    <a href="#catalogo" class="categoria-card">

                        <div class="categoria-icono">
                            👕
                        </div>

                        <h3>
                            Camisas
                        </h3>

                        <p>
                            Camisas y prendas superiores.
                        </p>

                    </a>


                    <a href="#catalogo" class="categoria-card">

                        <div class="categoria-icono">
                            👖
                        </div>

                        <h3>
                            Pantalones
                        </h3>

                        <p>
                            Pantalones para diferentes programas.
                        </p>

                    </a>


                    <a href="#catalogo" class="categoria-card">

                        <div class="categoria-icono">
                            🧥
                        </div>

                        <h3>
                            Chaquetas
                        </h3>

                        <p>
                            Chaquetas y prendas complementarias.
                        </p>

                    </a>


                    <a href="#catalogo" class="categoria-card">

                        <div class="categoria-icono">
                            📦
                        </div>

                        <h3>
                            Otros uniformes
                        </h3>

                        <p>
                            Consulta otros productos disponibles.
                        </p>

                    </a>

                </div>

            </div>

        </section>



        <section id="catalogo" class="seccion catalogo">

            <div class="contenedor">

                <div class="titulo-seccion">

                    <span>CATÁLOGO</span>

                    <h2>
                        Productos destacados
                    </h2>

                    <p>
                        Consulta algunos de los productos disponibles
                        en CONFISENA.
                    </p>

                </div>


                <div class="productos-grid">
<?php if($errorInicio): ?><p class="inicio-vacio">No fue posible cargar los productos.</p><?php elseif(!$productosInicio): ?><p class="inicio-vacio">Aun no hay productos con existencias disponibles.</p><?php else: foreach($productosInicio as $producto): ?>
<article class="producto-card"><div class="producto-imagen"><?php if(!empty($producto["imagen"])): ?><img src="<?= inicioEscapar($producto["imagen"]) ?>" alt="<?= inicioEscapar($producto["nombre_producto"]) ?>" loading="lazy"><?php else: ?><span>👕</span><?php endif; ?></div><div class="producto-info"><span class="producto-categoria"><?= inicioEscapar($producto["nombre_categoria"] ?: "Uniformes") ?></span><h3><?= inicioEscapar($producto["nombre_producto"]) ?></h3><p><?= inicioEscapar($producto["descripcion"] ?: "Producto disponible en CONFISENA.") ?></p><div class="producto-abajo"><strong><?= inicioPrecio($producto["precio"]) ?></strong></div></div></article>
<?php endforeach; endif; ?>
</div>

                <div class="centrar">

                    <a href="login.html" class="boton boton-principal">
                        Ver todo el catálogo
                    </a>

                </div>

            </div>

        </section>


        <section class="seccion sobre">

            <div class="contenedor sobre-contenido">

                <div class="sobre-icono">
                    🛒
                </div>

                <div class="sobre-texto">

                    <span>
                        SOBRE CONFISENA
                    </span>

                    <h2>
                        Transformamos la forma de comprar
                    </h2>

                    <p>
                        CONFISENA es una plataforma web diseñada para
                        facilitar la consulta y adquisición de uniformes
                        SENA.
                    </p>

                    <p>
                        Nuestro objetivo es reunir en un solo lugar la
                        información de productos, precios, tallas,
                        disponibilidad y tiendas autorizadas.
                    </p>

                    <a href="login.html" class="boton boton-principal">
                        Conocer nuestras tiendas
                    </a>

                </div>

            </div>

        </section>


        <section id="tiendas" class="seccion tiendas"><div class="contenedor"><div class="titulo-seccion"><span>PUNTOS DE VENTA</span><h2>Encuentra una tienda</h2><p>Estas son algunas de las tiendas activas registradas en CONFISENA.</p></div><div class="tiendas-grid"><?php if($errorInicio): ?><p class="inicio-vacio">No fue posible cargar las tiendas.</p><?php elseif(!$tiendasInicio): ?><p class="inicio-vacio">Todavia no hay tiendas activas registradas.</p><?php else: foreach($tiendasInicio as $tienda): ?><article class="tienda-card"><div class="tienda-icono">🏪</div><div><h3><?= inicioEscapar($tienda["nombre_tienda"]) ?></h3><?php if(!empty($tienda["direccion"])): ?><p>📍 <?= inicioEscapar($tienda["direccion"]) ?><?= !empty($tienda["localidad"]) ? ", ".inicioEscapar($tienda["localidad"]) : "" ?></p><?php endif; ?><?php if(!empty($tienda["telefono"])): ?><p>☎ <?= inicioEscapar($tienda["telefono"]) ?></p><?php endif; ?><p><?= (int)$tienda["productos_disponibles"] ?> productos disponibles</p></div></article><?php endforeach; endif; ?></div></div></section>

        <section class="cta">

            <div class="contenedor cta-contenido">

                <h2>
                    ¿Ya encontraste tu uniforme?
                </h2>

                <p>
                    Regístrate en CONFISENA y comienza a consultar
                    nuestros productos.
                </p>

                <a href="login.html" class="boton boton-blanco">
                    Crear mi cuenta
                </a>

            </div>

        </section>

    </main>


    <footer class="footer">

        <div class="contenedor footer-grid">


            <div class="footer-col">

                <h3>
                    CONFISENA
                </h3>

                <p>
                    Plataforma web de catálogo y venta
                    de uniformes SENA.
                </p>

                <p class="slogan">
                    Transformamos la forma de comprar.
                </p>

            </div>


            <div class="footer-col">

                <h4>
                    Mi cuenta
                </h4>

                <a href="login.html">
                    Iniciar sesión
                </a>

                <a href="#">
                    Mis pedidos
                </a>

                <a href="#">
                    Mis apartados
                </a>

            </div>



            <div class="footer-col">

                <h4>
                    Contacto
                </h4>

                <p>
                    📧 contacto@confisena.com
                </p>

                <p>
                    📍 Bogotá, Colombia
                </p>

            </div>

        </div>


        <div class="footer-bottom">

            <p>
                © 2026 CONFISENA. Todos los derechos reservados.
            </p>

            <div>

                <a href="#">
                    Términos y condiciones
                </a>

                <a href="#">
                    Política de privacidad
                </a>

            </div>

        </div>

    </footer>

</body>

</html>