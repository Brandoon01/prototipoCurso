<?php
require_once 'php/conexion.php';
require('lib/fpdf.php'); // Adjust the path based on where you placed the FPDF library

// Start session
session_start();
if (!isset($_POST['alumno_id'])) {
    header("Location: calificaciones.php"); // Redirect if no student ID provided
    exit();
}

// Get the student ID from the POST request
$alumno_id = $_POST['alumno_id'];

// SQL query to fetch activities and grades for the student
$sql = "SELECT a.titulo AS actividad, a.fecha_entrega, e.calificacion 
        FROM Actividades a 
        JOIN Entregas e ON a.id_actividad = e.id_actividad 
        WHERE e.id_alumno = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $alumno_id);
$stmt->execute();
$result = $stmt->get_result();

// Create PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'Informe de Calificaciones', 0, 1, 'C');
$pdf->Ln(10); // Add a line break

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(80, 10, 'Actividad', 1);
$pdf->Cell(60, 10, 'Fecha de Entrega', 1);
$pdf->Cell(40, 10, 'Calificacion', 1);
$pdf->Ln();

$pdf->SetFont('Arial', '', 12);

// Check if there are results
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pdf->Cell(80, 10, htmlspecialchars($row['actividad']), 1);
        $pdf->Cell(60, 10, htmlspecialchars($row['fecha_entrega']), 1);
        $pdf->Cell(40, 10, htmlspecialchars($row['calificacion']), 1);
        $pdf->Ln();
    }
} else {
    $pdf->Cell(0, 10, 'No se encontraron actividades o calificaciones.', 1, 1, 'C');
}

// Output the PDF
$pdf->Output('D', 'Informe_Calificaciones.pdf');

// Close the statement and connection
$stmt->close();
$conn->close();
?>
