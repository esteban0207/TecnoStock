
<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$busqueda_recibida = $_GET["buscar"] ?? "";

$busqueda = is_string($busqueda_recibida)
    ? trim($busqueda_recibida)
    : "";

$sql = "SELECT
            p.id_producto,
            p.codigo,
            p.nombre,
            p.precio,
            p.stock_actual,
            p.stock_minimo,
            p.estado,
            c.nombre AS categoria
        FROM producto p
        INNER JOIN categoria c
            ON p.id_categoria = c.id_categoria";

$parametros = [];

if ($busqueda !== "") {
    $sql .= " WHERE p.codigo LIKE :codigo
              OR p.nombre LIKE :nombre
              OR c.nombre LIKE :categoria";

    $termino = "%" . $busqueda . "%";

    $parametros = [
        ":codigo" => $termino,
        ":nombre" => $termino,
        ":categoria" => $termino
    ];
}

$sql .= " ORDER BY p.id_producto DESC";

$consulta = $conexion->prepare($sql);
$consulta->execute($parametros);

$productos = $consulta->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Productos - TecnoStock</title>

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

        <a class="navbar-brand"
           href="dashboard.php">
            TecnoStock
        </a>

        <a href="logout.php"
           class="btn btn-outline-light">
            Cerrar sesión
        </a>

    </div>

</nav>

<main class="container mt-5">

    <div class="d-flex justify-content-between
                align-items-center mb-4">

        <h2>Gestión de productos</h2>

        <a href="crear_producto.php"
           class="btn btn-success">
            + Nuevo producto
        </a>

    </div>

    <form
        method="GET"
        action="productos.php"
        class="row g-2 mb-4"
    >

        <div class="col-md-9">

            <input
                type="search"
                name="buscar"
                class="form-control"
                placeholder="Buscar por código, nombre o categoría"
                value="<?= htmlspecialchars(
                    $busqueda,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
            >

        </div>

        <div class="col-md-3 d-flex gap-2">

            <button
                type="submit"
                class="btn btn-primary flex-fill"
            >
                Buscar
            </button>

            <a href="productos.php"
               class="btn btn-secondary">
                Limpiar
            </a>

        </div>

    </form>

    <div class="card shadow-sm">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-striped
                              table-hover align-middle">

                    <thead class="table-dark">

                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($productos as $producto): ?>

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
                                <?= htmlspecialchars(
                                    $producto["categoria"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                $<?= number_format(
                                    $producto["precio"],
                                    0,
                                    ",",
                                    "."
                                ) ?>
                            </td>

                            <td>

                                <?= (int)$producto["stock_actual"] ?>

                                <?php if (
                                    (int)$producto["estado"] === 1 &&
                                    (int)$producto["stock_actual"] <
                                    (int)$producto["stock_minimo"]
                                ): ?>

                                    <span class="badge bg-danger">
                                        Stock bajo
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php if ((int)$producto["estado"] === 1): ?>

                                    <span class="badge bg-success">
                                        Activo
                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-secondary">
                                        Inactivo
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <div class="d-flex gap-2">

                                    <a
                                        href="editar_producto.php?id=<?= (int)$producto["id_producto"] ?>"
                                        class="btn btn-sm btn-warning"
                                    >
                                        Editar
                                    </a>

                                    <form
                                        method="POST"
                                        action="cambiar_estado.php"
                                        onsubmit="return confirm('¿Confirmas el cambio de estado de este producto?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="id_producto"
                                            value="<?= (int)$producto["id_producto"] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= htmlspecialchars(
                                                $_SESSION["csrf_token"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                        >

                                        <?php if (
                                            (int)$producto["estado"] === 1
                                        ): ?>

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-danger"
                                            >
                                                Desactivar
                                            </button>

                                        <?php else: ?>

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-success"
                                            >
                                                Reactivar
                                            </button>

                                        <?php endif; ?>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    <?php if (empty($productos)): ?>

                        <tr>

                            <td
                                colspan="7"
                                class="text-center text-body-secondary py-4"
                            >
                                Todavía no hay productos registrados.
                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</main>

<script src="js/tema.js"></script>

</body>
</html>
