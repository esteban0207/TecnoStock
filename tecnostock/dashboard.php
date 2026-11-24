
<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

$nombre = htmlspecialchars(
    $_SESSION["nombre"] ?? "Usuario",
    ENT_QUOTES,
    "UTF-8"
);

$total_productos = (int)$conexion->query(
    "SELECT COUNT(*)
     FROM producto
     WHERE estado = 1"
)->fetchColumn();

$stock_bajo = (int)$conexion->query(
    "SELECT COUNT(*)
     FROM producto
     WHERE estado = 1
       AND stock_actual < stock_minimo"
)->fetchColumn();

$total_unidades = (int)$conexion->query(
    "SELECT COALESCE(SUM(stock_actual), 0)
     FROM producto
     WHERE estado = 1"
)->fetchColumn();

$total_movimientos = (int)$conexion->query(
    "SELECT COUNT(*)
     FROM movimiento"
)->fetchColumn();

$consulta = $conexion->query(
    "SELECT codigo, nombre, stock_actual, stock_minimo
     FROM producto
     WHERE estado = 1
       AND stock_actual < stock_minimo
     ORDER BY stock_actual ASC"
);

$productos_bajos = $consulta->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Panel principal - TecnoStock</title>

    <script>
        try {
            const tema = localStorage.getItem("temaTecnoStock");
            if (tema === "dark" || tema === "light") {
                document.documentElement.setAttribute("data-bs-theme", tema);
            }
        } catch (error) {}
    </script>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            background-color: var(--bs-tertiary-bg);
            color: var(--bs-body-color);
        }

        .texto-secundario {
            color: var(--bs-secondary-color);
        }

        .numero-movimientos {
            color: var(--bs-emphasis-color);
        }

        .card {
            transition: background-color 0.2s, border-color 0.2s;
        }

        [data-bs-theme="dark"] .table {
            --bs-table-bg: transparent;
        }

        [data-bs-theme="dark"] .navbar {
            background-color: #162b4d !important;
        }

        [data-bs-theme="dark"] .card {
            border: 1px solid var(--bs-border-color) !important;
        }

        [data-bs-theme="dark"] .text-primary {
            color: #6ea8fe !important;
        }

        [data-bs-theme="dark"] .text-success {
            color: #75b798 !important;
        }

        [data-bs-theme="dark"] .text-danger {
            color: #ea868f !important;
        }
    </style>
</head>

<body>

<nav class="navbar navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">
            TecnoStock
        </a>

        <div class="d-flex align-items-center gap-2">
            <button
                type="button"
                id="botonTema"
                class="btn btn-outline-light"
            >
                Modo oscuro
            </button>

            <a href="logout.php" class="btn btn-outline-light">
                Cerrar sesión
            </a>
        </div>
    </div>
</nav>

<main class="container my-5">

    <h1>Bienvenido, <?= $nombre ?></h1>

    <p class="texto-secundario mb-4">
        Panel de administración de inventario
    </p>

    <div class="row g-4 mb-4">

        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="texto-secundario">Productos activos</h6>

                    <h2 class="text-primary">
                        <?= $total_productos ?>
                    </h2>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="texto-secundario">Stock bajo</h6>

                    <h2 class="text-danger">
                        <?= $stock_bajo ?>
                    </h2>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="texto-secundario">Unidades disponibles</h6>

                    <h2 class="text-success">
                        <?= number_format($total_unidades, 0, ",", ".") ?>
                    </h2>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="texto-secundario">Movimientos registrados</h6>

                    <h2 class="numero-movimientos">
                        <?= $total_movimientos ?>
                    </h2>
                </div>
            </div>
        </div>

    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">

            <h5 class="card-title mb-3">
                Accesos rápidos
            </h5>

            <div class="d-flex flex-wrap gap-2">

                <a href="productos.php"
                   class="btn btn-primary">
                    Gestionar productos
                </a>

                <a href="crear_producto.php"
                   class="btn btn-outline-primary">
                    Nuevo producto
                </a>

                <a href="movimientos.php"
                   class="btn btn-success">
                    Registrar movimiento
                </a>

                <a href="historial.php"
                   class="btn btn-secondary">
                    Ver historial
                </a>

            </div>
        </div>
    </div>

    <div class="card shadow-sm">

        <div class="card-header bg-danger text-white">
            Productos con stock bajo
        </div>

        <div class="card-body">

            <?php if (empty($productos_bajos)): ?>

                <div class="alert alert-success mb-0">
                    No hay productos activos con stock bajo.
                </div>

            <?php else: ?>

                <div class="table-responsive">

                    <table class="table table-striped align-middle">

                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Stock actual</th>
                                <th>Stock mínimo</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php foreach ($productos_bajos as $producto): ?>

                            <tr>
                                <td>
                                    <?= htmlspecialchars(
                                        $producto["codigo"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $producto["nombre"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </td>

                                <td>
                                    <span class="badge bg-danger">
                                        <?= (int)$producto["stock_actual"] ?>
                                    </span>
                                </td>

                                <td>
                                    <?= (int)$producto["stock_minimo"] ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                        </tbody>
                    </table>

                </div>

            <?php endif; ?>

        </div>
    </div>

</main>

<script src="js/tema.js"></script>

</body>
</html>
