<?php
include 'conexion.php';

session_start();

// Verificar que se ha recibido un ID de entrega
if (isset($_GET['id_entrega'])) {
    $id_entrega = intval($_GET['id_entrega']);
    
    // Obtener información de la entrega
    $sql = "SELECT ruta_archivo FROM Entregas WHERE id_entrega = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id_entrega);
    $stmt->execute();
    $stmt->bind_result($ruta_archivo);
    $stmt->fetch();
    $stmt->close();

    // Verificar si se obtuvo una ruta de archivo
    if (!$ruta_archivo) {
        die("Error: No se encontró la ruta del archivo en la base de datos.");
    }

    // Asegúrate de que la ruta incluye la carpeta "test"
    $ruta_completa = $_SERVER['DOCUMENT_ROOT'] . '/test/' . $ruta_archivo;

    // Verificar si el archivo existe
    if (file_exists($ruta_completa)) {
        // Configurar encabezados para la descarga
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($ruta_completa) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($ruta_completa));
        flush(); // Limpiar el búfer del sistema
        readfile($ruta_completa);
        exit;
    } else {
        die("Error: El archivo no existe en la ruta: " . htmlspecialchars($ruta_completa));
    }
} else {
    die("Error: Parámetros inválidos.");
}
?>
