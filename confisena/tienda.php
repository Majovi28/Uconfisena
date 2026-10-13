
<?php
session_start();

if (empty($_SESSION["id_usuario"])) {
    header("Location: login.html");
    exit;
}

if (($_SESSION["rol_usuario"] ?? "") !== "Vendedor") {
    http_response_code(403);
    exit("Acceso denegado. Esta página es exclusiva del Vendedor.");
}

require_once __DIR__ . "/php/conexion.php";

$idTienda = (int)($_SESSION["id_tienda"] ?? 0);

if ($idTienda < 1) {
    exit("Tu cuenta no tiene una tienda asignada. Comunícate con el Gestor.");
}

function escaparPanel($valor) {
    return htmlspecialchars((string)($valor ?? ""), ENT_QUOTES, "UTF-8");
}

$nombreVendedor = $_SESSION["nombre_usuario"] ?? "Vendedor";
$tienda = null;
$productos = 0;
$unidades = 0;
$inventarioBajo = 0;
$error = "";

try {
    $consulta = $conexion->prepare(
        "SELECT id_tienda, nombre_tienda, telefono,
                direccion, localidad, estado
         FROM tienda
         WHERE id_tienda = :id
         LIMIT 1"
    );

    $consulta->execute(["id" => $idTienda]);
    $tienda = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$tienda) {
        exit("No se encontró la tienda asignada a tu cuenta.");
    }

    $consulta = $conexion->prepare(
        "SELECT COUNT(DISTINCT id_producto)
         FROM inventario
         WHERE id_tienda = :tienda"
    );
    $consulta->execute(["tienda" => $idTienda]);
    $productos = (int)$consulta->fetchColumn();

    $consulta = $conexion->prepare(
        "SELECT COALESCE(SUM(cantidad_disponible), 0)
         FROM inventario
         WHERE id_tienda = :tienda"
    );
    $consulta->execute(["tienda" => $idTienda]);
    $unidades = (int)$consulta->fetchColumn();

    $consulta = $conexion->prepare(
        "SELECT COUNT(*)
         FROM (
             SELECT id_producto
             FROM inventario
             WHERE id_tienda = :tienda
             GROUP BY id_producto
             HAVING SUM(cantidad_disponible) <= 5
         ) AS bajo"
    );
    $consulta->execute(["tienda" => $idTienda]);
    $inventarioBajo = (int)$consulta->fetchColumn();

} catch (Throwable $e) {
    error_log("Error cargando panel del vendedor: " . $e->getMessage());
    $error = "No se pudo cargar toda la información de la tienda.";
}

$nombreSeguro = escaparPanel($nombreVendedor);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Vendedor - CONFISENA</title>
    <link rel="stylesheet" href="tienda.css">

    <style>
        .acciones-vendedor {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 20px;
            margin: 25px 0;
        }

        .accion-vendedor {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
        }

        .accion-vendedor h3 {
            color: #172554;
            margin-bottom: 10px;
        }

        .accion-vendedor p {
            color: #64748b;
            line-height: 1.5;
            min-height: 62px;
            margin-bottom: 15px;
        }

        .enlace-accion {
            display: inline-block;
            background: #2563eb;
            color: #fff;
            text-decoration: none;
            padding: 11px 15px;
            border-radius: 7px;
        }

        .enlace-accion:hover {
            background: #172554;
        }

        .mensaje-panel {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 18px;
            margin: 20px 0;
        }
    </style>
</head>
<body>

<header class="encabezado">
    <div class="logo">CONFISENA</div>

    <div class="informacion-vendedor">
        <span>Panel del Vendedor</span>
        <strong><?= $nombreSeguro ?></strong>
    </div>

    <a href="cerrar_sesion.php" class="boton-cerrar">
        Cerrar sesión
    </a>
</header>

<main class="contenedor">

    <section class="bienvenida">
        <p class="texto-pequeno">PANEL DE ADMINISTRACIÓN</p>
        <h1>Bienvenido a CONFISENA</h1>
        <p>
            Administra las existencias de tu tienda y consulta
            los pedidos y apartados relacionados con sus productos.
        </p>
    </section>

    <?php if ($error !== ""): ?>
        <div class="mensaje-panel">
            <?= escaparPanel($error) ?>
        </div>
    <?php else: ?>

        <section class="informacion-tienda">
            <div class="titulo-seccion">
                <h2>Mi tienda</h2>
                <p>Información registrada en la plataforma.</p>
            </div>

            <div class="datos-tienda">
                <div class="dato">
                    <span>Nombre de la tienda</span>
                    <strong><?= escaparPanel($tienda["nombre_tienda"]) ?></strong>
                </div>

                <div class="dato">
                    <span>Teléfono</span>
                    <strong><?= escaparPanel($tienda["telefono"]) ?></strong>
                </div>

                <div class="dato">
                    <span>Dirección</span>
                    <strong><?= escaparPanel($tienda["direccion"]) ?></strong>
                </div>

                <div class="dato">
                    <span>Localidad</span>
                    <strong><?= escaparPanel($tienda["localidad"]) ?></strong>
                </div>
            </div>
        </section>

        <section class="seccion-opciones">
            <div class="titulo-seccion">
                <h2>Opciones de mi tienda</h2>
                <p>Selecciona la función que necesitas.</p>
            </div>

            <div class="acciones-vendedor">

                <article class="accion-vendedor">
                    <h3>Inventario</h3>
                    <p>
                        Consulta los productos, agrega existencias
                        y actualiza sus cantidades disponibles.
                    </p>
                    <a class="enlace-accion" href="inventario_tienda.php">
                        Administrar inventario
                    </a>
                </article>

                <article class="accion-vendedor">
                    <h3>Pedidos</h3>
                    <p>
                        Consulta las compras relacionadas con productos
                        de tu tienda y actualiza su estado de preparación.
                    </p>
                    <a class="enlace-accion" href="pedidos_tienda.php">
                        Consultar pedidos
                    </a>
                </article>

                <article class="accion-vendedor">
                    <h3>Apartados</h3>
                    <p>
                        Consulta las reservas asignadas a tu tienda
                        y actualiza su estado de preparación o entrega.
                    </p>
                    <a class="enlace-accion" href="apartados_tienda.php">
                        Consultar apartados
                    </a>
                </article>

                <article class="accion-vendedor">
                    <h3>Catálogo</h3>
                    <p>
                        Consulta los productos, sus tallas y precios
                        disponibles en la plataforma CONFISENA.
                    </p>
                    <a class="enlace-accion" href="catalogo.php">
                        Ver catálogo
                    </a>
                </article>

            </div>
        </section>

        <section class="resumen">
            <div class="tarjeta-resumen">
                <span>Productos en mi inventario</span>
                <strong><?= $productos ?></strong>
            </div>

            <div class="tarjeta-resumen">
                <span>Unidades disponibles</span>
                <strong><?= $unidades ?></strong>
            </div>

            <div class="tarjeta-resumen">
                <span>Productos con cinco unidades o menos</span>
                <strong><?= $inventarioBajo ?></strong>
            </div>
        </section>

    <?php endif; ?>
</main>

<footer style="text-align:center;padding:25px;color:#64748b;">
    © 2026 CONFISENA - Transformamos la forma de comprar
</footer>

</body>
</html>
