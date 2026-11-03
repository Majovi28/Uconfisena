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
$tipoDocumento = trim($_POST["tipo_documento"] ?? "");
$documento = trim($_POST["documento"] ?? $_POST["documentoRegistro"] ?? "");
$aceptaTerminos = ($_POST["acepta_terminos"] ?? "0") === "1";
$telefono = trim($_POST["telefono"] ?? $_POST["telefonoRegistro"] ?? "");
$correo = strtolower(trim($_POST["correo"] ?? $_POST["correoRegistro"] ?? ""));
$contrasena = $_POST["contrasena"] ?? $_POST["contrasenaRegistro"] ?? "";

if ($nombre === "" || $apellido === "" || $tipoDocumento === "" || $documento === "" || $telefono === "" || $correo === "" || $contrasena === "" || !$aceptaTerminos) {
    echo json_encode(["success" => false, "mensaje" => "Todos los campos son obligatorios."]);
    exit;
}

if (!in_array($tipoDocumento, ["CC", "TI", "CE", "PPT", "Pasaporte"], true)) {
    echo json_encode(["success" => false, "mensaje" => "Selecciona un tipo de documento válido."]);
    exit;
}

$tiposDocumentoPermitidos = ["Cédula de ciudadanía", "Tarjeta de identidad", "Cédula de extranjería", "Pasaporte"];
if (!in_array($tipoDocumento, $tiposDocumentoPermitidos, true)) {
    echo json_encode(["success" => false, "mensaje" => "Selecciona un tipo de documento válido."]);
    exit;
}

if ($aceptaTerminos !== "1") {
    echo json_encode(["success" => false, "mensaje" => "Debes aceptar los términos y condiciones para registrarte."]);
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

    $sql = "INSERT INTO usuario (id_rol, nombre, apellido, tipo_documento, documento, correo, `contraseña`, telefono, acepta_terminos, estado)
            VALUES (1, :nombre, :apellido, :tipo_documento, :documento, :correo, :contrasena, :telefono, 1, 1)";

    $insertar = $conexion->prepare($sql);
    $insertar->execute([
        "nombre" => $nombre,
        "apellido" => $apellido,
        "tipo_documento" => $tipoDocumento,
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