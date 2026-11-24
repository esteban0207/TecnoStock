
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

$consulta = $conexion->query(
    "SELECT id_producto, codigo, nombre, stock_actual
     FROM producto
     WHERE estado = 1
     ORDER BY nombre"
);

$productos = $consulta->fetchAll(PDO::FETCH_ASSOC);

$mensajes = [
    "stock" => [
        "titulo" => "Stock insuficiente",
        "descripcion" => "No puedes retirar más unidades de las disponibles."
    ],
    "cantidad" => [
        "titulo" => "Cantidad inválida",
        "descripcion" => "Debes ingresar un número entero mayor que cero."
    ],
    "producto" => [
        "titulo" => "Producto no disponible",
        "descripcion" => "El producto seleccionado no existe o está inactivo."
    ],
    "datos" => [
        "titulo" => "Datos incorrectos",
        "descripcion" => "Revisa los datos ingresados."
    ],
    "1" => [
        "titulo" => "Error al registrar movimiento",
        "descripcion" => "No se pudo completar la operación."
    ]
];

$mensaje = null;

if (isset($_GET["error"])) {
    $codigo = $_GET["error"];

    if (!is_string($codigo)) {
        $codigo = "1";
    }

    $mensaje = $mensajes[$codigo] ?? $mensajes["1"];
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Movimientos - TecnoStock</title>

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
                Registrar movimiento
            </h4>

        </div>

        <div class="card-body p-4">

            <?php if (isset($_GET["exito"])): ?>

                <div class="alert alert-success">
                    Movimiento registrado correctamente.
                </div>

            <?php endif; ?>

            <?php if ($mensaje !== null): ?>

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
                            <?= htmlspecialchars(
                                $mensaje["titulo"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </strong>

                    </div>

                    <div>
                        <?= htmlspecialchars(
                            $mensaje["descripcion"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </div>

                </div>

            <?php endif; ?>

            <div
                id="errorJavaScriptMovimiento"
                class="alerta-personalizada mb-4"
                role="alert"
                aria-live="polite"
                style="display: none;">
            </div>

            <form
                method="POST"
                action="guardar_movimiento.php"
                id="formMovimiento"
                novalidate
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

                <div class="mb-3">

                    <label class="form-label">
                        Producto
                    </label>

                    <select
                        name="id_producto"
                        id="id_producto"
                        class="form-select"
                        required
                    >

                        <option value="">
                            Selecciona un producto
                        </option>

                        <?php foreach ($productos as $producto): ?>

                            <option
                                value="<?= (int)$producto["id_producto"] ?>"
                                data-stock="<?= (int)$producto["stock_actual"] ?>"
                            >
                                <?= htmlspecialchars(
                                    $producto["codigo"] . " - " . $producto["nombre"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                                (Stock: <?= (int)$producto["stock_actual"] ?>)

                            </option>

                        <?php endforeach; ?>

                    </select>

                    <div
                        id="stockDisponible"
                        class="form-text mt-2"
                        aria-live="polite"
                    >
                        Selecciona un producto para consultar su stock.
                    </div>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Tipo de movimiento
                    </label>

                    <select
                        name="tipo"
                        id="tipo"
                        class="form-select"
                        required
                    >

                        <option value="">
                            Selecciona el tipo
                        </option>

                        <option value="ENTRADA">
                            Entrada
                        </option>

                        <option value="SALIDA">
                            Salida
                        </option>

                    </select>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Cantidad
                    </label>

                    <input
                        type="number"
                        name="cantidad"
                        id="cantidad"
                        class="form-control"
                        min="1"
                        max="2147483647"
                        step="1"
                        required
                    >

                    <div class="form-text">
                        Ingresa un número entero mayor que cero.
                    </div>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Observación
                    </label>

                    <textarea
                        name="observacion"
                        class="form-control"
                        rows="3"
                        placeholder="Ejemplo: Recepción de mercadería"
                    ></textarea>

                </div>

                <div class="d-flex gap-2">

                    <button
                        type="submit"
                        class="btn btn-success"
                        <?= empty($productos) ? "disabled" : "" ?>
                    >
                        Guardar movimiento
                    </button>

                    <a
                        href="productos.php"
                        class="btn btn-secondary"
                    >
                        Cancelar
                    </a>

                </div>

            </form>

        </div>
    </div>

</main>

<script src="js/validar_movimientos.js"></script>
<script src="js/tema.js"></script>

</body>
</html>
