
document.addEventListener("DOMContentLoaded", () => {
    const obtener = id => document.getElementById(id);

    const formulario = obtener("formularioApartado");
    const metodo = obtener("metodoAbono");
    const datosTarjeta = obtener("datosTarjeta");
    const telefono = obtener("telefono");
    const tarjeta = obtener("numeroTarjeta");
    const cvv = obtener("cvv");
    const vencimiento = obtener("vencimiento");
    const fechaEntrega = obtener("fechaEntrega");

    if (!formulario || !metodo || !datosTarjeta) {
        alert("No se encontró el formulario. Revisa apartado.php.");
        return;
    }

    // Recuperar el producto seleccionado.
    const idProducto = Number(localStorage.getItem("id_producto")) || 0;
    const producto = localStorage.getItem("producto") || "";
    const talla = localStorage.getItem("talla") || "";
    const cantidad = Number(localStorage.getItem("cantidad")) || 1;
    const precio = Number(localStorage.getItem("precio")) || 0;

    // Mostrar el resumen del apartado.
    obtener("nombreProducto").textContent =
        producto || "Producto seleccionado";

    obtener("tallaProducto").textContent = talla || "-";
    obtener("cantidadProducto").textContent = String(cantidad);

    obtener("precioProducto").textContent =
        "$" + precio.toLocaleString("es-CO");

    const total = precio * cantidad;
    const abono = total * 0.5;
    const saldo = total - abono;

    obtener("totalApartado").textContent =
        "$" + total.toLocaleString("es-CO");

    obtener("abonoApartado").textContent =
        "$" + abono.toLocaleString("es-CO");

    obtener("saldoApartado").textContent =
        "$" + saldo.toLocaleString("es-CO");

    // Definir la fecha mínima de entrega.
    const hoy = new Date();

    const fechaMinima =
        hoy.getFullYear() + "-" +
        String(hoy.getMonth() + 1).padStart(2, "0") + "-" +
        String(hoy.getDate()).padStart(2, "0");

    fechaEntrega.min = fechaMinima;

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

    const boton = formulario.querySelector('button[type="submit"]');

    // Enviar el apartado al servidor.
    formulario.addEventListener("submit", async evento => {
        evento.preventDefault();

        if (!formulario.reportValidity()) {
            return;
        }

        if (!idProducto || !talla || cantidad < 1) {
            alert(
                "No se encontró el producto seleccionado. " +
                "Regresa al catálogo y vuelve a seleccionarlo."
            );
            return;
        }

        if (!/^[0-9]{10}$/.test(telefono.value.trim())) {
            alert("El teléfono debe tener exactamente 10 dígitos.");
            return;
        }

        const metodoSeleccionado = metodo.value;

        // Validar tarjeta únicamente cuando sea el método seleccionado.
        if (metodoSeleccionado === "tarjeta") {
            const numero = tarjeta.value.replace(/\D/g, "");
            const codigo = cvv.value.replace(/\D/g, "");
            const fecha = vencimiento.value.trim();

            if (numero.length !== 16) {
                alert("El número de tarjeta debe tener 16 dígitos.");
                return;
            }

            if (codigo.length !== 3) {
                alert("El CVV debe tener 3 dígitos.");
                return;
            }

            if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(fecha)) {
                alert("La fecha debe tener el formato MM/AA.");
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
                alert("La tarjeta está vencida.");
                return;
            }
        }

        // El servidor obtiene el precio real desde MySQL.
        const datos = new URLSearchParams({
            id_producto: String(idProducto),
            cantidad: String(cantidad),
            talla: talla,
            fecha_entrega: fechaEntrega.value,
            tienda: obtener("tienda").value,
            metodo: metodoSeleccionado
        });

        const textoOriginal = boton.textContent;

        boton.disabled = true;
        boton.textContent = "Registrando apartado...";

        try {
            const respuesta = await fetch("procesar_apartado.php", {
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
                    "El servidor no devolvió una respuesta válida. " +
                    "Revisa procesar_apartado.php."
                );
            }

            if (respuesta.status === 401) {
                alert("Tu sesión expiró. Inicia sesión nuevamente.");
                window.location.href = "login.html";
                return;
            }

            if (!respuesta.ok || !resultado.success) {
                throw new Error(
                    resultado.mensaje || "No se pudo registrar el apartado."
                );
            }

            // Guardar la referencia del apartado.
            localStorage.setItem(
                "ultimoApartado",
                String(resultado.id_apartado)
            );

            alert(
                "Apartado registrado correctamente.\n\n" +
                "Número de apartado: " + resultado.id_apartado + "\n" +
                "Producto: " + producto + "\n" +
                "Talla: " + talla + "\n" +
                "Cantidad: " + cantidad + "\n" +
                "Total: $" +
                Number(resultado.total).toLocaleString("es-CO") + "\n" +
                "Abono requerido: $" +
                Number(resultado.abono).toLocaleString("es-CO") + "\n" +
                "Saldo pendiente: $" +
                Number(resultado.saldo).toLocaleString("es-CO") +
                "\n\nEstado: " + resultado.estado
            );

            // CAMBIO: ir al historial de apartados.
            window.location.href = "mis_apartados.php";

        } catch (error) {
            alert(
                error.message || "Ocurrió un error al registrar el apartado."
            );

            boton.disabled = false;
            boton.textContent = textoOriginal;
        }
    });
});
