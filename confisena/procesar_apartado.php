
<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/php/conexion.php";

function responder($success, $mensaje, $codigo = 200, $datos = []) {
    http_response_code($codigo);
    echo json_encode(array_merge([
        "success" => $success,
        "mensaje" => $mensaje
    ], $datos), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Método no permitido.", 405);
}

if (empty($_SESSION["id_usuario"])) {
    responder(false, "Debes iniciar sesión nuevamente.", 401);
}

$idUsuario = (int) $_SESSION["id_usuario"];
$idProducto = filter_var($_POST["id_producto"] ?? null, FILTER_VALIDATE_INT);
$cantidad = filter_var($_POST["cantidad"] ?? null, FILTER_VALIDATE_INT);
$talla = trim($_POST["talla"] ?? "");
$fechaEntrega = trim($_POST["fecha_entrega"] ?? "");
$tienda = trim($_POST["tienda"] ?? "");
$metodo = trim($_POST["metodo"] ?? "");

$tiendasPermitidas = [
    "Tienda CONFISENA Centro",
    "Tienda CONFISENA Chapinero",
    "Tienda CONFISENA Kennedy"
];

if (!$idProducto || $idProducto < 1 ||
    !$cantidad || $cantidad < 1 || $cantidad > 99) {
    responder(false, "El producto o la cantidad no son válidos.", 400);
}

if ($talla === "") {
    responder(false, "Selecciona una talla.", 400);
}

if (!in_array($tienda, $tiendasPermitidas, true)) {
    responder(false, "Selecciona una tienda válida.", 400);
}

if (!in_array($metodo, ["tarjeta", "pse", "nequi"], true)) {
    responder(false, "Selecciona un método de pago válido.", 400);
}

$fecha = DateTime::createFromFormat("!Y-m-d", $fechaEntrega);

if (!$fecha || $fecha->format("Y-m-d") !== $fechaEntrega ||
    $fechaEntrega < date("Y-m-d")) {
    responder(false, "Selecciona una fecha de entrega válida.", 400);
}

try {
    $conexion->beginTransaction();

    $consultaProducto = $conexion->prepare(
        "SELECT id_producto, nombre_producto, tallas, precio
         FROM producto
         WHERE id_producto = :id
         FOR UPDATE"
    );
    $consultaProducto->execute(["id" => $idProducto]);
    $producto = $consultaProducto->fetch(PDO::FETCH_ASSOC);

    if (!$producto) {
        throw new DomainException("El producto no existe.");
    }

    $tallasDisponibles = preg_split(
        "/[\s,;]+/",
        trim((string) $producto["tallas"]),
        -1,
        PREG_SPLIT_NO_EMPTY
    );

    if (!in_array($talla, $tallasDisponibles, true)) {
        throw new DomainException("La talla seleccionada no está disponible.");
    }

    $consultaInventario = $conexion->prepare(
        "SELECT id_inventario, cantidad_disponible
         FROM inventario
         WHERE id_producto = :id
           AND cantidad_disponible > 0
         ORDER BY id_inventario
         FOR UPDATE"
    );
    $consultaInventario->execute(["id" => $idProducto]);
    $inventarios = $consultaInventario->fetchAll(PDO::FETCH_ASSOC);

    $disponible = 0;
    foreach ($inventarios as $fila) {
        $disponible += (int) $fila["cantidad_disponible"];
    }

    if ($disponible < $cantidad) {
        throw new DomainException(
            "No hay existencias suficientes. Disponibles: " . $disponible
        );
    }

    $total = round((float) $producto["precio"] * $cantidad, 2);
    $abono = round($total * 0.50, 2);
    $saldo = round($total - $abono, 2);

    $insertar = $conexion->prepare(
        "INSERT INTO apartado
        (
            id_usuario,
            id_producto,
            fecha_apartado,
            fecha_entrega,
            fecha_salida,
            abono,
            saldo,
            estado,
            cantidad,
            talla,
            tienda_recogida,
            metodo_pago
        )
        VALUES
        (
            :usuario,
            :producto,
            NOW(),
            :entrega,
            NULL,
            :abono,
            :saldo,
            'Pendiente',
            :cantidad,
            :talla,
            :tienda,
            :metodo
        )"
    );

    $insertar->execute([
        "usuario" => $idUsuario,
        "producto" => $idProducto,
        "entrega" => $fechaEntrega,
        "abono" => $abono,
        "saldo" => $saldo,
        "cantidad" => $cantidad,
        "talla" => $talla,
        "tienda" => $tienda,
        "metodo" => $metodo
    ]);

    $idApartado = (int) $conexion->lastInsertId();

    // Descontar las unidades reservadas del inventario.
    $actualizar = $conexion->prepare(
        "UPDATE inventario
         SET cantidad_disponible = :cantidad,
             fecha_actualizacion = NOW()
         WHERE id_inventario = :id"
    );

    $restante = $cantidad;

    foreach ($inventarios as $fila) {
        if ($restante <= 0) {
            break;
        }

        $existenciasFila = (int) $fila["cantidad_disponible"];
        $descontar = min($restante, $existenciasFila);

        $actualizar->execute([
            "cantidad" => $existenciasFila - $descontar,
            "id" => (int) $fila["id_inventario"]
        ]);

        $restante -= $descontar;
    }

    if ($restante > 0) {
        throw new RuntimeException("No se pudo reservar todo el inventario.");
    }

    $conexion->commit();

    responder(true, "Apartado registrado. El pago del abono está pendiente de confirmación.", 200, [
        "id_apartado" => $idApartado,
        "total" => $total,
        "abono" => $abono,
        "saldo" => $saldo,
        "estado" => "Pendiente"
    ]);

} catch (DomainException $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    responder(false, $e->getMessage(), 400);

} catch (Throwable $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    error_log("Error en apartado: " . $e->getMessage());

    responder(
        false,
        "No fue posible registrar el apartado. Revisa la estructura de la tabla y vuelve a intentarlo.",
        500
    );
}
?>
