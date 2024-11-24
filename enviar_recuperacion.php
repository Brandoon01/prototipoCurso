<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'php/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    // Verificar si el email existe en alguna de las tablas
    $stmt = $conn->prepare("SELECT email FROM administrador WHERE email = ? UNION SELECT email FROM Alumnos WHERE email = ? UNION SELECT email FROM Docentes WHERE email = ?");
    $stmt->bind_param("sss", $email, $email, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $token = bin2hex(random_bytes(50));
        $stmt = $conn->prepare("INSERT INTO RecuperacionClave (email, token) VALUES (?, ?)");
        $stmt->bind_param("ss", $email, $token);
        if ($stmt->execute()) {  // Verificar si el token se inserta correctamente
            $url = "http://tusitio.com/restablecer_clave.php?token=$token";

            // Configuración de PHPMailer
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'tu_email@gmail.com';
                $mail->Password = 'tu_contraseña';  // Cambiar por contraseña de aplicación si es necesario
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;  // Usar la constante para TLS
                $mail->Port = 587;

                // Configuración del mensaje
                $mail->setFrom('tu_email@gmail.com', 'Recuperación de Contraseña');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Recuperación de contraseña';
                $mail->Body = "Haz clic en el siguiente enlace para restablecer tu contraseña: <a href='$url'>$url</a>";

                $mail->send();
                echo "Correo de recuperación enviado. Revisa tu bandeja de entrada.";
            } catch (Exception $e) {
                echo "Error al enviar el correo: {$mail->ErrorInfo}";
            }
        } else {
            echo "Error al generar el token de recuperación.";
        }
    } else {
        echo "El correo electrónico no está registrado.";
    }
}
?>