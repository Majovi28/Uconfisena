
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

function precioCOP($precio) {
    return "$" . number_format((float)$precio, 0, ",", ".");
}

$idProducto = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$idProducto || $idProducto < 1) {
    header("Location: catalogo.php");
    exit;
}

try {
    $sql = "SELECT
                p.id_producto,
                p.nombre_producto,
                p.tallas,
                p.precio,
                p.descripcion,
                c.nombre_categoria,
                COUNT(DISTINCT i.id_inventario) AS registros_inventario,
                COALESCE(SUM(i.cantidad_disponible), 0) AS cantidad_disponible
            FROM producto p
            LEFT JOIN categoria c
                ON c.id_categoria = p.id_categoria
            LEFT JOIN inventario i
                ON i.id_producto = p.id_producto
            WHERE p.id_producto = :id
            GROUP BY
                p.id_producto,
                p.nombre_producto,
                p.tallas,
                p.precio,
                p.descripcion,
                c.nombre_categoria
            LIMIT 1";

    $consulta = $conexion->prepare($sql);
    $consulta->execute(["id" => $idProducto]);
    $producto = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$producto) {
        http_response_code(404);
        exit('Producto no encontrado. <a href="catalogo.php">Volver al catálogo</a>');
    }

    $sqlRelacionados = "SELECT
                            p.id_producto,
                            p.nombre_producto,
                            p.precio,
                            c.nombre_categoria
                        FROM producto p
                        LEFT JOIN categoria c
                            ON c.id_categoria = p.id_categoria
                        WHERE p.id_producto <> :id
                        ORDER BY p.id_producto ASC
                        LIMIT 3";

    $consultaRelacionados = $conexion->prepare($sqlRelacionados);
    $consultaRelacionados->execute(["id" => $idProducto]);
    $relacionados = $consultaRelacionados->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error al consultar el producto: " . $e->getMessage());
    http_response_code(500);
    exit("No fue posible cargar el producto. Intenta nuevamente.");
}

$tallas = preg_split(
    "/[\s,;]+/",
    trim((string)$producto["tallas"]),
    -1,
    PREG_SPLIT_NO_EMPTY
);

$stock = (int)$producto["cantidad_disponible"];
$hayInventario = (int)$producto["registros_inventario"] > 0;
$agotado = $hayInventario && $stock <= 0;
$cantidadMaxima = $hayInventario ? max(1, $stock) : 10;

$nombreProducto = (string)$producto["nombre_producto"];
$precioProducto = (float)$producto["precio"];
$categoriaProducto = $producto["nombre_categoria"] ?: "Sin categoría";

$codigoProducto = "CF-PRD-" . str_pad(
    (string)$producto["id_producto"],
    4,
    "0",
    STR_PAD_LEFT
);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escapar($nombreProducto) ?> | CONFISENA</title>
    <link rel="stylesheet" href="sproducto.css">
    <script src="jproducto.js" defer></script>
</head>

<body>

<header class="header">
    <div class="contenedor header-contenido">

        <a href="index.html" class="logo">
            <span class="logo-principal">CONFI<span>SENA</span></span>
            <small>Transformamos la forma de comprar</small>
        </a>

        <nav class="menu">
            <a href="index.html">Inicio</a>
            <a href="catalogo.php">Catálogo</a>
            <a href="catalogo.php">Productos</a>
            <a href="cerrar_sesion.php">Cerrar sesión</a>
        </nav>

    </div>
</header>

