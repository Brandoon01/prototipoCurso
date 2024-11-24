<?php
session_start();
include 'php/conexion.php';

// Verificar si hay una sesión activa
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$docente_id = $_SESSION['user_id'];

// Verificar si el usuario es un docente
$stmt = $conn->prepare("SELECT id_docente FROM Docentes WHERE id_docente = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Acceso denegado. Sólo los docentes pueden acceder a esta página.";
    exit();
}
$stmt->close();

// Obtener el número de documento del docente desde la tabla Docentes
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

// Obtener la lista de alumnos asociados a los cursos del docente
$sql = "SELECT DISTINCT Alumnos.id_alumno, Alumnos.nombre, Alumnos.apellido 
        FROM Inscripciones 
        JOIN Cursos ON Inscripciones.id_curso = Cursos.id_curso
        JOIN Alumnos ON Inscripciones.id_alumno = Alumnos.id_alumno
        WHERE Cursos.id_docente = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$students = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Chat Docente</title>
    <link rel="stylesheet" href="css/chat.css">
    <link rel="icon" href="img/logo.jpg" type="image/x-icon">
</head>
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
<body>
    <!-- Imagen de perfil del usuario -->
    <div class="profile-container">
        <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Foto de perfil" class="profile-pic">
    </div>

    <h2>Chat para Docentes</h2>
    <div id="messages"></div>
    <form id="sendMessageForm">
        <label for="destinatario">Seleccione un alumno:</label>
        <select id="destinatario" required>
        <option value="">Seleccione un alumno</option>
            <?php while ($student = $students->fetch_assoc()): ?>
                <option value="<?php echo $student['id_alumno']; ?>">
                    <?php echo htmlspecialchars($student['nombre'] . " " . $student['apellido']); ?>
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

        fetch(`fetch_messages_docente.php?destinatario=${selectedDestinatario}`)
            .then(response => response.text())
            .then(data => {
                messagesDiv.innerHTML = data;
                // Hacer scroll hasta el último mensaje
                messagesDiv.scrollTop = messagesDiv.scrollHeight;
            })
            .catch(error => console.error("Error fetching messages:", error));
    }

    // Actualizar mensajes solo cuando se cambie el destinatario
    destinatarioSelect.addEventListener("change", fetchMessages);

    document.getElementById("sendMessageForm").addEventListener("submit", function (event) {
        event.preventDefault();

        const destinatario = document.getElementById("destinatario").value;
        const mensaje = document.getElementById("mensaje").value;

        fetch("send_message_docente.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: `destinatario=${encodeURIComponent(destinatario)}&mensaje=${encodeURIComponent(mensaje)}`,
        })
            .then(response => response.text())
            .then(data => {
                document.getElementById("mensaje").value = "";
                fetchMessages(); // Actualizar mensajes inmediatamente después de enviar
            })
            .catch(error => console.error("Error sending message:", error));
    });
});
</script>
</body>
</html>
