<?php
session_start();
include 'php/conexion.php';

if (!isset($_SESSION['user_id'])) {
    echo "No hay sesión activa.";
    exit();
}

$user_id = $_SESSION['user_id']; // Assuming this is the teacher ID
$destinatario = $_POST['destinatario'];
$mensaje = $_POST['mensaje'];

// Verify that the user exists in the Docentes table
$stmt = $conn->prepare("SELECT id_docente FROM Docentes WHERE id_docente = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // User is a teacher, insert message with id_docente
    $sql = "INSERT INTO mensajes (id_docente, destinatario, mensaje) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $user_id, $destinatario, $mensaje);

    if ($stmt->execute()) {
        echo "Mensaje enviado con éxito.";
    } else {
        echo "Error al enviar el mensaje: " . $stmt->error;
    }
} else {
    echo "Error: Usuario no encontrado en la base de datos.";
}

$stmt->close();
$conn->close();
?>
