
<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/php/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "mensaje" => "Método no permitido."
    ]);

    exit;
}

// Recibir los datos del formulario
$nombre = trim($_POST["nombre"] ?? $_POST["nombreRegistro"] ?? "");
$apellido = trim($_POST["apellido"] ?? $_POST["apellidoRegistro"] ?? "");
$documento = trim($_POST["documento"] ?? $_POST["documentoRegistro"] ?? "");
$telefono = trim($_POST["telefono"] ?? $_POST["telefonoRegistro"] ?? "");
$correo = strtolower(trim($_POST["correo"] ?? $_POST["correoRegistro"] ?? ""));
$contrasena = $_POST["contrasena"] ?? $_POST["contrasenaRegistro"] ?? "";

// Validar campos obligatorios
if (
    $nombre === "" ||
    $apellido === "" ||
    $documento === "" ||
    $telefono === "" ||
    $correo === "" ||
    $contrasena === ""
) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Todos los campos son obligatorios."
    ]);

    exit;
}

// Validar documento numérico
if (!ctype_digit($documento)) {
    echo json_encode([
        "success" => false,
        "mensaje" => "El documento debe contener solo números."
    ]);

    exit;
}

// Validar teléfono de 10 dígitos
if (!preg_match('/^[0-9]{10}$/', $telefono)) {
    echo json_encode([
        "success" => false,
        "mensaje" => "El teléfono debe tener exactamente 10 dígitos."
    ]);

    exit;
}

// Validar correo electrónico
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Ingresa un correo electrónico válido."
    ]);

    exit;
}

// Validar contraseña
if (strlen($contrasena) < 6) {
    echo json_encode([
        "success" => false,
        "mensaje" => "La contraseña debe tener al menos 6 caracteres."
    ]);

    exit;
}

try {
    // Comprobar si el correo o documento ya existen
    $consulta = $conexion->prepare(
        "SELECT id_usuario
         FROM usuario
         WHERE correo = :correo OR documento = :documento
         LIMIT 1"
    );

    $consulta->execute([
        "correo" => $correo,
        "documento" => $documento
    ]);

    if ($consulta->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode([
            "success" => false,
            "mensaje" => "El correo o el documento ya están registrados."
        ]);

        exit;
    }

    // Encriptar contraseña antes de guardarla
    $contrasenaEncriptada = password_hash(
        $contrasena,
        PASSWORD_DEFAULT
    );

    // Crear la cuenta como usuario activo.
    // id_rol = 1 corresponde al rol Usuario.
    // estado = 1 significa Activo.
    $sql = "INSERT INTO usuario
            (
                id_rol,
                nombre,
                apellido,
                documento,
                correo,
                `contraseña`,
                telefono,
                estado
            )
            VALUES
            (
                1,
                :nombre,
                :apellido,
                :documento,
                :correo,
                :contrasena,
                :telefono,
                1
            )";

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

    echo json_encode([
        "success" => false,
        "mensaje" => "No fue posible registrar el usuario. Verifica los datos e inténtalo de nuevo."
    ]);
}
?>
