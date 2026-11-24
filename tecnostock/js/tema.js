
(function () {
    const claveTema = "temaTecnoStock";
    const raiz = document.documentElement;

    function obtenerTema() {
        try {
            const tema = localStorage.getItem(claveTema);

            if (tema === "dark" || tema === "light") {
                return tema;
            }
        } catch (error) {}

        return "light";
    }

    function aplicarTema(tema) {
        raiz.setAttribute("data-bs-theme", tema);
        actualizarBoton();
    }

    function actualizarBoton() {
        const boton = document.getElementById("botonTema");

        if (!boton) {
            return;
        }

        const oscuro = raiz.getAttribute("data-bs-theme") === "dark";

        boton.textContent = oscuro
            ? "☀️ Modo claro"
            : "🌙 Modo oscuro";
    }

    aplicarTema(obtenerTema());

    document.addEventListener("DOMContentLoaded", function () {
        actualizarBoton();

        const boton = document.getElementById("botonTema");

        if (boton) {
            boton.addEventListener("click", function () {
                const temaActual = raiz.getAttribute("data-bs-theme");

                const nuevoTema = temaActual === "dark"
                    ? "light"
                    : "dark";

                try {
                    localStorage.setItem(claveTema, nuevoTema);
                } catch (error) {}

                aplicarTema(nuevoTema);
            });
        }
    });

    window.addEventListener("storage", function (evento) {
        if (evento.key === claveTema) {
            aplicarTema(obtenerTema());
        }
    });
})();
