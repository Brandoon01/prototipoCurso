<?php
// Conexión a la base de datos
require_once 'php/conexion.php';

class AdminPanel {
    private $db;

    public function __construct($conexion) {
        $this->db = $conexion;
    }

    // Función para obtener el ID del docente usando su documento
    private function obtenerIdDocentePorDocumento($documento) {
        $stmt = $this->db->prepare("SELECT id_docente FROM Docentes WHERE documento = ?");
        $stmt->bind_param("s", $documento);
        $stmt->execute();
        $stmt->bind_result($id_docente);
        $stmt->fetch();
        $stmt->close();
        return $id_docente;
    }

    // Función para obtener el nombre del docente usando su ID
    public function obtenerNombreDocentePorId($id_docente) {
        $stmt = $this->db->prepare("SELECT nombre FROM Docentes WHERE id_docente = ?");
        $stmt->bind_param("i", $id_docente);
        $stmt->execute();
        $stmt->bind_result($nombre_docente);
        $stmt->fetch();
        $stmt->close();
        return $nombre_docente;
    }

    // Función para agregar un curso con el documento del docente
    public function agregarCurso($nombre_curso, $documento_docente) {
        $id_docente = $this->obtenerIdDocentePorDocumento($documento_docente);
        if (!$id_docente) {
            return "error: Docente no encontrado.";
        }
        
        $stmt = $this->db->prepare("INSERT INTO Cursos (nombre_curso, id_docente) VALUES (?, ?)");
        $stmt->bind_param("si", $nombre_curso, $id_docente);
        return $stmt->execute() ? "mensaje: Curso agregado con éxito." : "error: Error al agregar el curso.";
    }

    // Función para modificar un curso usando el nombre del curso y el documento del docente
    public function modificarCurso($nombre_curso, $nuevo_nombre_curso, $documento_docente) {
        $id_docente = $this->obtenerIdDocentePorDocumento($documento_docente);
        if (!$id_docente) {
            return "error: Docente no encontrado.";
        }

        $stmt = $this->db->prepare("UPDATE Cursos SET nombre_curso = ?, id_docente = ? WHERE nombre_curso = ?");
        $stmt->bind_param("sis", $nuevo_nombre_curso, $id_docente, $nombre_curso);
        return $stmt->execute() ? "mensaje: Curso modificado con éxito." : "error: Error al modificar el curso.";
    }

    // Función para eliminar un curso y agregarlo a la tabla CursosEliminados
    public function eliminarCurso($nombre_curso) {
        // Obtener datos del curso antes de eliminarlo
        $stmt = $this->db->prepare("SELECT nombre_curso, id_docente FROM Cursos WHERE nombre_curso = ?");
        $stmt->bind_param("s", $nombre_curso);
        $stmt->execute();
        $stmt->bind_result($curso, $docente);
        $stmt->fetch();
        $stmt->close();

        // Insertar el curso en la tabla CursosEliminados junto con la fecha de eliminación
        if ($curso && $docente) {
            $fecha_eliminacion = date('Y-m-d H:i:s'); // Fecha de eliminación actual
            $stmt = $this->db->prepare("INSERT INTO CursosEliminados (nombre_curso, id_docente, fecha_eliminacion) VALUES (?, ?, ?)");
            $stmt->bind_param("sis", $curso, $docente, $fecha_eliminacion);
            $stmt->execute();
        }

        // Eliminar el curso de la tabla Cursos
        $stmt = $this->db->prepare("DELETE FROM Cursos WHERE nombre_curso = ?");
        $stmt->bind_param("s", $nombre_curso);
        return $stmt->execute() ? "mensaje: Curso eliminado con éxito y agregado a CursosEliminados." : "error: Error al eliminar el curso.";
    }

    // Función para obtener los cursos con el nombre del docente
    public function obtenerCursos() {
        $result = $this->db->query("SELECT c.nombre_curso, d.nombre, d.documento FROM Cursos c JOIN Docentes d ON c.id_docente = d.id_docente");
        $cursos = [];
        while ($row = $result->fetch_assoc()) {
            $cursos[] = $row;
        }
        return $cursos;
    }

