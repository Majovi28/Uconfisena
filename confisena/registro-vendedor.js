const formulario = document.getElementById("formularioVendedor");
const selectorTienda = document.getElementById("tienda");
const mensajeTiendas = document.getElementById("mensajeTiendas");
const hoy = new Date().toISOString().split("T")[0];

document.getElementById("fechaNacimiento").max = hoy;
document.getElementById("fechaIngreso").max = hoy;

function leerLista(clave) {
    try {
        const datos = JSON.parse(localStorage.getItem(clave) || "[]");
        return Array.isArray(datos) ? datos : [];
    } catch (error) {
        console.error("No se pudo leer " + clave, error);
        return [];
    }
}

function cargarTiendas() {
    const tiendas = leerLista("tiendas").filter(tienda => tienda.estado !== "Inactiva");
    selectorTienda.innerHTML = '<option value="">Selecciona una tienda</option>';
    selectorTienda.disabled = tiendas.length === 0;
    mensajeTiendas.textContent = tiendas.length
        ? "Selecciona la sede en la que estará asignado el vendedor."
        : "No hay tiendas activas. Registra o activa una tienda antes de continuar.";

    tiendas.forEach(tienda => {
        const opcion = document.createElement("option");
        opcion.value = tienda.id;
        opcion.textContent = tienda.nombreTienda + (tienda.localidad ? " — " + tienda.localidad : "");
        selectorTienda.appendChild(opcion);
    });
}

function limitarTelefono(campo) {
    campo.addEventListener("input", function () {
        let valor = this.value.replace(/\D/g, "").slice(0, 10);
        if (valor && valor[0] !== "3") valor = "";
        this.value = valor;
    });
}
limitarTelefono(document.getElementById("telefono"));
limitarTelefono(document.getElementById("telefonoEmergencia"));

document.getElementById("documento").addEventListener("input", function () {
    this.value = this.value.replace(/\D/g, "").slice(0, 10);
});
const usuario = document.getElementById("usuario");
usuario.addEventListener("input", function () {
    this.value = this.value.toLowerCase().replace(/\s/g, "");
});

formulario.addEventListener("submit", function (evento) {
    evento.preventDefault();
    if (!formulario.reportValidity()) return;

    const valor = id => document.getElementById(id).value.trim();
    const fechaNacimiento = valor("fechaNacimiento");
    const fechaIngreso = valor("fechaIngreso");
    const telefono = valor("telefono");
    const telefonoEmergencia = valor("telefonoEmergencia");
    const documento = valor("documento");
    const contrasena = document.getElementById("contrasena").value;
    const confirmar = document.getElementById("confirmarContrasena").value;

    const nacimiento = new Date(fechaNacimiento + "T00:00:00");
    const edad = new Date().getFullYear() - nacimiento.getFullYear() -
        (new Date() < new Date(new Date().getFullYear(), nacimiento.getMonth(), nacimiento.getDate()) ? 1 : 0);

    if (edad < 18 || edad > 100) {
        alert("El vendedor debe tener al menos 18 años y una fecha de nacimiento válida.");
        return;
    }
    if (fechaIngreso > hoy) {
        alert("La fecha de ingreso no puede ser posterior a hoy.");
        return;
    }
    if (!/^3\d{9}$/.test(telefono) || !/^3\d{9}$/.test(telefonoEmergencia)) {
        alert("Los teléfonos celulares deben tener 10 dígitos y comenzar por 3.");
        return;
    }
    if (telefono === telefonoEmergencia) {
        alert("El teléfono de emergencia debe ser diferente al del vendedor.");
        return;
    }
    if (!/^[0-9]{6,10}$/.test(documento)) {
        alert("El documento debe contener entre 6 y 10 números.");
        return;
    }
    if (contrasena.length < 8) {
        alert("La contraseña debe tener mínimo 8 caracteres.");
        return;
    }
    if (contrasena !== confirmar) {
        alert("Las contraseñas no coinciden.");
        return;
    }

    const vendedores = leerLista("vendedores");
    const usuarioValor = valor("usuario");
    if (vendedores.some(v => v.usuario && v.usuario.toLowerCase() === usuarioValor)) {
        alert("Este nombre de usuario ya está registrado.");
        return;
    }
    if (vendedores.some(v => String(v.documento) === documento)) {
        alert("Ya existe un vendedor con este documento.");
        return;
    }

    const tiendas = leerLista("tiendas");
    const tiendaSeleccionada = tiendas.find(t => String(t.id) === String(selectorTienda.value) && t.estado !== "Inactiva");
    if (!tiendaSeleccionada) {
        alert("Selecciona una tienda activa de la lista.");
        return;
    }

    const nuevoVendedor = {
        id: Date.now(),
        nombre: valor("nombre"),
        documento,
        fechaNacimiento,
        direccion: valor("direccion"),
        correo: valor("correo").toLowerCase(),
        telefono,
        contactoEmergencia: valor("contactoEmergencia"),
        telefonoEmergencia,
        fechaIngreso,
        tiendaId: tiendaSeleccionada.id,
        tienda: tiendaSeleccionada.nombreTienda,
        usuario: usuarioValor,
        contrasena,
        rol: "Vendedor",
        estado: "Activo"
    };

    vendedores.push(nuevoVendedor);
    try {
        localStorage.setItem("vendedores", JSON.stringify(vendedores));
    } catch (error) {
        alert("No fue posible guardar el vendedor en este navegador. Revisa el espacio disponible.");
        return;
    }
    alert("Vendedor registrado correctamente.\n\nNombre: " + nuevoVendedor.nombre +
        "\nTienda: " + nuevoVendedor.tienda + "\nUsuario: " + usuarioValor);
    window.location.href = "gestor.html";
});

document.getElementById("cancelar").addEventListener("click", () => window.location.href = "gestor.html");
document.getElementById("volverGestor").addEventListener("click", () => window.location.href = "gestor.html");
cargarTiendas();