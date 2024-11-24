<?php
session_start();
include 'php/conexion.php'; // Asegúrate de que la ruta sea correcta

// Verifica si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    // Si no está logueado, redirige al inicio de sesión
    header("Location: login.html");
    exit();
}

// Obtener el ID del docente desde la sesión
$docente_id = $_SESSION['user_id'];

// Consulta para obtener los datos del docente
$sql = "SELECT nombre, apellido, email, telefono, documento FROM docentes WHERE id_docente = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $docente_id);
$stmt->execute();
$result = $stmt->get_result();

// Verificar si se encontró al docente
if ($result->num_rows > 0) {
    // Obtener los datos del docente
    $docente_datos = $result->fetch_assoc();
    $documento = $docente_datos['documento'];
} else {
    echo "No se encontraron datos para el docente.";
}
$stmt->close();

// Procesar la imagen de perfil si se ha subido una nueva
if (isset($_POST['submit_image']) && isset($_FILES['profile_image'])) {
    $target_dir = "Pperfil/"; // Directorio donde se guardará la imagen
    $target_file = $target_dir . $documento . ".jpg"; // Ruta con el número de documento

    // Verificar si el archivo es una imagen
    $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
    if ($check !== false) {
        // Si ya existe una imagen, eliminarla
        if (file_exists($target_file)) {
            unlink($target_file); // Elimina la imagen anterior
        }

        // Subir la nueva imagen
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            // Actualizar la base de datos con la nueva imagen
            $update_sql = "UPDATE docentes SET documento_imagen = ? WHERE id_docente = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $target_file, $docente_id);
            $update_stmt->execute();
            $update_stmt->close();

            echo "La imagen de perfil ha sido actualizada con éxito.";
        } else {
            echo "Lo siento, ocurrió un error al subir la imagen.";
        }
    } else {
        echo "El archivo no es una imagen válida.";
    }
}

// Consulta para obtener los cursos que imparte el docente
$query = "SELECT nombre_curso FROM cursos WHERE id_docente = ?";
$stmt = $conn->prepare($query); // Prepara la consulta
$stmt->bind_param("i", $docente_id);    // Asigna el ID del docente
$stmt->execute();
$result_materias = $stmt->get_result(); // Obtiene los resultados de la consulta
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil del Docente</title>
    <link rel="stylesheet" href="css/perfil.css">
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
    <main>
        <section class="perfil">
            <h2>Información Personal</h2>
            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($docente_datos['nombre']); ?></p>
            <p><strong>Apellido:</strong> <?php echo htmlspecialchars($docente_datos['apellido']); ?></p>
        </section>
        
        <section class="foto-perfil">
            <h2>Foto de Perfil</h2>
            <?php
            // Ruta de la imagen de perfil
            $profile_picture = "Pperfil/" . $documento . ".jpg";
            
            // Verificar si la imagen existe
            if (!file_exists($profile_picture)) {
                $profile_picture = "img/default-profile.png"; // Imagen predeterminada si no existe
            }
            ?>
            <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Foto de perfil" class="profile-pic">
            
            <!-- Formulario para cambiar la foto -->
            <form action="Pperfil.php" method="POST" enctype="multipart/form-data">
                <label for="profile_image">Cambiar Foto de Perfil</label>
                <input type="file" name="profile_image" id="profile_image" accept="image/*">
                <button type="submit" name="submit_image">Subir Nueva Imagen</button>
            </form>
        </section>
        
        <section class="materias">
            <h2>Cursos Impartidos</h2>
            <ul>
                <?php
                // Verifica si el docente tiene cursos asignados
                if ($result_materias->num_rows > 0) {
                    // Recorre los cursos y los muestra
                    while ($row = $result_materias->fetch_assoc()) {
                        echo '<li>' . htmlspecialchars($row['nombre_curso'], ENT_QUOTES, 'UTF-8') . '</li>';
                    }
                } else {
                    // Si no tiene cursos
                    echo '<p>No tienes cursos registrados.</p>';
                }
                ?>
            </ul>
        </section>

        <section class="contacto">
            <h2>Contacto</h2>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($docente_datos['email']); ?></p>
            <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($docente_datos['telefono']); ?></p>
        </section>
    </main>
    <footer>
        <p>&copy; 2024 Juan Pérez</p>
    </footer>
</body>
</html>

<?php
// Cierra la conexión y la declaración
$stmt->close();
$conn->close();
?>
