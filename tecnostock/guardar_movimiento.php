
<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: movimientos.php");
    exit;
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

$id_producto = filter_input(
    INPUT_POST,
    "id_producto",
    FILTER_VALIDATE_INT,
    [
        "options" => [
            "min_range" => 1
        ]
    ]
);

$cantidad = filter_input(
    INPUT_POST,
    "cantidad",
    FILTER_VALIDATE_INT,
    [
        "options" => [
            "min_range" => 1,
            "max_range" => 2147483647
        ]
    ]
);

$tipo = $_POST["tipo"] ?? "";
$observacion = $_POST["observacion"] ?? "";

if (
    !is_string($tipo) ||
    !is_string($observacion)
) {
    header("Location: movimientos.php?error=datos");
    exit;
}

$observacion = trim($observacion);

$id_usuario = (int)$_SESSION["id_usuario"];

if ($cantidad === false || $cantidad === null) {
    header("Location: movimientos.php?error=cantidad");
    exit;
}

if (
    $id_producto === false ||
    $id_producto === null ||
    !in_array($tipo, ["ENTRADA", "SALIDA"], true)
) {
    header("Location: movimientos.php?error=datos");
    exit;
}

try {

    $conexion->beginTransaction();

    $sql = "SELECT stock_actual
            FROM producto
            WHERE id_producto = :id
              AND estado = 1
            FOR UPDATE";

    $consulta = $conexion->prepare($sql);

    $consulta->execute([
        ":id" => $id_producto
    ]);

    $producto = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$producto) {
        throw new Exception("producto");
    }

    $stock_actual = (int)$producto["stock_actual"];

    if ($tipo === "ENTRADA") {

        $nuevo_stock = $stock_actual + $cantidad;

        if ($nuevo_stock > 2147483647) {
            throw new Exception(
                "El stock supera el máximo permitido."
            );
        }

    } else {

        if ($cantidad > $stock_actual) {
            throw new Exception("stock");
        }

        $nuevo_stock = $stock_actual - $cantidad;
    }

    $sql = "UPDATE producto
            SET stock_actual = :stock
            WHERE id_producto = :id";

    $consulta = $conexion->prepare($sql);

    $consulta->execute([
        ":stock" => $nuevo_stock,
        ":id" => $id_producto
    ]);

    $sql = "INSERT INTO movimiento
            (
                tipo,
                cantidad,
                observacion,
                id_producto,
                id_usuario
            )
            VALUES
            (
                :tipo,
                :cantidad,
                :observacion,
                :producto,
                :usuario
            )";

    $consulta = $conexion->prepare($sql);

    $consulta->execute([
        ":tipo" => $tipo,
        ":cantidad" => $cantidad,
        ":observacion" => $observacion,
        ":producto" => $id_producto,
        ":usuario" => $id_usuario
    ]);

    $conexion->commit();

    header("Location: movimientos.php?exito=1");
    exit;

} catch (Throwable $error) {

    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    $codigo = $error->getMessage();

    if (!in_array($codigo, ["stock", "producto"], true)) {

        error_log($error->getMessage());

        $codigo = "1";
    }

    header("Location: movimientos.php?error=" . $codigo);
    exit;
}
