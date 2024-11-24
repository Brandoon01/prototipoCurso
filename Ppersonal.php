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

// Obtener el ID del docente desde la sesión
$docente_id = $_SESSION['user_id'];

// Consulta para obtener el número de documento del docente desde la tabla docentes
$sql = "SELECT documento FROM docentes WHERE id_docente = ?";
$stmt = $conn->prepare($sql);
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

$stmt->close();

// Establecer el orden por defecto (por fecha)
$orden = 'fecha_entrega DESC'; 

// Verifica si se seleccionó un filtro para ordenar
if (isset($_GET['orden'])) {
    if ($_GET['orden'] == 'fecha') {
        $orden = 'fecha_entrega DESC'; // Ordenar por fecha descendente
    } elseif ($_GET['orden'] == 'materia') {
        $orden = 'c.nombre_curso ASC'; // Ordenar por nombre del curso de forma alfabética
    }
}

// Consulta para obtener las actividades que el docente ha recibido entregas de los alumnos,
// pero solo las entregas que no han sido calificadas (calificacion IS NULL)
$query_actividades = "SELECT a.titulo, e.fecha_entrega, s.nombre_subcurso, c.nombre_curso
                      FROM entregas e
                      JOIN actividades a ON e.id_actividad = a.id_actividad
                      JOIN subcursos s ON a.id_subcurso = s.id_subcurso
                      JOIN cursos c ON s.id_curso = c.id_curso
                      WHERE c.id_docente = ?
                      AND e.calificacion IS NULL  -- Filtra las entregas no calificadas
                      ORDER BY $orden"; // Ordenar dinámicamente según el parámetro
$stmt_actividades = $conn->prepare($query_actividades);
$stmt_actividades->bind_param("i", $docente_id);
$stmt_actividades->execute();
$result_actividades = $stmt_actividades->get_result();
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

    <!-- Imagen de perfil del usuario fuera del navbar, en la parte superior de la página -->
    <div class="profile-container">
        <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Foto de perfil" class="profile-pic">
    </div>

    <main>
        <section>
            <h1>Área personal</h1>
            <section class="timeline">
                <h2>Línea de tiempo</h2>
                <div class="timeline-filters">
                    <select onchange="window.location.href=this.value">
                        <option value="Ppersonal.php?orden=fecha" <?php echo (isset($_GET['orden']) && $_GET['orden'] == 'fecha') ? 'selected' : ''; ?>>Ordenar por fecha</option>
                        <option value="Ppersonal.php?orden=materia" <?php echo (isset($_GET['orden']) && $_GET['orden'] == 'materia') ? 'selected' : ''; ?>>Ordenar por materia</option>
                    </select>
                    <input type="text" placeholder="Buscar por tipo o nombre de actividad">
                </div>
                <div id="activity-list">
                    <?php
                    // Mostrar las actividades que han recibido entregas
                    if ($result_actividades->num_rows > 0) {
                        while ($row = $result_actividades->fetch_assoc()) {
                            echo '<div class="activity">';
                            echo '<h3>' . htmlspecialchars($row['titulo']) . '</h3>';
                            echo '<p>Curso: ' . htmlspecialchars($row['nombre_curso']) . '</p>';
                            echo '<p>Subcurso: ' . htmlspecialchars($row['nombre_subcurso']) . '</p>';
                            echo '<p>Fecha de entrega: ' . htmlspecialchars($row['fecha_entrega']) . '</p>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="no-activities">';
                        echo '<img src="img/icono.jpg" alt="No hay actividades">';
                        echo '<p>No hay actividades con entregas registradas o todas las entregas han sido calificadas.</p>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </section>
            <footer>
                <p>&copy; 2024 Aula Virtual. Todos los derechos reservados.</p>
            </footer>
        </main>
    </body>
</html>

<?php
// Cierra la conexión y la declaración
$stmt_actividades->close();
$conn->close();
?>
