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

    if ($nit === "" || $nombre === "" || $direccion === "" || $barrio === "" ||
        $localidad === "" || $gerente === "" || $vendedor === "" ||
        $telefono === "" || $correo === "") {
        $mensaje = "Completa todos los campos obligatorios, incluido el vendedor responsable.";
    } elseif (!preg_match('/^[0-9]{9,10}(-[0-9])?$/', $nit)) {
        $mensaje = "Ingresa un NIT válido: 9 o 10 dígitos y dígito de verificación opcional.";
    } elseif (mb_strlen($nombre) < 3 || mb_strlen($direccion) < 6 ||
        mb_strlen($barrio) < 2 || mb_strlen($localidad) < 2 ||
        mb_strlen($gerente) < 5 || mb_strlen($vendedor) < 5) {
        $mensaje = "Revisa los nombres y la información de ubicación; algunos datos están incompletos.";
    } elseif (!preg_match('/^3[0-9]{9}$/', $telefono)) {
        $mensaje = "El celular debe tener exactamente 10 dígitos y comenzar por 3.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "Ingresa un correo electrónico válido.";
    } else {
        try {
            $existe = $conexion->prepare("SELECT id_tienda FROM tienda WHERE nit = :nit LIMIT 1");
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
    <link rel="stylesheet" href="registro-tienda.css">
    <link rel="stylesheet" href="gestor-compartido.css">
    <link rel="stylesheet" href="registro-db.css">
    
</head>
<body>
<header class="encabezado"><h1>CONFISENA</h1><p>Registrar tienda</p></header>
<main>
    <h2>Datos de la tienda</h2>
    <?php if ($mensaje !== ""): ?>
        <p class="mensaje <?= $exito ? "" : "error" ?>"><?= h($mensaje) ?></p>
    <?php endif; ?>
    <form method="POST" action="registrar_tienda.php">
        <label for="nit">NIT</label>
        <input id="nit" name="nit" maxlength="12" pattern="[0-9]{9,10}(-[0-9])?" title="9 o 10 dígitos y dígito de verificación opcional" required>
        <label for="nombre_tienda">Nombre de la tienda</label>
        <input id="nombre_tienda" name="nombre_tienda" maxlength="150" minlength="3" required>
        <label for="direccion">Dirección completa</label>
        <input id="direccion" name="direccion" maxlength="200" minlength="6" required>
        <label for="barrio">Barrio</label>
        <input id="barrio" name="barrio" maxlength="100" minlength="2" required>
        <label for="localidad">Localidad o municipio</label>
        <input id="localidad" name="localidad" maxlength="100" minlength="2" required>
        <label for="nombre_gerente">Nombre completo del gerente</label>
        <input id="nombre_gerente" name="nombre_gerente" maxlength="150" minlength="5" required>
        <label for="nombre_vendedor">Nombre completo del vendedor responsable</label>
        <input id="nombre_vendedor" name="nombre_vendedor" maxlength="150" minlength="5" required>
        <label for="telefono">Teléfono celular (10 dígitos)</label>
        <input id="telefono" name="telefono" type="tel" maxlength="10" minlength="10" pattern="3[0-9]{9}" inputmode="numeric" required>
        <label for="correo">Correo electrónico</label>
        <input id="correo" name="correo" type="email" maxlength="150" required>
        <button type="submit">Guardar tienda</button>
        <a class="enlace" href="<?= h($volver) ?>">Volver al panel</a>
    </form>
</main>
</body>
</html>