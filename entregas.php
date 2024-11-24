<?php
include 'php/conexion.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html"); // Redirigir al login si no hay sesión activa
    exit();
}

$id_subcurso = isset($_GET['id_subcurso']) ? intval($_GET['id_subcurso']) : 0;
$id_actividad = isset($_GET['id_actividad']) ? intval($_GET['id_actividad']) : 0;
$id_alumno = $_SESSION['user_id'] ?? 0;

// Consulta para obtener el número de documento del alumno
$sql = "SELECT documento FROM alumnos WHERE id_alumno = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_alumno);
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

// Verificar si la actividad existe
$sql_check_activity = "SELECT * FROM Actividades WHERE id_actividad = ? AND id_subcurso = ?";
$stmt_check_activity = $conn->prepare($sql_check_activity);
$stmt_check_activity->bind_param('ii', $id_actividad, $id_subcurso);
$stmt_check_activity->execute();
$stmt_check_activity->store_result();

if ($stmt_check_activity->num_rows === 0) {
    die("La actividad especificada no existe o no pertenece al subcurso seleccionado. (ID Subcurso: $id_subcurso, ID Actividad: $id_actividad)");
}
$stmt_check_activity->close();

// Verificar si el alumno ya ha realizado una entrega y obtener la calificación si está disponible
$sql_verificar_entrega = "SELECT * FROM Entregas WHERE id_actividad = ? AND id_alumno = ?";
$stmt_verificar_entrega = $conn->prepare($sql_verificar_entrega);
$stmt_verificar_entrega->bind_param('ii', $id_actividad, $id_alumno);
$stmt_verificar_entrega->execute();
$result_verificar_entrega = $stmt_verificar_entrega->get_result();

$entrega_existente = $result_verificar_entrega->fetch_assoc();
$nota = $entrega_existente['calificacion'] ?? null;
$stmt_verificar_entrega->close();

// Obtener el nombre del subcurso
$sql_materia = "SELECT nombre_subcurso FROM Subcursos WHERE id_subcurso = ?";
$stmt_materia = $conn->prepare($sql_materia);
$stmt_materia->bind_param('i', $id_subcurso);
$stmt_materia->execute();
$stmt_materia->bind_result($nombre_subcurso);
$stmt_materia->fetch();
$stmt_materia->close();

// Limpiar el nombre del curso para crear la carpeta
$nombre_materia = preg_replace('/[^A-Za-z0-9_\-]/', '', $nombre_subcurso);

// Crear carpeta para las entregas si no existe
$carpeta_materia = 'uploads/' . $nombre_materia;
if (!is_dir($carpeta_materia)) {
    mkdir($carpeta_materia, 0777, true);
}

$mensaje = '';

// Manejar la subida de archivos
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['archivo'])) {
    $nombre_archivo = $_FILES['archivo']['name'];
    $ruta_archivo = $carpeta_materia . '/' . basename($nombre_archivo);

    if (move_uploaded_file($_FILES['archivo']['tmp_name'], $ruta_archivo)) {
        if ($entrega_existente) {
            // Actualizar entrega existente
            $sql_update = "UPDATE Entregas SET nombre_archivo = ?, ruta_archivo = ? WHERE id_actividad = ? AND id_alumno = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param('ssii', $nombre_archivo, $ruta_archivo, $id_actividad, $id_alumno);

            if ($stmt_update->execute()) {
                $mensaje = "Entrega actualizada con éxito.";
            } else {
                $mensaje = "Error al actualizar la entrega: " . $stmt_update->error;
            }
            $stmt_update->close();
        } else {
            // Insertar nueva entrega
            $sql_insert = "INSERT INTO Entregas (id_actividad, id_alumno, nombre_archivo, ruta_archivo) VALUES (?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param('iiss', $id_actividad, $id_alumno, $nombre_archivo, $ruta_archivo);

            if ($stmt_insert->execute()) {
                $mensaje = "Archivo subido y entrega registrada con éxito.";
            } else {
                $mensaje = "Error al registrar la entrega: " . $stmt_insert->error;
            }
            $stmt_insert->close();
        }
    } else {
        $mensaje = "Error al subir el archivo.";
    }
}

