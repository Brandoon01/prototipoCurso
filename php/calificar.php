<?php
// Incluir el archivo de conexión
include 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener el ID de entrega y la calificación desde el formulario
    $id_entrega = $_POST['id_entrega'];
    $calificacion = $_POST['calificacion'];

    // Asegúrate de que la calificación esté en el rango adecuado
    if ($calificacion >= 0 && $calificacion <= 5) {
        // Consulta para actualizar la calificación en la tabla Entregas
        $query = "UPDATE Entregas SET calificacion = ? WHERE id_entrega = ?";
        $stmt = $conn->prepare($query);
        
        // Usamos 'd' para indicar que el parámetro de calificación es un decimal (float) y 'i' para el ID
        $stmt->bind_param('di', $calificacion, $id_entrega);

        // Ejecutar la consulta y verificar si fue exitosa
        if ($stmt->execute()) {
            echo "Calificación asignada con éxito.";
        } else {
            echo "Error al asignar la calificación: " . $conn->error;
        }
        
        // Cerrar el statement
        $stmt->close();
    } else {
        echo "La calificación debe estar entre 0 y 5.";
    }
}

// Cerrar la conexión
$conn->close();

// Redirige de vuelta a la página de entregas después de calificar
header("Location: ../entregadas.php");
exit;
?>
