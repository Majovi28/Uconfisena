
<?php
// Archivo temporal. Ejecutar únicamente desde el computador local.
$ip = $_SERVER["REMOTE_ADDR"] ?? "";

if (!in_array($ip, ["127.0.0.1", "::1"], true)) {
    http_response_code(403);
    exit("Acceso denegado.");
}

require_once __DIR__ . "/php/conexion.php";

$cuentas = [
    [
        "correo" => "gerenteconfisena@gmail.com",
        "clave" => "Gerente2026*",
        "nombre" => "Gerente",
        "apellido" => "General",
        "telefono" => "3000000001",
        "rol" => "Gerente General"
    ],
    [
        "correo" => "gestorconfisena@gmail.com",
        "clave" => "Gestor2026*",
        "nombre" => "Gestor",
        "apellido" => "CONFISENA",
        "telefono" => "3000000002",
        "rol" => "Gestor"
    ]
];

try {
    $conexion->beginTransaction();

    foreach ($cuentas as $cuenta) {
        $consultaRol = $conexion->prepare(
            "SELECT id_rol FROM rol
             WHERE nombre_rol = :rol LIMIT 1"
        );
        $consultaRol->execute(["rol" => $cuenta["rol"]]);
        $idRol = $consultaRol->fetchColumn();

        if (!$idRol) {
            throw new RuntimeException(
                "Falta el rol: " . $cuenta["rol"]
            );
        }

        $buscar = $conexion->prepare(
            "SELECT id_usuario FROM usuario
             WHERE correo = :correo LIMIT 1"
        );
        $buscar->execute(["correo" => $cuenta["correo"]]);
        $usuarioExistente = $buscar->fetchColumn();

        $hash = password_hash(
            $cuenta["clave"],
            PASSWORD_DEFAULT
        );

        if ($usuarioExistente) {
            // Actualizar la cuenta que ya existe.
            $actualizar = $conexion->prepare(
                "UPDATE usuario
                 SET `contraseña` = :clave,
                     id_rol = :rol,
                     estado = 1
                 WHERE id_usuario = :id"
            );

            $actualizar->execute([
                "clave" => $hash,
                "rol" => $idRol,
                "id" => $usuarioExistente
            ]);

            echo "<p>Cuenta actualizada: " .
                htmlspecialchars($cuenta["correo"]) . "</p>";

        } else {
            // Buscar un documento numérico disponible.
            $documento = null;

            for ($numero = 9000000000; $numero <= 9000009999; $numero++) {
                $verificarDocumento = $conexion->prepare(
                    "SELECT id_usuario FROM usuario
                     WHERE documento = :documento LIMIT 1"
                );

                $verificarDocumento->execute([
                    "documento" => (string)$numero
                ]);

                if (!$verificarDocumento->fetchColumn()) {
                    $documento = (string)$numero;
                    break;
                }
            }

            if ($documento === null) {
                throw new RuntimeException(
                    "No se encontró un documento disponible para la cuenta."
                );
            }

            $insertar = $conexion->prepare(
                "INSERT INTO usuario
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
                    :rol,
                    :nombre,
                    :apellido,
                    :documento,
                    :correo,
                    :clave,
                    :telefono,
                    1
                )"
            );

            $insertar->execute([
                "rol" => $idRol,
                "nombre" => $cuenta["nombre"],
                "apellido" => $cuenta["apellido"],
                "documento" => $documento,
                "correo" => $cuenta["correo"],
                "clave" => $hash,
                "telefono" => $cuenta["telefono"]
            ]);

            echo "<p>Cuenta creada: " .
                htmlspecialchars($cuenta["correo"]) . "</p>";
        }
    }

    $conexion->commit();

    echo "<h3>Configuración terminada correctamente.</h3>";
    echo "<p>Gerente: gerenteconfisena@gmail.com</p>";
    echo "<p>Gestor: gestorconfisena@gmail.com</p>";
    echo "<p>Ya puedes probar el inicio de sesión.</p>";

} catch (Throwable $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    error_log("Error configurando cuentas: " . $e->getMessage());

    http_response_code(500);
    echo "<p>No se pudo completar el proceso. Revisa el registro de errores de PHP.</p>";
}
?>
