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

// Obtener el ID del alumno desde la sesión y el curso desde la URL
$user_id = $_SESSION['user_id'];
$id_curso = isset($_GET['id_curso']) ? intval($_GET['id_curso']) : 0;

// Consulta para obtener el número de documento del alumno
$sql = "SELECT documento FROM alumnos WHERE id_alumno = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
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

// Consultar los subcursos del curso actual en los que está inscrito el alumno
$query_subcursos = "
    SELECT s.id_subcurso, s.nombre_subcurso 
    FROM Subcursos s
    JOIN InscripcionesSubcursos isub ON s.id_subcurso = isub.id_subcurso
    WHERE isub.id_alumno = ? AND s.id_curso = ?";
$stmt_subcursos = $conn->prepare($query_subcursos);
$stmt_subcursos->bind_param("ii", $user_id, $id_curso);
$stmt_subcursos->execute();
$result_subcursos = $stmt_subcursos->get_result();

// Almacenar todos los subcursos en un array
$subcursos = [];
while ($subcurso = $result_subcursos->fetch_assoc()) {
    $subcursos[] = $subcurso; // Agregar cada subcurso al array
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subcursos</title>
    <link rel="stylesheet" href="css/deplo.css">
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
        <a href="cursos.php" class="back-link">
            <span class="arrow">&#8592;</span> Volver
        </a>
    </div>

    <!-- Imagen de perfil del usuario en la parte superior de la página -->
    <div class="profile-container">
        <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Foto de perfil" class="profile-pic">
    </div>

    <div class="container">

        <!-- Tabs de navegación -->
        <ul class="tabs">
            <li class="active"><a href="deploy.php?id_curso=<?php echo urlencode($id_curso); ?>">Subcursos</a></li>
            <?php
            // Mostrar el primer subcurso como enlace a participantes
            if (!empty($subcursos)) {
                echo "<li><a href='participantes.php?id_subcurso=" . htmlspecialchars($subcursos[0]['id_subcurso']) . "' class='course-button'>Participantes</a></li>";
            } else {
                echo "<li>No hay subcursos disponibles para este curso.</li>";
            }
            ?>
            <li><a href="calificaciones.php?id_curso=<?php echo htmlspecialchars($id_curso); ?>" class="course-button">Calificaciones</a></li>
        </ul>

        <!-- Información de los subcursos -->
        <div class="course-info">
            <div class="curso">
                <ul>
                    <?php
                    // Mostrar todos los subcursos en la lista redirigiendo a deploy.php
                    foreach ($subcursos as $subcurso) {
                        echo "<li><a href='desploy.php?id_subcurso=" . htmlspecialchars($subcurso['id_subcurso']) . "' class='course-button'>" . htmlspecialchars($subcurso['nombre_subcurso']) . "</a></li>";
                    }

                    // Cerrar la conexión
                    $stmt_subcursos->close();
                    $conn->close();
                    ?>
                </ul>
            </div>
        </div>

        <!-- Sección de bienvenida -->
        <div class="bienvenido">
            <h2>Bienvenido</h2>
        </div>
    </div>
</body>
</html>
