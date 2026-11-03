const formulario = document.getElementById("formularioTienda");
const telefono = document.getElementById("telefono");
const nit = document.getElementById("nit");

telefono.addEventListener("input", function () {
    let valor = this.value.replace(/\D/g, "").slice(0, 10);
    if (valor && valor[0] !== "3") valor = "";
    this.value = valor;
});

nit.addEventListener("input", function () {
    this.value = this.value.replace(/[^0-9-]/g, "").slice(0, 12);
});

function leerTiendas() {
    try {
        const datos = JSON.parse(localStorage.getItem("tiendas") || "[]");
        return Array.isArray(datos) ? datos : [];
    } catch (error) {
        console.error("No se pudieron leer las tiendas", error);
        return [];
    }
}

formulario.addEventListener("submit", function (evento) {
    evento.preventDefault();
    if (!formulario.reportValidity()) return;

    const valor = id => document.getElementById(id).value.trim();
    const nombreTienda = valor("nombreTienda");
    const nitValor = valor("nit");
    const telefonoValor = valor("telefono");
    const direccion = valor("direccion");
    const barrio = valor("barrio");
    const localidad = valor("localidad");
    const correo = valor("correo").toLowerCase();
    const nombreGerente = valor("nombreGerente");
    const nombreVendedor = valor("nombreVendedor");
    const estado = valor("estado");

    if (!/^[0-9]{9,10}(-[0-9])?$/.test(nitValor)) {
        alert("Escribe un NIT válido: 9 o 10 números, con dígito de verificación opcional separado por guion.");
        nit.focus();
        return;
    }
    if (!/^3[0-9]{9}$/.test(telefonoValor)) {
        alert("El celular debe tener 10 dígitos y comenzar por 3.");
        telefono.focus();
        return;
    }
    if (nombreTienda.length < 3 || direccion.length < 6 || barrio.length < 2 || localidad.length < 2) {
        alert("Revisa el nombre de la tienda y los datos de ubicación. Deben contener información completa.");
        return;
    }
    if (nombreGerente.length < 5 || nombreVendedor.length < 5) {
        alert("Escribe el nombre y apellido del gerente y del vendedor responsable.");
        return;
    }

    const tiendas = leerTiendas();
    const nitNormalizado = nitValor.replace(/-/g, "");
    if (tiendas.some(t => String(t.nit || "").replace(/-/g, "") === nitNormalizado)) {
        alert("Ya existe una tienda registrada con este NIT.");
        nit.focus();
        return;
    }

    const nuevaTienda = {
        id: Date.now(),
        nombreTienda, nit: nitValor, telefonoValor,
        telefono: telefonoValor, direccion, barrio, localidad,
        correo, nombreGerente, nombreVendedor, estado
    };

    tiendas.push(nuevaTienda);
    try {
        localStorage.setItem("tiendas", JSON.stringify(tiendas));
    } catch (error) {
        alert("No fue posible guardar la tienda en este navegador. Revisa el espacio disponible.");
        return;
    }

    alert("Tienda registrada correctamente.\n\nTienda: " + nombreTienda +
        "\nNIT: " + nitValor + "\nResponsable: " + nombreVendedor +
        "\nEstado: " + estado);
    window.location.href = "gestor.html";
});

document.getElementById("cancelar").addEventListener("click", () => window.location.href = "gestor.html");
document.getElementById("volverGestor").addEventListener("click", () => window.location.href = "gestor.html");