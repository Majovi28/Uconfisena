
<?php
session_start();

if (empty($_SESSION["id_usuario"])) {
    header("Location: login.html");
    exit;
}

$rol = $_SESSION["rol_usuario"] ?? "";

if (!in_array($rol, ["Gestor", "Gerente General"], true)) {
    http_response_code(403);
    exit("Acceso denegado. No tienes permiso para consultar vendedores.");
}

require_once __DIR__ . "/php/conexion.php";

$vendedores = [];
$error = "";
$aviso = "";

function escapar($valor) {
    return htmlspecialchars(
        (string)($valor ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

$volver = $rol === "Gestor"
    ? "gestor.php"
    : "gerente.php";

try {
    // Comprobar si existe la columna que relaciona usuarios y tiendas.
    $comprobarColumna = $conexion->query(
        "SHOW COLUMNS FROM usuario LIKE 'id_tienda'"
    );

    $existeIdTienda = (bool)$comprobarColumna->fetch(PDO::FETCH_ASSOC);

    if ($existeIdTienda) {
        // Consultar vendedores y su tienda asignada.
        $sql = "SELECT
                    u.id_usuario,
                    u.nombre,
                    u.apellido,
                    u.documento,
                    u.correo,
                    u.telefono,
                    u.estado,
                    t.nombre_tienda
                FROM usuario u
                INNER JOIN rol r
                    ON r.id_rol = u.id_rol
                LEFT JOIN tienda t
                    ON t.id_tienda = u.id_tienda
                WHERE r.nombre_rol = 'Vendedor'
                ORDER BY u.nombre ASC, u.apellido ASC";

        $consulta = $conexion->query($sql);

    } else {
        // Permitir la consulta incluso si falta la relación con tienda.
        $aviso = "La columna id_tienda no existe en usuario. " .
                 "Se mostrarán los vendedores sin información de tienda.";

        $sql = "SELECT
                    u.id_usuario,
                    u.nombre,
                    u.apellido,
                    u.documento,
                    u.correo,
                    u.telefono,
                    u.estado,
                    NULL AS nombre_tienda
                FROM usuario u
                INNER JOIN rol r
                    ON r.id_rol = u.id_rol
                WHERE r.nombre_rol = 'Vendedor'
                ORDER BY u.nombre ASC, u.apellido ASC";

        $consulta = $conexion->query($sql);
    }

    $vendedores = $consulta->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    error_log(
        "Error consultando vendedores en CONFISENA: " .
        $e->getMessage()
    );

    $error = "No fue posible consultar los vendedores. " .
             "Verifica la estructura de las tablas usuario, rol y tienda.";
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Consultar vendedores - CONFISENA</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #334155;
        }

        header {
            background: #172554;
            color: white;
            padding: 22px 5%;
        }

        header h1 {
            margin: 0;
            color: white;
        }

        header p {
            margin-bottom: 0;
        }

        main {
            width: 94%;
            max-width: 1200px;
            margin: 30px auto;
        }

        h2 {
            color: #172554;
        }

        .descripcion {
            color: #64748b;
            line-height: 1.5;
        }

        .acciones {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin: 20px 0;
        }

        .boton {
            display: inline-block;
            padding: 11px 16px;
            background: #2563eb;
            color: white;
            border-radius: 7px;
            text-decoration: none;
        }

        .boton-secundario {
            background: #172554;
        }

        .contenedor-tabla {
            background: white;
            padding: 16px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 850px;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 13px 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: #dbeafe;
            color: #172554;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        .estado {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 13px;
            background: #e2e8f0;
        }

        .activo {
            color: #166534;
            background: #dcfce7;
        }

        .inactivo {
            color: #991b1b;
            background: #fee2e2;
        }

        .mensaje {
            margin: 18px 0;
            padding: 15px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            line-height: 1.5;
        }

        .mensaje-error {
            color: #991b1b;
        }

        .mensaje-aviso {
            color: #854d0e;
        }

        .vacio {
            padding: 20px;
            color: #64748b;
            text-align: center;
        }

        footer {
            text-align: center;
            padding: 25px;
            color: #64748b;
        }
    </style>
</head>

<body>

<header>
    <h1>CONFISENA</h1>
    <p>Consulta de vendedores registrados</p>
</header>

<main>

    <h2>Vendedores registrados</h2>

    <p class="descripcion">
        Consulta los datos de los vendedores, su estado y la tienda
        que tienen asignada.
    </p>

    <div class="acciones">
        <a class="boton boton-secundario"
           href="<?= escapar($volver) ?>">
            Volver al panel
        </a>

        <a class="boton"
           href="registrar_vendedor.php">
            Registrar vendedor
        </a>
    </div>

    <?php if ($aviso !== ""): ?>
        <div class="mensaje mensaje-aviso">
            <?= escapar($aviso) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>

        <div class="mensaje mensaje-error">
            <?= escapar($error) ?>
        </div>

    <?php elseif (empty($vendedores)): ?>

        <div class="contenedor-tabla">
            <p class="vacio">
                Todavía no hay vendedores registrados en la base de datos.
            </p>
        </div>

    <?php else: ?>

        <div class="contenedor-tabla">

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre completo</th>
                        <th>Documento</th>
                        <th>Correo</th>
                        <th>Teléfono</th>
                        <th>Tienda asignada</th>
                        <th>Estado</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($vendedores as $vendedor): ?>

                        <tr>
                            <td>
                                <?= (int)$vendedor["id_usuario"] ?>
                            </td>

                            <td>
                                <?= escapar(
                                    $vendedor["nombre"] . " " .
                                    $vendedor["apellido"]
                                ) ?>
                            </td>

                            <td>
                                <?= escapar($vendedor["documento"]) ?>
                            </td>

                            <td>
                                <?= escapar($vendedor["correo"]) ?>
                            </td>

                            <td>
                                <?= escapar($vendedor["telefono"]) ?>
                            </td>

                            <td>
                                <?= escapar(
                                    $vendedor["nombre_tienda"]
                                    ?? "Sin tienda asignada"
                                ) ?>
                            </td>

                            <td>
                                <?php if ((int)$vendedor["estado"] === 1): ?>
                                    <span class="estado activo">Activo</span>
                                <?php else: ?>
                                    <span class="estado inactivo">Inactivo</span>
                                <?php endif; ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>
                </tbody>
            </table>

        </div>

    <?php endif; ?>

</main>

<footer>
    © 2026 CONFISENA - Transformamos la forma de comprar
</footer>

</body>
</html>
