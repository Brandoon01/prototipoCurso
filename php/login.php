<?php
session_start();
require 'conexion.php'; // Incluir la conexión a la base de datos

// Verificar si se han recibido los datos del formulario de inicio de sesión
if (isset($_POST['email']) && isset($_POST['contraseña'])) {
    $email = $_POST['email'];
    $password = $_POST['contraseña'];

    // Escapar caracteres especiales para prevenir inyecciones SQL
    $email = mysqli_real_escape_string($conectar, $email);
    $password = mysqli_real_escape_string($conectar, $password);

    // Consulta en la tabla administrador
    $query_admin = "SELECT * FROM administrador WHERE email = '$email' AND contraseña = '$password'";
    $result_admin = mysqli_query($conectar, $query_admin);

    // Consulta en la tabla Alumnos
    $query_alumno = "SELECT * FROM Alumnos WHERE email = '$email' AND contraseña = '$password'";
    $result_alumno = mysqli_query($conectar, $query_alumno);

    // Consulta en la tabla Docentes
    $query_docente = "SELECT * FROM Docentes WHERE email = '$email' AND contraseña = '$password'";
    $result_docente = mysqli_query($conectar, $query_docente);

    // Verificar si la consulta en la tabla Alumnos devuelve resultados
    if (mysqli_num_rows($result_alumno) > 0) {
        $row = mysqli_fetch_assoc($result_alumno);
        $_SESSION['user_id'] = $row['id_alumno']; // ID del alumno
        $_SESSION['user_name'] = $row['nombre'];  // Nombre del alumno
        header("Location: ../inicio.php"); // Redirigir al inicio
        exit();
    } 
    // Verificar si la consulta en la tabla Docentes devuelve resultados
    elseif (mysqli_num_rows($result_docente) > 0) {
        $row = mysqli_fetch_assoc($result_docente);
        $_SESSION['user_id'] = $row['id_docente']; // ID del docente
        $_SESSION['user_name'] = $row['nombre'];  // Nombre del docente
        header("Location: ../Pinicio.php"); // Redirigir al inicio
        exit();
    } 
    // Verificar si la consulta en la tabla Administrador devuelve resultados
    elseif (mysqli_num_rows($result_admin) > 0) {
        $row = mysqli_fetch_assoc($result_admin);
        header("Location: ../admin.php");
        exit();
    }
    // Si no se encuentra el usuario en ninguna de las tablas
    else {
        echo "<script>alert('Correo o contraseña incorrectos. Inténtelo de nuevo.'); window.location.href='../login.html';</script>";
    }
} else {
    // Si no se completan los campos del formulario
    echo "<script>alert('Por favor complete todos los campos.'); window.location.href='../login.html';</script>";
}
?>