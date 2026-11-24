
document.addEventListener("DOMContentLoaded", function () {

    const formulario = document.getElementById("formMovimiento");
    const cajaError = document.getElementById("errorJavaScriptMovimiento");

    const producto = document.getElementById("id_producto");
    const tipo = document.getElementById("tipo");
    const cantidad = document.getElementById("cantidad");
    const stockDisponible = document.getElementById("stockDisponible");

    const STOCK_MAXIMO = 2147483647;

    if (
        !formulario ||
        !cajaError ||
        !producto ||
        !tipo ||
        !cantidad ||
        !stockDisponible
    ) {
        return;
    }

    function obtenerStock(opcion) {

        if (!opcion) {
            return null;
        }

        const stockTexto = opcion.dataset.stock;

        if (
            typeof stockTexto !== "string" ||
            !/^\d+$/.test(stockTexto)
        ) {
            return null;
        }

        const stock = Number(stockTexto);

        if (
            !Number.isSafeInteger(stock) ||
            stock < 0 ||
            stock > STOCK_MAXIMO
        ) {
            return null;
        }

        return stock;
    }

    function actualizarStock() {

        const opcion = producto.selectedOptions[0];

        if (!opcion || producto.value === "") {

            stockDisponible.textContent =
                "Selecciona un producto para consultar su stock.";

            return;
        }

        const stock = obtenerStock(opcion);

        if (stock === null) {

            stockDisponible.textContent =
                "No se pudo consultar el stock del producto.";

            return;
        }

        stockDisponible.textContent =
            "Stock disponible: " + stock + " unidades.";
    }

    producto.addEventListener("change", actualizarStock);

    actualizarStock();

    formulario.addEventListener("submit", function (event) {

        const errores = [];

        const opcion = producto.selectedOptions[0];

        const productoExiste =
            producto.value !== "" &&
            Array.from(producto.options).some(function (elemento) {
                return elemento.value === producto.value;
            });

        const tipoSeleccionado = tipo.value;

        const cantidadTexto = cantidad.value.trim();
        const cantidadNumero = Number(cantidadTexto);

        if (!productoExiste) {

            errores.push(
                "Debes seleccionar un producto válido."
            );
        }

        if (
            tipoSeleccionado !== "ENTRADA" &&
            tipoSeleccionado !== "SALIDA"
        ) {

            errores.push(
                "Debes seleccionar un tipo de movimiento."
            );
        }

        const cantidadValida =
            /^\d+$/.test(cantidadTexto) &&
            Number.isSafeInteger(cantidadNumero) &&
            cantidadNumero >= 1 &&
            cantidadNumero <= STOCK_MAXIMO;

        if (!cantidadValida) {

            errores.push(
                "La cantidad debe ser un número entero mayor que cero, " +
                "sin superar 2.147.483.647."
            );
        }

        if (productoExiste && cantidadValida) {

            const stockActual = obtenerStock(opcion);

            if (stockActual === null) {

                errores.push(
                    "No se pudo verificar el stock del producto."
                );

            } else {

                if (
                    tipoSeleccionado === "SALIDA" &&
                    cantidadNumero > stockActual
                ) {

                    errores.push(
                        "Stock insuficiente. Solo hay " +
                        stockActual +
                        " unidades disponibles."
                    );
                }

                if (
                    tipoSeleccionado === "ENTRADA" &&
                    cantidadNumero > STOCK_MAXIMO - stockActual
                ) {

                    errores.push(
                        "La entrada supera el stock máximo permitido."
                    );
                }
            }
        }

        if (errores.length > 0) {

            event.preventDefault();

            cajaError.replaceChildren();

            const titulo = document.createElement("strong");
            titulo.textContent = "No se pudo registrar el movimiento";

            const lista = document.createElement("ul");
            lista.className = "mb-0 mt-2";

            errores.forEach(function (error) {

                const elemento = document.createElement("li");
                elemento.textContent = error;
                lista.appendChild(elemento);
            });

            cajaError.appendChild(titulo);
            cajaError.appendChild(lista);

            cajaError.style.display = "block";

            cajaError.scrollIntoView({
                behavior: "smooth",
                block: "center"
            });

        } else {

            cajaError.style.display = "none";
            cajaError.replaceChildren();
        }

    });

});
