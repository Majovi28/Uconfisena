
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
                p.id_producto, p.nombre_producto, p.tallas, p.descripcion,
                c.nombre_categoria, i.id_inventario, i.id_tienda,
                t.nombre_tienda,
                COALESCE(i.precio, p.precio) AS precio,
                i.cantidad_disponible
            FROM inventario i
            INNER JOIN producto p ON p.id_producto = i.id_producto
            LEFT JOIN categoria c ON c.id_categoria = p.id_categoria
            INNER JOIN tienda t ON t.id_tienda = i.id_tienda
            WHERE t.estado = 1
            ORDER BY p.nombre_producto, t.nombre_tienda"
