<?php
require 'php/conexion.php'; // Asegúrate de que este archivo tenga la conexión correcta con tu base de datos

// Variables para almacenar errores y mensajes de éxito
$error = '';
$success = '';

// Registrar inscripciones en cursos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Inscribir alumno en curso
        if ($_POST['action'] === 'inscribirCurso') {
            $documento = $_POST['documento'] ?? '';
            $curso = $_POST['curso'] ?? '';

            if (empty($documento) || empty($curso)) {
                $error = 'Debe completar todos los campos para inscribir al alumno en un curso.';
            } else {
                $query = "
                    INSERT INTO Inscripciones (id_alumno, id_curso)
                    SELECT a.id_alumno, c.id_curso
                    FROM Alumnos a, Cursos c
                    WHERE a.documento = ? AND c.nombre_curso = ?
                ";

                $stmt = $conn->prepare($query);
                $stmt->bind_param('ss', $documento, $curso);

                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        $success = 'Alumno inscrito correctamente en el curso.';
                    } else {
                        $error = 'No se pudo inscribir al alumno. Verifique el documento y el nombre del curso.';
                    }
                } else {
                    $error = 'Error al ejecutar la consulta de inscripción en curso.';
                }
            }
        }

        // Inscribir alumno en subcurso
        if ($_POST['action'] === 'inscribirSubcurso') {
            $documento = $_POST['documento'] ?? '';
            $subcurso = $_POST['subcurso'] ?? '';

            if (empty($documento) || empty($subcurso)) {
                $error = 'Debe completar todos los campos para inscribir al alumno en un subcurso.';
            } else {
                $query = "
                    INSERT INTO InscripcionesSubcursos (id_alumno, id_subcurso)
                    SELECT a.id_alumno, s.id_subcurso
                    FROM Alumnos a, Subcursos s
                    WHERE a.documento = ? AND s.nombre_subcurso = ?
                ";

                $stmt = $conn->prepare($query);
                $stmt->bind_param('ss', $documento, $subcurso);

                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        $success = 'Alumno inscrito correctamente en el subcurso.';
                    } else {
                        $error = 'No se pudo inscribir al alumno. Verifique el documento y el nombre del subcurso.';
                    }
                } else {
                    $error = 'Error al ejecutar la consulta de inscripción en subcurso.';
                }
            }
        }

        // Eliminar inscripción en curso
        if ($_POST['action'] === 'eliminarInscripcion') {
            $id_inscripcion = $_POST['id_inscripcion'] ?? 0;

            $query = "DELETE FROM Inscripciones WHERE id_inscripcion = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $id_inscripcion);

            if ($stmt->execute()) {
                $success = 'Inscripción eliminada correctamente.';
            } else {
                $error = 'Error al eliminar la inscripción.';
            }
        }

        // Eliminar inscripción en subcurso
        if ($_POST['action'] === 'eliminarInscripcionSubcurso') {
            $id_inscripcion_subcurso = $_POST['id_inscripcion_subcurso'] ?? 0;

            $query = "DELETE FROM InscripcionesSubcurso WHERE id_inscripcion_subcurso = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $id_inscripcion_subcurso);

            if ($stmt->execute()) {
                $success = 'Inscripción en subcurso eliminada correctamente.';
            } else {
                $error = 'Error al eliminar la inscripción en subcurso.';
            }
        }
    }
}

// Listar inscripciones en cursos
$queryCursos = "
    SELECT i.id_inscripcion, a.documento, CONCAT(a.nombre, ' ', a.apellido) AS alumno, c.nombre_curso AS curso
    FROM Inscripciones i
    JOIN Alumnos a ON i.id_alumno = a.id_alumno
    JOIN Cursos c ON i.id_curso = c.id_curso
";
$resultCursos = $conn->query($queryCursos);
$inscripcionesCursos = $resultCursos->fetch_all(MYSQLI_ASSOC);

