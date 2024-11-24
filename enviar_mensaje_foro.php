<?php
session_start();
include 'php/conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mensaje = $_POST['mensaje'];
    $userId = $_SESSION['user_id'];
    $userType = $_POST['user_type'];

    // Insertar el mensaje en la tabla 'foro_mensajes' según el tipo de usuario
    if ($userType === 'student') {
        $stmt = $conn->prepare("INSERT INTO foro_mensajes (id_alumno, mensaje) VALUES (?, ?)");
        $stmt->bind_param("is", $userId, $mensaje);
    } elseif ($userType === 'teacher') {
        $stmt = $conn->prepare("INSERT INTO foro_mensajes (id_docente, mensaje) VALUES (?, ?)");
        $stmt->bind_param("is", $userId, $mensaje);
    }

    if ($stmt->execute()) {
        header("Location: foro.php"); // Redirige de nuevo al foro
        exit;
    } else {
        echo "Error al enviar el mensaje: " . $conn->error;
    }
    $stmt->close();
}
?>
