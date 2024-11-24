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

// Obtener el id de actividad desde la URL, si existe
$id_actividad = isset($_GET['id_actividad']) ? $_GET['id_actividad'] : null;

// Asegurarse de que el id de actividad es válido
if ($id_actividad) {
    // Consulta para obtener las entregas de una actividad específica
    $query = "
        SELECT Entregas.id_entrega, Entregas.nombre_archivo, Entregas.ruta_archivo, Entregas.fecha_entrega,
               Alumnos.nombre AS nombre_alumno, Alumnos.apellido AS apellido_alumno,
               Actividades.titulo AS titulo_actividad, Actividades.descripcion AS descripcion_actividad,
               Entregas.calificacion
        FROM Entregas
        JOIN Alumnos ON Entregas.id_alumno = Alumnos.id_alumno
        JOIN Actividades ON Entregas.id_actividad = Actividades.id_actividad
        WHERE Actividades.id_actividad = $id_actividad
        ORDER BY Entregas.fecha_entrega DESC";
} else {
    echo "No se ha especificado una actividad.";
    exit;
}

$result = $conn->query($query);

// Comprobar si se envió una calificación
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_entrega']) && isset($_POST['calificacion'])) {
    $id_entrega = $_POST['id_entrega'];
    $calificacion = $_POST['calificacion'];

    // Consulta para actualizar la calificación
    $update_query = "UPDATE Entregas SET calificacion = ? WHERE id_entrega = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("di", $calificacion, $id_entrega);
    $stmt->execute();

    // Redirigir a la misma página para recargar
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Entregas de Alumnos</title>
    <link rel="stylesheet" href="css/deplo.css">
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
<div class="container">
    <!-- Foto de perfil -->
    <div class="profile-container">
        <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Foto de perfil" class="profile-pic">
    </div>

    <h1>Entregas de Alumnos</h1>

    <table>
        <tr>
            <th>Alumno</th>
            <th>Actividad</th>
            <th>Descripción</th>
            <th>Archivo</th>
            <th>Fecha de Entrega</th>
            <th>Calificación</th>
        </tr>

        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['nombre_alumno'] . ' ' . $row['apellido_alumno']; ?></td>
                <td><?php echo $row['titulo_actividad']; ?></td>
                <td><?php echo $row['descripcion_actividad']; ?></td>
                <td><a href="<?php echo $row['ruta_archivo']; ?>" target="_blank"><?php echo $row['nombre_archivo']; ?></a></td>
                <td><?php echo $row['fecha_entrega']; ?></td>
                <td>
                    <?php if ($row['calificacion'] === null): ?>
                        <form action="entregas.php?id_actividad=<?php echo $id_actividad; ?>" method="POST">
                            <input type="hidden" name="id_entrega" value="<?php echo $row['id_entrega']; ?>">
                            <input type="number" name="calificacion" min="0" max="5" step="0.1" placeholder="0.0-5.0" required>
                            <button type="submit">Calificar</button>
                        </form>
                    <?php else: ?>
                        <?php echo $row['calificacion']; ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>

<?php
$conn->close();
?>
