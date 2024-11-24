<?php
session_start();
include 'php/conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $mensaje = $_POST['mensaje'];
    $userId = $_SESSION['user_id'];

    // Insertar el mensaje en la tabla 'foro_mensajes' con id_docente
    $stmt = $conn->prepare("INSERT INTO foro_mensajes (id_docente, mensaje) VALUES (?, ?)");
    $stmt->bind_param("is", $userId, $mensaje);

    if ($stmt->execute()) {
        header("Location: foro_docentes.php"); // Redirige de nuevo al foro de docentes
        exit;
    } else {
        echo "Error al enviar el mensaje: " . $conn->error;
    }
    $stmt->close();
}
?>