// Obtener detalles de la actividad
$sql_actividad = "SELECT titulo, archivo_adjunto, ruta_archivo FROM Actividades WHERE id_actividad = ?";
$stmt_actividad = $conn->prepare($sql_actividad);
$stmt_actividad->bind_param('i', $id_actividad);
$stmt_actividad->execute();
$stmt_actividad->bind_result($actividad_titulo, $archivo_adjunto, $ruta_archivo);
$stmt_actividad->fetch();
$stmt_actividad->close();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Entregas</title>
    <link rel="stylesheet" href="css/archivo.css">
    <link rel="icon" href="img/logo.jpg" type="image/x-icon">
</head>
<body>
<header>
    <h1>Entregas del Alumno</h1>
    <nav>
        <a href="inicio.php">Inicio</a>
        <a href="personal.php">Personal</a>    
        <a href="cursos.php">Cursos</a>
        <a href="perfil.php">Perfil</a>
        <a href="chat_estudiantes.php">Chat</a>
        <a href="php/cerrar.php">Cerrar sesión</a>
        
    </nav>
    <!-- Imagen de perfil del usuario -->
    <div class="profile-container">
        <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Foto de perfil" class="profile-pic">
    </div>
</header>

<h1>Lista de Entregas del Subcurso: <?php echo htmlspecialchars($nombre_subcurso); ?></h1>

<h2>Entrega para la Actividad: <?php echo htmlspecialchars($actividad_titulo); ?></h2>

<?php if ($entrega_existente): ?>
    <p>Ya has realizado una entrega para esta actividad:</p>
    <ul>
        <li><strong>Archivo:</strong> <?php echo htmlspecialchars($entrega_existente['nombre_archivo']); ?></li>
        <li><a href="php/descargar.php?id_entrega=<?php echo $entrega_existente['id_entrega']; ?>">Descargar</a></li>
        <?php if (!is_null($nota)): ?>
            <li><strong>Calificación:</strong> <?php echo htmlspecialchars($nota); ?></li>
        <?php else: ?>
            <li><em>Nota aún no asignada</em></li>
        <?php endif; ?>
    </ul>

    <form action="entregas.php?id_subcurso=<?php echo $id_subcurso; ?>&id_actividad=<?php echo $id_actividad; ?>" method="post" enctype="multipart/form-data">
        <label for="archivo">Subir un nuevo archivo para reemplazar el actual:</label>
        <input type="file" id="archivo" name="archivo" required><br>
        <button type="submit">Actualizar Entrega</button>
    </form>
<?php else: ?>
    <form action="entregas.php?id_subcurso=<?php echo $id_subcurso; ?>&id_actividad=<?php echo $id_actividad; ?>" method="post" enctype="multipart/form-data">
        <label for="archivo">Archivo:</label>
        <input type="file" id="archivo" name="archivo" required><br>
        <button type="submit">Subir Archivo</button>
    </form>
<?php endif; ?>

<h2>Documentos Cargados por el Docente</h2>
<?php if (!empty($archivo_adjunto)): ?>
    <ul>
        <li>
            <strong><?php echo htmlspecialchars($actividad_titulo); ?></strong> - 
            <a href="<?php echo htmlspecialchars($archivo_adjunto); ?>" target="_blank">Descargar</a>
        </li>
    </ul>
<?php else: ?>
    <p>No hay documentos cargados para esta actividad.</p>
<?php endif; ?>

<?php if ($mensaje): ?>
    <script>
        alert("<?php echo htmlspecialchars($mensaje); ?>");
        window.location.href = "entregas.php?id_subcurso=<?php echo $id_subcurso; ?>&id_actividad=<?php echo $id_actividad; ?>";
    </script>
<?php endif; ?>

</body>
</html>
