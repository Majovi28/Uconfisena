
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

                <label for="tienda">Tienda donde recogerás el producto</label>
                <select id="tienda" required>
                    <option value="">Selecciona una tienda</option>
                    <option value="Tienda CONFISENA Centro">CONFISENA - Centro</option>
                    <option value="Tienda CONFISENA Chapinero">CONFISENA - Chapinero</option>
                    <option value="Tienda CONFISENA Kennedy">CONFISENA - Kennedy</option>
                </select>

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

<footer>
    <p>© 2026 CONFISENA - Transformamos la forma de comprar</p>
</footer>

<script src="apartado.js"></script>
</body>
</html>
