// ==========================================
// ELEMENTOS DEL FORMULARIO
// ==========================================

const formulario =
    document.getElementById("formularioVendedor");

const selectorTienda =
    document.getElementById("tienda");

const mensajeTiendas =
    document.getElementById("mensajeTiendas");



// ==========================================
// CARGAR TIENDAS REGISTRADAS
// ==========================================

function cargarTiendas() {

    const tiendas =
        JSON.parse(
            localStorage.getItem("tiendas")
        ) || [];


    // Limpiar opciones anteriores

    selectorTienda.innerHTML = `
        <option value="">
            Selecciona una tienda
        </option>
    `;


    // ======================================
    // SI NO HAY TIENDAS
    // ======================================

    if (tiendas.length === 0) {

        mensajeTiendas.textContent =
            "No hay tiendas registradas. Primero debes registrar una tienda.";

        selectorTienda.disabled = true;

        return;

    }


    mensajeTiendas.textContent =
        "Selecciona la tienda donde trabajará el vendedor.";

    selectorTienda.disabled = false;



    // ======================================
    // MOSTRAR TIENDAS
    // ======================================

    tiendas.forEach(function (tienda) {

        const opcion =
            document.createElement("option");


        opcion.value =
            tienda.id;


        opcion.textContent =
            tienda.nombreTienda;


        selectorTienda.appendChild(opcion);

    });

}



// ==========================================
// TELÉFONO
// ==========================================

const telefono =
    document.getElementById("telefono");


telefono.addEventListener("input", function () {

    this.value = this.value
        .replace(/\D/g, "")
        .substring(0, 10);

});



// ==========================================
// DOCUMENTO
// ==========================================

const documento =
    document.getElementById("documento");


documento.addEventListener("input", function () {

    this.value = this.value
        .replace(/\D/g, "");

});



// ==========================================
// USUARIO
// ==========================================

const usuario =
    document.getElementById("usuario");


usuario.addEventListener("input", function () {

    this.value = this.value
        .toLowerCase()
        .replace(/\s/g, "");

});



// ==========================================
// FORMULARIO
// ==========================================

formulario.addEventListener("submit", function (evento) {

    evento.preventDefault();



    // ======================================
    // OBTENER DATOS
    // ======================================

    const nombre =
        document.getElementById("nombre")
        .value.trim();


    const documentoValor =
        document.getElementById("documento")
        .value.trim();


    const correo =
        document.getElementById("correo")
        .value.trim();


    const telefonoValor =
        document.getElementById("telefono")
        .value.trim();


    const tiendaId =
        selectorTienda.value;


    const usuarioValor =
        document.getElementById("usuario")
        .value.trim();


    const contrasena =
        document.getElementById("contrasena")
        .value;


    const confirmarContrasena =
        document.getElementById("confirmarContrasena")
        .value;



    // ======================================
    // VALIDAR TIENDA
    // ======================================

    if (tiendaId === "") {

        alert(
            "Por favor selecciona una tienda."
        );

        return;

    }



    // ======================================
    // VALIDAR TELÉFONO
    // ======================================

    if (telefonoValor.length !== 10) {

        alert(
            "El teléfono debe tener exactamente 10 dígitos."
        );

        return;

    }



    // ======================================
    // VALIDAR CONTRASEÑA
    // ======================================

    if (contrasena.length < 8) {

        alert(
            "La contraseña debe tener mínimo 8 caracteres."
        );

        return;

    }



    // ======================================
    // CONFIRMAR CONTRASEÑA
    // ======================================

    if (contrasena !== confirmarContrasena) {

        alert(
            "Las contraseñas no coinciden."
        );

        return;

    }



    // ======================================
    // OBTENER VENDEDORES
    // ======================================

    const vendedores =
        JSON.parse(
            localStorage.getItem("vendedores")
        ) || [];



    // ======================================
    // VALIDAR USUARIO REPETIDO
    // ======================================

    const usuarioExiste =
        vendedores.some(function (vendedor) {

            return vendedor.usuario === usuarioValor;

        });


    if (usuarioExiste) {

        alert(
            "Este nombre de usuario ya está registrado."
        );

        return;

    }



    // ======================================
    // VALIDAR DOCUMENTO REPETIDO
    // ======================================

    const documentoExiste =
        vendedores.some(function (vendedor) {

            return vendedor.documento === documentoValor;

        });


    if (documentoExiste) {

        alert(
            "Ya existe un vendedor registrado con este documento."
        );

        return;

    }



    // ======================================
    // BUSCAR TIENDA
    // ======================================

    const tiendas =
        JSON.parse(
            localStorage.getItem("tiendas")
        ) || [];


    const tiendaSeleccionada =
        tiendas.find(function (tienda) {

            return String(tienda.id) === String(tiendaId);

        });



    if (!tiendaSeleccionada) {

        alert(
            "No se encontró la tienda seleccionada."
        );

        return;

    }



    // ======================================
    // CREAR VENDEDOR
    // ======================================

    const nuevoVendedor = {

        id: Date.now(),

        nombre:
            nombre,

        documento:
            documentoValor,

        correo:
            correo,

        telefono:
            telefonoValor,

        tiendaId:
            tiendaSeleccionada.id,

        tienda:
            tiendaSeleccionada.nombreTienda,

        usuario:
            usuarioValor,

        contrasena:
            contrasena,

        rol:
            "Vendedor",

        estado:
            "Activo"

    };



    // ======================================
    // GUARDAR VENDEDOR
    // ======================================

    vendedores.push(nuevoVendedor);


    localStorage.setItem(
        "vendedores",
        JSON.stringify(vendedores)
    );



    // ======================================
    // CONFIRMACIÓN
    // ======================================

    alert(

        "¡Vendedor registrado correctamente!\n\n" +

        "Nombre: " +
        nombre + "\n" +

        "Tienda: " +
        tiendaSeleccionada.nombreTienda + "\n" +

        "Usuario: " +
        usuarioValor + "\n" +

        "Rol: Vendedor"

    );



    // ======================================
    // REGRESAR AL PANEL
    // ======================================

    window.location.href =
        "gestor.html";

});



// ==========================================
// CANCELAR
// ==========================================

const cancelar =
    document.getElementById("cancelar");


cancelar.addEventListener("click", function () {

    window.location.href =
        "gestor.html";

});



// ==========================================
// VOLVER AL PANEL
// ==========================================

const volverGestor =
    document.getElementById("volverGestor");


volverGestor.addEventListener("click", function () {

    window.location.href =
        "gestor.html";

});



// ==========================================
// INICIAR
// ==========================================

cargarTiendas();