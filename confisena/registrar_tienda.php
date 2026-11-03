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
    <style>
        *{box-sizing:border-box}
        body{font-family:Arial,Helvetica,sans-serif;background:#f4f3ef;margin:0;color:#303b46;line-height:1.5}
        header{background:#183b5b;color:#fff;padding:18px 7%}
        header h1{margin:0;color:#fff;font-size:27px}header p{margin:3px 0 0;color:#d9e1e8;font-size:13px}
        main{width:90%;max-width:760px;margin:30px auto;padding:28px;background:#fff;border:1px solid #d7dce1;border-radius:5px}
        h2{color:#183b5b;margin-top:0}label{display:block;margin-top:15px;font-size:13px;font-weight:600}
        input{width:100%;padding:11px 12px;margin-top:6px;border:1px solid #c9d0d6;border-radius:4px;font:14px Arial}
        input:focus{outline:2px solid #426b8a;outline-offset:1px}
        button,.enlace{display:inline-block;background:#183b5b;color:#fff;padding:11px 16px;margin-top:20px;border:1px solid #183b5b;border-radius:4px;text-decoration:none;cursor:pointer}
        .enlace{background:#fff;color:#303b46;border-color:#c9d0d6;margin-left:7px}
        .mensaje{padding:12px;background:#f1f4f6;border-left:3px solid #64839c;border-radius:3px}
        .error{color:#9b2929;border-left-color:#b85c55}
        @media(max-width:600px){main{padding:19px}.enlace{margin-left:0}}
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