    // Función para obtener cursos eliminados con el nombre del docente y la fecha de eliminación
    public function obtenerCursosEliminados() {
        $result = $this->db->query("SELECT nombre_curso, id_docente, fecha_eliminacion FROM CursosEliminados");
        $cursosEliminados = [];
        while ($row = $result->fetch_assoc()) {
            // Obtener el nombre del docente usando el id_docente
            $nombre_docente = $this->obtenerNombreDocentePorId($row['id_docente']);
            $cursosEliminados[] = [
                'nombre_curso' => $row['nombre_curso'],
                'nombre_docente' => $nombre_docente,
                'fecha_eliminacion' => $row['fecha_eliminacion']
            ];
        }
        return $cursosEliminados;
    }
}

// Crear una instancia del panel de administración
$adminPanel = new AdminPanel($conn);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'agregarCurso':
                $mensaje = $adminPanel->agregarCurso($_POST['nombre_curso'], $_POST['documento_docente']);
                break;
            case 'eliminarCurso':
                $mensaje = $adminPanel->eliminarCurso($_POST['nombre_curso']);
                break;
            case 'modificarCurso':
                $mensaje = $adminPanel->modificarCurso($_POST['nombre_curso'], $_POST['nuevo_nombre_curso'], $_POST['documento_docente']);
                break;
        }
    }
}

// Obtener lista de cursos y cursos eliminados
$cursos = $adminPanel->obtenerCursos();
$cursosEliminados = $adminPanel->obtenerCursosEliminados();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrador de Cursos</title>
    <link rel="stylesheet" href="css/admi.css">
    <link rel="icon" href="img/logo.jpg" type="image/x-icon">
</head>
<body>
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
    <main>
        <!-- Formulario para agregar nuevo curso en la primera parte -->
        <h2>Agregar Curso</h2>
        <form method="POST">
            <input type="hidden" name="action" value="agregarCurso">
            <label for="nombre_curso">Nombre del Curso:</label>
            <input type="text" name="nombre_curso" required>
            <label for="documento_docente">Documento del Docente:</label>
            <input type="text" name="documento_docente" required>
            <input type="submit" value="Agregar Curso">
        </form>

        <!-- Mensaje de acción en cuadro tipo alerta -->
        <?php if (isset($mensaje)): ?>
            <?php if (strpos($mensaje, 'mensaje:') === 0): ?>
                <div class="mensaje"><?php echo htmlspecialchars(substr($mensaje, 8)); ?></div>
            <?php elseif (strpos($mensaje, 'error:') === 0): ?>
                <div class="error"><?php echo htmlspecialchars(substr($mensaje, 7)); ?></div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Mostrar cursos existentes -->
        <h2>Cursos Existentes</h2>
        <table>
            <thead>
                <tr>
                    <th>Curso</th>
                    <th>Docente</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cursos as $curso): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($curso['nombre_curso']); ?></td>
                        <td><?php echo htmlspecialchars($curso['nombre']); ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="modificarCurso">
                                <input type="hidden" name="nombre_curso" value="<?php echo htmlspecialchars($curso['nombre_curso']); ?>">
                                <input type="text" name="nuevo_nombre_curso" placeholder="Nuevo nombre" required>
                                <input type="text" name="documento_docente" placeholder="Documento docente" required>
                                <input type="submit" value="Modificar">
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="eliminarCurso">
                                <input type="hidden" name="nombre_curso" value="<?php echo htmlspecialchars($curso['nombre_curso']); ?>">
                                <input type="submit" value="Eliminar">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Mostrar cursos eliminados -->
        <h2>Cursos Eliminados</h2>
        <?php if (count($cursosEliminados) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Curso</th>
                        <th>Docente</th>
                        <th>Fecha de Eliminación</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cursosEliminados as $cursoEliminado): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cursoEliminado['nombre_curso']); ?></td>
                            <td><?php echo htmlspecialchars($cursoEliminado['nombre_docente']); ?></td>
                            <td><?php echo htmlspecialchars($cursoEliminado['fecha_eliminacion']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No hay cursos eliminados.</p>
        <?php endif; ?>
    </main>
</body>
</html>
