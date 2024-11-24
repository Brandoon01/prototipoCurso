<?php
// Conexión a la base de datos
require_once 'php/conexion.php';

class AdminPanel {
    private $db;

    public function __construct($conexion) {
        $this->db = $conexion;
    }

    public function agregarAlumno($documento, $nombre, $apellido, $carrera, $email, $contraseña, $telefono) {
        $stmt = $this->db->prepare("INSERT INTO Alumnos (documento, nombre, apellido, carrera, email, contraseña, telefono) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $documento, $nombre, $apellido, $carrera, $email, $contraseña, $telefono);
        return $stmt->execute();
    }

    public function eliminarAlumno($documento) {
        $alumno = $this->buscarAlumno($documento);
        if ($alumno) {
            $stmt = $this->db->prepare("INSERT INTO AlumnosEliminados (documento, nombre, apellido, carrera, email, telefono) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $alumno['documento'], $alumno['nombre'], $alumno['apellido'], $alumno['carrera'], $alumno['email'], $alumno['telefono']);
            $stmt->execute();

            $stmt = $this->db->prepare("DELETE FROM Alumnos WHERE documento = ?");
            $stmt->bind_param("s", $documento);
            return $stmt->execute();
        }
        return false;
    }

    public function modificarAlumno($documento, $nombre, $apellido, $carrera, $email, $contraseña, $telefono) {
        $stmt = $this->db->prepare("UPDATE Alumnos SET nombre = ?, apellido = ?, carrera = ?, email = ?, contraseña = ?, telefono = ? WHERE documento = ?");
        $stmt->bind_param("sssssss", $nombre, $apellido, $carrera, $email, $contraseña, $telefono, $documento);
        return $stmt->execute();
    }

    public function buscarAlumno($documento) {
        $stmt = $this->db->prepare("SELECT * FROM Alumnos WHERE documento = ?");
        $stmt->bind_param("s", $documento);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function obtenerAlumnos($search = "") {
        if ($search) {
            $search = "%$search%";
            $stmt = $this->db->prepare("SELECT * FROM Alumnos WHERE documento LIKE ? OR nombre LIKE ? ORDER BY nombre ASC");
            $stmt->bind_param("ss", $search, $search);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM Alumnos ORDER BY nombre ASC");
        }
        $stmt->execute();
        return $stmt->get_result();
    }

    public function obtenerAlumnosEliminados() {
        $stmt = $this->db->prepare("SELECT * FROM AlumnosEliminados ORDER BY fecha_eliminacion DESC");
        $stmt->execute();
        return $stmt->get_result();
    }
}

// Crear instancia del panel de administración
$adminPanel = new AdminPanel($conn);

// Manejo de acciones del formulario
$alumnoEditar = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'agregarAlumno':
                $adminPanel->agregarAlumno($_POST['documento'], $_POST['nombre'], $_POST['apellido'], $_POST['carrera'], $_POST['email'], $_POST['contraseña'], $_POST['telefono']);
                break;
            case 'eliminarAlumno':
                $adminPanel->eliminarAlumno($_POST['documento']);
                break;
            case 'modificarAlumno':
                $adminPanel->modificarAlumno($_POST['documento'], $_POST['nombre'], $_POST['apellido'], $_POST['carrera'], $_POST['email'], $_POST['contraseña'], $_POST['telefono']);
                break;
            case 'buscarAlumno':
                $alumnoEditar = $adminPanel->buscarAlumno($_POST['documento']);
                break;
        }
    }
}

// Obtener listas de alumnos activos y eliminados, incluyendo la búsqueda
$search = $_GET['search'] ?? "";
$alumnos = $adminPanel->obtenerAlumnos($search);
$alumnosEliminados = $adminPanel->obtenerAlumnosEliminados();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrador</title>
    <link rel="stylesheet" href="css/admi.css">
    <link rel="icon" href="img/logo.jpg" type="image/x-icon">
