// ==========================================
// ELEMENTOS
// ==========================================

const tablaTiendas =
    document.getElementById("tablaTiendas");

const sinResultados =
    document.getElementById("sinResultados");

const buscarTienda =
    document.getElementById("buscarTienda");

const filtroEstado =
    document.getElementById("filtroEstado");

const totalTiendas =
    document.getElementById("totalTiendas");

const tiendasActivas =
    document.getElementById("tiendasActivas");

const tiendasInactivas =
    document.getElementById("tiendasInactivas");


// ==========================================
// MODAL
// ==========================================

const modalTienda =
    document.getElementById("modalTienda");

const informacionTienda =
    document.getElementById("informacionTienda");

const cerrarModal =
    document.getElementById("cerrarModal");


// ==========================================
// OBTENER TIENDAS
// ==========================================

function obtenerTiendas() {

    return JSON.parse(
        localStorage.getItem("tiendas")
    ) || [];

}


// ==========================================
// MOSTRAR TIENDAS
// ==========================================

function mostrarTiendas() {

    const tiendas =
        obtenerTiendas();


    const textoBusqueda =
        buscarTienda.value
            .trim()
            .toLowerCase();


    const estadoSeleccionado =
        filtroEstado.value;


    // ======================================
    // FILTRAR
    // ======================================

    const tiendasFiltradas =
        tiendas.filter(function (tienda) {


            const coincideBusqueda =

                tienda.nombreTienda
                    .toLowerCase()
                    .includes(textoBusqueda)

                ||

                tienda.nit
                    .toLowerCase()
                    .includes(textoBusqueda)

                ||

                tienda.barrio
                    .toLowerCase()
                    .includes(textoBusqueda)

                ||

                tienda.localidad
                    .toLowerCase()
                    .includes(textoBusqueda)

                ||

                tienda.direccion
                    .toLowerCase()
                    .includes(textoBusqueda);


            const coincideEstado =

                estadoSeleccionado === "todos"

                ||

                tienda.estado === estadoSeleccionado;


            return coincideBusqueda &&
                   coincideEstado;

        });


    // ======================================
    // LIMPIAR TABLA
    // ======================================

    tablaTiendas.innerHTML = "";


    // ======================================
    // MOSTRAR MENSAJE VACÍO
    // ======================================

    if (tiendasFiltradas.length === 0) {

        sinResultados.style.display = "block";

    }

    else {

        sinResultados.style.display = "none";

    }


    // ======================================
    // CREAR FILAS
    // ======================================

    tiendasFiltradas.forEach(function (tienda) {


        const fila =
            document.createElement("tr");


        const claseEstado =

            tienda.estado === "Activa"

                ? "estado-activa"

                : "estado-inactiva";


        const textoBoton =

            tienda.estado === "Activa"

                ? "Desactivar"

                : "Activar";


        const claseBoton =

            tienda.estado === "Activa"

                ? "boton-desactivar"

                : "boton-activar";


        fila.innerHTML = `

            <td>

                <strong>
                    ${tienda.nombreTienda}
                </strong>

            </td>


            <td>
                ${tienda.nit}
            </td>


            <td>
                ${tienda.telefono}
            </td>


            <td>
                ${tienda.direccion}
            </td>


            <td>
                ${tienda.barrio}
            </td>


            <td>
                ${tienda.localidad}
            </td>


            <td>
                ${tienda.nombreGerente}
            </td>


            <td>
                ${tienda.nombreVendedor}
            </td>


            <td>

                <span class="estado ${claseEstado}">

                    ${tienda.estado}

                </span>

            </td>


            <td>

                <div class="acciones-tabla">


                    <button
                        class="boton-tabla"
                        onclick="verTienda(${tienda.id})"
                    >

                        Ver

                    </button>


                    <button
                        class="boton-tabla ${claseBoton}"
                        onclick="cambiarEstado(${tienda.id})"
                    >

                        ${textoBoton}

                    </button>


                </div>

            </td>

        `;


        tablaTiendas.appendChild(fila);

    });


    actualizarResumen();

}


// ==========================================
// RESUMEN
// ==========================================

function actualizarResumen() {

    const tiendas =
        obtenerTiendas();


    const activas =
        tiendas.filter(function (tienda) {

            return tienda.estado === "Activa";

        });


    const inactivas =
        tiendas.filter(function (tienda) {

            return tienda.estado === "Inactiva";

        });


    totalTiendas.textContent =
        tiendas.length;


    tiendasActivas.textContent =
        activas.length;


    tiendasInactivas.textContent =
        inactivas.length;

}


