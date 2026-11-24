
document.addEventListener("DOMContentLoaded", function () {

    const formulario = document.getElementById("loginForm");
    const cajaError = document.getElementById("errorJavaScriptLogin");

    if (!formulario || !cajaError) {
        return;
    }

    formulario.addEventListener("submit", function (event) {

        const errores = [];

        const correo = formulario.elements["correo"].value.trim();
        const clave = formulario.elements["clave"].value;

        if (correo === "") {

            errores.push("El correo electrónico es obligatorio.");

        } else {

            if (correo.length > 150) {
                errores.push(
                    "El correo no puede superar 150 caracteres."
                );
            }

            const formatoCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!formatoCorreo.test(correo)) {
                errores.push(
                    "Ingresa un correo electrónico válido."
                );
            }

        }

        if (clave === "") {
            errores.push("La contraseña es obligatoria.");
        }

        if (errores.length > 0) {

            event.preventDefault();

            cajaError.replaceChildren();

            const titulo = document.createElement("strong");
            titulo.textContent = "No se pudo iniciar sesión";

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

        } else {

            cajaError.style.display = "none";
            cajaError.replaceChildren();

        }

    });

});
