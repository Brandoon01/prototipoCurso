<?php
// Conectar a la base de datos
include 'php/conexion.php';

session_start(); // Inicia sesión para acceder a datos del alumno
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html"); // Redirigir al login si no hay sesión activa
    exit();
}
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$id_curso = isset($_GET['id_curso']) ? intval($_GET['id_curso']) : 0;

$alumno_id = $_SESSION['user_id'] ?? null;
$id_subcurso = isset($_GET['id_subcurso']) ? intval($_GET['id_subcurso']) : 0;

if (!$alumno_id) {
    die("Error: No se encontró el ID del alumno en la sesión.");
}

if (!$id_subcurso) {
    die("Error: No se encontró el ID del subcurso en la sesión.");
}

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
    $profile_picture = "img/default-profile.png";
}

$stmt->close();

// Consulta para obtener las actividades del alumno en el subcurso específico
$query = "
    SELECT a.id_actividad, a.titulo, a.descripcion, a.fecha_entrega, d.nombre AS docente_nombre 
    FROM Actividades a
    JOIN Subcursos s ON a.id_subcurso = s.id_subcurso
    JOIN Docentes d ON a.id_docente = d.id_docente
    JOIN Inscripciones i ON s.id_curso = i.id_curso
    WHERE i.id_alumno = ? AND a.id_subcurso = ? 
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $alumno_id, $id_subcurso);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actividades Disponibles</title>
    <link rel="stylesheet" href="css/desploy.css">
    <link rel="icon" href="img/logo.jpg" type="image/x-icon">
</head>
<body>
<header>
    <h1>Perfil del Alumno</h1>
    <nav>
        <a href="inicio.php">Inicio</a>
        <a href="personal.php">Personal</a>    
        <a href="cursos.php">Cursos</a>
        <a href="perfil.php">Perfil</a>
        <a href="chat_estudiantes.php">Chat</a>
        <a href="php/cerrar.php">Cerrar sesión</a>
    </nav>
</header>

<!-- Botón "Volver" con flecha -->
<div class="back-button">
    <a href="deploy.php" class="back-link">
        <span class="arrow">&#8592;</span> Volver
    </a>
</div>

<!-- Imagen de perfil del usuario -->
<div class="profile-container">
    <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Foto de perfil" class="profile-pic">
</div>

<h2>Actividades Disponibles en el Subcurso</h2>

<?php
// Verificar si hay actividades
if ($result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>Título</th><th>Descripción</th><th>Fecha de Entrega</th><th>Docente</th><th>Acciones</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $fecha_entrega = strtotime($row['fecha_entrega']);
        $fecha_actual = time();
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['titulo']) . "</td>";
        echo "<td>" . htmlspecialchars($row['descripcion']) . "</td>";
        echo "<td>" . htmlspecialchars($row['fecha_entrega']) . "</td>";
        echo "<td>" . htmlspecialchars($row['docente_nombre']) . "</td>";

        if ($fecha_actual > $fecha_entrega) {
            echo "<td><span>Fecha límite pasada</span></td>";
        } else {
            echo "<td><a href=\"entregas.php?id_subcurso={$id_subcurso}&id_actividad={$row['id_actividad']}\">Subir Entrega</a></td>";
        }

        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No hay actividades disponibles en este subcurso.</p>";
}?>
<!-- Botón de Foro -->
<div class="forum-button-container">
    <a href="foro_estudiantes.php" class="forum-button">Foro</a>
</div>


</body>
</html>