// ==========================================
// VER TIENDA
// ==========================================

function verTienda(id) {

    const tiendas =
        obtenerTiendas();


    const tienda =
        tiendas.find(function (item) {

            return item.id === id;

        });


    if (!tienda) {

        alert(
            "No se encontró la información de la tienda."
        );

        return;

    }


    informacionTienda.innerHTML = `

        <div class="dato">

            <strong>
                Nombre de la tienda
            </strong>

            <span>
                ${tienda.nombreTienda}
            </span>

        </div>


        <div class="dato">

            <strong>
                NIT
            </strong>

            <span>
                ${tienda.nit}
            </span>

        </div>


        <div class="dato">

            <strong>
                Teléfono
            </strong>

            <span>
                ${tienda.telefono}
            </span>

        </div>


        <div class="dato">

            <strong>
                Correo
            </strong>

            <span>
                ${tienda.correo}
            </span>

        </div>


        <div class="dato">

            <strong>
                Dirección
            </strong>

            <span>
                ${tienda.direccion}
            </span>

        </div>


        <div class="dato">

            <strong>
                Barrio
            </strong>

            <span>
                ${tienda.barrio}
            </span>

        </div>


        <div class="dato">

            <strong>
                Localidad
            </strong>

            <span>
                ${tienda.localidad}
            </span>

        </div>


        <div class="dato">

            <strong>
                Nombre del gerente
            </strong>

            <span>
                ${tienda.nombreGerente}
            </span>

        </div>


        <div class="dato">

            <strong>
                Nombre del vendedor
            </strong>

            <span>
                ${tienda.nombreVendedor}
            </span>

        </div>


        <div class="dato">

            <strong>
                Estado
            </strong>

            <span>
                ${tienda.estado}
            </span>

        </div>

    `;


    modalTienda.style.display =
        "flex";

}


// ==========================================
// CAMBIAR ESTADO
// ==========================================

function cambiarEstado(id) {

    const tiendas =
        obtenerTiendas();


    const tienda =
        tiendas.find(function (item) {

            return item.id === id;

        });


    if (!tienda) {

        return;

    }


    if (tienda.estado === "Activa") {


        const confirmar =
            confirm(
                "¿Deseas desactivar esta tienda?"
            );


        if (!confirmar) {

            return;

        }


        tienda.estado = "Inactiva";


        alert(
            "La tienda ha sido desactivada."
        );

    }


    else {


        const confirmar =
            confirm(
                "¿Deseas activar nuevamente esta tienda?"
            );


        if (!confirmar) {

            return;

        }


        tienda.estado = "Activa";


        alert(
            "La tienda ha sido activada."
        );

    }


    localStorage.setItem(
        "tiendas",
        JSON.stringify(tiendas)
    );


    mostrarTiendas();

}


// ==========================================
// CERRAR MODAL
// ==========================================

cerrarModal.addEventListener(
    "click",
    function () {

        modalTienda.style.display =
            "none";

    }
);


// ==========================================
// CERRAR MODAL AL HACER CLIC AFUERA
// ==========================================

modalTienda.addEventListener(
    "click",
    function (evento) {

        if (evento.target === modalTienda) {

            modalTienda.style.display =
                "none";

        }

    }
);


// ==========================================
// BUSCADOR
// ==========================================

buscarTienda.addEventListener(
    "input",
    function () {

        mostrarTiendas();

    }
);


// ==========================================
// FILTRO
// ==========================================

filtroEstado.addEventListener(
    "change",
    function () {

        mostrarTiendas();

    }
);


// ==========================================
// NUEVA TIENDA
// ==========================================

document.getElementById("nuevaTienda")
    .addEventListener(
        "click",
        function () {

            window.location.href =
                "registro-tienda.html";

        }
    );


// ==========================================
// REGISTRAR DESDE PÁGINA VACÍA
// ==========================================

document.getElementById("registrarDesdeVacio")
    .addEventListener(
        "click",
        function () {

            window.location.href =
                "registro-tienda.html";

        }
    );


// ==========================================
// VOLVER AL PANEL
// ==========================================

document.getElementById("volverGestor")
    .addEventListener(
        "click",
        function () {

            window.location.href =
                "gestor.html";

        }
    );


// ==========================================
// INICIAR
// ==========================================

mostrarTiendas();