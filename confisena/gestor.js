// ==========================================
// BOTÓN REGISTRAR TIENDA
// ==========================================

const registrarTienda =
    document.getElementById("registrarTienda");


registrarTienda.addEventListener("click", function () {

    window.location.href = "registro-tienda.html";

});



// ==========================================
// BOTÓN REGISTRAR VENDEDOR
// ==========================================

const registrarVendedor =
    document.getElementById("registrarVendedor");


registrarVendedor.addEventListener("click", function () {

    window.location.href = "registro-vendedor.html";

});



// ==========================================
// CONSULTAR TIENDAS
// ==========================================

const consultarTiendas =
    document.getElementById("consultarTiendas");


consultarTiendas.addEventListener("click", function () {

    window.location.href = "tiendas-gestor.html";

});



// ==========================================
// CONSULTAR VENDEDORES
// ==========================================

const consultarVendedores =
    document.getElementById("consultarVendedores");


consultarVendedores.addEventListener("click", function () {

    window.location.href = "vendedores-gestor.html";

});



// ==========================================
// GESTIONAR PROBLEMAS
// ==========================================

const gestionarProblemas =
    document.getElementById("gestionarProblemas");


gestionarProblemas.addEventListener("click", function () {

    window.location.href = "reportes-gestor.html";

});



// ==========================================
// CONTADORES
// ==========================================

function actualizarResumen() {


    const tiendas =
        JSON.parse(
            localStorage.getItem("tiendas")
        ) || [];


    const vendedores =
        JSON.parse(
            localStorage.getItem("vendedores")
        ) || [];


    const reportes =
        JSON.parse(
            localStorage.getItem("reportes")
        ) || [];



    // ======================================
    // CONTAR REPORTES PENDIENTES
    // ======================================

    const reportesPendientes =
        reportes.filter(function (reporte) {

            return reporte.estado === "Pendiente";

        });



    document.getElementById(
        "cantidadTiendas"
    ).textContent = tiendas.length;



    document.getElementById(
        "cantidadVendedores"
    ).textContent = vendedores.length;



    document.getElementById(
        "cantidadReportes"
    ).textContent = reportesPendientes.length;

}



// ==========================================
// CERRAR SESIÓN
// ==========================================

const cerrarSesion =
    document.getElementById("cerrarSesion");


cerrarSesion.addEventListener("click", function () {

    localStorage.removeItem("sesionActiva");

    localStorage.removeItem("rolUsuario");

    window.location.href = "login.html";

});



// ==========================================
// INICIAR
// ==========================================

actualizarResumen();