
<?php
session_start();

if (empty($_SESSION["id_usuario"])) {
    header("Location: login.html");
    exit;
}

$rol = $_SESSION["rol_usuario"] ?? "";

if (!in_array($rol, ["Gestor", "Gerente General"], true)) {
    http_response_code(403);
    exit("No tienes permiso para registrar vendedores.");
}

require_once __DIR__ . "/php/conexion.php";

$mensaje = "";
$exito = false;

$tiendas = $conexion->query(
    "SELECT id_tienda, nombre_tienda
     FROM tienda
     ORDER BY nombre_tienda"
)->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"] ?? "");
    $apellido = trim($_POST["apellido"] ?? "");
    $documento = trim($_POST["documento"] ?? "");
    $correo = strtolower(trim($_POST["correo"] ?? ""));
    $telefono = preg_replace('/\D/', '', $_POST["telefono"] ?? "");
    $contrasena = $_POST["contrasena"] ?? "";
    $idTienda = filter_var($_POST["id_tienda"] ?? null, FILTER_VALIDATE_INT);

    if (
        $nombre === "" || $apellido === "" || $documento === "" ||
        $correo === "" || $telefono === "" || $contrasena === "" ||
        !$idTienda
    ) {
        $mensaje = "Completa todos los campos.";
    } elseif (!ctype_digit($documento)) {
        $mensaje = "El documento debe contener solo números.";
    } elseif (!preg_match('/^[0-9]{10}$/', $telefono)) {
        $mensaje = "El teléfono debe tener exactamente 10 dígitos.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "Ingresa un correo electrónico válido.";
    } elseif (strlen($contrasena) < 6) {
        $mensaje = "La contraseña debe tener al menos 6 caracteres.";
    } else {
        try {
            $verificarTienda = $conexion->prepare(
                "SELECT id_tienda FROM tienda WHERE id_tienda = :id"
            );
            $verificarTienda->execute(["id" => $idTienda]);

            if (!$verificarTienda->fetch()) {
                $mensaje = "Selecciona una tienda válida.";
            } else {
                $duplicado = $conexion->prepare(
                    "SELECT id_usuario FROM usuario
                     WHERE correo = :correo OR documento = :documento
                     LIMIT 1"
                );
                $duplicado->execute([
                    "correo" => $correo,
                    "documento" => $documento
                ]);

                if ($duplicado->fetch()) {
                    $mensaje = "El correo o el documento ya están registrados.";
                } else {
                    $consultaRol = $conexion->prepare(
                        "SELECT id_rol FROM rol
                         WHERE nombre_rol = 'Vendedor'
                         LIMIT 1"
                    );
                    $consultaRol->execute();
                    $idRol = $consultaRol->fetchColumn();

                    if (!$idRol) {
                        throw new RuntimeException("No existe el rol Vendedor en la tabla rol.");
                    }

                    $hash = password_hash($contrasena, PASSWORD_DEFAULT);

                    $insertar = $conexion->prepare(
                        "INSERT INTO usuario (
                            id_rol, id_tienda, nombre, apellido,
                            documento, correo, `contraseña`, telefono, estado
                        ) VALUES (
                            :rol, :tienda, :nombre, :apellido,
                            :documento, :correo, :contrasena, :telefono, 1
                        )"
                    );

                    $insertar->execute([
                        "rol" => $idRol,
                        "tienda" => $idTienda,
                        "nombre" => $nombre,
                        "apellido" => $apellido,
                        "documento" => $documento,
                        "correo" => $correo,
                        "contrasena" => $hash,
                        "telefono" => $telefono
                    ]);

                    $mensaje = "Vendedor registrado correctamente.";
                    $exito = true;
                }
            }
        } catch (Throwable $e) {
            error_log("Error registrando vendedor: " . $e->getMessage());
            $mensaje = "No se pudo registrar el vendedor. Verifica la base de datos.";
        }
    }
}

function h($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

$volver = $rol === "Gestor" ? "gestor.php" : "gerente.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar vendedor - CONFISENA</title>
    <style>
        body { font-family:Arial,sans-serif; background:#f3f5f8; margin:0; color:#263449; }
        header { background:#123563; color:white; padding:20px 6%; }
        header h1 { margin:0; color:white; }
        main { max-width:760px; margin:30px auto; padding:25px; background:white; border-radius:12px; }
        h2 { color:#123563; }
        label { display:block; margin-top:14px; font-weight:bold; }
        input,select { width:100%; box-sizing:border-box; padding:11px; margin-top:6px; border:1px solid #ccd5e1; border-radius:6px; }
        button,.enlace { display:inline-block; background:#123563; color:white; padding:11px 16px; margin-top:20px; border:0; border-radius:6px; text-decoration:none; cursor:pointer; }
        .mensaje { padding:12px; background:#edf2f8; border-radius:6px; }
        .error { color:#a61b1b; }
    </style>
</head>
<body>
<header><h1>CONFISENA</h1><p>Registrar vendedor</p></header>

<main>
    <h2>Datos del vendedor</h2>

    <?php if ($mensaje !== ""): ?>
        <p class="mensaje <?= $exito ? "" : "error" ?>"><?= h($mensaje) ?></p>
    <?php endif; ?>

    <?php if (empty($tiendas)): ?>
        <p>No hay tiendas registradas. Registra primero una tienda.</p>
        <a class="enlace" href="registrar_tienda.php">Registrar tienda</a>
    <?php else: ?>
        <form method="POST" action="registrar_vendedor.php">
            <label for="nombre">Nombre</label>
            <input id="nombre" name="nombre" maxlength="100" required>

            <label for="apellido">Apellido</label>
            <input id="apellido" name="apellido" maxlength="100" required>

            <label for="documento">Documento</label>
            <input id="documento" name="documento" inputmode="numeric" required>

            <label for="correo">Correo electrónico</label>
            <input id="correo" name="correo" type="email" maxlength="150" required>

            <label for="telefono">Teléfono (10 dígitos)</label>
            <input id="telefono" name="telefono" type="tel" maxlength="10" pattern="[0-9]{10}" inputmode="numeric" required>

            <label for="id_tienda">Tienda asignada</label>
            <select id="id_tienda" name="id_tienda" required>
                <option value="">Selecciona una tienda</option>
                <?php foreach ($tiendas as $tienda): ?>
                    <option value="<?= (int)$tienda["id_tienda"] ?>">
                        <?= h($tienda["nombre_tienda"]) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="contrasena">Contraseña inicial</label>
            <input id="contrasena" name="contrasena" type="password" minlength="6" autocomplete="new-password" required>

            <button type="submit">Registrar vendedor</button>
            <a class="enlace" href="<?= h($volver) ?>">Volver al panel</a>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
