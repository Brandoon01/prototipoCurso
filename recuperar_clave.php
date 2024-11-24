<?php
require 'vendor/autoload.php'; // Asegúrate de instalar PHPMailer con Composer
require 'php/conexion.php';       // Archivo de conexión a la base de datos

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

    if (!$email) {
        echo "Correo inválido.";
        exit;
    }

    // Verificar si el correo existe en las tablas Alumnos o Docentes
    $stmt = $conn->prepare("SELECT email FROM Alumnos WHERE email = ? UNION SELECT email FROM Docentes WHERE email = ?");
    $stmt->bind_param('ss', $email, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "Correo no encontrado.";
        exit;
    }

    // Generar token único
    $token = bin2hex(random_bytes(32));

    // Insertar el token en la tabla RecuperacionClave
    $stmt = $conn->prepare("INSERT INTO RecuperacionClave (email, token) VALUES (?, ?)");
    $stmt->bind_param('ss', $email, $token);
    $stmt->execute();

    // Configurar y enviar el correo
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP para Gmail
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Servidor SMTP de Gmail
        $mail->SMTPAuth = true;
        $mail->Username = 'buitragoruizb24@gmail.com'; // Cambia esto a tu correo de Gmail
        $mail->Password = 'jjtt tthk izxc kziw'; // Cambia esto a tu contraseña para aplicaciones
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Encriptación SSL
        $mail->Port = 465; // Puerto para SSL

        // Configurar el correo
        $mail->setFrom('tu-correo@gmail.com', 'Recuperación de Contraseña'); // Cambia esto
        $mail->addAddress($email); // Destinatario
        $mail->isHTML(true);
        $mail->Subject = 'Recuperación de contraseña';
        $mail->Body = "
            <h3>Recupera tu contraseña</h3>
            <p>Haz clic en el enlace para restablecer tu contraseña:</p>
            <a href='http://localhost/test/restablecer_clave.php?token=$token'>Restablecer contraseña</a>
            <p>Este enlace expira pronto, úsalo lo antes posible.</p>
        ";

        $mail->send();
        echo "Correo enviado con éxito. Revisa tu bandeja de entrada.";
    } catch (Exception $e) {
        echo "Hubo un error al enviar el correo: {$mail->ErrorInfo}";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Clave</title>
        <link rel="stylesheet" href="css/login.css">
    <link rel="icon" href="img/logo.jpg" type="image/x-icon">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <form method="post">
        <label for="email">Ingresa tu correo electrónico:</label>
        <input type="email" id="email" name="email" required>
        <button type="submit">Recuperar</button>
    </form>
</body>
</html>
