<?php
session_start();
include 'php/conexion.php';

// Verificar si hay una sesión activa
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html"); // Redirigir al login si no hay sesión activa
    exit();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Obtener el ID del alumno desde la sesión
$alumno_id = $_SESSION['user_id'];

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
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aula Virtual</title>
    <link rel="stylesheet" href="css/inicio.css">
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

    <!-- Imagen de perfil del usuario fuera del navbar, en la parte superior de la página -->
    <div class="profile-container">
        <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Foto de perfil" class="profile-pic">
    </div>
    <main>
        <section class="virtual-classroom">
            <div class="container">
                <h2>Aula Virtual</h2>
                <p>Nuestra aula virtual está diseñada para proporcionar una experiencia de aprendizaje enriquecedora y flexible. A continuación, se presentan algunas de sus características clave:</p>
                <div class="features-grid">
                    <div class="feature">
                        <h3>Interactividad</h3>
                        <p>Participa en discusiones en tiempo real y colabora con compañeros y profesores a través de foros y chats.</p>
                    </div>
                    <div class="feature">
                        <h3>Material Multimedia</h3>
                        <p>Accede a videos, presentaciones y recursos interactivos que facilitan el aprendizaje.</p>
                    </div>
                    <div class="feature">
                        <h3>Evaluaciones y Retroalimentación</h3>
                        <p>Realiza evaluaciones en línea y recibe retroalimentación inmediata para mejorar tu rendimiento.</p>
                    </div>
                    <div class="feature">
                        <h3>Acceso a Recursos</h3>
                        <p>Disponibilidad de materiales de estudio, libros y artículos en una biblioteca digital accesible las 24 horas.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>  
    <script src="java/script.js"></script>
</body>
</html>
