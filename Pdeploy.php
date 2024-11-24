<?php
// Incluir el archivo de conexión a la base de datos
include 'php/conexion.php';

// Iniciar sesión
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html"); // Redirigir al login si no hay sesión activa
    exit();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Obtener el ID del docente desde la sesión
$user_id = $_SESSION['user_id'];

// Obtener el ID del curso desde la URL
$id_curso = isset($_GET['id_curso']) ? intval($_GET['id_curso']) : 0;

// Consultar el nombre y correo del docente
$query_docente = "SELECT nombre, email, documento FROM Docentes WHERE id_docente = ?";
$stmt_docente = $conn->prepare($query_docente);
if ($stmt_docente === false) {
    die('Error al preparar la consulta del docente.');
}
$stmt_docente->bind_param("i", $user_id);
$stmt_docente->execute();
$result_docente = $stmt_docente->get_result();

// Verificar si se encontró el docente
$docente = $result_docente->fetch_assoc();

// Definir la ruta de la imagen de perfil basada en el documento del docente en la carpeta 'Pperfil'
if ($docente) {
    $documento = $docente['documento'];
    $profile_picture = "Pperfil/" . $documento . ".jpg";
    
    // Si la imagen de perfil no existe, usar una imagen predeterminada
    if (!file_exists($profile_picture)) {
        $profile_picture = "img/default-profile.png";
    }
}

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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aula Virtual</title>
    <link rel="stylesheet" href="css/deplo.css">
    <link rel="icon" href="img/logo.jpg" type="image/x-icon">
</head>
<body>
    <header>
        <h1>Perfil del Docente</h1>
        <nav>
            <a href="Pinicio.php">Inicio</a>
            <a href="Ppersonal.php">Personal</a>    
            <a href="Pcursos.php">Cursos</a>
            <a href="Pperfil.php">Perfil</a>
            <a href="chat_docentes.php">Chat</a>
            <a href="php/cerrar.php">Cerrar sesión</a>
        </nav>
    </header>
    <div class="container">

        <!-- Foto de perfil -->
        <div class="profile-container">
            <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Foto de perfil" class="profile-pic">
        </div>

        <!-- Tabs de navegación -->
        <ul class="tabs">
            <li class="active"><a href="Pdeploy.php?id_curso=<?php echo urlencode($id_curso); ?>">Curso</a></li>
            <li><a href="Pparticipantes.php?id_curso=<?php echo urlencode($id_curso); ?>">Participantes</a></li>
            <li><a href="Pcalificaciones.php?id_curso=<?php echo urlencode($id_curso); ?>">Calificaciones</a></li>
        </ul>

        <!-- Información del curso -->
        <div class="course-info">
            <h2>Materias</h2>
            <div class="curso">
                <ul>
                    <?php
                    // Mostrar los subcursos en la lista
                    if ($result_subcursos->num_rows > 0) {
                        while ($subcurso = $result_subcursos->fetch_assoc()) {
                            // Mostrar enlace para cada subcurso
                            echo "<li><a href='actividades.php?id_subcurso=" . htmlspecialchars($subcurso['id_subcurso']) . "' class='course-button'>" . htmlspecialchars($subcurso['nombre_subcurso']) . "</a></li>";
                        }
                    } else {
                        echo "<li>No hay subcursos disponibles para este curso.</li>";
                    }
                    ?>
                </ul>
            </div>
        </div>

        <!-- Sección de bienvenida -->
        <div class="bienvenido">
            <h2>Bienvenido</h2>
            <?php if ($docente): ?>
                <p><strong>Docente:</strong> <?php echo htmlspecialchars($docente['nombre']); ?></p>
                <p><strong>Correo electrónico:</strong> <?php echo htmlspecialchars($docente['email']); ?></p>
            <?php else: ?>
                <p>No se encontró información del docente.</p>
            <?php endif; ?>
        </div>
    </div>
 <!-- Botón de foro -->
        <div class="forum-button-container">
            <a href="foro_docentes.php" class="forum-button">Foro</a>
        </div>
    <!-- Footer (si es necesario) -->
</body>
</html>


<?php
// Cerrar la conexión del docente y los subcursos
$stmt_docente->close();
$stmt_subcursos->close();
$conn->close();
?>
