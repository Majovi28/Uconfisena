<?php

$servidor = "localhost";
$base_datos = "confisena";
$usuario = "root";
$contrasena = "";

try {

    $conexion = new PDO(
        "mysql:host=$servidor;dbname=$base_datos;charset=utf8mb4",
        $usuario,
        $contrasena
    );

    $conexion->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch (PDOException $e) {

    error_log(
        "Error de conexión: " .
        $e->getMessage()
    );

    exit(
        "No se pudo conectar con la base de datos."
    );
}

?>