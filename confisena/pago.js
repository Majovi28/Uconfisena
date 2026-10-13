
document.addEventListener("DOMContentLoaded", () => {
    const obtener = id => document.getElementById(id);

    const formulario = obtener("formularioPago");
    const metodo = obtener("metodo");
    const datosTarjeta = obtener("datosTarjeta");
    const telefono = obtener("telefono");
    const tarjeta = obtener("numeroTarjeta");
    const cvv = obtener("cvv");
    const vencimiento = obtener("vencimiento");

    if (!formulario || !metodo || !datosTarjeta) {
        alert("No se encontró el formulario de pago. Revisa pago.php.");
        return;
    }

    // Crear un espacio para mostrar los mensajes del servidor.
    const mensaje = document.createElement("p");
    mensaje.id = "mensajePago";
    mensaje.style.display = "none";
    mensaje.style.margin = "12px 0";
    mensaje.style.whiteSpace = "pre-wrap";
    mensaje.setAttribute("role", "status");
    mensaje.setAttribute("aria-live", "polite");

    const boton = formulario.querySelector('button[type="submit"]');

    if (boton) {
        formulario.insertBefore(mensaje, boton);
    }

    function mostrarMensaje(texto, error = false) {
        mensaje.textContent = texto;
        mensaje.style.display = "block";
        mensaje.style.color = error ? "#b00020" : "#173b67";
    }

    // Recuperar el producto seleccionado.
    const idProducto = Number(localStorage.getItem("id_producto")) || 0;
    const producto = localStorage.getItem("producto") || "";
    const talla = localStorage.getItem("talla") || "";
    const cantidad = Number(localStorage.getItem("cantidad")) || 1;
    const precio = Number(localStorage.getItem("precio")) || 0;

    // Mostrar el resumen de la compra.
    obtener("nombreProducto").textContent =
        producto || "Producto seleccionado";

    obtener("tallaProducto").textContent = talla || "-";
    obtener("cantidadProducto").textContent = String(cantidad);

    obtener("precioProducto").textContent =
        "$" + precio.toLocaleString("es-CO");

    obtener("totalPago").textContent =
        "$" + (precio * cantidad).toLocaleString("es-CO");

    // Mostrar u ocultar los campos de tarjeta.
    function actualizarMetodo() {
        datosTarjeta.style.display =
            metodo.value === "tarjeta" ? "block" : "none";
    }

    metodo.addEventListener("change", actualizarMetodo);
    actualizarMetodo();

    // Teléfono: máximo 10 dígitos.
    telefono.addEventListener("input", () => {
        telefono.value = telefono.value.replace(/\D/g, "").slice(0, 10);
    });

    // Tarjeta: máximo 16 dígitos.
    tarjeta.addEventListener("input", () => {
        const digitos = tarjeta.value.replace(/\D/g, "").slice(0, 16);

        tarjeta.value = digitos.replace(/(\d{4})(?=\d)/g, "$1 ");
    });

    // CVV: máximo 3 dígitos.
    cvv.addEventListener("input", () => {
        cvv.value = cvv.value.replace(/\D/g, "").slice(0, 3);
    });

    // Vencimiento: formato MM/AA.
    vencimiento.addEventListener("input", () => {
        const digitos = vencimiento.value.replace(/\D/g, "").slice(0, 4);

        vencimiento.value = digitos.length > 2
            ? digitos.slice(0, 2) + "/" + digitos.slice(2)
            : digitos;
    });

    // Procesar el formulario.
    formulario.addEventListener("submit", async evento => {
        evento.preventDefault();
        mensaje.style.display = "none";

        if (!formulario.reportValidity()) {
            return;
        }

        if (!idProducto || !talla || cantidad < 1) {
            mostrarMensaje(
                "No se encontró el producto seleccionado. Regresa al catálogo y vuelve a seleccionarlo.",
                true
            );
            return;
        }

        const nombre = obtener("nombre").value.trim();
        const correo = obtener("correo").value.trim();
        const telefonoValor = telefono.value.trim();
        const metodoPago = metodo.value;

        if (!/^[0-9]{10}$/.test(telefonoValor)) {
            mostrarMensaje(
                "El teléfono debe tener exactamente 10 dígitos.",
                true
            );
            return;
        }

        // Validaciones de tarjeta.
        if (metodoPago === "tarjeta") {
            const numero = tarjeta.value.replace(/\D/g, "");
            const codigo = cvv.value.replace(/\D/g, "");
            const fecha = vencimiento.value.trim();

            if (numero.length !== 16) {
                mostrarMensaje(
                    "El número de tarjeta debe tener 16 dígitos.",
                    true
                );
                return;
            }

            if (codigo.length !== 3) {
                mostrarMensaje(
                    "El CVV debe tener 3 dígitos.",
                    true
                );
                return;
            }

            if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(fecha)) {
                mostrarMensaje(
                    "El vencimiento debe tener el formato MM/AA.",
                    true
                );
                return;
            }

            const partes = fecha.split("/");
            const mes = Number(partes[0]);
            const anio = 2000 + Number(partes[1]);

            const finVigencia = new Date(
                anio,
                mes,
                0,
                23,
                59,
                59
            );

            if (finVigencia < new Date()) {
                mostrarMensaje(
                    "La tarjeta está vencida. Verifica la fecha de vencimiento.",
                    true
                );
                return;
            }
        }

        // Enviar al PHP los datos necesarios para registrar el pedido.
        // Nunca se envían ni se guardan el número de tarjeta o el CVV.
        const datos = new URLSearchParams({
            id_producto: String(idProducto),
            cantidad: String(cantidad),
            talla: talla,
            nombre: nombre,
            correo: correo,
            telefono: telefonoValor,
            metodo: metodoPago
        });

        const textoOriginal = boton.textContent;

        boton.disabled = true;
        boton.textContent = "Registrando pedido...";

        try {
            const respuesta = await fetch("procesar_pago.php", {
                method: "POST",
                headers: {
                    "Content-Type":
                        "application/x-www-form-urlencoded;charset=UTF-8"
                },
                body: datos.toString()
            });

            const texto = await respuesta.text();
            let resultado;

            try {
                resultado = JSON.parse(texto);
            } catch {
                throw new Error(
                    "El servidor no devolvió una respuesta válida. Revisa procesar_pago.php."
                );
            }

            if (respuesta.status === 401) {
                alert("Tu sesión expiró. Inicia sesión nuevamente.");
                window.location.href = "login.html";
                return;
            }

            if (!respuesta.ok || !resultado.success) {
                throw new Error(
                    resultado.mensaje || "No se pudo registrar el pedido."
                );
            }

            // Guardar la referencia del último pedido.
            localStorage.setItem(
                "ultimoPedido",
                String(resultado.id_pedido)
            );

            alert(
                "Pedido registrado correctamente.\n\n" +
                "Número de pedido: " + resultado.id_pedido + "\n" +
                "Producto: " + producto + "\n" +
                "Talla: " + talla + "\n" +
                "Cantidad: " + cantidad + "\n" +
                "Total: $" +
                Number(resultado.total).toLocaleString("es-CO") +
                "\n\nEstado del pago: " + resultado.estado
            );

            // CAMBIO: ir al historial de pedidos.
            window.location.href = "mis_pedidos.php";

        } catch (error) {
            mostrarMensaje(
                error.message || "Ocurrió un error al registrar el pedido.",
                true
            );

            alert(
                error.message || "Ocurrió un error al registrar el pedido."
            );

            boton.disabled = false;
            boton.textContent = textoOriginal;
        }
    });
});
