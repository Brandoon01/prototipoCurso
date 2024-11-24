<?php
session_start();
include 'php/conexion.php';

// Verificar si hay una sesión activa
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Verificar si el usuario es un alumno
$stmt = $conn->prepare("SELECT id_alumno FROM Alumnos WHERE id_alumno = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Acceso denegado. Sólo los alumnos pueden acceder a esta página.";
    exit();
}
$stmt->close();

// Obtener el número de documento del alumno
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

// Obtener la lista de docentes asociados a los cursos del alumno
$sql = "SELECT DISTINCT d.id_docente, d.nombre, d.apellido 
        FROM Inscripciones i
        JOIN Cursos c ON i.id_curso = c.id_curso
        JOIN Docentes d ON c.id_docente = d.id_docente
        WHERE i.id_alumno = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teachers = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Chat Alumno</title>
    <link rel="stylesheet" href="css/chat.css">
    <link rel="icon" href="img/logo.jpg" type="image/x-icon">
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
<body>
    <!-- Imagen de perfil del usuario -->
    <div class="profile-container">
        <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Foto de perfil" class="profile-pic">
    </div>

    <h2>Chat para Alumnos</h2>
    <div id="messages"></div>
    <form id="sendMessageForm">
        <label for="destinatario">Seleccione un profesor:</label>
        <select id="destinatario" required>
            <option value="">Seleccione un profesor</option>
            <?php while ($teacher = $teachers->fetch_assoc()): ?>
                <option value="<?php echo $teacher['id_docente']; ?>">
                    <?php echo htmlspecialchars($teacher['nombre'] . " " . $teacher['apellido']); ?>
                </option>
            <?php endwhile; ?>
        </select>
        <textarea id="mensaje" placeholder="Escriba su mensaje aquí" required></textarea>
        <button type="submit">Enviar</button>
    </form>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const messagesDiv = document.getElementById("messages");
        const destinatarioSelect = document.getElementById("destinatario");

        function fetchMessages() {
            const selectedDestinatario = destinatarioSelect.value;
            if (!selectedDestinatario) return;

            fetch(`fetch_messages.php?destinatario=${selectedDestinatario}`)
                .then(response => response.text())
                .then(data => {
                    messagesDiv.innerHTML = data;
                    messagesDiv.scrollTop = messagesDiv.scrollHeight;
                })
                .catch(error => console.error("Error fetching messages:", error));
        }

        // Actualizar mensajes cuando se cambie el destinatario
        destinatarioSelect.addEventListener("change", fetchMessages);

        // Actualizar mensajes periódicamente solo si hay un destinatario seleccionado

        document.getElementById("sendMessageForm").addEventListener("submit", function (event) {
            event.preventDefault();

            const destinatario = document.getElementById("destinatario").value;
            const mensaje = document.getElementById("mensaje").value;

            fetch("send_message.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: `destinatario=${encodeURIComponent(destinatario)}&mensaje=${encodeURIComponent(mensaje)}`,
            })
                .then(response => response.text())
                .then(data => {
                    document.getElementById("mensaje").value = "";
                    fetchMessages();
                })
                .catch(error => console.error("Error sending message:", error));
        });
    });
    </script>
</body>
</html>
