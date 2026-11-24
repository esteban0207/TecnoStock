
<?php
require_once "conexion.php";

$nombre = "Administrador";
$correo = "admin@tecnostock.cl";
$clave = password_hash("Admin123!", PASSWORD_DEFAULT);

try {
    $sql = "INSERT INTO usuario
            (nombre, correo, clave, estado)
            VALUES (:nombre, :correo, :clave, 1)";

    $consulta = $conexion->prepare($sql);

    $consulta->execute([
        ":nombre" => $nombre,
        ":correo" => $correo,
        ":clave" => $clave
    ]);

    echo "Usuario administrador creado correctamente.";

} catch (PDOException $error) {
    error_log($error->getMessage());
    echo "No se pudo crear el usuario. Comprueba si ya existe.";
}
?>
