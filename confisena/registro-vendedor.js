// ==========================================
// ELEMENTOS DEL FORMULARIO
// ==========================================

const formulario = document.getElementById("formularioVendedor");
const selectorTienda = document.getElementById("tienda");
const mensajeTiendas = document.getElementById("mensajeTiendas");

function cargarTiendas() {
    const tiendas = JSON.parse(localStorage.getItem("tiendas")) || [];

    selectorTienda.innerHTML = `
        <option value="">Selecciona una tienda</option>
    `;

    if (tiendas.length === 0) {
        mensajeTiendas.textContent = "No hay tiendas registradas. Primero debes registrar una tienda.";
        selectorTienda.disabled = true;
        return;
    }

    mensajeTiendas.textContent = "Selecciona la tienda donde trabajará el vendedor.";
    selectorTienda.disabled = false;

    tiendas.forEach(function (tienda) {
        const opcion = document.createElement("option");
        opcion.value = tienda.id;
        opcion.textContent = tienda.nombreTienda;
        selectorTienda.appendChild(opcion);
    });
}

// ==========================================
// TELÉFONO: 10 DÍGITOS Y DEBE EMPEZAR EN 3
// ==========================================

const telefono = document.getElementById("telefono");

telefono.addEventListener("input", function () {
    let valor = this.value.replace(/\D/g, "").slice(0, 10);

    if (valor.length > 0 && valor.charAt(0) !== "3") {
        valor = "";
    }

    this.value = valor;
});

// ==========================================
// DOCUMENTO: SOLO NÚMEROS, ENTRE 6 Y 10
// ==========================================

const documento = document.getElementById("documento");

documento.addEventListener("input", function () {
    this.value = this.value.replace(/\D/g, "").slice(0, 10);
});

// ==========================================
// USUARIO
// ==========================================

const usuario = document.getElementById("usuario");

usuario.addEventListener("input", function () {
    this.value = this.value.toLowerCase().replace(/\s/g, "");
});

// ==========================================
// FORMULARIO
// ==========================================

formulario.addEventListener("submit", function (evento) {
    evento.preventDefault();

    const nombre = document.getElementById("nombre").value.trim();
    const documentoValor = document.getElementById("documento").value.trim();
    const correo = document.getElementById("correo").value.trim();
    const telefonoValor = document.getElementById("telefono").value.trim();
    const tiendaId = selectorTienda.value;
    const usuarioValor = document.getElementById("usuario").value.trim();
    const contrasena = document.getElementById("contrasena").value;
    const confirmarContrasena = document.getElementById("confirmarContrasena").value;

    if (tiendaId === "") {
        alert("Por favor selecciona una tienda.");
        return;
    }

    if (!/^[0-9]{6,10}$/.test(documentoValor)) {
        alert("El documento debe contener solo números y tener entre 6 y 10 dígitos.");
        return;
    }

    if (!/^3[0-9]{9}$/.test(telefonoValor)) {
        alert("El teléfono debe tener exactamente 10 dígitos y comenzar con 3.");
        return;
    }

    if (contrasena.length < 8) {
        alert("La contraseña debe tener mínimo 8 caracteres.");
        return;
    }

    if (contrasena !== confirmarContrasena) {
        alert("Las contraseñas no coinciden.");
        return;
    }

    const vendedores = JSON.parse(localStorage.getItem("vendedores")) || [];

    const usuarioExiste = vendedores.some(function (vendedor) {
        return vendedor.usuario === usuarioValor;
    });

    if (usuarioExiste) {
        alert("Este nombre de usuario ya está registrado.");
        return;
    }

    const documentoExiste = vendedores.some(function (vendedor) {
        return vendedor.documento === documentoValor;
    });

    if (documentoExiste) {
        alert("Ya existe un vendedor registrado con este documento.");
        return;
    }

    const tiendas = JSON.parse(localStorage.getItem("tiendas")) || [];

    const tiendaSeleccionada = tiendas.find(function (tienda) {
        return String(tienda.id) === String(tiendaId);
    });

    if (!tiendaSeleccionada) {
        alert("No se encontró la tienda seleccionada.");
        return;
    }

    const nuevoVendedor = {
        id: Date.now(),
        nombre: nombre,
        documento: documentoValor,
        correo: correo,
        telefono: telefonoValor,
        tiendaId: tiendaSeleccionada.id,
        tienda: tiendaSeleccionada.nombreTienda,
        usuario: usuarioValor,
        contrasena: contrasena,
        rol: "Vendedor",
        estado: "Activo"
    };

    vendedores.push(nuevoVendedor);
    localStorage.setItem("vendedores", JSON.stringify(vendedores));

    alert(
        "¡Vendedor registrado correctamente!\n\n" +
        "Nombre: " + nombre + "\n" +
        "Tienda: " + tiendaSeleccionada.nombreTienda + "\n" +
        "Usuario: " + usuarioValor + "\n" +
        "Rol: Vendedor"
    );

    window.location.href = "gestor.html";
});

const cancelar = document.getElementById("cancelar");
cancelar.addEventListener("click", function () {
    window.location.href = "gestor.html";
});

const volverGestor = document.getElementById("volverGestor");
volverGestor.addEventListener("click", function () {
    window.location.href = "gestor.html";
});

cargarTiendas();