<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/php/conexion.php";
require_once __DIR__ . "/php/validar_fecha_entrega.php";
date_default_timezone_set("America/Bogota");

function responder($success, $mensaje, $codigo = 200, $datos = []) {
    http_response_code($codigo);
    echo json_encode(array_merge(["success" => $success, "mensaje" => $mensaje], $datos), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") responder(false, "Método no permitido.", 405);
if (empty($_SESSION["id_usuario"])) responder(false, "Debes iniciar sesión nuevamente.", 401);

$idUsuario = (int) $_SESSION["id_usuario"];
$idProducto = filter_var($_POST["id_producto"] ?? null, FILTER_VALIDATE_INT);
$idInventario = filter_var($_POST["id_inventario"] ?? null, FILTER_VALIDATE_INT);
$cantidad = filter_var($_POST["cantidad"] ?? null, FILTER_VALIDATE_INT);
$talla = trim($_POST["talla"] ?? "");
$fechaEntrega = trim($_POST["fecha_entrega"] ?? "");
$metodo = trim($_POST["metodo"] ?? "");
$barrio = trim($_POST["barrio"] ?? "");
$localidad = trim($_POST["localidad"] ?? "");
$direccion = trim($_POST["direccion"] ?? "");
$horarioEntrega = trim($_POST["horario_entrega"] ?? "");
$indicacionesEntrega = trim($_POST["indicaciones_entrega"] ?? "");


if (!$idProducto || $idProducto < 1 || !$idInventario || $idInventario < 1 || !$cantidad || $cantidad < 1 || $cantidad > 99) responder(false, "El producto o la cantidad no son válidos.", 400);
if ($talla === "") responder(false, "Selecciona una talla.", 400);
if (!in_array($metodo, ["tarjeta", "pse", "nequi"], true)) responder(false, "Selecciona un método de pago válido.", 400);
if ($barrio === "" || $localidad === "" || $direccion === "") responder(false, "Completa barrio, localidad y dirección de entrega.", 400);
if (mb_strlen($barrio) > 80 || mb_strlen($localidad) > 80 || mb_strlen($direccion) > 180 || mb_strlen($indicacionesEntrega) > 250) responder(false, "Los datos de entrega superan la longitud permitida.", 400);
if (!confisenaFechaHorarioValido($fechaEntrega, $horarioEntrega, $errorFecha)) responder(false, $errorFecha, 400);

try {
    $conexion->beginTransaction();

    $consultaProducto = $conexion->prepare("SELECT id_producto, nombre_producto, tallas, precio FROM producto WHERE id_producto = :id FOR UPDATE");
    $consultaProducto->execute(["id" => $idProducto]);
    $producto = $consultaProducto->fetch(PDO::FETCH_ASSOC);
    if (!$producto) throw new DomainException("El producto no existe.");

    $tallasDisponibles = preg_split("/[\s,;]+/", trim((string) $producto["tallas"]), -1, PREG_SPLIT_NO_EMPTY);
    if (!in_array($talla, $tallasDisponibles, true)) throw new DomainException("La talla seleccionada no está disponible.");

    $consultaInventario = $conexion->prepare("SELECT i.id_inventario, i.id_tienda, i.cantidad_disponible, i.precio FROM inventario i INNER JOIN tienda t ON t.id_tienda = i.id_tienda WHERE i.id_inventario = :inventario AND i.id_producto = :producto AND t.estado = 1 FOR UPDATE");
    $consultaInventario->execute(["inventario" => $idInventario, "producto" => $idProducto]);
    $filaInventario = $consultaInventario->fetch(PDO::FETCH_ASSOC);
    if (!$filaInventario) throw new DomainException("La tienda seleccionada no corresponde a este producto.");
    if ((int)$filaInventario["cantidad_disponible"] < $cantidad) throw new DomainException("La tienda seleccionada no tiene existencias suficientes.");

    $precioUnitario = (float)($filaInventario["precio"] ?? $producto["precio"]);
    $total = round($precioUnitario * $cantidad, 2);
    $abono = round($total * 0.50, 2);
    $saldo = round($total - $abono, 2);

    $insertar = $conexion->prepare("INSERT INTO apartado (id_usuario, id_producto, id_tienda, fecha_apartado, fecha_entrega, fecha_salida, abono, saldo, estado, cantidad, talla, metodo_pago, barrio, localidad, direccion, horario_entrega, indicaciones_entrega) VALUES (:usuario, :producto, :tienda, NOW(), :entrega, NULL, :abono, :saldo, 'Pendiente', :cantidad, :talla, :metodo, :barrio, :localidad, :direccion, :horario, :indicaciones)");
    $insertar->execute([
        "usuario" => $idUsuario,
        "producto" => $idProducto,
        "tienda" => (int)$filaInventario["id_tienda"],
        "entrega" => $fechaEntrega,
        "abono" => $abono,
        "saldo" => $saldo,
        "cantidad" => $cantidad,
        "talla" => $talla,
        "metodo" => $metodo,
        "barrio" => $barrio,
        "localidad" => $localidad,
        "direccion" => $direccion,
        "horario" => $horarioEntrega,
        "indicaciones" => $indicacionesEntrega
    ]);

    $idApartado = (int) $conexion->lastInsertId();

    $actualizar = $conexion->prepare("UPDATE inventario SET cantidad_disponible = cantidad_disponible - :cantidad_resta, fecha_actualizacion = NOW() WHERE id_inventario = :id AND cantidad_disponible >= :cantidad_minima");
    $actualizar->execute(["cantidad_resta" => $cantidad, "cantidad_minima" => $cantidad, "id" => $idInventario]);
    if ($actualizar->rowCount() !== 1) throw new DomainException("El inventario cambió. Actualiza el catálogo e inténtalo de nuevo.");

    $conexion->commit();
    responder(true, "Apartado registrado. El pago del abono está pendiente de confirmación.", 200, ["id_apartado" => $idApartado, "total" => $total, "abono" => $abono, "saldo" => $saldo, "estado" => "Pendiente"]);
} catch (DomainException $e) {
    if ($conexion->inTransaction()) $conexion->rollBack();
    responder(false, $e->getMessage(), 400);
} catch (Throwable $e) {
    if ($conexion->inTransaction()) $conexion->rollBack();
    error_log("Error en apartado: " . $e->getMessage());
    responder(false, "No fue posible registrar el apartado. Revisa la estructura de la tabla y vuelve a intentarlo.", 500);
}
?>