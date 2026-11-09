<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/php/conexion.php";
require_once __DIR__ . "/php/validar_fecha_entrega.php";
date_default_timezone_set("America/Bogota");

function responder($exito, $mensaje, $codigo = 200, $datos = []) {
    http_response_code($codigo);
    echo json_encode(array_merge(["success" => $exito, "mensaje" => $mensaje], $datos), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") responder(false, "Método no permitido.", 405);
if (empty($_SESSION["id_usuario"])) responder(false, "Inicia sesión para realizar una compra.", 401);

$idUsuario = (int) $_SESSION["id_usuario"];
$idProducto = filter_var($_POST["id_producto"] ?? null, FILTER_VALIDATE_INT);
$idInventario = filter_var($_POST["id_inventario"] ?? null, FILTER_VALIDATE_INT);
$cantidad = filter_var($_POST["cantidad"] ?? null, FILTER_VALIDATE_INT);
$talla = trim($_POST["talla"] ?? "");
$nombre = trim($_POST["nombre"] ?? "");
$correo = strtolower(trim($_POST["correo"] ?? ""));
$telefono = preg_replace('/\D/', '', $_POST["telefono"] ?? "");
$metodo = trim($_POST["metodo"] ?? "");
$barrio = trim($_POST["barrio"] ?? "");
$localidad = trim($_POST["localidad"] ?? "");
$direccion = trim($_POST["direccion"] ?? "");
$fechaEntrega = trim($_POST["fecha_entrega"] ?? "");
$horarioEntrega = trim($_POST["horario_entrega"] ?? "");
$indicacionesEntrega = trim($_POST["indicaciones_entrega"] ?? "");

if (!$idProducto || $idProducto < 1 || !$idInventario || $idInventario < 1) responder(false, "Selecciona un producto y una tienda válidos desde el catálogo.", 400);
if (!$cantidad || $cantidad < 1 || $cantidad > 99) responder(false, "La cantidad debe estar entre 1 y 99.", 400);
if ($talla === "" || $nombre === "") responder(false, "Completa el nombre y la talla del producto.", 400);
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) responder(false, "El correo electrónico no es válido.", 400);
if (!preg_match('/^3[0-9]{9}$/', $telefono)) responder(false, "El teléfono debe tener exactamente 10 dígitos y comenzar con 3.", 400);
if (!in_array($metodo, ["tarjeta", "pse", "nequi"], true)) responder(false, "Selecciona un método de pago válido.", 400);
if ($barrio === "" || $localidad === "" || $direccion === "") responder(false, "Completa barrio, localidad y dirección de entrega.", 400);
if (mb_strlen($barrio) > 80 || mb_strlen($localidad) > 80 || mb_strlen($direccion) > 180 || mb_strlen($indicacionesEntrega) > 250) responder(false, "Los datos de entrega superan la longitud permitida.", 400);
if (!confisenaFechaHorarioValido($fechaEntrega, $horarioEntrega, $errorFecha)) responder(false, $errorFecha, 400);

try {
    $conexion->beginTransaction();

    $consultaUsuario = $conexion->prepare("SELECT id_usuario, estado FROM usuario WHERE id_usuario = :id LIMIT 1");
    $consultaUsuario->execute(["id" => $idUsuario]);
    $usuario = $consultaUsuario->fetch(PDO::FETCH_ASSOC);
    if (!$usuario || (int)$usuario["estado"] !== 1) throw new DomainException("Tu cuenta no está activa. Inicia sesión nuevamente.");

    $consultaProducto = $conexion->prepare("SELECT id_producto, nombre_producto, tallas, precio FROM producto WHERE id_producto = :id LIMIT 1 FOR UPDATE");
    $consultaProducto->execute(["id" => $idProducto]);
    $producto = $consultaProducto->fetch(PDO::FETCH_ASSOC);
    if (!$producto) throw new DomainException("El producto ya no existe en el catálogo.");

    $tallasPermitidas = preg_split("/[\s,;]+/", trim((string)$producto["tallas"]), -1, PREG_SPLIT_NO_EMPTY);
    if (!in_array($talla, $tallasPermitidas, true)) throw new DomainException("La talla seleccionada no está disponible para este producto.");

    $consultaInventario = $conexion->prepare("SELECT i.id_inventario, i.id_tienda, i.cantidad_disponible FROM inventario i INNER JOIN tienda t ON t.id_tienda = i.id_tienda WHERE i.id_inventario = :inventario AND i.id_producto = :producto AND t.estado = 1 FOR UPDATE");
    $consultaInventario->execute(["inventario" => $idInventario, "producto" => $idProducto]);
    $filaInventario = $consultaInventario->fetch(PDO::FETCH_ASSOC);
    if (!$filaInventario) throw new DomainException("La tienda seleccionada no corresponde a este producto.");
    if ((int)$filaInventario["cantidad_disponible"] < $cantidad) throw new DomainException("La tienda seleccionada no tiene existencias suficientes.");

    $precioUnitario = (float)$producto["precio"];
    $total = round($precioUnitario * $cantidad, 2);
    $sqlPedido = "INSERT INTO pedido (id_usuario, fecha_pedido, fecha_entrega, estado_pedido, direccion, barrio, localidad, horario_entrega, indicaciones_entrega, remitente, total) VALUES (:usuario, NOW(), :entrega, 'Pendiente', :direccion, :barrio, :localidad, :horario, :indicaciones, :remitente, :total)";
    $insertarPedido = $conexion->prepare($sqlPedido);
    $insertarPedido->execute([
        "usuario" => $idUsuario,
        "entrega" => $fechaEntrega,
        "direccion" => $direccion,
        "barrio" => $barrio,
        "localidad" => $localidad,
        "horario" => $horarioEntrega,
        "indicaciones" => $indicacionesEntrega,
        "remitente" => $nombre,
        "total" => $total
    ]);
    $idPedido = (int)$conexion->lastInsertId();

    $nombreConTalla = $producto["nombre_producto"] . " (Talla " . $talla . ")";
    $sqlDetalle = "INSERT INTO detalle_pedido (id_pedido, id_producto, id_tienda, nombre_producto, remitente, destinatario, cantidad, total) VALUES (:pedido, :producto, :tienda, :nombre_producto, :remitente, :destinatario, :cantidad, :total)";
    $insertarDetalle = $conexion->prepare($sqlDetalle);
    $insertarDetalle->execute([
        "pedido" => $idPedido,
        "producto" => $idProducto,
        "tienda" => (int)$filaInventario["id_tienda"],
        "nombre_producto" => $nombreConTalla,
        "remitente" => "CONFISENA",
        "destinatario" => $nombre,
        "cantidad" => $cantidad,
        "total" => $total
    ]);

    $sqlPago = "INSERT INTO pago (id_pedido, metodo_pago, monto, fecha_pago, estado) VALUES (:pedido, :metodo, :monto, NOW(), 'Pendiente')";
    $insertarPago = $conexion->prepare($sqlPago);
    $insertarPago->execute(["pedido" => $idPedido, "metodo" => $metodo, "monto" => $total]);

    $actualizarInventario = $conexion->prepare("UPDATE inventario SET cantidad_disponible = cantidad_disponible - :cantidad, fecha_actualizacion = NOW() WHERE id_inventario = :id AND cantidad_disponible >= :cantidad");
    $actualizarInventario->execute(["cantidad" => $cantidad, "id" => $idInventario]);
    if ($actualizarInventario->rowCount() !== 1) throw new DomainException("El inventario cambió. Actualiza el catálogo e inténtalo de nuevo.");
    $conexion->commit();

    responder(true, "El pedido fue registrado. El pago está pendiente de confirmación.", 200, ["id_pedido" => $idPedido, "total" => $total, "metodo" => $metodo, "estado" => "Pendiente"]);
} catch (DomainException $e) {
    if ($conexion->inTransaction()) $conexion->rollBack();
    responder(false, $e->getMessage(), 400);
} catch (Throwable $e) {
    if ($conexion->inTransaction()) $conexion->rollBack();
    error_log("Error al procesar el pedido: " . $e->getMessage());
    responder(false, "No fue posible registrar el pedido. Revisa los datos e inténtalo nuevamente.", 500);
}
?>