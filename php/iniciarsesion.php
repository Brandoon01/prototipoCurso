<?php
session_start();
include 'conexion.php'; // Conexión a la base de datos

if (isset($_POST['email']) && isset($_POST['contraseña'])) {
    $email = $_POST['email'];
    $password = $_POST['contraseña'];

    // Consulta en la tabla Alumnos
    $query_alumno = "SELECT * FROM Alumnos WHERE email = '$email' AND password = '$password'";
    $result_alumno = mysqli_query($conn, $query_alumno);

    // Consulta en la tabla Docentes
    $query_docente = "SELECT * FROM Docentes WHERE email = '$email' AND password = '$password'";
    $result_docente = mysqli_query($conn, $query_docente);

    if (mysqli_num_rows($result_alumno) > 0) {
        // Si el usuario está en la tabla Alumnos
        $row = mysqli_fetch_assoc($result_alumno);
        $_SESSION['id_alumno'] = $row['id_alumno']; // ID del alumno
        $_SESSION['nombre'] = $row['nombre']; // Nombre del alumno
        header("Location: inicio.php"); // Redirigir al inicio
        exit();
    } elseif (mysqli_num_rows($result_docente) > 0) {
        // Si el usuario está en la tabla Docentes
        $row = mysqli_fetch_assoc($result_docente);
        $_SESSION['id_docente'] = $row['id_docente']; // ID del docente
        $_SESSION['nombre'] = $row['nombre']; // Nombre del docente
        $_SESSION['role'] = 'Docente'; // Rol de Docente
        header("Location: PInicios.php"); // Redirigir al inicio
        exit();
    } else {
        // Si el usuario no existe
        echo "<script>alert('Correo o contraseña incorrecta. Inténtelo de nuevo.'); window.location.href='index.html';</script>";
    }
}
?>
