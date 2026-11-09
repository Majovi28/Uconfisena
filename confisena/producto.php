
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
$idInventario = filter_input(INPUT_GET, "id_inventario", FILTER_VALIDATE_INT);

if (!$idProducto || $idProducto < 1 || !$idInventario || $idInventario < 1) {
    header("Location: catalogo.php");
    exit;
}

try {
    $sql = "SELECT
                p.id_producto, p.nombre_producto, p.tallas,
                COALESCE(i.precio, p.precio) AS precio,
                p.descripcion, c.nombre_categoria,
                i.id_inventario, i.id_tienda, i.cantidad_disponible,
                t.nombre_tienda
            FROM producto p
            INNER JOIN inventario i ON i.id_producto = p.id_producto
            INNER JOIN tienda t ON t.id_tienda = i.id_tienda
            LEFT JOIN categoria c ON c.id_categoria = p.id_categoria
            WHERE p.id_producto = :id
              AND i.id_inventario = :id_inventario
              AND t.estado = 1
            LIMIT 1"
