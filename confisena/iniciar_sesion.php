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

$maxIntentos = 3;
$minutosBloqueo = 15;

try {
    // Crear/consultar el registro de intentos y bloquearlo para evitar
    // que dos solicitudes simultáneas superen el límite.
    $conexion->beginTransaction();

    $crearIntentos = $conexion->prepare(
        "INSERT IGNORE INTO login_intentos (correo, intentos, bloqueado_hasta)
         VALUES (:correo, 0, NULL)"
    );
    $crearIntentos->execute(["correo" => $correo]);

    $consultaIntentos = $conexion->prepare(
        "SELECT intentos, bloqueado_hasta
         FROM login_intentos
         WHERE correo = :correo
         FOR UPDATE"
    );
    $consultaIntentos->execute(["correo" => $correo]);
    $registroIntentos = $consultaIntentos->fetch(PDO::FETCH_ASSOC);

    if (!empty($registroIntentos["bloqueado_hasta"])) {
        $finBloqueo = strtotime($registroIntentos["bloqueado_hasta"]);

        if ($finBloqueo !== false && $finBloqueo > time()) {
            $segundosRestantes = $finBloqueo - time();
            $minutosRestantes = max(1, (int) ceil($segundosRestantes / 60));
            $conexion->commit();

            responder(
                false,
                "Has superado los 3 intentos. Intenta nuevamente en aproximadamente " .
                $minutosRestantes . " minuto(s).",
                429,
                ["intentos_restantes" => 0, "minutos_restantes" => $minutosRestantes]
            );
        }

        // Si el tiempo de bloqueo ya terminó, reiniciar el contador.
        $reiniciar = $conexion->prepare(
            "UPDATE login_intentos
             SET intentos = 0, bloqueado_hasta = NULL
             WHERE correo = :correo"
        );
        $reiniciar->execute(["correo" => $correo]);
        $registroIntentos["intentos"] = 0;
    }

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
            LEFT JOIN rol r ON r.id_rol = u.id_rol
            LEFT JOIN tienda t ON t.id_tienda = u.id_tienda
            WHERE u.correo = :correo
            LIMIT 1";

    $consulta = $conexion->prepare($sql);
    $consulta->execute(["correo" => $correo]);
    $usuario = $consulta->fetch(PDO::FETCH_ASSOC);

    if (
        !$usuario ||
        !password_verify($contrasena, $usuario["contraseña"])
    ) {
        $intentos = (int) $registroIntentos["intentos"] + 1;

        if ($intentos >= $maxIntentos) {
            $bloquear = $conexion->prepare(
                "UPDATE login_intentos
                 SET intentos = :intentos,
                     bloqueado_hasta = DATE_ADD(NOW(), INTERVAL :minutos MINUTE)
                 WHERE correo = :correo"
            );
            $bloquear->bindValue(":intentos", $maxIntentos, PDO::PARAM_INT);
            $bloquear->bindValue(":minutos", $minutosBloqueo, PDO::PARAM_INT);
            $bloquear->bindValue(":correo", $correo, PDO::PARAM_STR);
            $bloquear->execute();

            $conexion->commit();
            responder(
                false,
                "Has superado los 3 intentos. Tu acceso quedó bloqueado durante 15 minutos.",
                429,
                ["intentos_restantes" => 0, "minutos_bloqueo" => $minutosBloqueo]
            );
        }

        $actualizar = $conexion->prepare(
            "UPDATE login_intentos
             SET intentos = :intentos
             WHERE correo = :correo"
        );
        $actualizar->execute([
            "intentos" => $intentos,
            "correo" => $correo
        ]);

        $conexion->commit();

        $restantes = $maxIntentos - $intentos;
        responder(
            false,
            "Correo o contraseña incorrectos. Te quedan " . $restantes . " intento(s).",
            401,
            ["intentos_restantes" => $restantes]
        );
    }

    // La contraseña es correcta: limpiar los intentos fallidos.
    $limpiarIntentos = $conexion->prepare(
        "DELETE FROM login_intentos WHERE correo = :correo"
    );
    $limpiarIntentos->execute(["correo" => $correo]);
    $conexion->commit();

    if ((int)$usuario["estado"] !== 1) {
        responder(false, "Tu cuenta está inactiva.", 403);
    }

    if (empty($usuario["nombre_rol"])) {
        responder(false, "Tu cuenta no tiene un rol asignado.", 403);
    }

    session_regenerate_id(true);

    $_SESSION["id_usuario"] = (int)$usuario["id_usuario"];
    $_SESSION["nombre_usuario"] = $usuario["nombre"] . " " . $usuario["apellido"];
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
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    error_log("Error al iniciar sesión: " . $e->getMessage());

    responder(
        false,
        "No fue posible iniciar sesión. Revisa la conexión con la base de datos.",
        500
    );
}
?>