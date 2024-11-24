<?php
// Incluir el archivo de conexión a la base de datos
include 'php/conexion.php';

// Incluir la librería FPDF
require('lib/fpdf.php');

// Iniciar sesión
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Obtener el ID del docente desde la sesión
$user_id = $_SESSION['user_id'];

// Obtener el ID del curso desde la URL, verificando que no sea 0
$id_curso = isset($_GET['id_curso']) ? intval($_GET['id_curso']) : 0;
if ($id_curso === 0) {
    die("ID de curso inválido.");
}

// Consultar el nombre y correo del docente
$query_docente = "SELECT nombre, email, documento FROM Docentes WHERE id_docente = ?";
$stmt_docente = $conn->prepare($query_docente);
if ($stmt_docente === false) {
    die('Error al preparar la consulta del docente.');
}
$stmt_docente->bind_param("i", $user_id);
$stmt_docente->execute();
$result_docente = $stmt_docente->get_result();
$docente = $result_docente->fetch_assoc();

// Consultar los subcursos que imparte el docente para el curso específico
$query_subcursos = "
    SELECT sc.id_subcurso, sc.nombre_subcurso 
    FROM Subcursos sc 
    WHERE sc.id_docente = ? AND sc.id_curso = ?";
$stmt_subcursos = $conn->prepare($query_subcursos);
if ($stmt_subcursos === false) {
    die('Error al preparar la consulta de subcursos.');
}
$stmt_subcursos->bind_param("ii", $user_id, $id_curso);
$stmt_subcursos->execute();
$result_subcursos = $stmt_subcursos->get_result();

// Generar el PDF si se solicita
if (isset($_POST['generar_informe'])) {
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    
    $pdf->Cell(200, 10, 'Informe de Calificaciones - Aula Virtual', 0, 1, 'C');
    $pdf->Ln(10);

    // Consultar las calificaciones de los alumnos
    $query_calificaciones = "
        SELECT a.nombre, a.apellido, act.titulo AS actividad, e.calificacion
        FROM Alumnos a
        JOIN Inscripciones i ON a.id_alumno = i.id_alumno
        JOIN Actividades act ON act.id_subcurso = i.id_curso
        LEFT JOIN Entregas e ON e.id_actividad = act.id_actividad AND e.id_alumno = a.id_alumno
        WHERE i.id_curso = ?";
    $stmt_calificaciones = $conn->prepare($query_calificaciones);
    $stmt_calificaciones->bind_param("i", $id_curso);
    $stmt_calificaciones->execute();
    $result_calificaciones = $stmt_calificaciones->get_result();

    $pdf->SetFont('Arial', '', 12);
    while ($row = $result_calificaciones->fetch_assoc()) {
        $pdf->Cell(40, 10, htmlspecialchars($row['nombre'] . ' ' . $row['apellido']), 0, 0);
        $pdf->Cell(80, 10, htmlspecialchars($row['actividad']), 0, 0);
        $pdf->Cell(30, 10, isset($row['calificacion']) ? htmlspecialchars($row['calificacion']) : 'No entregado', 0, 1);
    }

    $pdf->Output('D', 'informe_calificaciones_' . $id_curso . '.pdf');
    exit();
}

// Obtener la foto de perfil del docente
$sql = "SELECT documento FROM docentes WHERE id_docente = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $docente = $result->fetch_assoc();
    $documento = $docente['documento'];
    $profile_picture = "Pperfil/" . $documento . ".jpg";
    if (!file_exists($profile_picture)) {
        $profile_picture = "img/default-profile.png";
    }
} else {
    $profile_picture = "img/default-profile.png";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calificaciones - Aula Virtual</title>
    <link rel="stylesheet" href="css/deplo.css">
    <link rel="icon" href="img/logo.jpg" type="image/x-icon">
</head>
<body>
    <header>
        <h1>Calificaciones - Aula Virtual</h1>
        <nav>
            <a href="Pinicio.php">Inicio</a>
            <a href="Ppersonal.php">Personal</a>    
            <a href="Pcursos.php">Cursos</a>
            <a href="Pperfil.php">Perfil</a>
            <a href="chat_docentes.php">Chat</a>
            <a href="php/cerrar.php">Cerrar sesión</a>
        </nav>
    </header>
    <div class="profile-container">
        <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Foto de perfil" class="profile-pic">
    </div>

    <div class="container">
        <ul class="tabs">
            <li><a href="Pdeploy.php?id_curso=<?php echo urlencode($id_curso); ?>">Curso</a></li>
            <li><a href="Pparticipantes.php?id_curso=<?php echo urlencode($id_curso); ?>">Participantes</a></li>
            <li  class="active"><a href="Pcalificaciones.php?id_curso=<?php echo urlencode($id_curso); ?>">Calificaciones</a></li>
        </ul>
        <h2>Calificaciones de los Alumnos</h2>
        <table>
            <thead>
                <tr>
                    <th>Alumno</th>
                    <th>Actividad</th>
                    <th>Calificación</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query_calificaciones = "
                    SELECT a.nombre, a.apellido, act.titulo AS actividad, e.calificacion
                    FROM Alumnos a
                    JOIN Inscripciones i ON a.id_alumno = i.id_alumno
                    JOIN Actividades act ON act.id_subcurso = i.id_curso
                    LEFT JOIN Entregas e ON e.id_actividad = act.id_actividad AND e.id_alumno = a.id_alumno
                    WHERE i.id_curso = ?";
                $stmt_calificaciones = $conn->prepare($query_calificaciones);
                $stmt_calificaciones->bind_param("i", $id_curso);
                $stmt_calificaciones->execute();
                $result_calificaciones = $stmt_calificaciones->get_result();

                if ($result_calificaciones->num_rows > 0) {
                    while ($row = $result_calificaciones->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['nombre']) . " " . htmlspecialchars($row['apellido']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['actividad']) . "</td>";
                        echo "<td>" . (isset($row['calificacion']) ? htmlspecialchars($row['calificacion']) : 'No entregado') . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='3'>No se encontraron calificaciones para este curso.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Formulario para generar el informe PDF -->
        <form method="POST" action="">
            <button type="submit" name="generar_informe">Generar Informe PDF</button>
        </form>
    </div>
</body>
</html>
