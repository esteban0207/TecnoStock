
document.addEventListener("DOMContentLoaded", function () {

    const formulario = document.getElementById("formProducto");
    const cajaError = document.getElementById("errorJavaScript");

    if (!formulario || !cajaError) {
        return;
    }

    formulario.addEventListener("submit", function (event) {

        const errores = [];

        const codigo = formulario.elements["codigo"].value.trim();
        const nombre = formulario.elements["nombre"].value.trim();
        const categoria = formulario.elements["id_categoria"].value;

        const precioTexto = formulario.elements["precio"].value.trim();
        const stockTexto = formulario.elements["stock_actual"].value.trim();
        const minimoTexto = formulario.elements["stock_minimo"].value.trim();

        const precio = Number(precioTexto);
        const stock = Number(stockTexto);
        const minimo = Number(minimoTexto);

        if (codigo === "") {
            errores.push("El código es obligatorio.");
        } else if ([...codigo].length > 50) {
            errores.push("El código no puede superar 50 caracteres.");
        }

        if (nombre === "") {
            errores.push("El nombre es obligatorio.");
        } else if ([...nombre].length > 150) {
            errores.push("El nombre no puede superar 150 caracteres.");
        }

        if (
            categoria === "" ||
            !Array.from(formulario.elements["id_categoria"].options)
                .some(opcion =>
                    opcion.value === categoria &&
                    opcion.value !== ""
                )
        ) {
            errores.push("Debes seleccionar una categoría válida.");
        }

        if (
            !/^\d+(\.\d{1,2})?$/.test(precioTexto) ||
            !Number.isFinite(precio) ||
            precio > 9999999999.99
        ) {
            errores.push(
                "El precio debe ser no negativo y tener máximo dos decimales."
            );
        }

        if (
            !/^\d+$/.test(stockTexto) ||
            !Number.isSafeInteger(stock) ||
            stock > 2147483647
        ) {
            errores.push(
                "El stock actual debe ser un entero no negativo."
            );
        }

        if (
            !/^\d+$/.test(minimoTexto) ||
            !Number.isSafeInteger(minimo) ||
            minimo > 2147483647
        ) {
            errores.push(
                "El stock mínimo debe ser un entero no negativo."
            );
        }

        if (errores.length > 0) {

            event.preventDefault();

            cajaError.replaceChildren();

            const titulo = document.createElement("strong");
            titulo.textContent = "No se pudo registrar el producto";

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
