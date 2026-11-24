
<?php
ini_set("session.use_strict_mode", "1");
ini_set("session.use_only_cookies", "1");
ini_set("session.cookie_httponly", "1");

if (!empty($_SERVER["HTTPS"]) &&
    $_SERVER["HTTPS"] !== "off") {
    ini_set("session.cookie_secure", "1");
}

session_start();

require_once "conexion.php";

if (isset($_SESSION["id_usuario"])) {
    header("Location: dashboard.php");
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
$correo = "";

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

    $correo_recibido = $_POST["correo"] ?? "";
    $clave = $_POST["clave"] ?? "";

    if (
        !is_string($correo_recibido) ||
        !is_string($clave)
    ) {

        $error = "Los datos enviados no son válidos.";

    } else {

        $correo = trim($correo_recibido);

        if ($correo === "" || $clave === "") {

            $error = "Completa todos los campos.";

        } elseif (
            strlen($correo) > 150 ||
            !filter_var($correo, FILTER_VALIDATE_EMAIL)
        ) {

            $error = "Ingresa un correo electrónico válido.";

        } else {

            try {

                $sql = "SELECT
                            id_usuario,
                            nombre,
                            correo,
                            clave
                        FROM usuario
                        WHERE correo = :correo
                          AND estado = 1
                        LIMIT 1";

                $consulta = $conexion->prepare($sql);

                $consulta->execute([
                    ":correo" => $correo
                ]);

                $usuario = $consulta->fetch(
                    PDO::FETCH_ASSOC
                );

                if (
                    $usuario &&
                    password_verify(
                        $clave,
                        $usuario["clave"]
                    )
                ) {

                    session_regenerate_id(true);

                    $_SESSION["id_usuario"] =
                        (int)$usuario["id_usuario"];

                    $_SESSION["nombre"] =
                        $usuario["nombre"];

                    header("Location: dashboard.php");
                    exit;

                } else {

                    $error = "Correo o contraseña incorrectos.";
                }

            } catch (PDOException $e) {

                error_log($e->getMessage());

                $error = "No se pudo iniciar sesión. Intenta nuevamente.";
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

    <title>Iniciar sesión - TecnoStock</title>

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
            padding: 14px;
            color: var(--bs-body-color);
        }

        .alerta-personalizada strong {
            font-weight: 600;
        }
    </style>

</head>

<body class="d-flex align-items-center
             justify-content-center min-vh-100">

<div class="card shadow p-4"
     style="width: 400px; max-width: 95%;">

    <h2 class="text-center text-primary mb-2">
        TecnoStock
    </h2>

    <p class="text-center text-body-secondary mb-4">
        Sistema de inventario
    </p>

    <?php if ($error !== ""): ?>

        <div class="alerta-personalizada mb-3"
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
                    No se pudo iniciar sesión
                </strong>

            </div>

            <div>
                <?= escapar($error) ?>
            </div>

        </div>

    <?php endif; ?>

    <div
        id="errorJavaScriptLogin"
        class="alerta-personalizada mb-3"
        role="alert"
        aria-live="polite"
        style="display: none;">
    </div>

    <form
        method="POST"
        id="loginForm"
        novalidate
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= escapar($_SESSION["csrf_token"]) ?>"
        >

        <div class="mb-3">

            <label for="correo"
                   class="form-label">
                Correo electrónico
            </label>

            <input
                type="email"
                name="correo"
                id="correo"
                class="form-control"
                maxlength="150"
                autocomplete="username"
                value="<?= escapar($correo) ?>"
                required
            >

        </div>

        <div class="mb-3">

            <label for="clave"
                   class="form-label">
                Contraseña
            </label>

            <input
                type="password"
                name="clave"
                id="clave"
                class="form-control"
                autocomplete="current-password"
                required
            >

        </div>

        <button
            type="submit"
            class="btn btn-primary w-100"
        >
            Iniciar sesión
        </button>

    </form>

</div>

<script src="js/validar_login.js"></script>
<script src="js/tema.js"></script>

</body>
</html>
