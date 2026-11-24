
document.addEventListener("DOMContentLoaded", function () {

    const formulario = document.getElementById("formFiltrosHistorial");
    const resultados = document.getElementById("resultadosHistorial");

    if (!formulario || !resultados) {
        return;
    }

    let controlador = null;

    const paginaHistorial = new URL(
        "historial.php",
        window.location.href
    );

    function esUrlHistorial(url) {

        const destino = new URL(url, window.location.href);

        return (
            destino.origin === paginaHistorial.origin &&
            destino.pathname === paginaHistorial.pathname
        );
    }

    function mostrarCarga() {

        resultados.style.opacity = "0.5";
        resultados.setAttribute("aria-busy", "true");
    }

    function ocultarCarga() {

        resultados.style.opacity = "1";
        resultados.removeAttribute("aria-busy");
    }

    async function cargarHistorial(url, guardarHistorial = true) {

        if (!esUrlHistorial(url)) {
            return;
        }

        if (controlador) {
            controlador.abort();
        }

        controlador = new AbortController();

        const solicitudActual = controlador;

        mostrarCarga();

        try {

            const respuesta = await fetch(url, {
                method: "GET",
                credentials: "same-origin",
                signal: solicitudActual.signal,
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                }
            });

            if (!respuesta.ok) {
                throw new Error(
                    "No se pudo cargar el historial."
                );
            }

            if (!esUrlHistorial(respuesta.url)) {
                throw new Error(
                    "La respuesta no pertenece al historial."
                );
            }

            const html = await respuesta.text();

            const parser = new DOMParser();

            const documento = parser.parseFromString(
                html,
                "text/html"
            );

            const nuevosResultados = documento.getElementById(
                "resultadosHistorial"
            );

            const nuevoFormulario = documento.getElementById(
                "formFiltrosHistorial"
            );

            if (!nuevosResultados || !nuevoFormulario) {
                throw new Error(
                    "La respuesta no contiene el historial esperado."
                );
            }

            if (solicitudActual.signal.aborted) {
                return;
            }

            resultados.innerHTML = nuevosResultados.innerHTML;

            ["buscar", "tipo", "fecha"].forEach(function (campo) {

                const actual = formulario.elements[campo];
                const nuevo = nuevoFormulario.elements[campo];

                if (actual && nuevo) {
                    actual.value = nuevo.value;
                }

            });

            if (guardarHistorial) {

                window.history.pushState(
                    {},
                    "",
                    respuesta.url
                );
            }

            resultados.scrollIntoView({
                behavior: "smooth",
                block: "start"
            });

        } catch (error) {

            if (error.name === "AbortError") {
                return;
            }

            if (solicitudActual.signal.aborted) {
                return;
            }

            console.error(error);

            window.location.assign(url);

        } finally {

            if (controlador === solicitudActual) {

                ocultarCarga();
                controlador = null;
            }

        }
    }

    formulario.addEventListener("submit", function (event) {

        event.preventDefault();

        const datos = new FormData(formulario);
        const parametros = new URLSearchParams(datos);

        parametros.set("pagina", "1");

        cargarHistorial(
            paginaHistorial.pathname + "?" + parametros.toString()
        );

    });

    resultados.addEventListener("click", function (event) {

        const objetivo = event.target;

        if (!(objetivo instanceof Element)) {
            return;
        }

        const enlace = objetivo.closest(
            ".pagination a.page-link"
        );

        if (!enlace) {
            return;
        }

        if (!esUrlHistorial(enlace.href)) {
            return;
        }

        event.preventDefault();

        cargarHistorial(enlace.href);

    });

    const limpiar = document.getElementById("limpiarHistorial");

    if (limpiar) {

        limpiar.addEventListener("click", function (event) {

            if (!esUrlHistorial(limpiar.href)) {
                return;
            }

            event.preventDefault();

            cargarHistorial(limpiar.href);

        });

    }

    window.addEventListener("popstate", function () {

        cargarHistorial(
            window.location.href,
            false
        );

    });

});
