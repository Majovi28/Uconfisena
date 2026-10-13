
<?php
session_start();

// PROTEGER EL CATÁLOGO: requiere una sesión iniciada desde PHP.
if (empty($_SESSION["id_usuario"])) {
    header("Location: login.html");
    exit;
}

require_once __DIR__ . "/php/conexion.php";

try {
    $sql = "SELECT
                p.id_producto,
                p.nombre_producto,
                p.tallas,
                p.precio,
                p.descripcion,
                c.nombre_categoria,
                COUNT(DISTINCT i.id_inventario) AS registros_inventario,
                COALESCE(SUM(i.cantidad_disponible), 0)
                    AS cantidad_disponible
            FROM producto p
            LEFT JOIN categoria c
                ON c.id_categoria = p.id_categoria
            LEFT JOIN inventario i
                ON i.id_producto = p.id_producto
            GROUP BY
                p.id_producto,
                p.nombre_producto,
                p.tallas,
                p.precio,
                p.descripcion,
                c.nombre_categoria
            ORDER BY p.id_producto ASC";

    $consulta = $conexion->query($sql);
    $productos = $consulta->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error al consultar el catálogo: " . $e->getMessage());
    $productos = [];
    $errorCatalogo = true;
}

function escapar($valor) {
    return htmlspecialchars((string)($valor ?? ""), ENT_QUOTES, "UTF-8");
}

function slugCategoria($categoria) {
    $categoria = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $categoria);
    $categoria = strtolower($categoria);
    $categoria = preg_replace("/[^a-z0-9]+/", "-", $categoria);
    return trim($categoria, "-");
}

