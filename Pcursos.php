<?php
session_start();
include('php/conexion.php'); // Incluye la conexión a la base de datos

// Verifica si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    // Si no está logueado, redirige al inicio de sesión
    header("Location: login.html");
    exit();
}

// Obtén el ID del docente
$user_id = $_SESSION['user_id'];

// Prepara la consulta para obtener los cursos impartidos por el docente
$query = "SELECT nombre_curso, id_curso
          FROM cursos 
          WHERE id_docente = ?";

$stmt = $conectar->prepare($query); // Prepara la consulta
$stmt->bind_param("i", $user_id);    // Asigna el ID del docente
$stmt->execute();
$result = $stmt->get_result();       // Obtiene los resultados de la consulta

// Consulta para obtener el número de documento del docente
$sql = "SELECT documento FROM docentes WHERE id_docente = ?";
$stmt_docente = $conectar->prepare($sql);
$stmt_docente->bind_param("i", $user_id);
$stmt_docente->execute();
$result_docente = $stmt_docente->get_result();

if ($result_docente->num_rows > 0) {
    $docente = $result_docente->fetch_assoc();
    $documento = $docente['documento'];

    // Definir la ruta de la imagen de perfil
    $profile_picture = "Pperfil/" . $documento . ".jpg";
    
    // Si la imagen de perfil no existe, usar una imagen predeterminada
    if (!file_exists($profile_picture)) {
        $profile_picture = "img/default-profile.png";
    }
} else {
    // En caso de que no se encuentre el documento, asignar la imagen predeterminada
    $profile_picture = "img/default-profile.png";
}

$stmt_docente->close();
?>
<!DOCTYPE html>
<html lang="en">
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
                <a href="Pinicio.php">Inicio</a>
                <a href="Ppersonal.php">Personal</a>    
                <a href="Pcursos.php">Cursos</a>
                <a href="Pperfil.php">Perfil</a>
                <a href="chat_docentes.php">Chat</a>
                <a href="php/cerrar.php">Cerrar sesión</a>
            </nav>
            <!-- Imagen de perfil en el header -->
            <div class="profile-container">
                <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Foto de perfil" class="profile-pic">
            </div>
        </div>
    </header>

    <main>
        <section class="container">
            <h2>Mis cursos</h2>

            <div class="filters">
                <input type="text" placeholder="Buscar" id="search">
            </div>

            <div class="courses">
                <?php
                // Verifica si el docente tiene cursos registrados
                if ($result->num_rows > 0) {
                    // Recorre los cursos y los muestra
                    while ($row = $result->fetch_assoc()) {
                        echo '<div class="course">';
                        echo '<img src="cursos/course2.jpg" alt="Curso">'; // Imagen genérica del curso
                        // Cambia el nombre del curso a un enlace con clase 'course-link'
                        echo '<a href="Pdeploy.php?id_curso=' . htmlspecialchars($row['id_curso']) . '" class="course-link">' . htmlspecialchars($row['nombre_curso']) . '</a>';
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
        <p>&copy; 2023 Aula Virtual</p>
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
$stmt->close();
$conectar->close();
?>
