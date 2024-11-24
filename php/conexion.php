<?php
// Datos de conexión a la base de datos
$NAMEHOSTBD = 'localhost';
$USERNAMEBD = 'root';
$PASSWORDBD = '';
$BDNAME     = 'prototipo'; 

// Realizar la conexión a la base de datos
$conn = new mysqli($NAMEHOSTBD, $USERNAMEBD, $PASSWORDBD, $BDNAME);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
$conectar = mysqli_connect($NAMEHOSTBD, $USERNAMEBD, $PASSWORDBD, $BDNAME);
if (!$conectar) {
    die("No se pudo conectar a la base de datos: " . mysqli_connect_error());
}

// Intentos máximos de reconexión
$max_retries = 3;
$retry_count = 0;

// Intentar conectar
do {
    $conn = new mysqli($NAMEHOSTBD, $USERNAMEBD, $PASSWORDBD, $BDNAME);

    if ($conn->connect_error) {
        $retry_count++;
        if ($retry_count >= $max_retries) {
            die("Conexión fallida tras múltiples intentos: " . $conn->connect_error);
        }
        // Espera antes de reintentar (ej. 1 segundo)
        sleep(1);
    } else {
        break;
    }
} while ($retry_count < $max_retries);

// Verificar conexión final
if ($conn->connect_error) {
    die("No se pudo conectar a la base de datos: " . $conn->connect_error);
} else {
  
}

?>