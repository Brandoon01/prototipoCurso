CREATE TABLE administrador(
email varchar(100),
contraseña varchar(50)
);

CREATE TABLE Alumnos (
    id_alumno INT AUTO_INCREMENT PRIMARY KEY,
    documento int(250),
    nombre VARCHAR(100) NOT NULL,
    apellido varchar(100) NOT NULL,
    carrera varchar(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    contraseña varchar(50),
    telefono varchar(50),
    UNIQUE(documento)
);

CREATE TABLE AlumnosEliminados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    documento VARCHAR(50),
    nombre VARCHAR(50),
    apellido VARCHAR(50),
    carrera VARCHAR(50),
    email VARCHAR(100),
    telefono VARCHAR(20),
    fecha_eliminacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE DocentesEliminados (
    id_docente INT AUTO_INCREMENT PRIMARY KEY,
    documento VARCHAR(50),
    nombre VARCHAR(50),
    apellido VARCHAR(50),
    email VARCHAR(100),
    telefono VARCHAR(20),
    fecha_eliminacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE Docentes (
    id_docente INT AUTO_INCREMENT PRIMARY KEY,
    documento int(250),
    nombre VARCHAR(100) NOT NULL,
    apellido varchar(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    contraseña varchar(50),
	telefono varchar(50),
    UNIQUE(documento)
);

CREATE TABLE CursosEliminados (
    id_curso INT AUTO_INCREMENT PRIMARY KEY,
    nombre_curso VARCHAR(100) NOT NULL,
    id_docente INT,
    fecha_eliminacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Cursos (
    id_curso INT AUTO_INCREMENT PRIMARY KEY,
    nombre_curso VARCHAR(100) NOT NULL,
    id_docente INT,
    FOREIGN KEY (id_docente) REFERENCES Docentes(id_docente) ON DELETE SET NULL
);

CREATE TABLE Inscripciones (
    id_inscripcion INT AUTO_INCREMENT PRIMARY KEY,
    id_alumno INT,
    id_curso INT,
    FOREIGN KEY (id_alumno) REFERENCES Alumnos(id_alumno) ON DELETE CASCADE,
    FOREIGN KEY (id_curso) REFERENCES Cursos(id_curso) ON DELETE CASCADE,
    UNIQUE KEY (id_alumno, id_curso)  -- Evita inscripciones duplicadas
);


CREATE TABLE Subcursos (
    id_subcurso INT AUTO_INCREMENT PRIMARY KEY,
    nombre_subcurso VARCHAR(100) NOT NULL,
    descripcion TEXT,
    id_curso INT,
    id_docente INT,
    FOREIGN KEY (id_curso) REFERENCES Cursos(id_curso) ON DELETE CASCADE,
    FOREIGN KEY (id_docente) REFERENCES Docentes(id_docente) ON DELETE SET NULL
);

CREATE TABLE InscripcionesSubcursos (
    id_inscripcion_subcurso INT AUTO_INCREMENT PRIMARY KEY,
    id_alumno INT,
    id_subcurso INT,
    FOREIGN KEY (id_alumno) REFERENCES Alumnos(id_alumno) ON DELETE CASCADE,
    FOREIGN KEY (id_subcurso) REFERENCES Subcursos(id_subcurso) ON DELETE CASCADE,
    UNIQUE (id_alumno, id_subcurso) -- Evitar duplicados
);


CREATE TABLE Actividades (
    id_actividad INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100) NOT NULL,              -- Título de la actividad
    descripcion TEXT,                          -- Descripción de la actividad
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Fecha de creación
    fecha_entrega DATE,                        -- Fecha límite para entregar la actividad
    id_subcurso INT,                           -- Relación con el subcurso
    id_docente INT,                            -- Relación con el docente que crea la actividad
    archivo_adjunto varchar(250),
    ruta_archivo VARCHAR(255) NOT NULL,        -- Ruta del archivo o URL
    tipo_recurso ENUM('archivo', 'url') NOT NULL DEFAULT 'archivo', -- Indica si es archivo o URL
    FOREIGN KEY (id_subcurso) REFERENCES Subcursos(id_subcurso) ON DELETE CASCADE,
    FOREIGN KEY (id_docente) REFERENCES Docentes(id_docente) ON DELETE SET NULL
);


CREATE TABLE Entregas (
    id_entrega INT AUTO_INCREMENT PRIMARY KEY,
    id_actividad INT,                          -- Relación con la actividad a la que pertenece la entrega
    id_alumno INT,                             -- Relación con el alumno que entrega el archivo
    nombre_archivo VARCHAR(255) NOT NULL,      -- Nombre del archivo subido
    ruta_archivo VARCHAR(255) NOT NULL,        -- Ruta del archivo en el servidor
    calificacion decimal (2,1),
    fecha_entrega TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Fecha y hora de la entrega
    FOREIGN KEY (id_actividad) REFERENCES Actividades(id_actividad) ON DELETE CASCADE,
    FOREIGN KEY (id_alumno) REFERENCES Alumnos(id_alumno) ON DELETE CASCADE,
    UNIQUE KEY (id_actividad, id_alumno)       -- Evita que un alumno entregue más de un archivo por actividad
);

CREATE TABLE mensajes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_alumno INT,
    id_docente INT,
    destinatario INT,  -- The ID of the recipient (can be either `id_alumno` or `id_docente`)
    mensaje TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_alumno) REFERENCES Alumnos(id_alumno) ON DELETE CASCADE,
    FOREIGN KEY (id_docente) REFERENCES Docentes(id_docente) ON DELETE CASCADE
);

CREATE TABLE foro_mensajes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_alumno INT,
    id_docente INT,
    mensaje TEXT NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_alumno) REFERENCES Alumnos(id_alumno) ON DELETE CASCADE,
    FOREIGN KEY (id_docente) REFERENCES Docentes(id_docente) ON DELETE CASCADE
);


