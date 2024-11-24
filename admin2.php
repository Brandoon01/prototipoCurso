<?php
// Conexión a la base de datos
require_once 'php/conexion.php';

class AdminPanel {
    private $db;

    public function __construct($conexion) {
        $this->db = $conexion;
    }

    // Función para agregar docente
    public function agregarDocente($documento, $nombre, $apellido, $email, $contraseña, $telefono) {
        $stmt = $this->db->prepare("INSERT INTO Docentes (documento, nombre, apellido, email, contraseña, telefono) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $documento, $nombre, $apellido, $email, $contraseña, $telefono);
        return $stmt->execute();
    }

    // Función para eliminar docente y mover a DocentesEliminados
    public function eliminarDocente($documento) {
        $docente = $this->buscarDocente($documento);
        if ($docente) {
            $stmt = $this->db->prepare("INSERT INTO DocentesEliminados (documento, nombre, apellido, email, telefono) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $docente['documento'], $docente['nombre'], $docente['apellido'], $docente['email'], $docente['telefono']);
            $stmt->execute();

            $stmt = $this->db->prepare("DELETE FROM Docentes WHERE documento = ?");
            $stmt->bind_param("s", $documento);
            return $stmt->execute();
        }
        return false;
    }

    // Función para modificar docente
    public function modificarDocente($documento, $nombre, $apellido, $email, $contraseña, $telefono) {
        $stmt = $this->db->prepare("UPDATE Docentes SET nombre = ?, apellido = ?, email = ?, contraseña = ?, telefono = ? WHERE documento = ?");
        $stmt->bind_param("ssssss", $nombre, $apellido, $email, $contraseña, $telefono, $documento);
        return $stmt->execute();
    }

    // Función para buscar un docente por documento
    public function buscarDocente($documento) {
        $stmt = $this->db->prepare("SELECT * FROM Docentes WHERE documento = ?");
        $stmt->bind_param("s", $documento);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Obtener lista de docentes activos
    public function obtenerDocentes() {
        $stmt = $this->db->prepare("SELECT * FROM Docentes ORDER BY nombre ASC");
        $stmt->execute();
        return $stmt->get_result();
    }

    // Obtener lista de docentes eliminados
    public function obtenerDocentesEliminados() {
        $stmt = $this->db->prepare("SELECT * FROM DocentesEliminados ORDER BY fecha_eliminacion DESC");
        $stmt->execute();
        return $stmt->get_result();
    }
}

// Crear instancia del panel de administración
$adminPanel = new AdminPanel($conn);

// Manejo de acciones del formulario
$docenteEditar = null;
$accion = 'agregarDocente'; // Acción predeterminada
$tituloFormulario = "Agregar Docente";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'agregarDocente':
                $adminPanel->agregarDocente($_POST['documento'], $_POST['nombre'], $_POST['apellido'], $_POST['email'], $_POST['contraseña'], $_POST['telefono']);
                break;
            case 'eliminarDocente':
                $adminPanel->eliminarDocente($_POST['documento']);
                break;
            case 'modificarDocente':
                $adminPanel->modificarDocente($_POST['documento'], $_POST['nombre'], $_POST['apellido'], $_POST['email'], $_POST['contraseña'], $_POST['telefono']);
                break;
            case 'buscarDocente':
                $docenteEditar = $adminPanel->buscarDocente($_POST['documento']);
                $accion = 'modificarDocente';
                $tituloFormulario = "Modificar Docente";
                break;
        }
    }
}

// Obtener lista de docentes activos y eliminados
$docentes = $adminPanel->obtenerDocentes();
$docentesEliminados = $adminPanel->obtenerDocentesEliminados();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrador de Docentes</title>
    <link rel="stylesheet" href="css/admi.css">
    <link rel="icon" href="img/logo.jpg" type="image/x-icon">
</head>
<body>
    <header>
        <h1>Panel de Administración de Docentes</h1>
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
        <h2><?php echo $tituloFormulario; ?></h2>
        <form method="POST">
            <input type="hidden" name="action" value="<?php echo $accion; ?>">
            <input type="text" name="documento" placeholder="Documento" value="<?php echo $docenteEditar['documento'] ?? ''; ?>" required>
            <input type="text" name="nombre" placeholder="Nombre" value="<?php echo $docenteEditar['nombre'] ?? ''; ?>" required>
            <input type="text" name="apellido" placeholder="Apellido" value="<?php echo $docenteEditar['apellido'] ?? ''; ?>" required>
            <input type="email" name="email" placeholder="Email" value="<?php echo $docenteEditar['email'] ?? ''; ?>" required>
            <input type="password" name="contraseña" placeholder="Contraseña" value="<?php echo $docenteEditar['contraseña'] ?? ''; ?>" required>
            <input type="text" name="telefono" placeholder="Teléfono" value="<?php echo $docenteEditar['telefono'] ?? ''; ?>" required>
            <input type="submit" value="<?php echo $tituloFormulario; ?>">
        </form>

        <h2>Lista de Docentes Activos</h2>
        <table>
            <tr>
                <th>Documento</th>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>Email</th>
                <th>Teléfono</th>
                <th>Acciones</th>
            </tr>
            <?php while ($docente = $docentes->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $docente['documento']; ?></td>
                    <td><?php echo $docente['nombre']; ?></td>
                    <td><?php echo $docente['apellido']; ?></td>
                    <td><?php echo $docente['email']; ?></td>
                    <td><?php echo $docente['telefono']; ?></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="buscarDocente">
                            <input type="hidden" name="documento" value="<?php echo $docente['documento']; ?>">
                            <button type="submit">Modificar</button>
                        </form>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="eliminarDocente">
                            <input type="hidden" name="documento" value="<?php echo $docente['documento']; ?>">
                            <button type="submit">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>

        <h2>Lista de Docentes Eliminados</h2>
        <table>
            <tr>
                <th>Documento</th>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>Email</th>
                <th>Teléfono</th>
                <th>Fecha Eliminación</th>
            </tr>
            <?php while ($docenteEliminado = $docentesEliminados->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $docenteEliminado['documento']; ?></td>
                    <td><?php echo $docenteEliminado['nombre']; ?></td>
                    <td><?php echo $docenteEliminado['apellido']; ?></td>
                    <td><?php echo $docenteEliminado['email']; ?></td>
                    <td><?php echo $docenteEliminado['telefono']; ?></td>
                    <td><?php echo $docenteEliminado['fecha_eliminacion']; ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    </main>
</body>
</html>
