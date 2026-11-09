document.addEventListener("DOMContentLoaded", () => {
    const obtener = id => document.getElementById(id);
    const formulario = obtener("formularioPago");
    const metodo = obtener("metodo");
    const datosTarjeta = obtener("datosTarjeta");
    const telefono = obtener("telefono");
    const tarjeta = obtener("numeroTarjeta");
    const cvv = obtener("cvv");
    const vencimiento = obtener("vencimiento");
    const fechaEntrega = obtener("fechaEntrega");
    function fechaHoraColombia() {
        const partes = new Intl.DateTimeFormat("en-CA", {
            timeZone: "America/Bogota",
            year: "numeric", month: "2-digit", day: "2-digit",
            hour: "2-digit", minute: "2-digit", hourCycle: "h23"
        }).formatToParts(new Date());
        const dato = Object.fromEntries(partes.map(p => [p.type, p.value]));
        return { fecha: `${dato.year}-${dato.month}-${dato.day}`, hora: Number(dato.hour), minuto: Number(dato.minute) };
    }

    function fechaYFranjaValidas() {
        const actual = fechaHoraColombia();
        const horario = obtener("horarioEntrega").value;
        const fecha = fechaEntrega.value;
        if (!fecha || fecha < actual.fecha) {
            mostrarErrorFecha("Selecciona hoy o una fecha posterior.");
            return false;
        }
        if (fecha === actual.fecha) {
            const inicio = horario === "Manana" ? 8 : horario === "Tarde" ? 14 : null;
            if (inicio === null || actual.hora * 60 + actual.minuto >= inicio * 60) {
                mostrarErrorFecha("La franja seleccionada ya comenzó. Elige otra franja o una fecha posterior.");
                return false;
            }
        }
        return true;
    }

    function mostrarErrorFecha(texto) {
        const campo = obtener("horarioEntrega");
        campo.setCustomValidity(texto);
        campo.reportValidity();
        campo.setCustomValidity("");
    }



    if (!formulario || !metodo || !datosTarjeta) {
        alert("No se encontró el formulario de pago. Revisa pago.php.");
        return;
    }

    fechaEntrega.min = fechaHoraColombia().fecha;
    fechaEntrega.addEventListener("change", () => {
        if (fechaEntrega.value) fechaYFranjaValidas();
    });
    obtener("horarioEntrega").addEventListener("change", () => {
        if (fechaEntrega.value) fechaYFranjaValidas();
    });

    const mensaje = document.createElement("p");
    mensaje.id = "mensajePago";
    mensaje.style.display = "none";
    mensaje.style.margin = "12px 0";
    mensaje.style.whiteSpace = "pre-wrap";
    mensaje.setAttribute("role", "status");
    mensaje.setAttribute("aria-live", "polite");

    const boton = formulario.querySelector('button[type="submit"]');
    if (boton) formulario.insertBefore(mensaje, boton);

    function mostrarMensaje(texto, error = false) {
        mensaje.textContent = texto;
        mensaje.style.display = "block";
        mensaje.style.color = error ? "#b00020" : "#173b67";
    }

    obtener("cerrarModalPedido").addEventListener("click", () => {
        window.location.href = "mis_pedidos.php";
    });

    const idProducto = Number(localStorage.getItem("id_producto")) || 0;
    const producto = localStorage.getItem("producto") || "";
    const talla = localStorage.getItem("talla") || "";
    const cantidad = Number(localStorage.getItem("cantidad")) || 1;
    const precio = Number(localStorage.getItem("precio")) || 0;

    obtener("nombreProducto").textContent = producto || "Producto seleccionado";
    obtener("tallaProducto").textContent = talla || "-";
    obtener("cantidadProducto").textContent = String(cantidad);
    obtener("precioProducto").textContent = "$" + precio.toLocaleString("es-CO");
    obtener("totalPago").textContent = "$" + (precio * cantidad).toLocaleString("es-CO");

    function actualizarMetodo() {
        datosTarjeta.style.display = metodo.value === "tarjeta" ? "block" : "none";
    }

    metodo.addEventListener("change", actualizarMetodo);
    actualizarMetodo();

    // Teléfono: exactamente 10 dígitos y debe comenzar con 3.
    telefono.addEventListener("input", () => {
        let valor = telefono.value.replace(/\D/g, "").slice(0, 10);
        if (valor.length > 0 && valor.charAt(0) !== "3") valor = "";
        telefono.value = valor;
    });

    tarjeta.addEventListener("input", () => {
        const digitos = tarjeta.value.replace(/\D/g, "").slice(0, 16);
        tarjeta.value = digitos.replace(/(\d{4})(?=\d)/g, "$1 ");
    });

    cvv.addEventListener("input", () => {
        cvv.value = cvv.value.replace(/\D/g, "").slice(0, 3);
    });

    vencimiento.addEventListener("input", () => {
        const digitos = vencimiento.value.replace(/\D/g, "").slice(0, 4);
        vencimiento.value = digitos.length > 2 ? digitos.slice(0, 2) + "/" + digitos.slice(2) : digitos;
    });

    formulario.addEventListener("submit", async evento => {
        evento.preventDefault();
        mensaje.style.display = "none";

        if (!formulario.reportValidity()) return;
        if (!fechaYFranjaValidas()) return;

        if (!idProducto || !talla || cantidad < 1) {
            mostrarMensaje("No se encontró el producto seleccionado. Regresa al catálogo y vuelve a seleccionarlo.", true);
            return;
        }

        const nombre = obtener("nombre").value.trim();
        const correo = obtener("correo").value.trim();
        const telefonoValor = telefono.value.trim();
        const metodoPago = metodo.value;

        if (!/^3[0-9]{9}$/.test(telefonoValor)) {
            mostrarMensaje("El teléfono debe tener exactamente 10 dígitos y comenzar con 3.", true);
            return;
        }

        if (metodoPago === "tarjeta") {
            const numero = tarjeta.value.replace(/\D/g, "");
            const codigo = cvv.value.replace(/\D/g, "");
            const fecha = vencimiento.value.trim();

            if (numero.length !== 16) {
                mostrarMensaje("El número de tarjeta debe tener 16 dígitos.", true);
                return;
            }
            if (codigo.length !== 3) {
                mostrarMensaje("El CVV debe tener 3 dígitos.", true);
                return;
            }
            if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(fecha)) {
                mostrarMensaje("El vencimiento debe tener el formato MM/AA.", true);
                return;
            }

            const partes = fecha.split("/");
            const mes = Number(partes[0]);
            const anio = 2000 + Number(partes[1]);
            const finVigencia = new Date(anio, mes, 0, 23, 59, 59);

            if (finVigencia < new Date()) {
                mostrarMensaje("La tarjeta está vencida. Verifica la fecha de vencimiento.", true);
                return;
            }
        }

        const datos = new URLSearchParams({
            id_producto: String(idProducto),
            cantidad: String(cantidad),
            talla: talla,
            nombre: nombre,
            correo: correo,
            telefono: telefonoValor,
            barrio: obtener("barrio").value.trim(),
            localidad: obtener("localidad").value.trim(),
            direccion: obtener("direccion").value.trim(),
            fecha_entrega: fechaEntrega.value,
            horario_entrega: obtener("horarioEntrega").value,
            indicaciones_entrega: obtener("indicacionesEntrega").value.trim(),
            metodo: metodoPago
        });

        const textoOriginal = boton.textContent;
        boton.disabled = true;
        boton.textContent = "Registrando pedido...";

        try {
            const respuesta = await fetch("procesar_pago.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8" },
                body: datos.toString()
            });

            const texto = await respuesta.text();
            let resultado;
            try {
                resultado = JSON.parse(texto);
            } catch {
                throw new Error("El servidor no devolvió una respuesta válida. Revisa procesar_pago.php.");
            }

            if (respuesta.status === 401) {
                alert("Tu sesión expiró. Inicia sesión nuevamente.");
                window.location.href = "login.html";
                return;
            }

            if (!respuesta.ok || !resultado.success) {
                throw new Error(resultado.mensaje || "No se pudo registrar el pedido.");
            }

            localStorage.setItem("ultimoPedido", String(resultado.id_pedido));

            obtener("numeroPedidoExito").textContent = resultado.id_pedido;
            obtener("productoPedidoExito").textContent = producto;
            obtener("tallaPedidoExito").textContent = talla;
            obtener("cantidadPedidoExito").textContent = String(cantidad);
            obtener("totalPedidoExito").textContent = "$" + Number(resultado.total).toLocaleString("es-CO");
            obtener("estadoPedidoExito").textContent = resultado.estado;

            const modalExito = obtener("modalPedidoExito");
            modalExito.classList.add("activo");
            modalExito.setAttribute("aria-hidden", "false");
            document.body.classList.add("modal-abierto");
            obtener("cerrarModalPedido").focus();
        } catch (error) {
            mostrarMensaje(error.message || "Ocurrió un error al registrar el pedido.", true);
            alert(error.message || "Ocurrió un error al registrar el pedido.");
            boton.disabled = false;
            boton.textContent = textoOriginal;
        }
    });
});