<?php
// actualizar_clave.php
require 'php/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $nueva_clave = password_hash($_POST['nueva_clave'], PASSWORD_DEFAULT);

    // Actualiza la contraseña del usuario
    $stmt = $conn->prepare("UPDATE administrador SET contraseña = ? WHERE email = ? UNION UPDATE Alumnos SET contraseña = ? WHERE email = ? UNION UPDATE Docentes SET contraseña = ? WHERE email = ?");
    $stmt->bind_param("ssssss", $nueva_clave, $email, $nueva_clave, $email, $nueva_clave, $email);

    if ($stmt->execute()) {
        // Elimina el token de recuperación
        $stmt = $conn->prepare("DELETE FROM RecuperacionClave WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();

        echo "Contraseña actualizada correctamente.";
    } else {
        echo "Error al actualizar la contraseña.";
    }
}
?>