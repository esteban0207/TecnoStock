
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

$error = "";

$codigo = "";
$nombre = "";
$descripcion = "";
$precio = "";
$stock_actual = "";
$stock_minimo = "";
$id_categoria = "";

$consulta = $conexion->query(
    "SELECT id_categoria, nombre
     FROM categoria
     WHERE estado = 1
     ORDER BY nombre"
);

$categorias = $consulta->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token_recibido = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token_recibido) ||
        !hash_equals(
            $_SESSION["csrf_token"],
            $token_recibido
        )
    ) {
        http_response_code(403);
        exit("Solicitud no autorizada.");
    }

    $campos = [
        "codigo",
        "nombre",
        "descripcion",
        "precio",
        "stock_actual",
        "stock_minimo",
        "id_categoria"
    ];

    $datos_validos = true;
    $datos = [];

    foreach ($campos as $campo) {
        $valor = $_POST[$campo] ?? "";

        if (!is_string($valor)) {
            $datos_validos = false;
            $valor = "";
        }

        $datos[$campo] = trim($valor);
    }

    $codigo = $datos["codigo"];
    $nombre = $datos["nombre"];
    $descripcion = $datos["descripcion"];
    $precio = $datos["precio"];
    $stock_actual = $datos["stock_actual"];
    $stock_minimo = $datos["stock_minimo"];
    $id_categoria = $datos["id_categoria"];

    if (!$datos_validos) {

        $error = "Los datos enviados no son válidos.";

    } elseif (
        $codigo === "" ||
        $nombre === "" ||
        $precio === "" ||
        $stock_actual === "" ||
        $stock_minimo === "" ||
        $id_categoria === ""
    ) {

        $error = "Completa todos los campos obligatorios.";

    } elseif (
        mb_strlen($codigo, "UTF-8") > 50 ||
        mb_strlen($nombre, "UTF-8") > 150
    ) {

        $error = "El código o nombre supera la longitud permitida.";

    } elseif (
        !preg_match('/^\d+(\.\d{1,2})?$/D', $precio) ||
        (float)$precio > 9999999999.99
    ) {

        $error = "El precio debe ser un número válido y no negativo, con máximo dos decimales.";

    } elseif (
        !preg_match('/^\d+$/D', $stock_actual) ||
        !preg_match('/^\d+$/D', $stock_minimo) ||
        (float)$stock_actual > 2147483647 ||
        (float)$stock_minimo > 2147483647
    ) {

        $error = "El stock actual y el stock mínimo deben ser números enteros no negativos.";

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

                $consulta = $conexion->prepare(
                    "SELECT COUNT(*)
                     FROM producto
                     WHERE codigo = :codigo"
                );

                $consulta->execute([
                    ":codigo" => $codigo
                ]);

                if ((int)$consulta->fetchColumn() > 0) {

                    $error = "Ya existe un producto con ese código.";

                } else {

                    $sql = "INSERT INTO producto
                            (
                                codigo,
                                nombre,
                                descripcion,
                                precio,
                                stock_actual,
                                stock_minimo,
                                id_categoria
                            )
                            VALUES
                            (
                                :codigo,
                                :nombre,
                                :descripcion,
                                :precio,
                                :stock_actual,
                                :stock_minimo,
                                :id_categoria
                            )";

                    $consulta = $conexion->prepare($sql);

                    $consulta->execute([
                        ":codigo" => $codigo,
                        ":nombre" => $nombre,
                        ":descripcion" => $descripcion,
                        ":precio" => $precio,
                        ":stock_actual" => $stock_actual,
                        ":stock_minimo" => $stock_minimo,
                        ":id_categoria" => $id_categoria
                    ]);

                    header("Location: productos.php");
                    exit;
                }
            }

        } catch (PDOException $e) {

            error_log($e->getMessage());

            if ($e->getCode() === "23000") {

                $error = "El código ya existe o la categoría no es válida.";

            } else {

                $error = "Ocurrió un error al guardar el producto.";

            }
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

    <title>Nuevo producto - TecnoStock</title>

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

    <div class="card shadow-sm mx-auto"
         style="max-width: 700px;">

        <div class="card-header bg-primary text-white">

            <h4 class="mb-0">
                Registrar producto
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
                            No se pudo registrar el producto
                        </strong>

                    </div>

                    <div>
                        <?= escapar($error) ?>
                    </div>

                </div>

            <?php endif; ?>

            <?php if (empty($categorias)): ?>

                <div class="alert alert-warning">
                    No existen categorías activas.
                    Primero debes registrar una categoría.
                </div>

            <?php endif; ?>

            <div
                id="errorJavaScript"
                class="alerta-personalizada mb-4"
                role="alert"
                aria-live="polite"
                style="display: none;">
            </div>

            <form method="POST"
                  id="formProducto"
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
                        name="codigo"
                        class="form-control"
                        maxlength="50"
                        value="<?= escapar($codigo) ?>"
                        required
                    >

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
                        value="<?= escapar($nombre) ?>"
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
                                <?= (string)$id_categoria ===
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
                        placeholder="Descripción del producto"
                    ><?= escapar($descripcion) ?></textarea>

                </div>

                <div class="row">

                    <div class="col-md-4 mb-3">

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
                            value="<?= escapar($precio) ?>"
                            required
                        >

                    </div>

                    <div class="col-md-4 mb-3">

                        <label class="form-label">
                            Stock actual
                        </label>

                        <input
                            type="number"
                            name="stock_actual"
                            class="form-control"
                            min="0"
                            max="2147483647"
                            step="1"
                            value="<?= escapar($stock_actual) ?>"
                            required
                        >

                    </div>

                    <div class="col-md-4 mb-3">

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
                            value="<?= escapar($stock_minimo) ?>"
                            required
                        >

                    </div>

                </div>

                <div class="d-flex gap-2">

                    <button
                        type="submit"
                        class="btn btn-success"
                        <?= empty($categorias) ? "disabled" : "" ?>
                    >
                        Guardar producto
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

<script src="js/validar_producto.js"></script>
<script src="js/tema.js"></script>

</body>
</html>
