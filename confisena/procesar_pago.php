
<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/php/conexion.php";

function responder($exito, $mensaje, $codigo = 200, $datos = []) {
    http_response_code($codigo);

    echo json_encode(array_merge([
        "success" => $exito,
        "mensaje" => $mensaje
    ], $datos), JSON_UNESCAPED_UNICODE);

    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Método no permitido.", 405);
}

// El pedido debe pertenecer a un usuario que inició sesión.
if (empty($_SESSION["id_usuario"])) {
    responder(false, "Inicia sesión para realizar una compra.", 401);
}

$idUsuario = (int) $_SESSION["id_usuario"];
$idProducto = filter_var(
    $_POST["id_producto"] ?? null,
    FILTER_VALIDATE_INT
);
$cantidad = filter_var(
    $_POST["cantidad"] ?? null,
    FILTER_VALIDATE_INT
);

$talla = trim($_POST["talla"] ?? "");
$nombre = trim($_POST["nombre"] ?? "");
$correo = strtolower(trim($_POST["correo"] ?? ""));
$telefono = preg_replace('/\D/', '', $_POST["telefono"] ?? "");
$metodo = trim($_POST["metodo"] ?? "");

if (!$idProducto || $idProducto < 1) {
    responder(false, "El producto seleccionado no es válido.", 400);
}

if (!$cantidad || $cantidad < 1 || $cantidad > 99) {
    responder(false, "La cantidad debe estar entre 1 y 99.", 400);
}

if ($talla === "" || $nombre === "") {
    responder(false, "Completa el nombre y la talla del producto.", 400);
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    responder(false, "El correo electrónico no es válido.", 400);
}

if (!preg_match('/^[0-9]{10}$/', $telefono)) {
    responder(false, "El teléfono debe tener exactamente 10 dígitos.", 400);
}

if (!in_array($metodo, ["tarjeta", "pse", "nequi"], true)) {
    responder(false, "Selecciona un método de pago válido.", 400);
}