CREATE TABLE RecuperacionClave (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(100) NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


INSERT INTO administrador(email,contraseña)VALUES ('admin@gmail.com','admin123');

INSERT INTO Alumnos (documento,nombre,apellido,carrera, email,contraseña,telefono) VALUES
('102045321','daniel','perez','Software','daniel@gmail.com','daniel123','3102402030'),
('103045631','María', 'petunia','telecomunicaciones','maria.gomez@gmail.com','maria123','312314598'),
('52954321','Carlos', 'mario','','carlos.lopez@gmail.com','carlos123','332235498');

INSERT INTO Docentes (documento,nombre,apellido, email,contraseña,telefono) VALUES
('1030520881','danilo','puentes','danilo@gmail.com','danilo123','3224802010'),
('56765109','Luis','alfonso','luis.fernandez@gmail.com.com','luis123','320098723');

INSERT INTO Cursos (nombre_curso, id_docente) VALUES
('Matemáticas', 1),  -- id_docente = 1 (Ana Martínez)
('Ciencias', 2),     -- id_docente = 2 (Luis Fernández)
('Historia', 1);     -- id_docente = 1 (Ana Martínez)

INSERT INTO Inscripciones (id_alumno, id_curso) VALUES
(1, 1),  -- Daniel Pérez se inscribe en Matemáticas
(1, 2),  -- Daniel Pérez se inscribe en Ciencias
(2, 1),  -- María Gómez se inscribe en Matemáticas
(3, 3);  -- Carlos López se inscribe en Historia

-- Insertar datos en la tabla Subcursos
INSERT INTO Subcursos (nombre_subcurso, descripcion, id_curso, id_docente) 
VALUES 
('Matematicas Avanzadas', 'Este subcurso cubre álgebra y cálculo avanzado.', 1, 1),
('Introduccion a la Fisica', 'Fundamentos de la mecánica y la física moderna.', 2, 2),
('Literatura Clasica', 'Estudio de textos clásicos de la literatura universal.', 3, 1);

-- Insertar datos en la tabla Actividades
INSERT INTO Actividades (titulo, descripcion, fecha_entrega, id_subcurso, id_docente)
VALUES 
('Ejercicio de Integrales', 'Resolver las integrales asignadas en el archivo adjunto.', '2024-11-01', 1, 1),
('Problemas de Cinemática', 'Completar los problemas de cinemática para el próximo laboratorio.', '2024-11-05', 2, 2),
('Análisis de la Odisea', 'Escribir un ensayo sobre el libro La Odisea de Homero.', '2024-11-10', 3, 1);