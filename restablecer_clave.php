<?php
require 'vendor/autoload.php';
require 'php/conexion.php';

$conn = new PDO("mysql:host=$NAMEHOSTBD;dbname=$BDNAME;charset=utf8mb4", $USERNAMEBD, $PASSWORDBD);

// Validar el token desde el enlace
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['token'])) {
    $token = $_GET['token'];

    // Verificar si el token existe y no ha expirado
    $stmt = $conn->prepare("SELECT id, fecha_creacion FROM RecuperacionClave WHERE token = :token");
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    $recuperacion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$recuperacion) {
        echo "El token no es válido.";
        exit;
    }

    // Validar si el token ha expirado (24 horas)
    $fechaCreacion = strtotime($recuperacion['fecha_creacion']);
    $expira = $fechaCreacion + (24 * 60 * 60); // 24 horas en segundos

    if (time() > $expira) {
        echo "El token ha expirado.";
        exit;
    }
}

// Procesar el formulario enviado para actualizar la contraseña
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['token'])) {
    $token = $_POST['token'];
    $nuevaClave = password_hash($_POST['nueva_clave'], PASSWORD_BCRYPT);

    // Obtener el email asociado al token
    $stmt = $conn->prepare("SELECT email FROM RecuperacionClave WHERE token = :token");
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    $recuperacion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$recuperacion) {
        echo "El token no es válido.";
        exit;
    }

    $email = $recuperacion['email'];

    // Actualizar la contraseña del usuario (Alumnos o Docentes)
    $stmtAlumno = $conn->prepare("UPDATE Alumnos SET contraseña = :clave WHERE email = :email");
    $stmtAlumno->bindParam(':clave', $nuevaClave);
    $stmtAlumno->bindParam(':email', $email);
    $stmtAlumno->execute();

    // Si no se actualizó en Alumnos, intentar con Docentes
    if ($stmtAlumno->rowCount() === 0) {
        $stmtDocente = $conn->prepare("UPDATE Docentes SET contraseña = :clave WHERE email = :email");
        $stmtDocente->bindParam(':clave', $nuevaClave);
        $stmtDocente->bindParam(':email', $email);
        $stmtDocente->execute();

        if ($stmtDocente->rowCount() === 0) {
            echo "No se pudo actualizar la contraseña. Contacta al administrador.";
            exit;
        }
    }

    // Eliminar el token de recuperación
    $stmt = $conn->prepare("DELETE FROM RecuperacionClave WHERE token = :token");
    $stmt->bindParam(':token', $token);
    $stmt->execute();

    echo "Contraseña actualizada con éxito.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Clave</title>
        <link rel="stylesheet" href="css/login.css">
    <link rel="icon" href="img/logo.jpg" type="image/x-icon">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <?php if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($token)): ?>
        <form method="post">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <label for="nueva_clave">Nueva Contraseña:</label>
            <input type="password" id="nueva_clave" name="nueva_clave" required>
            <button type="submit">Actualizar Contraseña</button>
        </form>
    <?php endif; ?>
</body>
</html>
