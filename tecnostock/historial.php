
<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

function escapar($valor) {
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        "UTF-8"
    );
}

$buscar = $_GET["buscar"] ?? "";
$tipo = $_GET["tipo"] ?? "";
$fecha = $_GET["fecha"] ?? "";

$buscar = is_string($buscar) ? trim($buscar) : "";
$tipo = is_string($tipo) ? $tipo : "";
$fecha = is_string($fecha) ? $fecha : "";

$por_pagina = 20;

$pagina = filter_input(
    INPUT_GET,
    "pagina",
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 1]]
);

if ($pagina === false || $pagina === null) {
    $pagina = 1;
}

$condiciones = " WHERE 1 = 1";
$parametros = [];

if ($buscar !== "") {

    $condiciones .= " AND (
        p.codigo LIKE :codigo
        OR p.nombre LIKE :nombre
    )";

    $termino = "%" . $buscar . "%";

    $parametros[":codigo"] = $termino;
    $parametros[":nombre"] = $termino;
}

if (in_array($tipo, ["ENTRADA", "SALIDA"], true)) {

    $condiciones .= " AND m.tipo = :tipo";
    $parametros[":tipo"] = $tipo;
}

if ($fecha !== "") {

    $fecha_objeto = DateTimeImmutable::createFromFormat(
        "!Y-m-d",
        $fecha
    );

    if (
        $fecha_objeto !== false &&
        $fecha_objeto->format("Y-m-d") === $fecha
    ) {

        $condiciones .= "
            AND m.fecha_hora >= :fecha_inicio
            AND m.fecha_hora < :fecha_fin
        ";

        $parametros[":fecha_inicio"] =
            $fecha_objeto->format("Y-m-d 00:00:00");

        $parametros[":fecha_fin"] =
            $fecha_objeto
                ->modify("+1 day")
                ->format("Y-m-d 00:00:00");
    }
}

$desde = " FROM movimiento m
           INNER JOIN producto p
               ON m.id_producto = p.id_producto
           INNER JOIN usuario u
               ON m.id_usuario = u.id_usuario";

$sql_total = "SELECT COUNT(*)
              $desde
              $condiciones";

$consulta_total = $conexion->prepare($sql_total);

foreach ($parametros as $clave => $valor) {
    $consulta_total->bindValue(
        $clave,
        $valor,
        PDO::PARAM_STR
    );
}

$consulta_total->execute();

$total_resultados = (int)$consulta_total->fetchColumn();

$total_paginas = max(
    1,
    (int)ceil($total_resultados / $por_pagina)
);

$pagina = min($pagina, $total_paginas);

$offset = ($pagina - 1) * $por_pagina;

$sql = "SELECT
            m.id_movimiento,
            m.fecha_hora,
            m.tipo,
            m.cantidad,
            m.observacion,
            p.codigo,
            p.nombre AS producto,
            u.nombre AS usuario
        $desde
        $condiciones
        ORDER BY
            m.fecha_hora DESC,
            m.id_movimiento DESC
        LIMIT :limite OFFSET :offset";

$consulta = $conexion->prepare($sql);

foreach ($parametros as $clave => $valor) {
    $consulta->bindValue(
        $clave,
        $valor,
        PDO::PARAM_STR
    );
}

$consulta->bindValue(
    ":limite",
    $por_pagina,
    PDO::PARAM_INT
);

$consulta->bindValue(
    ":offset",
    $offset,
    PDO::PARAM_INT
);

$consulta->execute();

$movimientos = $consulta->fetchAll(PDO::FETCH_ASSOC);

function enlacePagina($numero, $buscar, $tipo, $fecha) {

    return "historial.php?" . http_build_query([
        "buscar" => $buscar,
        "tipo" => $tipo,
        "fecha" => $fecha,
        "pagina" => $numero
    ]);
}

$desde_resultado = $total_resultados > 0
    ? $offset + 1
    : 0;

