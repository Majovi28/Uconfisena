// =====================================================
// OBTENER REPORTES
// =====================================================

function obtenerReportes() {

    return JSON.parse(
        localStorage.getItem("reportes")
    ) || [];

}


// =====================================================
// VARIABLES
// =====================================================

let reporteSeleccionado = null;


// =====================================================
// ELEMENTOS
// =====================================================

const tablaReportes =
    document.getElementById("tablaReportes");

const sinReportes =
    document.getElementById("sinReportes");

const buscarReporte =
    document.getElementById("buscarReporte");

const filtroEstado =
    document.getElementById("filtroEstado");

const modalReporte =
    document.getElementById("modalReporte");

const cerrarModal =
    document.getElementById("cerrarModal");

const cancelarModal =
    document.getElementById("cancelarModal");

const guardarEstado =
    document.getElementById("guardarEstado");

const volverGestor =
    document.getElementById("volverGestor");


// =====================================================
// MOSTRAR REPORTES
// =====================================================

function mostrarReportes() {

    const reportes = obtenerReportes();

    const textoBusqueda =
        buscarReporte.value.toLowerCase().trim();

    const estadoSeleccionado =
        filtroEstado.value;


    const reportesFiltrados = reportes.filter(function(reporte) {

        const usuario =
            (reporte.usuario || "").toLowerCase();

        const correo =
            (reporte.correo || "").toLowerCase();

        const asunto =
            (reporte.asunto || "").toLowerCase();

        const descripcion =
            (reporte.descripcion || "").toLowerCase();


        const coincideBusqueda =
            usuario.includes(textoBusqueda) ||
            correo.includes(textoBusqueda) ||
            asunto.includes(textoBusqueda) ||
            descripcion.includes(textoBusqueda);


        const coincideEstado =
            estadoSeleccionado === "Todos" ||
            reporte.estado === estadoSeleccionado;


        return coincideBusqueda && coincideEstado;

    });


    tablaReportes.innerHTML = "";


    // =================================================
    // SI NO HAY REPORTES
    // =================================================

    if (reportesFiltrados.length === 0) {

        sinReportes.style.display = "block";

    } else {

        sinReportes.style.display = "none";


        reportesFiltrados.forEach(function(reporte) {

            const fila =
                document.createElement("tr");


            let claseEstado =
                "estado-pendiente";


            if (reporte.estado === "En revisión") {

                claseEstado =
                    "estado-revision";

            }


            if (reporte.estado === "Resuelto") {

                claseEstado =
                    "estado-resuelto";

            }


            fila.innerHTML = `

                <td>
                    ${reporte.usuario || "Sin nombre"}
                </td>

                <td>
                    ${reporte.asunto || "Sin asunto"}
                </td>

                <td>
                    ${reporte.descripcion || "Sin descripción"}
                </td>

                <td>
                    ${reporte.fecha || "Sin fecha"}
                </td>

                <td>

                    <span class="estado ${claseEstado}">
                        ${reporte.estado || "Pendiente"}
                    </span>

                </td>

                <td>

                    <button
                        class="boton-ver"
                        onclick="verReporte(${reporte.id})"
                    >
                        Ver
                    </button>

                </td>

            `;


            tablaReportes.appendChild(fila);

        });

    }


    actualizarResumen();

}


// =====================================================
// ACTUALIZAR RESUMEN
// =====================================================

function actualizarResumen() {

    const reportes = obtenerReportes();


    const pendientes =
        reportes.filter(function(reporte) {

            return reporte.estado === "Pendiente";

        });


    const resueltos =
        reportes.filter(function(reporte) {

            return reporte.estado === "Resuelto";

        });


    document.getElementById(
        "totalReportes"
    ).textContent = reportes.length;


    document.getElementById(
        "reportesPendientes"
    ).textContent = pendientes.length;


    document.getElementById(
        "reportesResueltos"
    ).textContent = resueltos.length;

}


// =====================================================
// VER REPORTE
// =====================================================

function verReporte(id) {

    const reportes = obtenerReportes();


    const reporte =
        reportes.find(function(item) {

            return item.id === id;

        });


    if (!reporte) {

        alert("No se encontró el reporte.");

        return;

    }


    reporteSeleccionado = id;


    document.getElementById(
        "detalleUsuario"
    ).textContent =
        reporte.usuario || "Sin nombre";


    document.getElementById(
        "detalleCorreo"
    ).textContent =
        reporte.correo || "Sin correo";


    document.getElementById(
        "detalleAsunto"
    ).textContent =
        reporte.asunto || "Sin asunto";


    document.getElementById(
        "detalleFecha"
    ).textContent =
        reporte.fecha || "Sin fecha";


    document.getElementById(
        "detalleDescripcion"
    ).textContent =
        reporte.descripcion || "Sin descripción";


    document.getElementById(
        "detalleEstado"
    ).value =
        reporte.estado || "Pendiente";


    modalReporte.style.display = "flex";

}


// =====================================================
// GUARDAR CAMBIO DE ESTADO
// =====================================================

guardarEstado.addEventListener("click", function() {

    if (reporteSeleccionado === null) {

        return;

    }


    const reportes = obtenerReportes();


    const indice =
        reportes.findIndex(function(reporte) {

            return reporte.id === reporteSeleccionado;

        });


    if (indice === -1) {

        alert("No se encontró el reporte.");

        return;

    }


    const nuevoEstado =
        document.getElementById(
            "detalleEstado"
        ).value;


    reportes[indice].estado =
        nuevoEstado;


    localStorage.setItem(
        "reportes",
        JSON.stringify(reportes)
    );


    alert(
        "El estado del reporte fue actualizado correctamente."
    );


    cerrarVentana();

    mostrarReportes();

});


// =====================================================
// CERRAR MODAL
// =====================================================

function cerrarVentana() {

    modalReporte.style.display = "none";

    reporteSeleccionado = null;

}


cerrarModal.addEventListener(
    "click",
    cerrarVentana
);


cancelarModal.addEventListener(
    "click",
    cerrarVentana
);


// =====================================================
// CERRAR MODAL AL HACER CLIC AFUERA
// =====================================================

modalReporte.addEventListener(
    "click",
    function(evento) {

        if (evento.target === modalReporte) {

            cerrarVentana();

        }

    }
);


// =====================================================
// BUSCADOR
// =====================================================

buscarReporte.addEventListener(
    "input",
    mostrarReportes
);


// =====================================================
// FILTRO
// =====================================================

filtroEstado.addEventListener(
    "change",
    mostrarReportes
);


// =====================================================
// VOLVER AL PANEL DEL GESTOR
// =====================================================

volverGestor.addEventListener(
    "click",
    function() {

        window.location.href =
            "gestor.html";

    }
);


// =====================================================
// CARGAR INFORMACIÓN
// =====================================================

mostrarReportes();