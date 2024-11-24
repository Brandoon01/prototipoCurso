<?php
// Incluir el archivo de conexión a la base de datos
include 'php/conexion.php';

// Iniciar sesión
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Verificar si hay una sesión activa
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html"); // Redirigir al login si no hay sesión activa
    exit();
}

// Obtener el ID del alumno desde la sesión
$user_id = $_SESSION['user_id'];

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

// Consultar los cursos en los que está inscrito el alumno
$query_cursos = "
    SELECT c.id_curso, c.nombre_curso 
    FROM Cursos c 
    JOIN Inscripciones i ON c.id_curso = i.id_curso 
    WHERE i.id_alumno = ?";
$stmt_cursos = $conn->prepare($query_cursos);
$stmt_cursos->bind_param("i", $user_id);
$stmt_cursos->execute();
$result_cursos = $stmt_cursos->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Cursos</title>
    <link rel="stylesheet" href="css/curso.css">
    <link rel="icon" href="img/logo.jpg" type="image/x-icon">
</head>
<body>
    <header>
        <div class="container">
            <h1>AULA VIRTUAL</h1>
            <nav>
                <a href="inicio.php">Inicio</a>
            <a href="personal.php">Personal</a>    
            <a href="cursos.php">Cursos</a>
            <a href="perfil.php">Perfil</a>
            <a href="chat_estudiantes.php">Chat</a>
            <a href="php/cerrar.php">Cerrar sesión</a>
            
            </nav>
        </div>
    </header>

    <!-- Imagen de perfil del usuario en la parte superior de la página -->
    <div class="profile-container">
        <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Foto de perfil" class="profile-pic">
    </div>

    <main>
        <section class="container">
            <h2>Mis cursos</h2>

            <div class="filters">
                <input type="text" placeholder="Buscar" id="search">
            </div>

            <div class="courses" id="courses">
                <?php
                // Verifica si el alumno tiene cursos registrados
                if ($result_cursos->num_rows > 0) {
                    // Recorre los cursos y los muestra
                    while ($row = $result_cursos->fetch_assoc()) {
                        echo '<div class="course">';
                        echo '<img src="cursos/course.jpg" alt="Curso">'; // Imagen genérica del curso
                        
                        // Cambia el nombre del curso a un enlace
                        echo '<a href="deploy.php?id_curso=' . htmlspecialchars($row['id_curso']) . '" class="course-link">' . htmlspecialchars($row['nombre_curso']) . '</a>';
                        
                        echo '</div>';
                    }
                } else {
                    // Si no tiene cursos
                    echo '<p>No tienes cursos registrados.</p>';
                }
                ?>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 Aula Virtual. Todos los derechos reservados.</p>
    </footer>

    <script>
        // Función de búsqueda
        document.getElementById("search").addEventListener("input", function() {
            let searchValue = this.value.toLowerCase();
            let courses = document.querySelectorAll(".course");

            courses.forEach(course => {
                let courseName = course.querySelector(".course-link").textContent.toLowerCase();
                if (courseName.includes(searchValue)) {
                    course.style.display = "";
                } else {
                    course.style.display = "none";
                }
            });
        });
    </script>
</body>
</html>

<?php
// Cierra la conexión y la declaración
$stmt_cursos->close();
$conn->close();
?>
