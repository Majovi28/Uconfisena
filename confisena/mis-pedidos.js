document.addEventListener("DOMContentLoaded", function () {

    const listaPedidos = document.getElementById("listaPedidos");
    const sinPedidos = document.getElementById("sinPedidos");
    const cerrarSesion = document.getElementById("cerrarSesion");

    let pedidos = JSON.parse(localStorage.getItem("pedidos")) || [];

    // Mostrar únicamente los pedidos del usuario que inició sesión
    const idUsuario = localStorage.getItem("idUsuario");

    if (idUsuario) {
        pedidos = pedidos.filter(pedido =>
            pedido.id_usuario == idUsuario
        );
    }

    if (pedidos.length === 0) {
        sinPedidos.style.display = "block";
        listaPedidos.style.display = "none";
        return;
    }

    sinPedidos.style.display = "none";

    pedidos.forEach(function (pedido) {

        const tarjeta = document.createElement("div");
        tarjeta.classList.add("pedido");

        let claseEstado = "pendiente";

        if (pedido.estado_pedido === "Pagado") {
            claseEstado = "pagado";
        }

        if (pedido.estado_pedido === "Entregado") {
            claseEstado = "entregado";
        }

        tarjeta.innerHTML = `
            <div class="pedido-superior">

                <div>
                    <h3>${pedido.id_pedido}</h3>
                    <p class="fecha">
                        Fecha del pedido: ${pedido.fecha_pedido}
                    </p>
                </div>

                <span class="estado ${claseEstado}">
                    ${pedido.estado_pedido}
                </span>

            </div>

            <div class="informacion-pedido">

                <div class="dato">
                    <strong>Producto</strong>
                    <span>${pedido.producto}</span>
                </div>

                <div class="dato">
                    <strong>Talla</strong>
                    <span>${pedido.talla}</span>
                </div>

                <div class="dato">
                    <strong>Cantidad</strong>
                    <span>${pedido.cantidad}</span>
                </div>

                <div class="dato">
                    <strong>Método de pago</strong>
                    <span>${pedido.metodo_pago}</span>
                </div>

                <div class="dato">
                    <strong>Fecha de entrega</strong>
                    <span>${pedido.fecha_entrega}</span>
                </div>

                <div class="dato">
                    <strong>Estado del pago</strong>
                    <span>${pedido.estado_pago}</span>
                </div>

            </div>

            <div class="total">
                Total: $${Number(pedido.total).toLocaleString("es-CO")}
            </div>
        `;

        listaPedidos.appendChild(tarjeta);
    });


    // CERRAR SESIÓN

    cerrarSesion.addEventListener("click", function (event) {

        event.preventDefault();

        localStorage.removeItem("sesionActiva");
        localStorage.removeItem("rolUsuario");
        localStorage.removeItem("idUsuario");
        localStorage.removeItem("nombreUsuario");
        localStorage.removeItem("correoUsuario");

        window.location.href = "index.html";
    });

});