$hasta_resultado = min(
    $offset + $por_pagina,
    $total_resultados
);

$pagina_inicio = max(1, $pagina - 2);
$pagina_fin = min($total_paginas, $pagina + 2);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Historial de movimientos - TecnoStock</title>

    <script>
        try {
            const temaGuardado = localStorage.getItem("temaTecnoStock");

            if (temaGuardado === "dark" || temaGuardado === "light") {
                document.documentElement.setAttribute("data-bs-theme", temaGuardado);
            }
        } catch (error) {}
    </script>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="css/tema.css"
        rel="stylesheet"
    >

</head>

<body>

<nav class="navbar navbar-dark bg-primary">

    <div class="container">

        <a href="dashboard.php"
           class="navbar-brand fw-bold">
            TecnoStock
        </a>

        <a href="productos.php"
           class="btn btn-outline-light">
            Volver a productos
        </a>

    </div>

</nav>

<main class="container my-5">

    <div class="d-flex justify-content-between
                align-items-center flex-wrap gap-3 mb-4">

        <div>

            <h2 class="mb-1">
                Historial de movimientos
            </h2>

            <p class="text-body-secondary mb-0">
                Consulta las entradas y salidas del inventario.
            </p>

        </div>

        <a href="movimientos.php"
           class="btn btn-success">
            + Nuevo movimiento
        </a>

    </div>

    <div class="card shadow-sm mb-4">

        <div class="card-body">

            <h5 class="mb-3">
                Filtrar movimientos
            </h5>

            <form
                method="GET"
                action="historial.php"
                id="formFiltrosHistorial"
                class="row g-3 align-items-end"
            >

                <div class="col-md-4">

                    <label class="form-label">
                        Producto
                    </label>

                    <input
                        type="search"
                        name="buscar"
                        class="form-control"
                        placeholder="Código o nombre"
                        value="<?= escapar($buscar) ?>"
                    >

                </div>

                <div class="col-md-3">

                    <label class="form-label">
                        Tipo de movimiento
                    </label>

                    <select
                        name="tipo"
                        class="form-select"
                    >

                        <option value="">
                            Todos
                        </option>

                        <option
                            value="ENTRADA"
                            <?= $tipo === "ENTRADA" ? "selected" : "" ?>
                        >
                            Entrada
                        </option>

                        <option
                            value="SALIDA"
                            <?= $tipo === "SALIDA" ? "selected" : "" ?>
                        >
                            Salida
                        </option>

                    </select>

                </div>

                <div class="col-md-3">

                    <label class="form-label">
                        Fecha
                    </label>

                    <input
                        type="date"
                        name="fecha"
                        class="form-control"
                        value="<?= escapar($fecha) ?>"
                    >

                </div>

                <div class="col-md-2">

                    <div class="d-flex gap-2">

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Filtrar
                        </button>

                        <a
                            href="historial.php"
                            id="limpiarHistorial"
                            class="btn btn-secondary"
                        >
                            Limpiar
                        </a>

                    </div>

                </div>

            </form>

        </div>

    </div>

    <div
        class="card shadow-sm"
        id="resultadosHistorial"
        aria-live="polite"
    >

        <div class="card-header bg-body-tertiary
                    d-flex justify-content-between
                    align-items-center flex-wrap gap-2">

            <h5 class="mb-0">
                Movimientos registrados
            </h5>

            <span class="badge bg-primary">
                <?= $total_resultados ?> resultados
            </span>

        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-striped
                              table-hover align-middle">

                    <thead class="table-dark">

                        <tr>
                            <th>Fecha</th>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Tipo</th>
                            <th>Cantidad</th>
                            <th>Usuario</th>
                            <th>Observación</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($movimientos as $movimiento): ?>

                        <tr>

                            <td>
                                <?= escapar(
                                    $movimiento["fecha_hora"]
                                ) ?>
                            </td>

                            <td>
                                <?= escapar(
                                    $movimiento["codigo"]
                                ) ?>
                            </td>

                            <td>
                                <?= escapar(
                                    $movimiento["producto"]
                                ) ?>
                            </td>

                            <td>

                                <?php if ($movimiento["tipo"] === "ENTRADA"): ?>

                                    <span class="badge bg-success">
                                        Entrada
                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-danger">
                                        Salida
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php if ($movimiento["tipo"] === "ENTRADA"): ?>

                                    <span class="text-success fw-bold">
                                        +<?= (int)$movimiento["cantidad"] ?>
                                    </span>

                                <?php else: ?>

                                    <span class="text-danger fw-bold">
                                        -<?= (int)$movimiento["cantidad"] ?>
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>
                                <?= escapar(
                                    $movimiento["usuario"]
                                ) ?>
                            </td>

                            <td>
                                <?= escapar(
                                    $movimiento["observacion"]
                                ) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    <?php if (empty($movimientos)): ?>

                        <tr>

                            <td
                                colspan="7"
                                class="text-center text-body-secondary py-4"
                            >
                                No se encontraron movimientos
                                con los filtros seleccionados.
                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <div class="d-flex justify-content-between
                        align-items-center flex-wrap gap-3 mt-3">

                <p class="text-body-secondary mb-0">

                    Mostrando

                    <strong><?= $desde_resultado ?></strong>

                    a

                    <strong><?= $hasta_resultado ?></strong>

                    de

                    <strong><?= $total_resultados ?></strong>

                    movimientos

                </p>

                <?php if ($total_paginas > 1): ?>

                    <nav aria-label="Paginación del historial">

                        <ul class="pagination mb-0 flex-wrap">

                            <li class="page-item
                                <?= $pagina <= 1 ? "disabled" : "" ?>">

                                <?php if ($pagina > 1): ?>

                                    <a
                                        class="page-link"
                                        href="<?= escapar(
                                            enlacePagina(
                                                $pagina - 1,
                                                $buscar,
                                                $tipo,
                                                $fecha
                                            )
                                        ) ?>"
                                    >
                                        Anterior
                                    </a>

                                <?php else: ?>

                                    <span class="page-link">
                                        Anterior
                                    </span>

                                <?php endif; ?>

                            </li>

                            <?php for (
                                $i = $pagina_inicio;
                                $i <= $pagina_fin;
                                $i++
                            ): ?>

                                <li class="page-item
                                    <?= $i === $pagina ? "active" : "" ?>">

                                    <a
                                        class="page-link"
                                        <?= $i === $pagina
                                            ? 'aria-current="page"'
                                            : '' ?>
                                        href="<?= escapar(
                                            enlacePagina(
                                                $i,
                                                $buscar,
                                                $tipo,
                                                $fecha
                                            )
                                        ) ?>"
                                    >
                                        <?= $i ?>
                                    </a>

                                </li>

                            <?php endfor; ?>

                            <li class="page-item
                                <?= $pagina >= $total_paginas ? "disabled" : "" ?>">

                                <?php if ($pagina < $total_paginas): ?>

                                    <a
                                        class="page-link"
                                        href="<?= escapar(
                                            enlacePagina(
                                                $pagina + 1,
                                                $buscar,
                                                $tipo,
                                                $fecha
                                            )
                                        ) ?>"
                                    >
                                        Siguiente
                                    </a>

                                <?php else: ?>

                                    <span class="page-link">
                                        Siguiente
                                    </span>

                                <?php endif; ?>

                            </li>

                        </ul>

                    </nav>

                <?php endif; ?>

            </div>

            <?php if ($total_paginas > 1): ?>

                <p class="text-body-secondary small mt-2 mb-0">
                    Página <?= $pagina ?>
                    de <?= $total_paginas ?>
                </p>

            <?php endif; ?>

        </div>

    </div>

</main>

<script src="js/historial.js"></script>
<script src="js/tema.js"></script>

</body>
</html>
