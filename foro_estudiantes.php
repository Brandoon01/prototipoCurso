<?php
session_start();
include 'php/conexion.php'; // Conexión a la base de datos

// Verifica que el usuario sea un estudiante
if (!isset($_SESSION['user_id'])) {
    echo "Acceso denegado.";
    exit;
}

$userId = $_SESSION['user_id'];

// Obtener el ID del alumno desde la sesión
$alumno_id = $_SESSION['user_id'];

// Consulta para obtener el número de documento del alumno
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
    // En caso de que no se encuentre el documento, asignar la imagen predeterminada
    $profile_picture = "img/default-profile.png";
}

// Consultar todos los mensajes del foro
$query = "
    SELECT foro_mensajes.id, foro_mensajes.mensaje, foro_mensajes.fecha, 
           alumnos.nombre AS alumno_nombre, docentes.nombre AS docente_nombre
    FROM foro_mensajes
    LEFT JOIN Alumnos AS alumnos ON foro_mensajes.id_alumno = alumnos.id_alumno
    LEFT JOIN Docentes AS docentes ON foro_mensajes.id_docente = docentes.id_docente
    ORDER BY foro_mensajes.fecha ASC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Foro Global para Estudiantes</title>
    <link rel="stylesheet" href="css/foro.css">
    <link rel="icon" href="img/logo.jpg" type="image/x-icon">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- Cargar jQuery -->
</head>
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
    <div class="profile-container">
        <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Foto de perfil" class="profile-pic">
    </div>
<body>
    <h2>Foro Global para Estudiantes</h2>

    <!-- Mostrar mensajes del foro -->
    <div class="forum-messages" id="messages-container">
        <?php while ($row = $result->fetch_assoc()) : ?>
            <div class="message" id="message-<?php echo $row['id']; ?>">
                <strong>
                    <?php 
                    echo $row['alumno_nombre'] ? $row['alumno_nombre'] : $row['docente_nombre']; 
                    ?>
                </strong>:
                <p><?php echo htmlspecialchars($row['mensaje']); ?></p>
                <span><?php echo date('d-m-Y H:i', strtotime($row['fecha'])); ?></span>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- Formulario para enviar mensajes al foro -->
    <div class="message-form">
        <form id="message-form">
            <textarea name="mensaje" id="mensaje" placeholder="Escribe tu mensaje para todos..." required></textarea>
            <button type="submit">Enviar</button>
        </form>
    </div>

    <script>
        // AJAX para enviar el mensaje sin recargar la página
        $(document).ready(function() {
            // Función para enviar un mensaje
            $('#message-form').on('submit', function(e) {
                e.preventDefault();  // Evita que se recargue la página al enviar el formulario

                var mensaje = $('#mensaje').val();  // Obtener el valor del mensaje

                // Enviar el mensaje a través de AJAX
                $.ajax({
                    url: 'enviar_mensaje.php',  // Archivo que procesa el mensaje
                    type: 'POST',
                    data: { mensaje: mensaje },
                    success: function(response) {
                        if(response == 'success') {
                            $('#mensaje').val('');  // Limpiar el campo de mensaje
                        } else {
                            alert('Error al enviar el mensaje.');
                        }
                    }
                });
            });

je            function cargarMensajes() {
                $.ajax({
                    url: 'cargar_mensajes.php',  // Archivo que carga los mensajes del foro
                    type: 'GET',
                    success: function(response) {
                        $('#messages-container').html(response);  // Actualizar el contenedor de mensajes
                    }
                });
            }

            // Cargar los mensajes cada 2 segundos
            setInterval(cargarMensajes, 2000);  // 2000 milisegundos = 2 segundos
        });
    </script>
</body>
</html>
