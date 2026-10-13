
<?php
session_start();

// Verificar que el usuario haya iniciado sesión.
if (empty($_SESSION["id_usuario"])) {
    header("Location: login.html");
    exit;
}

// Obtener los datos del usuario de la sesión.
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

    <title>Pago - CONFISENA</title>

    <link rel="stylesheet" href="pago.css">
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
        <a href="catalogo.php">Tiendas</a>
        <a href="cerrar_sesion.php">Cerrar sesión</a>
    </nav>
</header>

<main class="contenedor">

    <div class="ruta">
        Inicio / Catálogo / Pago
    </div>

    <h2>Realizar pago</h2>

    <p class="descripcion">
        Completa la información para realizar el pago de tu compra.
    </p>

    <div class="contenido">

        <!-- RESUMEN DE LA COMPRA -->
        <section class="resumen">

            <h3>Resumen de compra</h3>

            <div class="producto-resumen">

                <div class="imagen-producto"></div>

                <div class="informacion-producto">

                    <h4 id="nombreProducto">
                        Producto seleccionado
                    </h4>

                    <p>
                        Talla:
                        <strong id="tallaProducto">-</strong>
                    </p>

                    <p>
                        Cantidad:
                        <strong id="cantidadProducto">1</strong>
                    </p>

                    <p>
                        Precio unitario:
                        <strong id="precioProducto">$0</strong>
                    </p>

                </div>

            </div>

            <div class="linea"></div>

            <div class="total">
                <span>Total a pagar</span>
                <strong id="totalPago">$0</strong>
            </div>

        </section>


        <!-- FORMULARIO DE PAGO -->
        <section class="formulario">

            <h3>Información de pago</h3>

            <form id="formularioPago">

                <label for="nombre">
                    Nombre completo
                </label>

                <input
                    type="text"
                    id="nombre"
                    value="<?= $nombreUsuario ?>"
                    placeholder="Ingresa tu nombre completo"
                    required
                >


                <label for="correo">
                    Correo electrónico
                </label>

                <input
                    type="email"
                    id="correo"
                    value="<?= $correoUsuario ?>"
                    placeholder="ejemplo@correo.com"
                    required
                >


                <label for="telefono">
                    Número de teléfono
                </label>

                <input
                    type="tel"
                    id="telefono"
                    placeholder="3001234567"
                    maxlength="10"
                    pattern="[0-9]{10}"
                    inputmode="numeric"
                    required
                >


                <label for="metodo">
                    Método de pago
                </label>

                <select id="metodo" required>

                    <option value="">
                        Selecciona un método
                    </option>

                    <option value="tarjeta">
                        Tarjeta débito / crédito
                    </option>

                    <option value="pse">
                        PSE
                    </option>

                    <option value="nequi">
                        Nequi
                    </option>

                </select>


                <!-- DATOS DE TARJETA -->
                <div id="datosTarjeta" class="datos-pago">

                    <label for="numeroTarjeta">
                        Número de tarjeta
                    </label>

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
                            <label for="vencimiento">
                                Vencimiento
                            </label>

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
                            <label for="cvv">
                                CVV
                            </label>

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
                    Tus datos serán tratados de forma segura.
                </div>


                <button
                    type="submit"
                    class="boton-pagar"
                >
                    Pagar ahora
                </button>

            </form>

        </section>

    </div>

</main>


<footer>
    <p>
        © 2026 CONFISENA -
        Transformamos la forma de comprar
    </p>
</footer>

<script src="pago.js"></script>

</body>
</html>
