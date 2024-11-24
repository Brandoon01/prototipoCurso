<?php
session_start();
include 'php/conexion.php';

// Verifica si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

// Obtener el ID y número de documento del usuario desde la sesión
$alumno_id = $_SESSION['user_id'];

// Consulta para obtener los datos del alumno, incluyendo el número de documento
$sql = "SELECT nombre, apellido, carrera, email, telefono, documento FROM alumnos WHERE id_alumno = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $alumno_id);
$stmt->execute();
$result = $stmt->get_result();

// Verificar si se encontró el alumno
if ($result->num_rows > 0) {
    $alumno_datos = $result->fetch_assoc();
} else {
    echo "No se encontraron datos para el alumno.";
    exit();
}
$stmt->close();

// Consulta para obtener las materias en las que el alumno está inscrito
$query = "SELECT c.nombre_curso FROM cursos c JOIN inscripciones i ON c.id_curso = i.id_curso WHERE i.id_alumno = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $alumno_id);
$stmt->execute();
$result_materias = $stmt->get_result();

// Establecer la ruta de la imagen de perfil
$profile_picture = 'perfil/' . $alumno_datos['documento'] . '.jpg';
if (!file_exists($profile_picture)) {
    $profile_picture = 'img/default-profile.png'; // Imagen predeterminada
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil del Estudiante</title>
    <link rel="stylesheet" href="css/perfil.css">
    <link rel="icon" href="img/logo.jpg" type="image/x-icon">
</head>
<body>
    <!-- Contenedor de la imagen de perfil a la izquierda -->
    <div class="profile-container">
        <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Foto de perfil" class="profile-pic">
    </div>

    <header>
        <h1>Perfil del Estudiante</h1>
        <nav>
            <a href="inicio.php">Inicio</a>
            <a href="personal.php">Personal</a>    
            <a href="cursos.php">Cursos</a>
            <a href="perfil.php">Perfil</a>
            <a href="chat_estudiantes.php">Chat</a>
            <a href="php/cerrar.php">Cerrar sesión</a>
            
        </nav>
    </header>
    
    <main>
        <section class="perfil">
            <h2>Información Personal</h2>
            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($alumno_datos['nombre']); ?></p>
            <p><strong>Apellido:</strong> <?php echo htmlspecialchars($alumno_datos['apellido']); ?></p>
            <p><strong>Carrera:</strong> <?php echo htmlspecialchars($alumno_datos['carrera']); ?></p>
        </section>
        
        <section class="materias">
            <h2>Materias Inscritas</h2>
            <ul>
                <?php
                if ($result_materias->num_rows > 0) {
                    while ($row = $result_materias->fetch_assoc()) {
                        echo '<li>' . htmlspecialchars($row['nombre_curso'], ENT_QUOTES, 'UTF-8') . '</li>';
                    }
                } else {
                    echo '<p>No tienes cursos registrados.</p>';
                }
                ?>
            </ul>
        </section>
        
        <section class="contacto">
            <h2>Contacto</h2>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($alumno_datos['email']); ?></p>
            <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($alumno_datos['telefono']); ?></p>
        </section>
        
        <section class="upload-profile-picture">
            <h2>Actualizar Foto de Perfil</h2>
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="file" name="profile_picture" accept="image/*" required>
                <button type="submit" name="upload">Subir Foto</button>
            </form>
        </section>
    </main>
    
    <footer>
        <p>&copy; 2024 Aula Virtual. Todos los derechos reservados.</p>
    </footer>
</body>
</html>

<?php
// Procesar la subida de imagen si se ha enviado el formulario
if (isset($_POST['upload']) && isset($_FILES['profile_picture'])) {
    $file_tmp = $_FILES['profile_picture']['tmp_name'];
    $file_type = mime_content_type($file_tmp);
    $target_dir = "perfil/";
    $target_file = $target_dir . $alumno_datos['documento'] . ".jpg";

    // Eliminar cualquier archivo existente con el mismo nombre para reemplazar la foto de perfil
    if (file_exists($target_file)) {
        unlink($target_file);
    }

    // Verificar que el archivo sea una imagen válida
    if (in_array($file_type, ['image/jpeg', 'image/png', 'image/gif'])) {
        if ($file_type != 'image/jpeg') {
            $image = null;
            if ($file_type == 'image/png') {
                $image = imagecreatefrompng($file_tmp);
            } elseif ($file_type == 'image/gif') {
                $image = imagecreatefromgif($file_tmp);
            }

            if ($image) {
                imagejpeg($image, $target_file, 90);
                imagedestroy($image);
            }
        } else {
            move_uploaded_file($file_tmp, $target_file);
        }

        header("Location: perfil.php?upload=success");
        exit();
    } else {
        echo "<p>El archivo debe ser una imagen (JPG, PNG o GIF).</p>";
    }
}

$stmt->close();
$conn->close();
?>
