<?php
session_start();
include 'php/conexion.php'; // Conexión a la base de datos

// Verifica que el usuario sea un estudiante
if (!isset($_SESSION['user_id'])) {
    echo "Acceso denegado.";
    exit;
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mensaje = $_POST['mensaje'];

    // Insertar el mensaje en la tabla 'foro_mensajes' con id_alumno
    $stmt = $conn->prepare("INSERT INTO foro_mensajes (id_alumno, mensaje) VALUES (?, ?)");
    $stmt->bind_param("is", $userId, $mensaje);
    
    if($stmt->execute()) {
        echo 'success';  // Respuesta positiva
    } else {
        echo 'error';  // Respuesta en caso de error
    }
    
    $stmt->close();
}
?>
