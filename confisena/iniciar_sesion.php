
<?php
session_start();

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/php/conexion.php";

function responder($success, $mensaje, $codigo = 200, $datos = []) {
    http_response_code($codigo);

    echo json_encode(
        array_merge([
            "success" => $success,
            "mensaje" => $mensaje
        ], $datos),
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Método no permitido.", 405);
}

$correo = strtolower(trim($_POST["correo"] ?? ""));
$contrasena = $_POST["contrasena"] ?? "";

if ($correo === "" || $contrasena === "") {
    responder(false, "Ingresa el correo y la contraseña.", 400);
}

try {
    $sql = "SELECT
                u.id_usuario,
                u.id_tienda,
                u.nombre,
                u.apellido,
                u.correo,
                u.`contraseña`,
                u.estado,
                r.nombre_rol,
                t.nombre_tienda
            FROM usuario u
            LEFT JOIN rol r
                ON r.id_rol = u.id_rol
            LEFT JOIN tienda t
                ON t.id_tienda = u.id_tienda
            WHERE u.correo = :correo
            LIMIT 1";

    $consulta = $conexion->prepare($sql);

    $consulta->execute([
        "correo" => $correo
    ]);

    $usuario = $consulta->fetch(PDO::FETCH_ASSOC);

    if (
        !$usuario ||
        !password_verify(
            $contrasena,
            $usuario["contraseña"]
        )
    ) {
        responder(false, "Correo o contraseña incorrectos.", 401);
    }

    if ((int)$usuario["estado"] !== 1) {
        responder(false, "Tu cuenta está inactiva.", 403);
    }

    if (empty($usuario["nombre_rol"])) {
        responder(false, "Tu cuenta no tiene un rol asignado.", 403);
    }

    session_regenerate_id(true);

    $_SESSION["id_usuario"] = (int)$usuario["id_usuario"];

    $_SESSION["nombre_usuario"] =
        $usuario["nombre"] . " " . $usuario["apellido"];

    $_SESSION["correo_usuario"] = $usuario["correo"];

    $_SESSION["rol_usuario"] = $usuario["nombre_rol"];

    $_SESSION["id_tienda"] = $usuario["id_tienda"]
        ? (int)$usuario["id_tienda"]
        : null;

    responder(true, "Inicio de sesión exitoso.", 200, [
        "id_usuario" => (int)$usuario["id_usuario"],
        "nombre" => $usuario["nombre"],
        "apellido" => $usuario["apellido"],
        "correo" => $usuario["correo"],
        "rol" => $usuario["nombre_rol"],
        "id_tienda" => $usuario["id_tienda"]
            ? (int)$usuario["id_tienda"]
            : null,
        "tienda" => $usuario["nombre_tienda"] ?? ""
    ]);

} catch (Throwable $e) {
    error_log("Error al iniciar sesión: " . $e->getMessage());

    responder(
        false,
        "No fue posible iniciar sesión. Revisa la conexión con la base de datos.",
        500
    );
}
?>