function precioCOP($precio) {
    return "$" . number_format((float)$precio, 0, ",", ".");
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo | CONFISENA</title>
    <link rel="stylesheet" href="stylecatalogo.css">
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
            <a href="catalogo.php" class="activo">Catálogo</a>
            <a href="#productos">Productos</a>
            <a href="#contacto">Contacto</a>
            <a href="cerrar_sesion.php">Cerrar sesión</a>
        </nav>

    </div>
</header>

<main>

    <section class="catalogo-encabezado">
        <div class="contenedor">
            <span class="etiqueta">CATÁLOGO CONFISENA</span>

            <h1>Encuentra el uniforme que necesitas</h1>

            <p>
                Consulta nuestros productos, tallas, precios
                y disponibilidad antes de realizar tu compra o apartado.
            </p>
        </div>
    </section>

    <section class="filtros-seccion">
        <div class="contenedor filtros">

            <div class="buscador">
                <label for="buscar">Buscar producto</label>
                <div class="buscador-input">
                    <span></span>
                    <input
                        type="text"
                        id="buscar"
                        placeholder="Buscar camiseta, pantalón, chaqueta..."
                    >
                </div>
            </div>

            <div class="filtro">
                <label for="categoria">Categoría</label>
                <select id="categoria">
                    <option value="todos">Todas las categorías</option>
                    <?php
                    $categorias = [];

                    foreach ($productos as $producto) {
                        $nombreCategoria = $producto["nombre_categoria"]
                            ?: "Sin categoría";

                        $slug = slugCategoria($nombreCategoria);

                        if (!isset($categorias[$slug])) {
                            $categorias[$slug] = $nombreCategoria;
                        }
                    }

                    foreach ($categorias as $slug => $nombreCategoria):
                    ?>
                        <option value="<?= escapar($slug) ?>">
                            <?= escapar($nombreCategoria) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filtro">
                <label for="ordenar">Ordenar por</label>
                <select id="ordenar">
                    <option value="destacados">Destacados</option>
                    <option value="menor">Precio menor a mayor</option>
                    <option value="mayor">Precio mayor a menor</option>
                    <option value="nombre">Nombre A - Z</option>
                </select>
            </div>

        </div>
    </section>

    <section class="catalogo-seccion" id="productos">
        <div class="contenedor">

            <div class="catalogo-superior">
                <h2>Productos disponibles</h2>
                <span id="totalProductos">
                    <?= count($productos) ?> productos
                </span>
            </div>

            <?php if (!empty($errorCatalogo)): ?>

                <p>No fue posible cargar el catálogo. Intenta nuevamente.</p>

            <?php elseif (empty($productos)): ?>

                <p>
                    Todavía no hay productos registrados en la base de datos.
                </p>

            <?php else: ?>

                <div class="productos-grid" id="productosGrid">

                    <?php foreach ($productos as $producto): ?>

                        <?php
                        $idProducto = (int)$producto["id_producto"];
                        $categoriaProducto = $producto["nombre_categoria"]
                            ?: "Sin categoría";

                        $slug = slugCategoria($categoriaProducto);

                        $tallas = preg_split(
                            "/[\s,;]+/",
                            trim((string)$producto["tallas"]),
                            -1,
                            PREG_SPLIT_NO_EMPTY
                        );

                        $cantidad = (int)$producto["cantidad_disponible"];
                        $tieneInventario = (int)$producto["registros_inventario"] > 0;
                        $agotado = $tieneInventario && $cantidad <= 0;
                        ?>

                        <article
                            class="producto-card"
                            data-categoria="<?= escapar($slug) ?>"
                            data-nombre="<?= escapar($producto["nombre_producto"]) ?>"
                            data-precio="<?= (float)$producto["precio"] ?>"
                        >

                            <div class="producto-imagen">

                                <span class="etiqueta-producto <?= $agotado ? 'agotado' : '' ?>">
                                    <?php if ($agotado): ?>
                                        Agotado
                                    <?php elseif ($cantidad > 0): ?>
                                        Disponible
                                    <?php else: ?>
                                        Consultar disponibilidad
                                    <?php endif; ?>
                                </span>

                                <div class="imagen-placeholder"></div>

                            </div>

                            <div class="producto-contenido">

                                <span class="categoria-producto">
                                    <?= escapar($categoriaProducto) ?>
                                </span>

                                <h3><?= escapar($producto["nombre_producto"]) ?></h3>

                                <p class="descripcion">
                                    <?= escapar($producto["descripcion"]) ?>
                                </p>

                                <div class="producto-dato">
                                    <strong>Tallas disponibles:</strong>

                                    <div class="tallas">

                                        <?php if (!empty($tallas)): ?>

                                            <?php foreach ($tallas as $talla): ?>
                                                <span><?= escapar($talla) ?></span>
                                            <?php endforeach; ?>

                                        <?php else: ?>

                                            <span>Consultar tallas</span>

                                        <?php endif; ?>

                                    </div>
                                </div>

                                <div class="precio">
                                    <?= precioCOP($producto["precio"]) ?>
                                </div>

                                <div class="disponibilidad <?= $agotado ? 'agotado-texto' : 'disponible' ?>">

                                    <span class="punto"></span>

                                    <?php if ($agotado): ?>
                                        Agotado
                                    <?php elseif ($cantidad > 0): ?>
                                        <?= $cantidad ?> unidades disponibles
                                    <?php else: ?>
                                        Disponibilidad por confirmar en tienda
                                    <?php endif; ?>

                                </div>

                                <div class="producto-botones">

                                    <a
                                        href="producto.php?id=<?= $idProducto ?>"
                                        class="btn detalles"
                                    >
                                        Ver detalles
                                    </a>

                                    <?php if ($agotado): ?>

                                        <button class="btn comprar deshabilitado" disabled>
                                            Comprar
                                        </button>

                                        <button class="btn apartar deshabilitado" disabled>
                                            Apartar
                                        </button>

                                    <?php else: ?>

                                        <a
                                            href="pago.html?id_producto=<?= $idProducto ?>"
                                            class="btn comprar"
                                            onclick="guardarProducto(<?= $idProducto ?>, '<?= escapar(addslashes($producto['nombre_producto'])) ?>', <?= (float)$producto['precio'] ?>)"
                                        >
                                            Comprar
                                        </a>

                                        <a
                                            href="apartado.html?id_producto=<?= $idProducto ?>"
                                            class="btn apartar"
                                            onclick="guardarProducto(<?= $idProducto ?>, '<?= escapar(addslashes($producto['nombre_producto'])) ?>', <?= (float)$producto['precio'] ?>)"
                                        >
                                            Apartar
                                        </a>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

                <p id="sinResultados" style="display:none;">
                    No se encontraron productos con esos filtros.
                </p>

            <?php endif; ?>

        </div>
    </section>

    <section class="como-comprar">
        <div class="contenedor">

            <div class="titulo-seccion">
                <span>¿CÓMO FUNCIONA?</span>
                <h2>Compra o aparta tu uniforme</h2>
                <p>Realiza tu proceso de manera sencilla desde CONFISENA.</p>
            </div>

            <div class="pasos-grid">

                <div class="paso">
                    <div class="numero">1</div>
                    <h3>Elige tu producto</h3>
                    <p>Selecciona el uniforme que necesitas y consulta sus tallas y precio.</p>
                </div>

                <div class="paso">
                    <div class="numero">2</div>
                    <h3>Compra o aparta</h3>
                    <p>Selecciona si deseas realizar una compra o apartar el producto.</p>
                </div>

                <div class="paso">
                    <div class="numero">3</div>
                    <h3>Realiza el pago</h3>
                    <p>Completa la información necesaria para realizar tu proceso.</p>
                </div>

                <div class="paso">
                    <div class="numero">4</div>
                    <h3>Recibe tu pedido</h3>
                    <p>Consulta el estado de tu pedido desde tu cuenta.</p>
                </div>

            </div>

        </div>
    </section>

</main>

<footer class="footer" id="contacto">
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
            <p>Correo: contacto@confisena.com</p>
            <p>Bogotá, Colombia</p>
        </div>

    </div>

    <div class="footer-bottom">
        <p>© 2026 CONFISENA. Todos los derechos reservados.</p>
    </div>
</footer>

<script>
// Guardar los datos del producto elegido para las páginas actuales
// de pago y apartado que usan localStorage.
function guardarProducto(id, nombre, precio) {
    localStorage.setItem("id_producto", String(id));
    localStorage.setItem("producto", nombre);
    localStorage.setItem("precio", String(precio));
    localStorage.setItem("cantidad", "1");
}

// Buscar y filtrar productos.
const buscar = document.getElementById("buscar");
const categoria = document.getElementById("categoria");
const ordenar = document.getElementById("ordenar");
const grid = document.getElementById("productosGrid");
const total = document.getElementById("totalProductos");
const sinResultados = document.getElementById("sinResultados");

function aplicarFiltros() {
    if (!grid) return;

    const texto = buscar.value.trim().toLowerCase();
    const categoriaSeleccionada = categoria.value;
    const tarjetas = Array.from(grid.querySelectorAll(".producto-card"));

    tarjetas.forEach(tarjeta => {
        const nombre = (tarjeta.dataset.nombre || "").toLowerCase();
        const categoriaProducto = tarjeta.dataset.categoria || "";

        const coincideTexto = nombre.includes(texto);
        const coincideCategoria =
            categoriaSeleccionada === "todos" ||
            categoriaProducto === categoriaSeleccionada;

        tarjeta.style.display =
            coincideTexto && coincideCategoria ? "" : "none";
    });

    const visibles = tarjetas.filter(
        tarjeta => tarjeta.style.display !== "none"
    );

    if (ordenar.value === "menor") {
        visibles.sort((a, b) =>
            Number(a.dataset.precio) - Number(b.dataset.precio)
        );
    } else if (ordenar.value === "mayor") {
        visibles.sort((a, b) =>
            Number(b.dataset.precio) - Number(a.dataset.precio)
        );
    } else if (ordenar.value === "nombre") {
        visibles.sort((a, b) =>
            a.dataset.nombre.localeCompare(b.dataset.nombre, "es")
        );
    }

    visibles.forEach(tarjeta => grid.appendChild(tarjeta));

    if (total) {
        total.textContent = visibles.length + " productos";
    }

    if (sinResultados) {
        sinResultados.style.display =
            visibles.length === 0 ? "block" : "none";
    }
}

if (buscar && categoria && ordenar) {
    buscar.addEventListener("input", aplicarFiltros);
    categoria.addEventListener("change", aplicarFiltros);
    ordenar.addEventListener("change", aplicarFiltros);
}
</script>

</body>
</html>
