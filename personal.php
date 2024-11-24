<?php
// Incluir el archivo de conexión a la base de datos
session_start();
include 'php/conexion.php'; // Asegúrate de que la ruta sea correcta

// Verifica si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    // Si no está logueado, redirige al inicio de sesión
    header("Location: login.html");
    exit();
}

// Obtener el ID del usuario desde la sesión
$alumno_id = $_SESSION['user_id'];

// Consulta para obtener las materias en las que el alumno está inscrito
$query = "SELECT c.nombre_curso, c.id_curso
          FROM cursos c 
          JOIN inscripciones i ON c.id_curso = i.id_curso 
          WHERE i.id_alumno = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $alumno_id);
$stmt->execute();
$result_materias = $stmt->get_result();

// Consulta para obtener las actividades pendientes de entrega
$query_actividades = "SELECT a.titulo, a.fecha_entrega, c.nombre_curso 
                      FROM actividades a
                      JOIN subcursos s ON a.id_subcurso = s.id_subcurso
                      JOIN cursos c ON s.id_curso = c.id_curso
                      LEFT JOIN entregas e ON a.id_actividad = e.id_actividad AND e.id_alumno = ?
                      WHERE c.id_curso IN (SELECT id_curso FROM inscripciones WHERE id_alumno = ?)
                      AND e.id_entrega IS NULL";
$stmt_actividades = $conn->prepare($query_actividades);
$stmt_actividades->bind_param("ii", $alumno_id, $alumno_id);
$stmt_actividades->execute();
$result_actividades = $stmt_actividades->get_result();

// Obtener el documento del alumno y establecer la imagen de perfil
$sql = "SELECT documento FROM alumnos WHERE id_alumno = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $alumno_id);
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aula Virtual</title>
    <link rel="stylesheet" href="css/personal.css">
    <link rel="icon" href="img/logo.jpg" type="image/x-icon">
</head>
<body>
    <div class="profile-container">
        <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Foto de perfil" class="profile-pic">
    </div>
    <header>
        <h1>AULA VIRTUAL</h1>
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
        <section>
            <h1>Área personal</h1>
            <section class="timeline">
                <h2>Actividades pendientes</h2>
                <div class="timeline-filters">
                    <select>
                        <option value="fecha">Ordenar por fecha</option>
                    </select>
                    <input type="text" placeholder="Buscar por tipo o nombre de actividad" id="search-activities">
                </div>
                <div id="activity-list">
                    <?php
                    if ($result_actividades->num_rows > 0) {
                        while ($row = $result_actividades->fetch_assoc()) {
                            echo '<div class="activity">';
                            echo '<h3>' . htmlspecialchars($row['titulo']) . '</h3>';
                            echo '<p>Curso: ' . htmlspecialchars($row['nombre_curso']) . '</p>';
                            echo '<p>Fecha de entrega: ' . htmlspecialchars($row['fecha_entrega']) . '</p>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="no-activities">';
                        echo '<img src="img/icono.jpg" alt="No hay actividades">';
                        echo '<p>No hay actividades que requieran acciones</p>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </section>
            <section>
                <ul>
                    <?php
                    // Mostrar los cursos obtenidos de la consulta
                    if ($result_materias->num_rows > 0) {
                        while ($row = $result_materias->fetch_assoc()) {
                            echo "<li><h3>{$row['nombre_curso']}</h3></li>";
                        }
                    } else {
                        echo "<p>No estás inscrito en ningún curso.</p>";
                    }
                    ?>
                </ul>
            </section>
            <footer>
                <p>&copy; 2024 Aula Virtual. Todos los derechos reservados.</p>
            </footer>
        </main>

    <script>
        // Función de búsqueda para filtrar actividades pendientes
        document.getElementById("search-activities").addEventListener("input", function() {
            let searchValue = this.value.toLowerCase();
            let activities = document.querySelectorAll(".activity");

            activities.forEach(activity => {
                let activityName = activity.querySelector("h3").textContent.toLowerCase();
                let courseName = activity.querySelector("p:nth-child(2)").textContent.toLowerCase();
                
                if (activityName.includes(searchValue) || courseName.includes(searchValue)) {
                    activity.style.display = "";
                } else {
                    activity.style.display = "none";
                }
            });
        });
    </script>
</body>
</html>

<?php
// Cierra la conexión y la declaración
$stmt->close();
$conn->close();
?>
