<?php
// Incluir el archivo de conexión a la base de datos
include 'php/conexion.php';

// Iniciar sesión
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html"); // Redirigir al login si no hay sesión activa
    exit();
}

// Obtener el ID del usuario desde la sesión
$user_id = $_SESSION['user_id'];
$id_subcurso = isset($_GET['id_subcurso']) ? intval($_GET['id_subcurso']) : 0;

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

// Consultar los alumnos inscritos en el subcurso específico
$query_participantes = "
    SELECT a.nombre, a.apellido
    FROM Alumnos a
    JOIN Inscripciones i ON a.id_alumno = i.id_alumno
    WHERE i.id_curso = (SELECT id_curso FROM Subcursos WHERE id_subcurso = ?)";

$stmt_participantes = $conn->prepare($query_participantes);
$stmt_participantes->bind_param("i", $id_subcurso);
$stmt_participantes->execute();
$result_participantes = $stmt_participantes->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participantes en Subcurso</title>
    <link rel="stylesheet" href="css/deplo.css">
    <link rel="icon" href="img/logo.jpg" type="image/x-icon">
</head>
<body>
    <header>
        <h1>Participantes del Subcurso</h1>
        <nav>
            <a href="inicio.php">Inicio</a>
            <a href="personal.php">Personal</a>    
            <a href="cursos.php">Cursos</a>
            <a href="perfil.php">Perfil</a>
            <a href="chat_estudiantes.php">Chat</a>
            <a href="php/cerrar.php">Cerrar sesión</a>
        </nav>
    </header>

    <!-- Imagen de perfil del usuario en la parte superior de la página -->
    <div class="profile-container">
        <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Foto de perfil" class="profile-pic">
    </div>

    <div class="container">
        <!-- Tabs de navegación -->
        <ul class="tabs">
            <li><a href="deploy.php?id_curso=<?php echo urlencode($id_subcurso); ?>">Curso</a></li>
            <li class="active"><a href="participantes.php?id_subcurso=<?php echo urlencode($id_subcurso); ?>" class="course-button">Participantes</a></li>
            <li><a href="calificaciones.php?id_curso=<?php echo urlencode($id_subcurso); ?>" class="course-button">Calificaciones</a></li>
        </ul>

        <!-- Información del subcurso -->
        <div class="course-info">
            <h2>Alumnos Inscritos</h2>
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Apellido</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Mostrar los alumnos inscritos
                    if ($result_participantes->num_rows > 0) {
                        while ($row = $result_participantes->fetch_assoc()) {
                            echo "<tr>
                                    <td>" . htmlspecialchars($row['nombre']) . "</td>
                                    <td>" . htmlspecialchars($row['apellido']) . "</td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='2'>No hay alumnos inscritos en este subcurso.</td></tr>";
                    }

                    // Cerrar la conexión
                    $stmt_participantes->close();
                    $conn->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
