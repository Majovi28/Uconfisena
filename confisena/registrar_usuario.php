<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/php/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "mensaje" => "Método no permitido."]);
    exit;
}

$nombre = trim($_POST["nombre"] ?? $_POST["nombreRegistro"] ?? "");
$apellido = trim($_POST["apellido"] ?? $_POST["apellidoRegistro"] ?? "");
$documento = trim($_POST["documento"] ?? $_POST["documentoRegistro"] ?? "");
$telefono = trim($_POST["telefono"] ?? $_POST["telefonoRegistro"] ?? "");
$correo = strtolower(trim($_POST["correo"] ?? $_POST["correoRegistro"] ?? ""));
$contrasena = $_POST["contrasena"] ?? $_POST["contrasenaRegistro"] ?? "";

if ($nombre === "" || $apellido === "" || $documento === "" || $telefono === "" || $correo === "" || $contrasena === "") {
    echo json_encode(["success" => false, "mensaje" => "Todos los campos son obligatorios."]);
    exit;
}

if (!preg_match('/^[0-9]{6,10}$/', $documento)) {
    echo json_encode(["success" => false, "mensaje" => "El documento debe contener solo números y tener entre 6 y 10 dígitos."]);
    exit;
}

if (!preg_match('/^3[0-9]{9}$/', $telefono)) {
    echo json_encode(["success" => false, "mensaje" => "El teléfono debe tener exactamente 10 dígitos y comenzar con 3."]);
    exit;
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "mensaje" => "Ingresa un correo electrónico válido."]);
    exit;
}

if (strlen($contrasena) < 6) {
    echo json_encode(["success" => false, "mensaje" => "La contraseña debe tener al menos 6 caracteres."]);
    exit;
}

try {
    $consulta = $conexion->prepare("SELECT id_usuario FROM usuario WHERE correo = :correo OR documento = :documento LIMIT 1");
    $consulta->execute(["correo" => $correo, "documento" => $documento]);

    if ($consulta->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode(["success" => false, "mensaje" => "El correo o el documento ya están registrados."]);
        exit;
    }

    $contrasenaEncriptada = password_hash($contrasena, PASSWORD_DEFAULT);

    $sql = "INSERT INTO usuario (id_rol, nombre, apellido, documento, correo, `contraseña`, telefono, estado)
            VALUES (1, :nombre, :apellido, :documento, :correo, :contrasena, :telefono, 1)";

    $insertar = $conexion->prepare($sql);
    $insertar->execute([
        "nombre" => $nombre,
        "apellido" => $apellido,
        "documento" => $documento,
        "correo" => $correo,
        "contrasena" => $contrasenaEncriptada,
        "telefono" => $telefono
    ]);

    echo json_encode([
        "success" => true,
        "mensaje" => "Usuario registrado correctamente. Ya puedes iniciar sesión.",
        "id_usuario" => (int) $conexion->lastInsertId()
    ]);
} catch (PDOException $e) {
    error_log("Error al registrar usuario: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "mensaje" => "No fue posible registrar el usuario. Verifica los datos e inténtalo de nuevo."]);
}
?>