try {
    $conexion->beginTransaction();

    // Verificar que la cuenta siga activa.
    $consultaUsuario = $conexion->prepare(
        "SELECT id_usuario, estado
         FROM usuario
         WHERE id_usuario = :id
         LIMIT 1"
    );

    $consultaUsuario->execute(["id" => $idUsuario]);
    $usuario = $consultaUsuario->fetch(PDO::FETCH_ASSOC);

    if (!$usuario || (int)$usuario["estado"] !== 1) {
        throw new DomainException(
            "Tu cuenta no está activa. Inicia sesión nuevamente."
        );
    }

    // Obtener el nombre, precio y tallas directamente de MySQL.
    $consultaProducto = $conexion->prepare(
        "SELECT id_producto, nombre_producto, tallas, precio
         FROM producto
         WHERE id_producto = :id
         LIMIT 1
         FOR UPDATE"
    );

    $consultaProducto->execute(["id" => $idProducto]);
    $producto = $consultaProducto->fetch(PDO::FETCH_ASSOC);

    if (!$producto) {
        throw new DomainException(
            "El producto ya no existe en el catálogo."
        );
    }

    // Comprobar que la talla exista para el producto.
    $tallasPermitidas = preg_split(
        "/[\s,;]+/",
        trim((string)$producto["tallas"]),
        -1,
        PREG_SPLIT_NO_EMPTY
    );

    if (!in_array($talla, $tallasPermitidas, true)) {
        throw new DomainException(
            "La talla seleccionada no está disponible para este producto."
        );
    }

    // Bloquear filas de inventario para evitar vender existencias
    // que ya fueron reservadas por otra operación.
    $consultaInventario = $conexion->prepare(
        "SELECT id_inventario, cantidad_disponible
         FROM inventario
         WHERE id_producto = :id
           AND cantidad_disponible > 0
         ORDER BY id_inventario
         FOR UPDATE"
    );

    $consultaInventario->execute(["id" => $idProducto]);
    $filasInventario = $consultaInventario->fetchAll(PDO::FETCH_ASSOC);

    $disponible = 0;

    foreach ($filasInventario as $fila) {
        $disponible += (int)$fila["cantidad_disponible"];
    }

    if ($disponible < $cantidad) {
        throw new DomainException(
            "No hay existencias suficientes. Disponibles: " .
            $disponible . "."
        );
    }

    // El precio se calcula desde la base de datos, nunca desde el navegador.
    $precioUnitario = (float)$producto["precio"];
    $total = round($precioUnitario * $cantidad, 2);
    $fechaEntrega = date("Y-m-d", strtotime("+7 days"));

    // Registrar el pedido.
    $sqlPedido = "INSERT INTO pedido
                    (
                        id_usuario,
                        fecha_pedido,
                        fecha_entrega,
                        estado_pedido,
                        direccion,
                        remitente,
                        total
                    )
                  VALUES
                    (
                        :usuario,
                        NOW(),
                        :entrega,
                        'Pendiente',
                        'Por confirmar',
                        :remitente,
                        :total
                    )";

    $insertarPedido = $conexion->prepare($sqlPedido);

    $insertarPedido->execute([
        "usuario" => $idUsuario,
        "entrega" => $fechaEntrega,
        "remitente" => $nombre,
        "total" => $total
    ]);

    $idPedido = (int)$conexion->lastInsertId();

    // Registrar el detalle y la talla elegida.
    $nombreConTalla = $producto["nombre_producto"] .
        " (Talla " . $talla . ")";

    $sqlDetalle = "INSERT INTO detalle_pedido
                    (
                        id_pedido,
                        id_producto,
                        nombre_producto,
                        remitente,
                        destinatario,
                        cantidad,
                        total
                    )
                   VALUES
                    (
                        :pedido,
                        :producto,
                        :nombre_producto,
                        :remitente,
                        :destinatario,
                        :cantidad,
                        :total
                    )";

    $insertarDetalle = $conexion->prepare($sqlDetalle);

    $insertarDetalle->execute([
        "pedido" => $idPedido,
        "producto" => $idProducto,
        "nombre_producto" => $nombreConTalla,
        "remitente" => "CONFISENA",
        "destinatario" => $nombre,
        "cantidad" => $cantidad,
        "total" => $total
    ]);

    // Registrar la solicitud de pago como pendiente.
    // No se guardan números de tarjeta ni códigos CVV.
    $sqlPago = "INSERT INTO pago
                    (
                        id_pedido,
                        metodo_pago,
                        monto,
                        fecha_pago,
                        estado
                    )
                VALUES
                    (
                        :pedido,
                        :metodo,
                        :monto,
                        NOW(),
                        'Pendiente'
                    )";

    $insertarPago = $conexion->prepare($sqlPago);

    $insertarPago->execute([
        "pedido" => $idPedido,
        "metodo" => $metodo,
        "monto" => $total
    ]);

    // Reservar las existencias para evitar que se sobrevendan.
    $pendiente = $cantidad;

    $actualizarInventario = $conexion->prepare(
        "UPDATE inventario
         SET cantidad_disponible = :cantidad,
             fecha_actualizacion = NOW()
         WHERE id_inventario = :id"
    );

    foreach ($filasInventario as $fila) {
        if ($pendiente <= 0) {
            break;
        }

        $existenciaFila = (int)$fila["cantidad_disponible"];
        $descontar = min($pendiente, $existenciaFila);
        $nuevoSaldo = $existenciaFila - $descontar;

        $actualizarInventario->execute([
            "cantidad" => $nuevoSaldo,
            "id" => (int)$fila["id_inventario"]
        ]);

        $pendiente -= $descontar;
    }

    if ($pendiente > 0) {
        throw new RuntimeException(
            "No fue posible reservar todas las unidades."
        );
    }

    $conexion->commit();

    responder(
        true,
        "El pedido fue registrado. El pago está pendiente de confirmación.",
        200,
        [
            "id_pedido" => $idPedido,
            "total" => $total,
            "metodo" => $metodo,
            "estado" => "Pendiente"
        ]
    );

} catch (DomainException $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    responder(false, $e->getMessage(), 400);

} catch (Throwable $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    error_log("Error al procesar el pedido: " . $e->getMessage());

    responder(
        false,
        "No fue posible registrar el pedido. Revisa los datos e inténtalo nuevamente.",
        500
    );
}
?>
