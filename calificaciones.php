<?php
// Include the database connection file
require_once 'php/conexion.php';

// Start session
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html"); // Redirect to login if no active session
    exit();
}

// Get the student ID from the session
$alumno_id = $_SESSION['user_id'];

// Get the course ID from the URL
$id_curso = isset($_GET['id_curso']) ? intval($_GET['id_curso']) : 0;

// Consulta para obtener el número de documento del alumno
$sql = "SELECT documento FROM alumnos WHERE id_alumno = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $alumno_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $alumno = $result->fetch_assoc();
    $documento = $alumno['documento'];

    // Definir la ruta de la imagen de perfil basada en el documento del alumno
    $profile_picture = "perfil/" . $documento . ".jpg";
    
    // Si la imagen de perfil no existe, usar una imagen predeterminada
    if (!file_exists($profile_picture)) {
        $profile_picture = "img/default-profile.png";
    }
} else {
    // En caso de que no se encuentre el documento, asignar la imagen predeterminada
    $profile_picture = "img/default-profile.png";
}

$stmt->close();

// SQL query to fetch activities and grades for the student in the specific course
$sql = "SELECT a.titulo AS actividad, a.fecha_entrega, e.calificacion 
        FROM Actividades a 
        JOIN Entregas e ON a.id_actividad = e.id_actividad 
        JOIN Subcursos s ON a.id_subcurso = s.id_subcurso
        WHERE e.id_alumno = ? AND s.id_curso = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $alumno_id, $id_curso); // Bind both $alumno_id and $id_curso
$stmt->execute();
$result = $stmt->get_result();


?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calificaciones del Alumno</title>
    <link rel="stylesheet" href="css/deplo.css">
    <link rel="icon" href="img/logo.jpg" type="image/x-icon">
</head>
<body>
<header>
    <h1>AULA VIRTUAL</h1>
    <nav>
        <a href="inicio.php">Inicio</a>
        <a href="personal.php">Personal</a>    
        <a href="cursos.php">Cursos</a>
        <a href="perfil.php">Perfil</a>
        <a href="chat_estudiantes.php">Chat</a>
        <a href="php/cerrar.php">Cerrar sesión</a>
    </nav>
</header>

<!-- Imagen de perfil del usuario -->
<div class="profile-container">
    <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Foto de perfil" class="profile-pic">
</div>

<div class="container">
    <!-- Tabs de navegación -->
    <ul class="tabs">
        <li><a href="deploy.php?id_curso=<?php echo urlencode($id_curso); ?>">Curso</a></li>
        <li><a href="participantes.php?id_subcurso=<?php echo urlencode($id_subcurso); ?>" class="course-button">Participantes</a></li>
        <li class="active"><a href="calificaciones.php?id_curso=<?php echo urlencode($id_curso); ?>" class="course-button">Calificaciones</a></li>
    </ul>

    <?php
    if ($result->num_rows > 0) {
        // Output data of each row
        echo "<h1>Calificaciones</h1>";
        echo "<table>
                <tr>
                    <th>Actividad</th>
                    <th>Fecha de Entrega</th>
                    <th>Calificación</th>
                </tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>" . htmlspecialchars($row['actividad']) . "</td>
                    <td>" . htmlspecialchars($row['fecha_entrega']) . "</td>
                    <td>" . htmlspecialchars($row['calificacion']) . "</td>
                  </tr>";
        }
        echo "</table>";

        // Add a button to generate PDF
        echo '<form method="post" action="generar.php">
                <input type="hidden" name="alumno_id" value="' . htmlspecialchars($alumno_id) . '">
                <input type="submit" value="Generar Informe PDF">
              </form>';
    } else {
        echo "No se encontraron actividades o calificaciones.";
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
    ?>
</div>
</body>
</html>
