<?php
include 'php/conexion.php';

// Iniciar sesión
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Obtener el ID del subcurso desde la URL
$id_subcurso = isset($_GET['id_subcurso']) ? intval($_GET['id_subcurso']) : 0;
$docente_id = $_SESSION['user_id'];

// Consulta para obtener el número de documento del docente desde la tabla docentes
$sql = "SELECT documento FROM docentes WHERE id_docente = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $docente_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $docente = $result->fetch_assoc();
    $documento = $docente['documento'];

    // Definir la ruta de la imagen de perfil basada en el documento del docente en la carpeta 'Pperfil'
    $profile_picture = "Pperfil/" . $documento . ".jpg";
    
    // Si la imagen de perfil no existe, usar una imagen predeterminada
    if (!file_exists($profile_picture)) {
        $profile_picture = "img/default-profile.png";
    }
} else {
    // En caso de que no se encuentre el documento, asignar la imagen predeterminada
    $profile_picture = "img/default-profile.png";
}

// Inicializar mensaje y datos del formulario
$mensaje = '';
$titulo = '';
$descripcion = '';
$fecha_entrega = '';
$archivo_adjunto = '';

// Insertar nueva actividad
// Insertar nueva actividad
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['nueva_fecha_entrega'])) {
    $titulo = $_POST['titulo'];
    $descripcion = $_POST['descripcion'];
    $fecha_entrega = $_POST['fecha_entrega'];
    $id_docente = $_SESSION['user_id'];
    $tipo_recurso = $_POST['tipo_recurso'];
    $archivo_adjunto = '';
    $ruta_archivo = '';

    // Validación de fecha para asegurarse de que sea futura y del año en curso
    $anio_actual = date("Y");
    if (strtotime($fecha_entrega) < strtotime('now') || date("Y", strtotime($fecha_entrega)) != $anio_actual) {
        $mensaje = "Error: La fecha de entrega debe ser futura y del año actual.";
    } else {
        if ($tipo_recurso === 'archivo') {
            // Manejo de carga de archivo
            if (isset($_FILES['documento']) && $_FILES['documento']['error'] == UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['documento']['tmp_name'];
                $file_extension = pathinfo($_FILES['documento']['name'], PATHINFO_EXTENSION);
                $file_name = uniqid('actividad_', true) . '.' . $file_extension;
                $upload_dir = 'uploads/';

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                if (move_uploaded_file($file_tmp, $upload_dir . $file_name)) {
                    $archivo_adjunto = $upload_dir . $file_name;
                    $ruta_archivo = $archivo_adjunto;
                } else {
                    $mensaje = "Error al subir el documento.";
                }
            }
        } else if ($tipo_recurso === 'url') {
            // Manejo de URL
            $ruta_archivo = trim($_POST['url']); // Eliminar espacios en blanco
            
            // Agregar http:// si no tiene protocolo
            if (!empty($ruta_archivo)) {
    if (!preg_match("~^(?:f|ht)tps?://~i", $ruta_archivo)) {
        // Si no tiene protocolo, agregar http:// por defecto
        $ruta_archivo = "http://" . $ruta_archivo;
    }
    // Validamos que la URL tenga el formato correcto
    if (!filter_var($ruta_archivo, FILTER_VALIDATE_URL)) {
        $mensaje = "Error: Por favor ingrese una URL válida.";
    }
}

        }

        // Solo continuar si no hay mensaje de error
        if (empty($mensaje)) {
            // Verificar si ya existe una actividad con el mismo título en el mismo subcurso
            $sql_check = "SELECT COUNT(*) FROM Actividades WHERE titulo = ? AND id_subcurso = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param('si', $titulo, $id_subcurso);
            $stmt_check->execute();
            $stmt_check->bind_result($count);
            $stmt_check->fetch();
            $stmt_check->close();

            if ($count > 0) {
                $mensaje = "Error: Ya existe una actividad con el mismo título en este subcurso.";
            } else {
                $sql = "INSERT INTO Actividades (titulo, descripcion, fecha_entrega, id_subcurso, id_docente, archivo_adjunto, ruta_archivo, tipo_recurso) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('sssiisss', $titulo, $descripcion, $fecha_entrega, $id_subcurso, $id_docente, $archivo_adjunto, $ruta_archivo, $tipo_recurso);

                if ($stmt->execute()) {
                    header("Location: actividades.php?id_subcurso=" . $id_subcurso . "&mensaje=Actividad creada exitosamente");
                    exit();
                } else {
                    $mensaje = "Error al crear la actividad: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

// Comprobar si se solicita una actualización de fecha de entrega
if (isset($_POST['nueva_fecha_entrega'], $_POST['id_actividad'])) {
    $nueva_fecha_entrega = $_POST['nueva_fecha_entrega'];
    $id_actividad = intval($_POST['id_actividad']);
    $anio_actual = date("Y");

    // Validación de fecha para asegurarse de que sea futura y del año en curso
    if (strtotime($nueva_fecha_entrega) < strtotime('now') || date("Y", strtotime($nueva_fecha_entrega)) != $anio_actual) {
        $mensaje = "Error: La nueva fecha de entrega debe ser futura y del año actual.";
    } else {
        // Actualizar la fecha de entrega en la base de datos
        $sql_update = "UPDATE Actividades SET fecha_entrega = ? WHERE id_actividad = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param('si', $nueva_fecha_entrega, $id_actividad);

        if ($stmt_update->execute()) {
            $mensaje = "Fecha de entrega actualizada exitosamente.";
        } else {
            $mensaje = "Error al actualizar la fecha de entrega: " . $stmt_update->error;
        }
        $stmt_update->close();
    }
}

// Mostrar actividades del subcurso específico
$sql = "SELECT * FROM Actividades WHERE id_subcurso = ?";
$stmt_actividades = $conn->prepare($sql);
$stmt_actividades->bind_param('i', $id_subcurso);
$stmt_actividades->execute();
$result = $stmt_actividades->get_result();

// Obtener el nombre del subcurso
$sql_subcurso = "SELECT nombre_subcurso FROM Subcursos WHERE id_subcurso = ?";
$stmt_subcurso = $conn->prepare($sql_subcurso);
$stmt_subcurso->bind_param('i', $id_subcurso);
$stmt_subcurso->execute();
$stmt_subcurso->bind_result($nombre_subcurso);
$stmt_subcurso->fetch();
$stmt_subcurso->close();

// Obtener mensaje de la URL si existe
if (isset($_GET['mensaje'])) {
    $mensaje = $_GET['mensaje'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Actividades</title>
    <link rel="stylesheet" href="css/archivo.css">
    <link rel="icon" href="img/logo.jpg" type="image/x-icon">
    <style>
        /* Estilos para el cuadro de diálogo */
        #mensajeDialog {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            border: 1px solid #ccc;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }
        #mensajeDialog .close {
            cursor: pointer;
            float: right;
        }
        
        /* Estilos adicionales para los campos de recurso */
        #url_field, #archivo_field {
            margin: 10px 0;
        }
        
        select#tipo_recurso {
            padding: 5px;
            margin: 10px 0;
        }
    </style>
    <script>
        function mostrarMensaje(mensaje) {
            document.getElementById('mensajeTexto').innerText = mensaje;
            document.getElementById('mensajeDialog').style.display = 'block';

            // Redirigir después de 3 segundos (3000 ms)
            setTimeout(function() {
                window.location.href = "actividades.php?id_subcurso=<?php echo $id_subcurso; ?>";
            }, 3000);
        }

        function cerrarMensaje() {
            document.getElementById('mensajeDialog').style.display = 'none';
            window.location.href = "actividades.php?id_subcurso=<?php echo $id_subcurso; ?>";
        }

        function toggleRecursoFields() {
            const tipoRecurso = document.getElementById('tipo_recurso').value;
            const archivoField = document.getElementById('archivo_field');
            const urlField = document.getElementById('url_field');
            
            if (tipoRecurso === 'archivo') {
                archivoField.style.display = 'block';
                urlField.style.display = 'none';
                document.getElementById('url').value = '';
            } else {
                archivoField.style.display = 'none';
                urlField.style.display = 'block';
                document.getElementById('documento').value = '';
            }
        }

        window.onload = function() {
            var mensaje = "<?php echo addslashes($mensaje); ?>";
            if (mensaje) {
                mostrarMensaje(mensaje);
            }
        }
    </script>
</head>
<body>
    <header>
        <h1>Actividades del Docente</h1>
        <nav>
            <a href="Pinicio.php">Inicio</a>
            <a href="Ppersonal.php">Personal</a>    
            <a href="Pcursos.php">Cursos</a>
            <a href="Pperfil.php">Perfil</a>
            <a href="chat_docentes.php">Chat</a>
            <a href="php/cerrar.php">Cerrar sesión</a>
        </nav>
    </header>

    <!-- Imagen de perfil del usuario -->
    <div class="profile-container">
        <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Foto de perfil" class="profile-pic">
    </div>

    <h1>Crear Nueva Actividad</h1>
    <form action="actividades.php?id_subcurso=<?php echo $id_subcurso; ?>" method="post" enctype="multipart/form-data">
        <label for="titulo">Título:</label>
        <input type="text" id="titulo" name="titulo" value="<?php echo htmlspecialchars($titulo); ?>" required><br>

        <label for="descripcion">Descripción:</label>
        <textarea id="descripcion" name="descripcion" required><?php echo htmlspecialchars($descripcion); ?></textarea><br>

        <label for="fecha_entrega">Fecha de Entrega:</label>
        <input type="date" id="fecha_entrega" name="fecha_entrega" value="<?php echo htmlspecialchars($fecha_entrega); ?>" required><br>

        <label for="tipo_recurso">Tipo de Recurso:</label>
        <select id="tipo_recurso" name="tipo_recurso" onchange="toggleRecursoFields()">
            <option value="archivo">Archivo</option>
            <option value="url">URL</option>
        </select><br>

        <div id="archivo_field">
            <label for="documento">Documento:</label>
            <input type="file" id="documento" name="documento" accept=".pdf,.doc,.docx,.ppt,.pptx"><br>
        </div>

        <div id="url_field" style="display: none;">
            <label for="url">URL:</label>
            <input type="url" id="url" name="url" placeholder="https://ejemplo.com"><br>
        </div>

        <button type="submit">Crear Actividad</button>
    </form>

    <h2>Lista de Actividades del Subcurso: <?php echo htmlspecialchars($nombre_subcurso); ?></h2>
    <table border="1">
    <tr>
        <th>ID</th>
        <th>Título</th>
        <th>Descripción</th>
        <th>Fecha de Entrega</th>
        <th>ID Subcurso</th>
        <th>Recurso</th>
        <th>Ver Entregas</th>
        <th>Acción</th>
    </tr>
    <?php while ($actividad = $result->fetch_assoc()): ?>
    <tr>
        <td><?php echo $actividad['id_actividad']; ?></td>
        <td><?php echo htmlspecialchars($actividad['titulo']); ?></td>
        <td><?php echo htmlspecialchars($actividad['descripcion']); ?></td>
        <td><?php echo htmlspecialchars($actividad['fecha_entrega']); ?></td>
        <td><?php echo htmlspecialchars($actividad['id_subcurso']); ?></td>
        <td>
    <?php if ($actividad['tipo_recurso'] === 'url'): ?>
        <?php 
            $url = trim($actividad['ruta_archivo']); // Limpiamos cualquier espacio
            if (!empty($url)) {
                // Verificamos si la URL es válida
                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    // La URL es válida, la mostramos directamente
                    echo '<a href="' . htmlspecialchars($url) . '" target="_blank" rel="noopener noreferrer">Ver URL</a>';
                } else {
                    // Intentamos arreglar la URL agregando el protocolo si falta
                    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
                        $url = "https://" . $url;
                    }
                    
                    // Verificamos nuevamente si la URL es válida después de agregar el protocolo
                    if (filter_var($url, FILTER_VALIDATE_URL)) {
                        echo '<a href="' . htmlspecialchars($url) . '" target="_blank" rel="noopener noreferrer">Ver URL</a>';
                    } else {
                        echo 'URL inválida: ' . htmlspecialchars($url);
                    }
                }
            } else {
                echo 'URL no disponible';
            }
        ?>
    <?php elseif (!empty($actividad['archivo_adjunto'])): ?>
        <a href="<?php echo htmlspecialchars($actividad['archivo_adjunto']); ?>" target="_blank">Ver Documento</a>
    <?php else: ?>
        Sin recurso
    <?php endif; ?>
</td>
        <td>
            <a href="entregadas.php?id_actividad=<?php echo $actividad['id_actividad']; ?>&id_subcurso=<?php echo $id_subcurso; ?>">Ver Entregas</a>
        </td>
        <td>
            <?php if (strtotime($actividad['fecha_entrega']) < strtotime('now')): ?>
                <form action="actividades.php?id_subcurso=<?php echo $id_subcurso; ?>" method="post" style="display:inline;">
                    <input type="date" name="nueva_fecha_entrega" required>
                    <input type="hidden" name="id_actividad" value="<?php echo $actividad['id_actividad']; ?>">
                    <button type="submit">Ampliar Fecha</button>
                </form>
            <?php else: ?>
                Fecha vigente
            <?php endif; ?>
        </td>
    </tr>
    <?php endwhile; ?>
</table>

    <!-- Cuadro de diálogo para mostrar el mensaje -->
    <div id="mensajeDialog">
        <span class="close" onclick="cerrarMensaje()">X</span>
        <p id="mensajeTexto"></p>
    </div>

</body>
</html>