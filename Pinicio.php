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

// Obtener el ID del docente desde la sesión
$docente_id = $_SESSION['user_id'];

// Consulta para obtener el número de documento del docente desde la tabla docentes
$sql = "SELECT documento FROM docentes WHERE id_docente = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $docente_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $docente = $result->fetch_assoc();
    $documento = $docente['documento'];

    // Definir la ruta de la imagen de perfil basada en el documento del docente en la carpeta 'Pperfil'
    $profile_picture = "Pperfil/" . $documento . ".jpg";
    
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
            <a href="Pinicio.php">Inicio</a>
            <a href="Ppersonal.php">Personal</a>    
            <a href="Pcursos.php">Cursos</a>
            <a href="Pperfil.php">Perfil</a>
            <a href="chat_docentes.php">Chat</a>
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
    <footer>
        <p>&copy; 2024 Aula Virtual. Todos los derechos reservados.</p>
    </footer>
</body>
</html>