// Listar inscripciones en subcursos
$querySubcursos = "
    SELECT isub.id_inscripcion_subcurso, a.documento, CONCAT(a.nombre, ' ', a.apellido) AS alumno, s.nombre_subcurso AS subcurso
    FROM InscripcionesSubcursos isub
    JOIN Alumnos a ON isub.id_alumno = a.id_alumno
    JOIN Subcursos s ON isub.id_subcurso = s.id_subcurso
";
$resultSubcursos = $conn->query($querySubcursos);
$inscripcionesSubcurso = $resultSubcursos->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscripciones</title>
    <link rel="stylesheet" href="css/admi.css">
</head>
<header>
        <h1>Panel de Administración de Cursos</h1>
    </header>
    <nav>
        <a href="admin.php">Alumnos</a>
        <a href="admin2.php">Docentes</a>
        <a href="admin3.php">Cursos</a>
        <a href="admin5.php">Materias</a>
        <a href="admin4.php">Inscripciones</a>
        <a href="php/cerrar.php">Cerrar sesión</a>
    </nav>
<body>
    <main>
        <h1>Gestión de Inscripciones</h1>

        <!-- Mostrar errores y mensajes -->
        <?php if (!empty($error)): ?>
            <p style="color: red;"><?= $error ?></p>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <p style="color: green;"><?= $success ?></p>
        <?php endif; ?>

        <!-- Inscripción en Cursos -->
        <section>
            <h2>Inscribir Alumno en Curso</h2>
            <form method="POST">
                <input type="hidden" name="action" value="inscribirCurso">
                <label for="documento">Documento del Alumno:</label>
                <input type="text" name="documento" id="documento" required>
                <label for="curso">Nombre del Curso:</label>
                <input type="text" name="curso" id="curso" required>
                <button type="submit">Inscribir</button>
            </form>
        </section>

        <!-- Inscripción en Subcursos -->
        <section>
            <h2>Inscribir Alumno en Subcurso</h2>
            <form method="POST">
                <input type="hidden" name="action" value="inscribirSubcurso">
                <label for="documento">Documento del Alumno:</label>
                <input type="text" name="documento" id="documento" required>
                <label for="subcurso">Nombre del Subcurso:</label>
                <input type="text" name="subcurso" id="subcurso" required>
                <button type="submit">Inscribir</button>
            </form>
        </section>

        <!-- Lista de Inscripciones en Cursos -->
        <section>
            <h2>Lista de Inscripciones en Cursos</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Documento</th>
                        <th>Alumno</th>
                        <th>Curso</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inscripcionesCursos as $inscripcion): ?>
                        <tr>
                            <td><?= $inscripcion['id_inscripcion'] ?></td>
                            <td><?= $inscripcion['documento'] ?></td>
                            <td><?= $inscripcion['alumno'] ?></td>
                            <td><?= $inscripcion['curso'] ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="eliminarInscripcion">
                                    <input type="hidden" name="id_inscripcion" value="<?= $inscripcion['id_inscripcion'] ?>">
                                    <button type="submit" onclick="return confirm('¿Está seguro de eliminar esta inscripción?')">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <!-- Lista de Inscripciones en Subcursos -->
        <section>
            <h2>Lista de Inscripciones en Subcursos</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Documento</th>
                        <th>Alumno</th>
                        <th>Subcurso</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inscripcionesSubcurso as $inscripcionSubcurso): ?>
                        <tr>
                            <td><?= $inscripcionSubcurso['id_inscripcion_subcurso'] ?></td>
                            <td><?= $inscripcionSubcurso['documento'] ?></td>
                            <td><?= $inscripcionSubcurso['alumno'] ?></td>
                            <td><?= $inscripcionSubcurso['subcurso'] ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="eliminarInscripcionSubcurso">
                                    <input type="hidden" name="id_inscripcion_subcurso" value="<?= $inscripcionSubcurso['id_inscripcion_subcurso'] ?>">
                                    <button type="submit" onclick="return confirm('¿Está seguro de eliminar esta inscripción?')">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
