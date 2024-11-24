<?php
session_start();
include 'php/conexion.php';

if (!isset($_SESSION['user_id'])) {
    echo "No hay sesión activa.";
    exit();
}

// Obtener el ID del destinatario seleccionado
$destinatario_id = isset($_GET['destinatario']) ? $_GET['destinatario'] : null;

if (!$destinatario_id) {
    echo "Por favor seleccione un destinatario para ver los mensajes.";
    exit();
}

$user_id = $_SESSION['user_id']; // ID del docente

// Query modificado para buscar mensajes usando el campo destinatario
$sql = "SELECT m.id, m.mensaje, m.fecha, m.id_alumno, m.id_docente, m.destinatario,
        a.nombre AS nombre_alumno, a.apellido AS apellido_alumno,
        d.nombre AS nombre_docente, d.apellido AS apellido_docente
        FROM mensajes m
        LEFT JOIN Alumnos a ON m.id_alumno = a.id_alumno
        LEFT JOIN Docentes d ON m.id_docente = d.id_docente
        WHERE (
            (m.id_docente = ? AND m.destinatario = ?) OR 
            (m.id_alumno = ? AND m.destinatario = ?)
        )
        ORDER BY m.fecha ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", 
    $user_id, $destinatario_id,    // Mensajes enviados por el docente
    $destinatario_id, $user_id     // Mensajes enviados por el alumno
);

$stmt->execute();
$result = $stmt->get_result();

// Para debug - comentar o eliminar en producción
echo "<!-- User ID: " . $user_id . " -->";
echo "<!-- Destinatario ID: " . $destinatario_id . " -->";
echo "<!-- Número de mensajes encontrados: " . $result->num_rows . " -->";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Determinar si el mensaje es enviado o recibido
        $isMessageFromTeacher = ($row['id_docente'] == $user_id);
        $messageClass = $isMessageFromTeacher ? 'message sent' : 'message received';
        
        echo "<div class='" . $messageClass . "'>";
        if ($isMessageFromTeacher) {
            echo "<p class='sender'>Tú</p>";
        } else {
            $senderName = $row['nombre_alumno'] ? 
                         htmlspecialchars($row['nombre_alumno'] . " " . $row['apellido_alumno']) : 
                         "Alumno";
            echo "<p class='sender'>" . $senderName . "</p>";
        }
        echo "<p class='message-content'>" . htmlspecialchars($row['mensaje']) . "</p>";
        echo "<p class='timestamp'>" . $row['fecha'] . "</p>";
        echo "</div>";
    }
} else {
    echo "<p class='no-messages'>No hay mensajes en esta conversación.</p>";
}

$stmt->close();
$conn->close();
?>