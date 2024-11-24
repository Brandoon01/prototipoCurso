<?php
session_start();
include 'php/conexion.php'; // Conexión a la base de datos

// Verifica que el usuario sea un docente
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$userId = $_SESSION['user_id'];
$user_id = $_SESSION['user_id'];

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
// Consultar todos los mensajes del foro publicados por estudiantes y docentes
$query = "
    SELECT foro_mensajes.id, foro_mensajes.mensaje, foro_mensajes.fecha, 
           alumnos.nombre AS alumno_nombre, docentes.nombre AS docente_nombre
    FROM foro_mensajes
    LEFT JOIN Alumnos AS alumnos ON foro_mensajes.id_alumno = alumnos.id_alumno
    LEFT JOIN Docentes AS docentes ON foro_mensajes.id_docente = docentes.id_docente
    ORDER BY foro_mensajes.fecha ASC";
$result = $conn->query($query);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Foro Global para Docentes</title>
    <link rel="stylesheet" href="css/foro.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- Cargar jQuery -->
</head>
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
<body>
<!-- Foto de perfil -->
        <div class="profile-container">
            <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Foto de perfil" class="profile-pic">
        </div>
    <h2>Foro Global para Docentes</h2>

    <!-- Mostrar mensajes del foro -->
    <div class="forum-messages">
        <?php while ($row = $result->fetch_assoc()) : ?>
            <div class="message">
                <strong>
                    <?php 
                    echo $row['alumno_nombre'] ? $row['alumno_nombre'] : $row['docente_nombre']; 
                    ?>
                </strong>:
                <p><?php echo htmlspecialchars($row['mensaje']); ?></p>
                <span><?php echo date('d-m-Y H:i', strtotime($row['fecha'])); ?></span>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- Formulario para enviar mensajes al foro -->
    <div class="message-form">
        <form action="mensaje_foro_docente.php" method="post">
            <textarea name="mensaje" placeholder="Escribe tu mensaje para todos..." required></textarea>
            <button type="submit">Enviar</button>
        </form>
    </div>
</body>
</html>
