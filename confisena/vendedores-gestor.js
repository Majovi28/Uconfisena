// ==========================================
// OBTENER ELEMENTOS
// ==========================================

const tablaVendedores =
    document.getElementById("tablaVendedores");

const sinResultados =
    document.getElementById("sinResultados");

const buscarVendedor =
    document.getElementById("buscarVendedor");

const filtroEstado =
    document.getElementById("filtroEstado");

const totalVendedores =
    document.getElementById("totalVendedores");

const vendedoresActivos =
    document.getElementById("vendedoresActivos");

const vendedoresInactivos =
    document.getElementById("vendedoresInactivos");



// ==========================================
// MODAL
// ==========================================

const modalVendedor =
    document.getElementById("modalVendedor");

const informacionVendedor =
    document.getElementById("informacionVendedor");

const cerrarModal =
    document.getElementById("cerrarModal");



// ==========================================
// OBTENER VENDEDORES
// ==========================================

function obtenerVendedores() {

    return JSON.parse(
        localStorage.getItem("vendedores")
    ) || [];

}



// ==========================================
// MOSTRAR VENDEDORES
// ==========================================

function mostrarVendedores() {

    const vendedores =
        obtenerVendedores();


    const textoBusqueda =
        buscarVendedor.value
            .trim()
            .toLowerCase();


    const estadoSeleccionado =
        filtroEstado.value;



    // ======================================
    // FILTRAR
    // ======================================

    const vendedoresFiltrados =
        vendedores.filter(function (vendedor) {


            const coincideBusqueda =

                vendedor.nombre
                    .toLowerCase()
                    .includes(textoBusqueda)

                ||

                vendedor.usuario
                    .toLowerCase()
                    .includes(textoBusqueda)

                ||

                vendedor.correo
                    .toLowerCase()
                    .includes(textoBusqueda)

                ||

                vendedor.tienda
                    .toLowerCase()
                    .includes(textoBusqueda);



            const coincideEstado =

                estadoSeleccionado === "todos"

                ||

                vendedor.estado === estadoSeleccionado;



            return coincideBusqueda &&
                   coincideEstado;

        });



    // ======================================
    // LIMPIAR TABLA
    // ======================================

    tablaVendedores.innerHTML = "";



    // ======================================
    // MOSTRAR / OCULTAR TABLA
    // ======================================

    if (vendedoresFiltrados.length === 0) {

        sinResultados.style.display = "block";

    } else {

        sinResultados.style.display = "none";

    }



    // ======================================
    // CREAR FILAS
    // ======================================

    vendedoresFiltrados.forEach(function (vendedor) {


        const fila =
            document.createElement("tr");


        const claseEstado =
            vendedor.estado === "Activo"
                ? "estado-activo"
                : "estado-inactivo";


        const textoBoton =
            vendedor.estado === "Activo"
                ? "Desactivar"
                : "Activar";


        const claseBoton =
            vendedor.estado === "Activo"
                ? "boton-desactivar"
                : "boton-activar";



        fila.innerHTML = `

            <td>

                <strong>
                    ${vendedor.nombre}
                </strong>

            </td>


            <td>

                ${vendedor.usuario}

            </td>


            <td>

                ${vendedor.tienda}

            </td>


            <td>

                ${vendedor.correo}

            </td>


            <td>

                ${vendedor.telefono}

            </td>


            <td>

                <span class="estado ${claseEstado}">

                    ${vendedor.estado}

                </span>

            </td>


            <td>

                <div class="acciones-tabla">


                    <button
                        class="boton-tabla"
                        onclick="verVendedor(${vendedor.id})"
                    >

                        Ver

                    </button>


                    <button
                        class="boton-tabla ${claseBoton}"
                        onclick="cambiarEstado(${vendedor.id})"
                    >

                        ${textoBoton}

                    </button>


                </div>

            </td>

        `;


        tablaVendedores.appendChild(fila);

    });



    // ======================================
    // ACTUALIZAR RESUMEN
    // ======================================

    actualizarResumen();

}



// ==========================================
// ACTUALIZAR RESUMEN
// ==========================================

