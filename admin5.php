    <?php
    // Conexión a la base de datos
    require_once 'php/conexion.php';

    class AdminPanel {
        private $db;

        public function __construct($conexion) {
            $this->db = $conexion;
        }

        // Mostrar subcursos activos
        public function mostrarSubcursos() {
            $query = "SELECT 
                        s.id_subcurso,
                        s.nombre_subcurso, 
                        s.descripcion, 
                        c.nombre_curso AS nombre_curso, 
                        CONCAT(d.nombre, ' ', d.apellido) AS nombre_docente 
                    FROM Subcursos s
                    JOIN Cursos c ON s.id_curso = c.id_curso
                    JOIN Docentes d ON s.id_docente = d.id_docente";

            $result = $this->db->query($query);
            return $result->fetch_all(MYSQLI_ASSOC);
        }

        // Modificar un subcurso
        public function modificarSubcurso($id_subcurso, $nuevo_nombre, $descripcion, $nombre_curso, $documento_docente) {
            // Obtener ID del curso por nombre
            $cursoQuery = "SELECT id_curso FROM Cursos WHERE nombre_curso = ?";
            $stmt = $this->db->prepare($cursoQuery);
            $stmt->bind_param("s", $nombre_curso);
            $stmt->execute();
            $cursoResult = $stmt->get_result();
            $curso = $cursoResult->fetch_assoc();

            if (!$curso) {
                echo "Error: El curso no existe.";
                return false;
            }
            $id_curso = $curso['id_curso'];

            // Obtener ID del docente por documento
            $docenteQuery = "SELECT id_docente FROM Docentes WHERE documento = ?";
            $stmt = $this->db->prepare($docenteQuery);
            $stmt->bind_param("i", $documento_docente);
            $stmt->execute();
            $docenteResult = $stmt->get_result();
            $docente = $docenteResult->fetch_assoc();

            if (!$docente) {
                echo "Error: El docente no existe.";
                return false;
            }
            $id_docente = $docente['id_docente'];

            // Actualizar subcurso
            $updateQuery = "UPDATE Subcursos SET nombre_subcurso = ?, descripcion = ?, id_curso = ?, id_docente = ? WHERE id_subcurso = ?";
            $stmt = $this->db->prepare($updateQuery);
            $stmt->bind_param("ssiii", $nuevo_nombre, $descripcion, $id_curso, $id_docente, $id_subcurso);
            return $stmt->execute();
        }

        // Eliminar un subcurso por ID
        public function eliminarSubcurso($id_subcurso) {
            $query = "DELETE FROM Subcursos WHERE id_subcurso = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $id_subcurso);
            return $stmt->execute();
        }

        // Añadir un nuevo subcurso
        public function añadirSubcurso($nombre_subcurso, $descripcion, $nombre_curso, $documento_docente) {
            // Obtener ID del curso por nombre
            $cursoQuery = "SELECT id_curso FROM Cursos WHERE nombre_curso = ?";
            $stmt = $this->db->prepare($cursoQuery);
            $stmt->bind_param("s", $nombre_curso);
            $stmt->execute();
            $cursoResult = $stmt->get_result();
            $curso = $cursoResult->fetch_assoc();

            if (!$curso) {
                echo "Error: El curso no existe.";
                return false;
            }
            $id_curso = $curso['id_curso'];

            // Obtener ID del docente por documento
            $docenteQuery = "SELECT id_docente FROM Docentes WHERE documento = ?";
            $stmt = $this->db->prepare($docenteQuery);
            $stmt->bind_param("i", $documento_docente);
            $stmt->execute();
            $docenteResult = $stmt->get_result();
            $docente = $docenteResult->fetch_assoc();

            if (!$docente) {
                echo "Error: El docente no existe.";
                return false;
            }
            $id_docente = $docente['id_docente'];

            // Insertar el nuevo subcurso
            $insertQuery = "INSERT INTO Subcursos (nombre_subcurso, descripcion, id_curso, id_docente) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($insertQuery);
            $stmt->bind_param("ssii", $nombre_subcurso, $descripcion, $id_curso, $id_docente);
            return $stmt->execute();
        }
    }

    // Crear instancia del panel de administración
    $adminPanel = new AdminPanel($conn);

    // Procesar formulario
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'modificarSubcurso') {
            $adminPanel->modificarSubcurso(
                $_POST['id_subcurso'], 
                $_POST['nuevo_nombre'], 
                $_POST['descripcion'], 
                $_POST['nombre_curso'], 
                $_POST['documento_docente']
            );
        } elseif ($action === 'eliminarSubcurso') {
            $adminPanel->eliminarSubcurso($_POST['id_subcurso']);
        } elseif ($action === 'añadirSubcurso') {
            $adminPanel->añadirSubcurso(
                $_POST['nombre_subcurso'], 
                $_POST['descripcion'], 
                $_POST['nombre_curso'],
                $_POST['documento_docente']
            );
        }
    }

    // Obtener lista de subcursos
    $subcursos = $adminPanel->mostrarSubcursos();
    ?>

    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Administrador - Subcursos</title>
        <link rel="stylesheet" href="css/admi.css">
        <link rel="icon" href="img/logo.jpg" type="image/x-icon">
    </head>
    <body>
        <header>
            <h1>Panel de Materias</h1>
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
            <h2>Añadir Materia</h2>
            <form method="POST">
                <input type="hidden" name="action" value="añadirSubcurso">
                <label for="nombre_subcurso">Nombre del Subcurso:</label>
                <input type="text" name="nombre_subcurso" required>

                <label for="descripcion">Descripción:</label>
                <textarea name="descripcion" required></textarea>

                <label for="nombre_curso">Nombre del Curso:</label>
                <input type="text" name="nombre_curso" required>

                <label for="documento_docente">Documento del Docente:</label>
                <input type="number" name="documento_docente" required>

                <button type="submit">Añadir Subcurso</button>
            </form>

            <h2>Subcursos Activos</h2>
            <table border="1">
                <thead>
                    <tr>
                        <th>Nombre del Subcurso</th>
                        <th>Descripción</th>
                        <th>Curso Asociado</th>
                        <th>Docente</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($subcursos)): ?>
                        <?php foreach ($subcursos as $subcurso): ?>
                            <tr>
                                <form method="POST">
                                    <td>
                                        <input type="hidden" name="id_subcurso" value="<?php echo $subcurso['id_subcurso']; ?>">
                                        <input type="text" name="nuevo_nombre" value="<?php echo htmlspecialchars($subcurso['nombre_subcurso']); ?>" required>
                                    </td>
                                    <td>
                                        <textarea name="descripcion" required><?php echo htmlspecialchars($subcurso['descripcion']); ?></textarea>
                                    </td>
                                    <td>
                                        <input type="text" name="nombre_curso" value="<?php echo htmlspecialchars($subcurso['nombre_curso']); ?>" required>
                                    </td>
                                    <td>
                                        <input type="text" name="documento_docente" placeholder="Documento del Docente" required>
                                        <small><?php echo htmlspecialchars($subcurso['nombre_docente']); ?></small>
                                    </td>
                                    <td>
                                        <!-- Botones para modificar y eliminar -->
                                        <input type="hidden" name="action" value="modificarSubcurso">
                                        <button type="submit">Modificar</button>
                                    </form>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="id_subcurso" value="<?php echo $subcurso['id_subcurso']; ?>">
                                            <input type="hidden" name="action" value="eliminarSubcurso">
                                            <button type="submit" onclick="return confirm('¿Estás seguro de que deseas eliminar este subcurso?');">Eliminar</button>
                                        </form>
                                    </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">No hay subcursos activos.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </body>
    </html>
