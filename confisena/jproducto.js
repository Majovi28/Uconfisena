
document.addEventListener("DOMContentLoaded", () => {
    // Este código solo se ejecuta en la ficha de detalle del producto.
    // No interfiere con los formularios de pago o apartado.
    const ficha = document.querySelector(".producto-seccion .producto");

    if (!ficha) {
        return;
    }

    const productoId = ficha.dataset.productId;
    const productoNombre = ficha.dataset.productName;
    const precioUnitario = Number(ficha.dataset.productPrice);
    const cantidadMaxima = Math.max(1, Number(ficha.dataset.maxStock) || 10);

    const botonesTalla = document.querySelectorAll(".producto-info .talla");
    const botonMenos = document.querySelector(".contador button:first-child");
    const botonMas = document.querySelector(".contador button:last-child");
    const cantidadTexto = document.querySelector(".contador span");
    const precioElemento = document.querySelector(".producto-info .precio");

    const botonComprar = document.querySelector(".boton.comprar");
    const botonApartar = document.querySelector(".boton.apartar");

    let tallaSeleccionada = null;
    let cantidad = 1;

    // Seleccionar la talla marcada inicialmente en PHP.
    const tallaInicial = document.querySelector(
        ".producto-info .talla.seleccionada"
    );

    if (tallaInicial) {
        tallaSeleccionada = tallaInicial.textContent.trim();
    }

    // Crear el elemento para mostrar el total.
    let totalElemento = document.querySelector(".precio-total");

    if (!totalElemento && precioElemento) {
        totalElemento = document.createElement("p");
        totalElemento.className = "precio-total";
        precioElemento.insertAdjacentElement("afterend", totalElemento);
    }

    function actualizarTotal() {
        if (!totalElemento) return;

        const total = precioUnitario * cantidad;

        totalElemento.textContent =
            "Total: $" + total.toLocaleString("es-CO");
    }

    function actualizarCantidad() {
        if (cantidadTexto) {
            cantidadTexto.textContent = String(cantidad);
        }

        if (botonMenos) {
            botonMenos.disabled = cantidad <= 1;
        }

        if (botonMas) {
            botonMas.disabled = cantidad >= cantidadMaxima;
        }

        actualizarTotal();
    }

    // Seleccionar talla.
    botonesTalla.forEach((boton) => {
        boton.addEventListener("click", () => {
            botonesTalla.forEach((item) => {
                item.classList.remove("seleccionada");
            });

            boton.classList.add("seleccionada");
            tallaSeleccionada = boton.textContent.trim();
        });
    });

    // Cambiar cantidad.
    if (botonMas) {
        botonMas.addEventListener("click", () => {
            if (cantidad < cantidadMaxima) {
                cantidad++;
                actualizarCantidad();
            }
        });
    }

    if (botonMenos) {
        botonMenos.addEventListener("click", () => {
            if (cantidad > 1) {
                cantidad--;
                actualizarCantidad();
            }
        });
    }

    // Guardar la selección para pago o apartado.
    function guardarSeleccion(destino, evento) {
        evento.preventDefault();

        if (!tallaSeleccionada) {
            alert("Por favor, selecciona una talla.");
            return;
        }

        if (!productoId || !productoNombre || !Number.isFinite(precioUnitario)) {
            alert("No fue posible identificar el producto. Regresa al catálogo.");
            return;
        }

        localStorage.setItem("id_producto", String(productoId));
        localStorage.setItem("producto", productoNombre);
        localStorage.setItem("talla", tallaSeleccionada);
        localStorage.setItem("cantidad", String(cantidad));
        localStorage.setItem("precio", String(precioUnitario));

        window.location.href = destino;
    }

    if (botonComprar && botonComprar.tagName === "A") {
        botonComprar.addEventListener("click", (evento) => {
            guardarSeleccion("pago.php", evento);
        });
    }

    if (botonApartar && botonApartar.tagName === "A") {
        botonApartar.addEventListener("click", (evento) => {
            guardarSeleccion("apartado.php", evento);
        });
    }

    actualizarCantidad();
});
