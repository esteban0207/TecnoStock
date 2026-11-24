
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

function escapar($valor) {
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        "UTF-8"
    );
}

$id = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 1]]
);

if ($id === false || $id === null) {
    http_response_code(400);
    exit("ID de producto inválido.");
}

$consulta = $conexion->prepare(
    "SELECT *
     FROM producto
     WHERE id_producto = :id"
);

$consulta->execute([
    ":id" => $id
]);

$producto = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$producto) {
    http_response_code(404);
    exit("Producto no encontrado.");
}

$consulta = $conexion->query(
    "SELECT id_categoria, nombre
     FROM categoria
     WHERE estado = 1
     ORDER BY nombre"
);

$categorias = $consulta->fetchAll(PDO::FETCH_ASSOC);

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals(
            $_SESSION["csrf_token"],
            $token
        )
    ) {
        http_response_code(403);
        exit("Solicitud no autorizada.");
    }

    $campos = [
        "nombre",
        "descripcion",
        "precio",
        "stock_minimo",
        "id_categoria"
    ];

    $datos = [];
    $datos_validos = true;

    foreach ($campos as $campo) {
        $valor = $_POST[$campo] ?? "";

        if (!is_string($valor)) {
            $datos_validos = false;
            $valor = "";
        }

        $datos[$campo] = trim($valor);
    }

    $nombre = $datos["nombre"];
    $descripcion = $datos["descripcion"];
    $precio = $datos["precio"];
    $stock_minimo = $datos["stock_minimo"];
    $id_categoria = $datos["id_categoria"];

    $producto["nombre"] = $nombre;
    $producto["descripcion"] = $descripcion;
    $producto["precio"] = $precio;
    $producto["stock_minimo"] = $stock_minimo;
    $producto["id_categoria"] = $id_categoria;

    if (!$datos_validos) {

        $error = "Los datos enviados no son válidos.";

    } elseif ($nombre === "") {

        $error = "El nombre del producto es obligatorio.";

    } elseif (mb_strlen($nombre, "UTF-8") > 150) {

        $error = "El nombre no puede superar los 150 caracteres.";

    } elseif (
        !preg_match('/^\d+(\.\d{1,2})?$/D', $precio) ||
        (float)$precio > 9999999999.99
    ) {

        $error = "El precio debe ser válido, no negativo y tener máximo dos decimales.";

    } elseif (
        !preg_match('/^\d+$/D', $stock_minimo) ||
        (float)$stock_minimo > 2147483647
    ) {

        $error = "El stock mínimo debe ser un número entero no negativo.";

    } elseif (
        !ctype_digit($id_categoria) ||
        (float)$id_categoria < 1 ||
        (float)$id_categoria > 2147483647
    ) {

        $error = "Selecciona una categoría válida.";

    } else {

        try {

            $consulta = $conexion->prepare(
                "SELECT COUNT(*)
                 FROM categoria
                 WHERE id_categoria = :id
                   AND estado = 1"
            );

            $consulta->execute([
                ":id" => $id_categoria
            ]);

            $categoria_valida =
                (int)$consulta->fetchColumn() > 0;

            if (!$categoria_valida) {

                $error = "La categoría seleccionada no existe o está inactiva.";

            } else {

                $sql = "UPDATE producto
                        SET nombre = :nombre,
                            descripcion = :descripcion,
                            precio = :precio,
                            stock_minimo = :stock_minimo,
                            id_categoria = :categoria
                        WHERE id_producto = :id";

                $consulta = $conexion->prepare($sql);

                $consulta->execute([
                    ":nombre" => $nombre,
                    ":descripcion" => $descripcion,
                    ":precio" => $precio,
                    ":stock_minimo" => $stock_minimo,
                    ":categoria" => $id_categoria,
                    ":id" => $id
                ]);

                header("Location: productos.php?actualizado=1");
                exit;
            }

        } catch (PDOException $e) {

            error_log($e->getMessage());

            $error = "No se pudo actualizar el producto.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Editar producto - TecnoStock</title>

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

    <style>
        .alerta-personalizada {
            background-color: rgba(220, 38, 38, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.55);
            border-radius: 10px;
            padding: 16px 14px;
            color: var(--bs-body-color);
            font-size: 16px;
        }

        .alerta-personalizada strong {
            font-weight: 600;
        }
    </style>
</head>

<body>

<nav class="navbar navbar-dark bg-primary">

    <div class="container">

        <a href="productos.php"
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

    <div class="card shadow-sm mx-auto"
         style="max-width: 700px;">

        <div class="card-header bg-primary text-white">

            <h4 class="mb-0">
                Editar producto
            </h4>

        </div>

        <div class="card-body p-4">

            <?php if ($error !== ""): ?>

                <div class="alerta-personalizada mb-4"
                     role="alert">

                    <div class="d-flex align-items-center gap-2 mb-2">

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="20"
                            height="20"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="#ef4444"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round">

                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>

                        </svg>

                        <strong>
                            No se pudo actualizar el producto
                        </strong>

                    </div>

                    <div>
                        <?= escapar($error) ?>
                    </div>

                </div>

            <?php endif; ?>

            <div
                id="errorJavaScriptEditar"
                class="alerta-personalizada mb-4"
                role="alert"
                aria-live="polite"
                style="display: none;">
            </div>

            <form method="POST"
                  id="formEditar"
                  novalidate>

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= escapar($_SESSION["csrf_token"]) ?>"
                >

                <div class="mb-3">

                    <label class="form-label">
                        Código
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        value="<?= escapar($producto["codigo"]) ?>"
                        disabled
                    >

                    <div class="form-text">
                        El código identifica al producto y no se modifica.
                    </div>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Nombre
                    </label>

                    <input
                        type="text"
                        name="nombre"
                        class="form-control"
                        maxlength="150"
                        value="<?= escapar($producto["nombre"]) ?>"
                        required
                    >

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Categoría
                    </label>

                    <select
                        name="id_categoria"
                        class="form-select"
                        required
                    >

                        <option value="">
                            Selecciona una categoría
                        </option>

                        <?php foreach ($categorias as $categoria): ?>

                            <option
                                value="<?= (int)$categoria["id_categoria"] ?>"
                                <?= (string)$producto["id_categoria"] ===
                                    (string)$categoria["id_categoria"]
                                    ? "selected"
                                    : "" ?>
                            >

                                <?= escapar($categoria["nombre"]) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Descripción
                    </label>

                    <textarea
                        name="descripcion"
                        class="form-control"
                        rows="3"
                    ><?= escapar($producto["descripcion"]) ?></textarea>

                </div>

                <div class="row">

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Precio
                        </label>

                        <input
                            type="number"
                            name="precio"
                            class="form-control"
                            min="0"
                            max="9999999999.99"
                            step="0.01"
                            value="<?= escapar($producto["precio"]) ?>"
                            required
                        >

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Stock mínimo
                        </label>

                        <input
                            type="number"
                            name="stock_minimo"
                            class="form-control"
                            min="0"
                            max="2147483647"
                            step="1"
                            value="<?= escapar($producto["stock_minimo"]) ?>"
                            required
                        >

                    </div>

                </div>

                <div class="alert alert-info">

                    Stock actual:

                    <strong>
                        <?= (int)$producto["stock_actual"] ?>
                    </strong>

                    unidades.

                    <div class="small mt-1">
                        Las entradas y salidas se registran
                        exclusivamente en el módulo de movimientos.
                    </div>

                </div>

                <div class="d-flex gap-2">

                    <button
                        type="submit"
                        class="btn btn-success"
                        <?= empty($categorias) ? "disabled" : "" ?>
                    >
                        Guardar cambios
                    </button>

                    <a href="productos.php"
                       class="btn btn-secondary">
                        Cancelar
                    </a>

                </div>

            </form>

        </div>
    </div>

</main>

<script src="js/validar_editar_producto.js"></script>
<script src="js/tema.js"></script>

</body>
</html>
