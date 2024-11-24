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
if ($stmt === false) {
    die('Error al preparar la consulta del docente.');
}
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

// Verificar si se ha pasado un id_curso en la URL
if (isset($_GET['id_curso'])) {
    $id_curso = $_GET['id_curso'];

    // Consulta SQL para obtener los alumnos inscritos en el curso
    $query = "SELECT a.id_alumno, a.nombre, a.apellido, a.documento
              FROM Alumnos a
              JOIN Inscripciones i ON a.id_alumno = i.id_alumno
              WHERE i.id_curso = ?";

    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        die('Error al preparar la consulta de alumnos.');
    }
    $stmt->bind_param("i", $id_curso);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    echo "<p>No se ha seleccionado un curso.</p>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/deplo.css">
    <title>Alumnos Inscritos</title>
    <link rel="icon" href="img/logo.jpg" type="image/x-icon">
</head>
<body>
<header>
    <h1>Participantes del Subcurso</h1>
    <nav>
        <a href="Pinicio.php">Inicio</a>
        <a href="Ppersonal.php">Personal</a>    
        <a href="Pcursos.php">Cursos</a>
        <a href="Pperfil.php">Perfil</a>
        <a href="chat_docentes.php">Chat</a>
        <a href="php/cerrar.php">Cerrar sesión</a>
    </nav>
</header>

<!-- Mostrar foto del docente en la parte superior -->
<div class="profile-container">
    <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Foto de perfil del docente" class="profile-pic-docente">
</div>

<div class="container">
    <ul class="tabs">
        <li><a href="Pdeploy.php?id_curso=<?php echo urlencode($id_curso); ?>">Curso</a></li>
        <li  class="active"><a href="Pparticipantes.php?id_curso=<?php echo urlencode($id_curso); ?>">Participantes</a></li>
        <li><a href="Pcalificaciones.php?id_curso=<?php echo urlencode($id_curso); ?>">Calificaciones</a></li>
    </ul>

    <?php if (isset($result) && $result->num_rows > 0): ?>
        <h2>Lista de Alumnos Inscritos</h2>
        <table border="1">
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Nombre</th>
                    <th>Apellido</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php
                    // Obtener el número de documento del alumno
                    $documento_alumno = $row['documento'];
                    
                    // Definir la ruta de la imagen de perfil basada en el documento del alumno en la carpeta 'perfil'
                    $profile_picture_alumno = "perfil/" . $documento_alumno . ".jpg";
                    
                    // Si la imagen de perfil no existe, usar una imagen predeterminada
                    if (!file_exists($profile_picture_alumno)) {
                        $profile_picture_alumno = "img/default-profile.png";
                    }
                    ?>
                    <tr>
                        <td><img src="<?php echo htmlspecialchars($profile_picture_alumno); ?>" alt="Foto de perfil" class="profile-pic" style="width: 50px; height: 50px; object-fit: cover;"></td>
                        <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($row['apellido']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php elseif (isset($result)): ?>
        <p>No hay alumnos inscritos en este curso.</p>
    <?php endif; ?>
</div>
</body>
</html>

<?php
// Cerrar las consultas y la conexión
$stmt->close();
$conn->close();
?>
