// ==========================================
// FORMULARIO DE REGISTRO DE TIENDA
// ==========================================

const formulario =
    document.getElementById("formularioTienda");



// ==========================================
// TELÉFONO - MÁXIMO 10 DÍGITOS
// ==========================================

const telefono =
    document.getElementById("telefono");


telefono.addEventListener("input", function () {

    this.value = this.value
        .replace(/\D/g, "")
        .substring(0, 10);

});



// ==========================================
// NIT
// ==========================================

const nit =
    document.getElementById("nit");


nit.addEventListener("input", function () {

    this.value = this.value
        .replace(/[^0-9-]/g, "")
        .substring(0, 12);

});



// ==========================================
// REGISTRAR TIENDA
// ==========================================

formulario.addEventListener("submit", function (evento) {

    evento.preventDefault();



    // ======================================
    // OBTENER INFORMACIÓN
    // ======================================

    const nombreTienda =
        document.getElementById("nombreTienda")
        .value.trim();


    const nitValor =
        document.getElementById("nit")
        .value.trim();


    const telefonoValor =
        document.getElementById("telefono")
        .value.trim();


    const direccion =
        document.getElementById("direccion")
        .value.trim();


    const barrio =
        document.getElementById("barrio")
        .value.trim();


    const localidad =
        document.getElementById("localidad")
        .value.trim();


    const correo =
        document.getElementById("correo")
        .value.trim();


    const nombreGerente =
        document.getElementById("nombreGerente")
        .value.trim();


    const nombreVendedor =
        document.getElementById("nombreVendedor")
        .value.trim();


    const estado =
        document.getElementById("estado")
        .value;



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
    // BUSCAR TIENDAS EXISTENTES
    // ======================================

    const tiendas =
        JSON.parse(
            localStorage.getItem("tiendas")
        ) || [];



    // ======================================
    // VALIDAR NIT REPETIDO
    // ======================================

    const nitExiste =
        tiendas.some(function (tienda) {

            return tienda.nit === nitValor;

        });


    if (nitExiste) {

        alert(
            "Ya existe una tienda registrada con este NIT."
        );

        return;

    }



    // ======================================
    // CREAR TIENDA
    // ======================================

    const nuevaTienda = {

        id: Date.now(),

        nombreTienda:
            nombreTienda,

        nit:
            nitValor,

        telefono:
            telefonoValor,

        direccion:
            direccion,

        barrio:
            barrio,

        localidad:
            localidad,

        correo:
            correo,

        nombreGerente:
            nombreGerente,

        nombreVendedor:
            nombreVendedor,

        estado:
            estado

    };



    // ======================================
    // GUARDAR TIENDA
    // ======================================

    tiendas.push(nuevaTienda);


    localStorage.setItem(
        "tiendas",
        JSON.stringify(tiendas)
    );



    // ======================================
    // CONFIRMACIÓN
    // ======================================

    alert(

        "¡Tienda registrada correctamente!\n\n" +

        "Tienda: " +
        nombreTienda + "\n" +

        "NIT: " +
        nitValor + "\n" +

        "Vendedor: " +
        nombreVendedor + "\n" +

        "Estado: " +
        estado

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