
<?php
session_start();

if (empty($_SESSION["id_usuario"])) {
    header("Location: login.html");
    exit;
}

$rol = $_SESSION["rol_usuario"] ?? "";

if (!in_array($rol, ["Gestor", "Gerente General"], true)) {
    http_response_code(403);
    exit("Acceso denegado.");
}

require_once __DIR__ . "/php/conexion.php";

$tiendas = $conexion->query(
    "SELECT t.*,
        (SELECT COUNT(*)
         FROM usuario u
         INNER JOIN rol r ON r.id_rol = u.id_rol
         WHERE u.id_tienda = t.id_tienda
           AND r.nombre_rol = 'Vendedor') AS cantidad_vendedores
     FROM tienda t
     ORDER BY t.nombre_tienda"
)->fetchAll(PDO::FETCH_ASSOC);

function h($v) {
    return htmlspecialchars((string)($v ?? ""), ENT_QUOTES, "UTF-8");
}

$volver = $rol === "Gestor" ? "gestor.php" : "gerente.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Consultar tiendas - CONFISENA</title>
<style>
body{font-family:Arial,sans-serif;background:#f3f5f8;margin:0;color:#263449}
header{background:#123563;color:white;padding:20px 6%}
main{width:94%;max-width:1200px;margin:30px auto}
h1,h2{color:#123563}header h1{color:white;margin:0}
.tabla{overflow-x:auto;background:white;border-radius:10px;padding:15px}
table{width:100%;border-collapse:collapse;min-width:900px}
th,td{text-align:left;padding:12px;border-bottom:1px solid #e1e6ed}
th{background:#eaf0f8;color:#123563}
a.boton{display:inline-block;padding:10px 15px;background:#123563;color:white;text-decoration:none;border-radius:6px;margin-bottom:15px}
</style>
</head>
<body>
<header><h1>CONFISENA</h1><p>Tiendas registradas</p></header>
<main>
    <h2>Consultar tiendas</h2>
    <a class="boton" href="<?= h($volver) ?>">Volver al panel</a>

    <div class="tabla">
    <?php if (!$tiendas): ?>
        <p>No hay tiendas registradas.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>NIT</th><th>Tienda</th><th>Dirección</th>
                    <th>Barrio</th><th>Localidad</th><th>Gerente</th>
                    <th>Vendedores</th><th>Teléfono</th><th>Correo</th><th>Estado</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($tiendas as $t): ?>
                <tr>
                    <td><?= h($t["nit"]) ?></td>
                    <td><?= h($t["nombre_tienda"]) ?></td>
                    <td><?= h($t["direccion"]) ?></td>
                    <td><?= h($t["barrio"]) ?></td>
                    <td><?= h($t["localidad"]) ?></td>
                    <td><?= h($t["nombre_gerente"]) ?></td>
                    <td><?= (int)$t["cantidad_vendedores"] ?></td>
                    <td><?= h($t["telefono"]) ?></td>
                    <td><?= h($t["correo"]) ?></td>
                    <td><?= (int)$t["estado"] === 1 ? "Activa" : "Inactiva" ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    </div>
</main>
</body>
</html>