function actualizarResumen() {

    const vendedores =
        obtenerVendedores();


    const activos =
        vendedores.filter(function (vendedor) {

            return vendedor.estado === "Activo";

        });


    const inactivos =
        vendedores.filter(function (vendedor) {

            return vendedor.estado === "Inactivo";

        });


    totalVendedores.textContent =
        vendedores.length;


    vendedoresActivos.textContent =
        activos.length;


    vendedoresInactivos.textContent =
        inactivos.length;

}



// ==========================================
// VER INFORMACIÓN
// ==========================================

function verVendedor(id) {

    const vendedores =
        obtenerVendedores();


    const vendedor =
        vendedores.find(function (item) {

            return item.id === id;

        });


    if (!vendedor) {

        alert(
            "No se encontró la información del vendedor."
        );

        return;

    }



    informacionVendedor.innerHTML = `

        <div class="dato">

            <strong>
                Nombre completo
            </strong>

            <span>
                ${vendedor.nombre}
            </span>

        </div>


        <div class="dato">

            <strong>
                Documento
            </strong>

            <span>
                ${vendedor.documento}
            </span>

        </div>


        <div class="dato">

            <strong>
                Correo electrónico
            </strong>

            <span>
                ${vendedor.correo}
            </span>

        </div>


        <div class="dato">

            <strong>
                Teléfono
            </strong>

            <span>
                ${vendedor.telefono}
            </span>

        </div>


        <div class="dato">

            <strong>
                Tienda
            </strong>

            <span>
                ${vendedor.tienda}
            </span>

        </div>


        <div class="dato">

            <strong>
                Nombre de usuario
            </strong>

            <span>
                ${vendedor.usuario}
            </span>

        </div>


        <div class="dato">

            <strong>
                Rol
            </strong>

            <span>
                ${vendedor.rol}
            </span>

        </div>


        <div class="dato">

            <strong>
                Estado
            </strong>

            <span>
                ${vendedor.estado}
            </span>

        </div>

    `;


    modalVendedor.style.display =
        "flex";

}



// ==========================================
// CAMBIAR ESTADO
// ==========================================

function cambiarEstado(id) {

    const vendedores =
        obtenerVendedores();


    const vendedor =
        vendedores.find(function (item) {

            return item.id === id;

        });


    if (!vendedor) {

        return;

    }



    if (vendedor.estado === "Activo") {

        const confirmar =
            confirm(
                "¿Deseas desactivar a este vendedor?"
            );


        if (!confirmar) {

            return;

        }


        vendedor.estado = "Inactivo";


        alert(
            "El vendedor ha sido desactivado."
        );

    }

    else {

        const confirmar =
            confirm(
                "¿Deseas activar nuevamente a este vendedor?"
            );


        if (!confirmar) {

            return;

        }


        vendedor.estado = "Activo";


        alert(
            "El vendedor ha sido activado."
        );

    }



    localStorage.setItem(
        "vendedores",
        JSON.stringify(vendedores)
    );


    mostrarVendedores();

}



// ==========================================
// CERRAR MODAL
// ==========================================

cerrarModal.addEventListener(
    "click",
    function () {

        modalVendedor.style.display =
            "none";

    }
);



// ==========================================
// CERRAR MODAL AL HACER CLIC AFUERA
// ==========================================

modalVendedor.addEventListener(
    "click",
    function (evento) {

        if (evento.target === modalVendedor) {

            modalVendedor.style.display =
                "none";

        }

    }
);



// ==========================================
// BUSCADOR
// ==========================================

buscarVendedor.addEventListener(
    "input",
    function () {

        mostrarVendedores();

    }
);



// ==========================================
// FILTRO
// ==========================================

filtroEstado.addEventListener(
    "change",
    function () {

        mostrarVendedores();

    }
);



// ==========================================
// NUEVO VENDEDOR
// ==========================================

document.getElementById("nuevoVendedor")
    .addEventListener("click", function () {

        window.location.href =
            "registro-vendedor.html";

    });



// ==========================================
// REGISTRAR DESDE PÁGINA VACÍA
// ==========================================

document.getElementById("registrarDesdeVacio")
    .addEventListener("click", function () {

        window.location.href =
            "registro-vendedor.html";

    });



// ==========================================
// VOLVER AL PANEL
// ==========================================

document.getElementById("volverGestor")
    .addEventListener("click", function () {

        window.location.href =
            "gestor.html";

    });



// ==========================================
// INICIAR PÁGINA
// ==========================================

mostrarVendedores();