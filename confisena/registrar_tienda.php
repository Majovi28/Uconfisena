
<?php
session_start();

if (empty($_SESSION["id_usuario"])) {
    header("Location: login.html");
    exit;
}

$rol = $_SESSION["rol_usuario"] ?? "";

if (!in_array($rol, ["Gestor", "Gerente General"], true)) {
    http_response_code(403);
    exit("No tienes permiso para registrar tiendas.");
}

require_once __DIR__ . "/php/conexion.php";

$mensaje = "";
$exito = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nit = trim($_POST["nit"] ?? "");
    $nombre = trim($_POST["nombre_tienda"] ?? "");
    $direccion = trim($_POST["direccion"] ?? "");
    $barrio = trim($_POST["barrio"] ?? "");
    $localidad = trim($_POST["localidad"] ?? "");
    $gerente = trim($_POST["nombre_gerente"] ?? "");
    $vendedor = trim($_POST["nombre_vendedor"] ?? "");
    $telefono = preg_replace('/\D/', '', $_POST["telefono"] ?? "");
    $correo = strtolower(trim($_POST["correo"] ?? ""));

    if (
        $nit === "" || $nombre === "" || $direccion === "" ||
        $barrio === "" || $localidad === "" || $gerente === "" ||
        $telefono === "" || $correo === ""
    ) {
        $mensaje = "Completa todos los campos obligatorios.";
    } elseif (!preg_match('/^[0-9]{10}$/', $telefono)) {
        $mensaje = "El teléfono debe tener exactamente 10 dígitos.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "Ingresa un correo electrónico válido.";
    } else {
        try {
            $existe = $conexion->prepare(
                "SELECT id_tienda FROM tienda WHERE nit = :nit LIMIT 1"
            );
            $existe->execute(["nit" => $nit]);

            if ($existe->fetch()) {
                $mensaje = "Ya existe una tienda con ese NIT.";
            } else {
                $sql = "INSERT INTO tienda (
                            nit, nombre_tienda, direccion, barrio,
                            localidad, nombre_gerente, nombre_vendedor,
                            telefono, correo, estado
                        ) VALUES (
                            :nit, :nombre, :direccion, :barrio,
                            :localidad, :gerente, :vendedor,
                            :telefono, :correo, 1
                        )";

                $insertar = $conexion->prepare($sql);
                $insertar->execute([
                    "nit" => $nit,
                    "nombre" => $nombre,
                    "direccion" => $direccion,
                    "barrio" => $barrio,
                    "localidad" => $localidad,
                    "gerente" => $gerente,
                    "vendedor" => $vendedor,
                    "telefono" => $telefono,
                    "correo" => $correo
                ]);

                $mensaje = "Tienda registrada correctamente.";
                $exito = true;
            }
        } catch (PDOException $e) {
            error_log("Error registrando tienda: " . $e->getMessage());
            $mensaje = "No se pudo registrar la tienda. Verifica los datos.";
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
    <title>Registrar tienda - CONFISENA</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f3f5f8; margin:0; color:#263449; }
        header { background:#123563; color:white; padding:20px 6%; }
        main { max-width:760px; margin:30px auto; padding:25px; background:white; border-radius:12px; }
        h1,h2 { color:#123563; }
        header h1 { color:white; margin:0; }
        label { display:block; margin-top:14px; font-weight:bold; }
        input { width:100%; box-sizing:border-box; padding:11px; margin-top:6px; border:1px solid #ccd5e1; border-radius:6px; }
        button,.enlace { display:inline-block; background:#123563; color:white; padding:11px 16px; margin-top:20px; border:0; border-radius:6px; text-decoration:none; cursor:pointer; }
        .mensaje { padding:12px; background:#edf2f8; border-radius:6px; }
        .error { color:#a61b1b; }
    </style>
</head>
<body>
<header><h1>CONFISENA</h1><p>Registrar tienda</p></header>

<main>
    <h2>Datos de la tienda</h2>

    <?php if ($mensaje !== ""): ?>
        <p class="mensaje <?= $exito ? "" : "error" ?>"><?= h($mensaje) ?></p>
    <?php endif; ?>

    <form method="POST" action="registrar_tienda.php">
        <label for="nit">NIT</label>
        <input id="nit" name="nit" maxlength="30" required>

        <label for="nombre_tienda">Nombre de la tienda</label>
        <input id="nombre_tienda" name="nombre_tienda" maxlength="150" required>

        <label for="direccion">Dirección</label>
        <input id="direccion" name="direccion" maxlength="200" required>

        <label for="barrio">Barrio</label>
        <input id="barrio" name="barrio" maxlength="100" required>

        <label for="localidad">Localidad</label>
        <input id="localidad" name="localidad" maxlength="100" required>

        <label for="nombre_gerente">Nombre del gerente</label>
        <input id="nombre_gerente" name="nombre_gerente" maxlength="150" required>

        <label for="nombre_vendedor">Nombre del vendedor (opcional)</label>
        <input id="nombre_vendedor" name="nombre_vendedor" maxlength="150">

        <label for="telefono">Teléfono (10 dígitos)</label>
        <input id="telefono" name="telefono" type="tel" maxlength="10" pattern="[0-9]{10}" inputmode="numeric" required>

        <label for="correo">Correo electrónico</label>
        <input id="correo" name="correo" type="email" maxlength="150" required>

        <button type="submit">Guardar tienda</button>
        <a class="enlace" href="<?= h($volver) ?>">Volver al panel</a>
    </form>
</main>
</body>
</html>
