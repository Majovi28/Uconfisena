
<?php
session_start();

if (empty($_SESSION["id_usuario"])) {
    header("Location: login.html");
    exit;
}

$nombreUsuario = htmlspecialchars(
    $_SESSION["nombre_usuario"] ?? "",
    ENT_QUOTES,
    "UTF-8"
);

$correoUsuario = htmlspecialchars(
    $_SESSION["correo_usuario"] ?? "",
    ENT_QUOTES,
    "UTF-8"
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apartado - CONFISENA</title>
    <link rel="stylesheet" href="apartado.css">
    <style>
        .modal-apartado-exito{display:none;position:fixed;inset:0;z-index:1000;background:rgba(35,42,34,.58);align-items:center;justify-content:center;padding:20px}
        .modal-apartado-exito.activo{display:flex}
        .modal-apartado-contenido{width:min(100%,480px);max-height:90vh;overflow:auto;background:#fff;border-radius:20px;padding:30px;box-shadow:0 18px 55px rgba(0,0,0,.22);text-align:center;color:#4d5148}
        .modal-apartado-icono{width:58px;height:58px;margin:0 auto 12px;border-radius:50%;display:grid;place-items:center;background:#eaf4e7;color:#547c4d;font-size:32px;font-weight:700}
        .modal-apartado-contenido h2{margin:8px 0;color:#405b3e}
        .modal-apartado-contenido .etiqueta{letter-spacing:2px;font-size:12px;font-weight:700;color:#8b9b7f}
        .modal-apartado-resumen{margin:20px 0;padding:15px;background:#f7f6f1;border-radius:12px;text-align:left}
        .modal-apartado-resumen div{display:flex;justify-content:space-between;gap:14px;padding:8px 0;border-bottom:1px solid #e8e5dc}
        .modal-apartado-resumen div:last-child{border:0}
        .modal-apartado-resumen strong{text-align:right;overflow-wrap:anywhere}
        .modal-apartado-boton{border:0;border-radius:10px;padding:13px 22px;background:#b8d5b0;color:#344b32;font-weight:700;cursor:pointer}
        body.modal-abierto{overflow:hidden}
    </style>
</head>
<body>

<header class="encabezado">
    <div class="logo">
        <h1>confisena</h1>
        <p>Transformamos la forma de comprar</p>
    </div>
    <nav>
        <a href="index.html">Inicio</a>
        <a href="catalogo.php">Catálogo</a>
        <a href="catalogo.php">Categorías</a>
        <a href="cerrar_sesion.php">Cerrar sesión</a>
    </nav>
</header>

<main class="contenedor">
    <div class="ruta">Inicio / Catálogo / Apartado</div>

    <h2>Apartar producto</h2>
    <p class="descripcion">
        Reserva tu producto con un abono del 50 % del valor total.
    </p>

    <div class="contenido">
        <section class="resumen">
            <h3>Producto seleccionado</h3>

            <div class="producto">
                <div class="imagen-producto"></div>
                <div class="informacion-producto">
                    <h4 id="nombreProducto">Producto seleccionado</h4>
                    <p>Talla: <strong id="tallaProducto">-</strong></p>
                    <p>Cantidad: <strong id="cantidadProducto">1</strong></p>
                    <p>Precio unitario: <strong id="precioProducto">$0</strong></p>
                </div>
            </div>

            <div class="linea"></div>

            <div class="fila-precio">
                <span>Total de la compra</span>
                <strong id="totalApartado">$0</strong>
            </div>

            <div class="fila-precio abono">
                <span>Abono requerido (50 %)</span>
                <strong id="abonoApartado">$0</strong>
            </div>

            <div class="fila-precio saldo">
                <span>Saldo pendiente</span>
                <strong id="saldoApartado">$0</strong>
            </div>

            <div class="informacion-apartado">
                <strong>¿Cómo funciona el apartado?</strong>
                <p>
                    Se requiere un abono del 50 % para reservar el producto.
                    El saldo restante se paga al recogerlo. El pago debe
                    confirmarse por un medio de pago habilitado.
                </p>
            </div>
        </section>

        <section class="formulario">
            <h3>Información del apartado</h3>

            <form id="formularioApartado">
                <label for="nombre">Nombre completo</label>
                <input
                    type="text"
                    id="nombre"
                    value="<?= $nombreUsuario ?>"
                    required
                >

                <label for="correo">Correo electrónico</label>
                <input
                    type="email"
                    id="correo"
                    value="<?= $correoUsuario ?>"
                    required
                >

                <label for="telefono">Número de teléfono</label>
                <input
                    type="tel"
                    id="telefono"
                    placeholder="3001234567"
                    maxlength="10"
                    pattern="[0-9]{10}"
                    inputmode="numeric"
                    required
                >

                
                <h4>Datos de entrega</h4>
                <label for="barrio">Barrio</label>
                <input type="text" id="barrio" maxlength="80" placeholder="Ej. Chapinero" required>

                <label for="localidad">Localidad</label>
                <input type="text" id="localidad" maxlength="80" placeholder="Ej. Teusaquillo" required>

                <label for="direccion">Dirección de entrega</label>
                <input type="text" id="direccion" maxlength="180" placeholder="Ej. Calle 45 # 12-30, apartamento 301" required>

                <label for="horarioEntrega">Horario de entrega</label>
                <select id="horarioEntrega" required>
                    <option value="">Selecciona un horario</option>
                    <option value="Manana">Mañana (8:00 a. m. - 12:00 p. m.)</option>
                    <option value="Tarde">Tarde (2:00 p. m. - 6:00 p. m.)</option>
                </select>

                <label for="indicacionesEntrega">Indicaciones adicionales (opcional)</label>
                <textarea id="indicacionesEntrega" maxlength="250" rows="3" placeholder="Punto de referencia, indicaciones para llegar, etc."></textarea>

                <label for="fechaEntrega">Fecha de entrega</label>
                <input type="date" id="fechaEntrega" required>

                <label for="metodoAbono">Método de pago del abono</label>
                <select id="metodoAbono" required>
                    <option value="">Selecciona un método</option>
                    <option value="tarjeta">Tarjeta débito / crédito</option>
                    <option value="pse">PSE</option>
                    <option value="nequi">Nequi</option>
                </select>

                <div id="datosTarjeta" class="datos-pago">
                    <label for="numeroTarjeta">Número de tarjeta</label>
                    <input
                        type="text"
                        id="numeroTarjeta"
                        placeholder="0000 0000 0000 0000"
                        maxlength="19"
                        inputmode="numeric"
                        autocomplete="cc-number"
                    >

                    <div class="fila">
                        <div>
                            <label for="vencimiento">Vencimiento</label>
                            <input
                                type="text"
                                id="vencimiento"
                                placeholder="MM/AA"
                                maxlength="5"
                                inputmode="numeric"
                                autocomplete="cc-exp"
                            >
                        </div>

                        <div>
                            <label for="cvv">CVV</label>
                            <input
                                type="password"
                                id="cvv"
                                placeholder="123"
                                maxlength="3"
                                inputmode="numeric"
                                autocomplete="cc-csc"
                            >
                        </div>
                    </div>
                </div>

                <div class="seguridad">
                    El abono requerido es del <strong>50 %</strong> del valor total.
                </div>

                <label class="check">
                    <input type="checkbox" id="terminos" required>
                    Acepto las condiciones del apartado.
                </label>

                <button type="submit" class="boton-apartar">
                    Confirmar apartado
                </button>
            </form>
        </section>
    </div>
</main>

<div class="modal-apartado-exito" id="modalApartadoExito" role="dialog" aria-modal="true" aria-labelledby="tituloApartadoExito" aria-hidden="true">
    <div class="modal-apartado-contenido">
        <div class="modal-apartado-icono" aria-hidden="true">✓</div>
        <p class="etiqueta">CONFISENA</p>
        <h2 id="tituloApartadoExito">¡Apartado registrado!</h2>
        <p>Tu apartado se registró correctamente. Aquí tienes el resumen:</p>
        <div class="modal-apartado-resumen">
            <div><span>Número de apartado</span><strong id="numeroApartadoExito">-</strong></div>
            <div><span>Producto</span><strong id="productoApartadoExito">-</strong></div>
            <div><span>Talla</span><strong id="tallaApartadoExito">-</strong></div>
            <div><span>Cantidad</span><strong id="cantidadApartadoExito">-</strong></div>
            <div><span>Total</span><strong id="totalApartadoExito">-</strong></div>
            <div><span>Abono requerido</span><strong id="abonoApartadoExito">-</strong></div>
            <div><span>Saldo pendiente</span><strong id="saldoApartadoExito">-</strong></div>
            <div><span>Estado</span><strong id="estadoApartadoExito">-</strong></div>
        </div>
        <p>Consulta el seguimiento de tu reserva en Mis apartados.</p>
        <button type="button" class="modal-apartado-boton" id="cerrarModalApartado">Ver mis apartados</button>
    </div>
</div>

<footer>
    <p>© 2026 CONFISENA - Transformamos la forma de comprar</p>
</footer>

<script src="apartado.js"></script>
</body>
</html>
