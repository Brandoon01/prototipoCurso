<?php
session_start();
include 'php/conexion.php'; // Conexión a la base de datos

// Consultar todos los mensajes del foro
$query = "
    SELECT foro_mensajes.id, foro_mensajes.mensaje, foro_mensajes.fecha, 
           alumnos.nombre AS alumno_nombre, docentes.nombre AS docente_nombre
    FROM foro_mensajes
    LEFT JOIN Alumnos AS alumnos ON foro_mensajes.id_alumno = alumnos.id_alumno
    LEFT JOIN Docentes AS docentes ON foro_mensajes.id_docente = docentes.id_docente
    ORDER BY foro_mensajes.fecha ASC";
$result = $conn->query($query);

// Mostrar los mensajes
while ($row = $result->fetch_assoc()) {
    echo '<div class="message" id="message-' . $row['id'] . '">
            <strong>' . ($row['alumno_nombre'] ? $row['alumno_nombre'] : $row['docente_nombre']) . '</strong>:
            <p>' . htmlspecialchars($row['mensaje']) . '</p>
            <span>' . date('d-m-Y H:i', strtotime($row['fecha'])) . '</span>
          </div>';
}
?>