</head>
<body>
    <header>
        <h1>Panel de Alumnos</h1>
    </header>
    <nav>
        <a href="admin.php">Alumnos</a>
        <a href="admin2.php">Docentes</a>
        <a href="admin3.php">Cursos</a>
        <a href="admin5.php">Materias</a>
        <a href="admin4.php">Inscripciones</a>
        <a href="php/cerrar.php">Cerrar sesión</a>
    </nav>
    <main class="container">
        <!-- Formulario para agregar/modificar alumno -->
        <div class="formulario">
            <h2><?php echo $alumnoEditar ? 'Modificar Alumno' : 'Agregar Alumno'; ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $alumnoEditar ? 'modificarAlumno' : 'agregarAlumno'; ?>">
                <input type="text" name="documento" placeholder="Documento" value="<?php echo $alumnoEditar['documento'] ?? ''; ?>" <?php echo $alumnoEditar ? 'readonly' : ''; ?> required>
                <input type="text" name="nombre" placeholder="Nombre" value="<?php echo $alumnoEditar['nombre'] ?? ''; ?>" required>
                <input type="text" name="apellido" placeholder="Apellido" value="<?php echo $alumnoEditar['apellido'] ?? ''; ?>" required>
                <input type="text" name="carrera" placeholder="Carrera" value="<?php echo $alumnoEditar['carrera'] ?? ''; ?>" required>
                <input type="email" name="email" placeholder="Email" value="<?php echo $alumnoEditar['email'] ?? ''; ?>" required>
                <input type="password" name="contraseña" placeholder="Contraseña" value="<?php echo $alumnoEditar['contraseña'] ?? ''; ?>" required>
                <input type="text" name="telefono" placeholder="Teléfono" value="<?php echo $alumnoEditar['telefono'] ?? ''; ?>" required>
                <input type="submit" value="<?php echo $alumnoEditar ? 'Modificar Alumno' : 'Agregar Alumno'; ?>">
            </form>
        </div>

        <!-- Barra de búsqueda para alumnos activos -->
        <div class="search-bar">
            <form method="GET">
                <input type="text" name="search" placeholder="Buscar por documento o nombre..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">Buscar</button>
            </form>
        </div>

        <!-- Tabla para mostrar alumnos activos -->
        <div class="tabla-alumnos">
            <h2>Lista de Alumnos Activos</h2>
            <table>
                <tr>
                    <th>Documento</th>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>Carrera</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Acciones</th>
                </tr>
                <?php while ($alumno = $alumnos->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $alumno['documento']; ?></td>
                        <td><?php echo $alumno['nombre']; ?></td>
                        <td><?php echo $alumno['apellido']; ?></td>
                        <td><?php echo $alumno['carrera']; ?></td>
                        <td><?php echo $alumno['email']; ?></td>
                        <td><?php echo $alumno['telefono']; ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="buscarAlumno">
                                <input type="hidden" name="documento" value="<?php echo $alumno['documento']; ?>">
                                <button type="submit">Modificar</button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="eliminarAlumno">
                                <input type="hidden" name="documento" value="<?php echo $alumno['documento']; ?>">
                                <button type="submit">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>

        <!-- Tabla para mostrar alumnos eliminados -->
        <div class="tabla-alumnos">
            <h2>Lista de Alumnos Eliminados</h2>
            <table>
                <tr>
                    <th>Documento</th>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>Carrera</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Fecha Eliminación</th>
                </tr>
                <?php while ($alumnoEliminado = $alumnosEliminados->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $alumnoEliminado['documento']; ?></td>
                        <td><?php echo $alumnoEliminado['nombre']; ?></td>
                        <td><?php echo $alumnoEliminado['apellido']; ?></td>
                        <td><?php echo $alumnoEliminado['carrera']; ?></td>
                        <td><?php echo $alumnoEliminado['email']; ?></td>
                        <td><?php echo $alumnoEliminado['telefono']; ?></td>
                        <td><?php echo $alumnoEliminado['fecha_eliminacion']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </main>
</body>
</html>
