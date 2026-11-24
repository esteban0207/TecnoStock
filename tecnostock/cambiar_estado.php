
<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Allow: POST", true, 405);
    exit("Método no permitido.");
}

$token_sesion = $_SESSION["csrf_token"] ?? "";
$token_recibido = $_POST["csrf_token"] ?? "";

if (
    !is_string($token_sesion) ||
    $token_sesion === "" ||
    !is_string($token_recibido) ||
    !hash_equals($token_sesion, $token_recibido)
) {
    http_response_code(403);
    exit("Solicitud no autorizada.");
}

$id = filter_input(
    INPUT_POST,
    "id_producto",
    FILTER_VALIDATE_INT,
    [
        "options" => [
            "min_range" => 1
        ]
    ]
);

if ($id === false || $id === null) {
    http_response_code(400);
    exit("ID de producto inválido.");
}

try {

    $sql = "UPDATE producto
            SET estado = CASE
                WHEN estado = 1 THEN 0
                ELSE 1
            END
            WHERE id_producto = :id";

    $consulta = $conexion->prepare($sql);

    $consulta->bindValue(
        ":id",
        $id,
        PDO::PARAM_INT
    );

    $consulta->execute();

    if ($consulta->rowCount() === 0) {
        http_response_code(404);
        exit("Producto no encontrado.");
    }

    header("Location: productos.php?estado=actualizado");
    exit;

} catch (PDOException $error) {

    error_log($error->getMessage());

    http_response_code(500);
    exit("No se pudo actualizar el estado del producto.");
}