<main>

    <div class="contenedor">
        <div class="migas">
            <a href="index.html">Inicio</a>
            <span>/</span>
            <a href="catalogo.php">Catálogo</a>
            <span>/</span>
            <span><?= escapar($nombreProducto) ?></span>
        </div>
    </div>

    <section class="producto-seccion">
        <div
            class="contenedor producto"
            data-product-id="<?= (int)$producto["id_producto"] ?>"
            data-product-name="<?= escapar($nombreProducto) ?>"
            data-product-price="<?= escapar($precioProducto) ?>"
            data-max-stock="<?= (int)$cantidadMaxima ?>"
            data-has-stock="<?= $hayInventario ? "1" : "0" ?>"
        >

            <div class="producto-imagen">

                <span class="estado">
                    <?php if ($agotado): ?>
                        Agotado
                    <?php elseif ($stock > 0): ?>
                        Disponible
                    <?php else: ?>
                        Consultar disponibilidad
                    <?php endif; ?>
                </span>

                <div class="imagen-placeholder"></div>

            </div>

            <div class="producto-info">

                <span class="categoria">
                    <?= escapar(strtoupper($categoriaProducto)) ?>
                </span>

                <h1><?= escapar($nombreProducto) ?></h1>

                <p class="codigo">
                    Código del producto: <?= escapar($codigoProducto) ?>
                </p>

                <div class="precio">
                    <?= precioCOP($precioProducto) ?>
                </div>

                <p class="iva">Precio de referencia en pesos colombianos</p>

                <div class="descripcion">
                    <h2>Descripción</h2>
                    <p>
                        <?= escapar($producto["descripcion"] ?: "Producto institucional del SENA. Consulta las tallas y disponibilidad antes de realizar tu compra o apartado.") ?>
                    </p>
                </div>

                <div class="seleccion">
                    <h2>Selecciona una talla</h2>

                    <div class="tallas">
                        <?php if (!empty($tallas)): ?>
                            <?php foreach ($tallas as $indice => $talla): ?>
                                <button
                                    type="button"
                                    class="talla <?= $indice === 0 ? "seleccionada" : "" ?>"
                                    <?= $agotado ? "disabled" : "" ?>
                                ><?= escapar($talla) ?></button>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span>Consulta las tallas disponibles en tienda.</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="cantidad">
                    <h2>Cantidad</h2>

                    <div class="contador">
                        <button type="button" aria-label="Disminuir cantidad"
                            <?= $agotado ? "disabled" : "" ?>>−</button>

                        <span>1</span>

                        <button type="button" aria-label="Aumentar cantidad"
                            <?= $agotado ? "disabled" : "" ?>>+</button>
                    </div>
                </div>

                <div class="disponibilidad">
                    <span class="punto"></span>

                    <strong>
                        <?php if ($agotado): ?>
                            Agotado
                        <?php elseif ($stock > 0): ?>
                            <?= $stock ?> unidades disponibles
                        <?php else: ?>
                            Disponibilidad por confirmar
                        <?php endif; ?>
                    </strong>

                    <span>
                        <?php if ($agotado): ?>
                            por el momento
                        <?php else: ?>
                            para compra o apartado
                        <?php endif; ?>
                    </span>
                </div>

                <div class="acciones">
                    <?php if ($agotado): ?>

                        <button type="button" class="boton comprar" disabled>
                            Producto agotado
                        </button>

                        <button type="button" class="boton apartar" disabled>
                            No disponible
                        </button>

                    <?php else: ?>

                        <a href="pago.php" class="boton comprar">
                            🛒 Comprar ahora
                        </a>

                        <a href="apartado.php" class="boton apartar">
                            Apartar producto
                        </a>

                    <?php endif; ?>
                </div>

                <div class="informacion">

                    <div class="informacion-item">
                        <span class="informacion-icono"></span>
                        <div>
                            <strong>Tienda disponible</strong>
                            <p>Consulta los puntos de venta registrados en la plataforma.</p>
                        </div>
                    </div>

                    <div class="informacion-item">
                        <span class="informacion-icono"></span>
                        <div>
                            <strong>Compra segura</strong>
                            <p>Realiza tu proceso de compra desde CONFISENA.</p>
                        </div>
                    </div>

                    <div class="informacion-item">
                        <span class="informacion-icono"></span>
                        <div>
                            <strong>Consulta tu pedido</strong>
                            <p>Conserva la información de tu compra para futuras consultas.</p>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </section>

    <?php if (!empty($relacionados)): ?>
    <section class="relacionados">
        <div class="contenedor">

            <div class="titulo-seccion">
                <span>TAMBIÉN TE PUEDE INTERESAR</span>
                <h2>Productos relacionados</h2>
            </div>

            <div class="productos-grid">

                <?php foreach ($relacionados as $relacionado): ?>
                    <article class="producto-card">

                        <div class="producto-card-imagen"></div>

                        <div class="producto-card-info">

                            <span>
                                <?= escapar($relacionado["nombre_categoria"] ?: "Sin categoría") ?>
                            </span>

                            <h3>
                                <?= escapar($relacionado["nombre_producto"]) ?>
                            </h3>

                            <strong>
                                <?= precioCOP($relacionado["precio"]) ?>
                            </strong>

                            <a href="producto.php?id=<?= (int)$relacionado["id_producto"] ?>">
                                Ver producto
                            </a>

                        </div>

                    </article>
                <?php endforeach; ?>

            </div>
        </div>
    </section>
    <?php endif; ?>

</main>

<footer class="footer">
    <div class="contenedor footer-grid">

        <div class="footer-col">
            <h3>CONFISENA</h3>
            <p>Plataforma web de catálogo y venta de uniformes SENA.</p>
            <p class="slogan">Transformamos la forma de comprar.</p>
        </div>

        <div class="footer-col">
            <h4>Enlaces</h4>
            <a href="index.html">Inicio</a>
            <a href="catalogo.php">Catálogo</a>
        </div>

        <div class="footer-col">
            <h4>Mi cuenta</h4>
            <a href="cerrar_sesion.php">Cerrar sesión</a>
        </div>

        <div class="footer-col">
            <h4>Contacto</h4>
            <p>contacto@confisena.com</p>
            <p>Bogotá, Colombia</p>
        </div>

    </div>

    <div class="footer-bottom">
        <p>© 2026 CONFISENA. Todos los derechos reservados.</p>
    </div>
</footer>

</body>
</html>
