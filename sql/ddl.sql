-- =====================================================
-- AND PROJECTS APP - DDL (Data Definition Language)
-- Base de datos para gestión de proyectos
-- MariaDB 11.8.3
-- =====================================================

-- Crear base de datos si no existe
CREATE DATABASE IF NOT EXISTS and_projects_app
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE and_projects_app;

-- =====================================================
-- TABLA: proyectos_empresas
-- Almacena información de las empresas clientes
-- =====================================================
CREATE OR REPLACE TABLE proyectos_empresas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    razon_social VARCHAR(255) NULL,
    nit VARCHAR(50) NULL,
    logo VARCHAR(255) NULL COMMENT 'Ruta al logo de la empresa',
    email VARCHAR(150) NULL,
    telefono VARCHAR(50) NULL,
    direccion VARCHAR(500) NULL,
    ciudad VARCHAR(100) NULL,
    pais VARCHAR(100) NULL DEFAULT 'Colombia',
    sitio_web VARCHAR(255) NULL,
    descripcion TEXT NULL,
    color_primario VARCHAR(20) NULL DEFAULT '#55A5C8',
    contacto_nombre VARCHAR(100) NULL,
    contacto_telefono VARCHAR(50) NULL,
    estado INT NOT NULL DEFAULT 1 COMMENT '0=inactivo, 1=activo',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: proyectos_usuarios
-- Almacena información de usuarios (colaboradores y clientes)
-- =====================================================
CREATE OR REPLACE TABLE proyectos_usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NULL COMMENT 'ID de la empresa si es cliente',
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NULL,
    rol VARCHAR(50) NOT NULL DEFAULT 'colaborador' COMMENT 'Valores: admin, colaborador, cliente',
    cargo VARCHAR(100) NULL,
    telefono VARCHAR(50) NULL,
    avatar VARCHAR(255) NULL,
    estado INT NOT NULL DEFAULT 1 COMMENT '0=inactivo, 1=activo',
    requiere_cambio_clave INT NOT NULL DEFAULT 0 COMMENT '0=no, 1=debe cambiar en primer login',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ultimo_acceso DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: proyectos_proyectos
-- Almacena información de los proyectos
-- =====================================================
CREATE OR REPLACE TABLE proyectos_proyectos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL COMMENT 'Referencia a proyectos_empresas.id',
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT NULL,
    color VARCHAR(20) NULL DEFAULT '#55A5C8' COMMENT 'Color identificador del proyecto',
    fecha_inicio DATE NULL,
    fecha_fin_estimada DATE NULL,
    fecha_fin_real DATE NULL,
    estado INT NOT NULL DEFAULT 1 COMMENT '1=pendiente, 2=en_progreso, 3=completado, 4=bloqueado, 5=cancelado',
    avance DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Porcentaje de avance',
    creado_por INT NULL COMMENT 'Referencia a proyectos_usuarios.id',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: proyectos_tareas
-- Almacena información de las tareas
-- =====================================================
CREATE OR REPLACE TABLE proyectos_tareas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proyecto_id INT NOT NULL COMMENT 'Referencia a proyectos_proyectos.id',
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT NULL,
    fecha_inicio_estimada DATE NULL,
    fecha_fin_estimada DATE NULL,
    fecha_fin_real DATE NULL,
    estado INT NOT NULL DEFAULT 1 COMMENT '1=pendiente, 2=en_progreso, 3=completado, 4=bloqueada, 5=cancelada',
    prioridad INT NOT NULL DEFAULT 2 COMMENT '1=baja, 2=media, 3=alta, 4=urgente',
    avance DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Porcentaje de avance',
    asignado_id INT NULL COMMENT 'Referencia a proyectos_usuarios.id',
    creado_por INT NOT NULL COMMENT 'Referencia a proyectos_usuarios.id',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: proyectos_subtareas
-- Almacena información de las subtareas
-- =====================================================
CREATE OR REPLACE TABLE proyectos_subtareas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tarea_id INT NOT NULL COMMENT 'Referencia a proyectos_tareas.id',
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT NULL,
    fecha_inicio_estimada DATE NULL,
    fecha_fin_estimada DATE NULL,
    fecha_fin_real DATE NULL,
    estado INT NOT NULL DEFAULT 1 COMMENT '1=pendiente, 2=en_progreso, 3=completado, 4=bloqueada, 5=cancelada',
    horas_estimadas DECIMAL(5,2) NULL,
    horas_reales DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    realizado_por INT NULL COMMENT 'Referencia a proyectos_usuarios.id',
    orden INT NOT NULL DEFAULT 0 COMMENT 'Orden de la subtarea en la lista',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: proyectos_tiempos
-- Registra el tiempo dedicado a subtareas
-- =====================================================
CREATE OR REPLACE TABLE proyectos_tiempos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subtarea_id INT NOT NULL COMMENT 'Referencia a proyectos_subtareas.id',
    usuario_id INT NOT NULL COMMENT 'Referencia a proyectos_usuarios.id',
    fecha DATE NOT NULL,
    horas INT NOT NULL DEFAULT 0,
    minutos INT NOT NULL DEFAULT 0,
    descripcion VARCHAR(500) NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: proyectos_dependencias
-- Define dependencias entre tareas/subtareas
-- =====================================================
CREATE OR REPLACE TABLE proyectos_dependencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_origen VARCHAR(50) NOT NULL COMMENT 'Valores: tarea, subtarea',
    id_origen INT NOT NULL,
    tipo_destino VARCHAR(50) NOT NULL COMMENT 'Valores: tarea, subtarea',
    id_destino INT NOT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_dependencia (tipo_origen, id_origen, tipo_destino, id_destino)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: proyectos_reuniones
-- Almacena información de las reuniones
-- =====================================================
CREATE OR REPLACE TABLE proyectos_reuniones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proyecto_id INT NULL COMMENT 'Referencia a proyectos_proyectos.id',
    empresa_id INT NULL COMMENT 'Referencia a proyectos_empresas.id',
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT NULL,
    fecha DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    duracion_minutos INT NOT NULL DEFAULT 60,
    tipo VARCHAR(50) NOT NULL DEFAULT 'presencial' COMMENT 'Valores: presencial, virtual, hibrida',
    ubicacion VARCHAR(500) NULL COMMENT 'Dirección o link de reunión',
    finalidad TEXT NULL,
    insights TEXT NULL COMMENT 'Notas o conclusiones de la reunión',
    creado_por INT NOT NULL COMMENT 'Referencia a proyectos_usuarios.id',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: proyectos_reunion_participantes
-- Participantes de cada reunión
-- =====================================================
CREATE OR REPLACE TABLE proyectos_reunion_participantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reunion_id INT NOT NULL COMMENT 'Referencia a proyectos_reuniones.id',
    usuario_id INT NOT NULL COMMENT 'Referencia a proyectos_usuarios.id',
    asistio INT NULL COMMENT '0=no asistió, 1=asistió, NULL=pendiente',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_participante (reunion_id, usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: proyectos_comentarios
-- Comentarios en proyectos, tareas o subtareas
-- =====================================================
CREATE OR REPLACE TABLE proyectos_comentarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_entidad VARCHAR(50) NOT NULL COMMENT 'Valores: proyecto, tarea, subtarea',
    entidad_id INT NOT NULL,
    usuario_id INT NOT NULL COMMENT 'Referencia a proyectos_usuarios.id',
    comentario TEXT NOT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: proyectos_adjuntos
-- Archivos adjuntos a proyectos, tareas o subtareas
-- =====================================================
CREATE OR REPLACE TABLE proyectos_adjuntos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_entidad VARCHAR(50) NOT NULL COMMENT 'Valores: proyecto, tarea, subtarea',
    entidad_id INT NOT NULL,
    usuario_id INT NOT NULL COMMENT 'Referencia a proyectos_usuarios.id',
    nombre_original VARCHAR(255) NOT NULL,
    nombre_servidor VARCHAR(255) NOT NULL COMMENT 'Nombre único en servidor',
    ruta VARCHAR(500) NOT NULL,
    tipo_mime VARCHAR(100) NOT NULL,
    tamano INT NOT NULL COMMENT 'Tamaño en bytes',
    descripcion TEXT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: proyectos_sesiones
-- Control de sesiones activas
-- =====================================================
CREATE OR REPLACE TABLE proyectos_sesiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL COMMENT 'Referencia a proyectos_usuarios.id',
    token VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion DATETIME NOT NULL,
    estado INT NOT NULL DEFAULT 1 COMMENT '0=expirada, 1=activa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: proyectos_verificacion_codigos
-- Códigos de verificación para email y recuperación
-- =====================================================
CREATE OR REPLACE TABLE proyectos_verificacion_codigos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    codigo VARCHAR(10) NOT NULL,
    tipo VARCHAR(30) NOT NULL COMMENT 'Valores: registro, recuperacion_password, primer_login',
    datos_temporales TEXT NULL COMMENT 'Datos JSON para registro',
    intentos INT NOT NULL DEFAULT 0,
    usado INT NOT NULL DEFAULT 0 COMMENT '0=no usado, 1=usado',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion DATETIME NOT NULL,
    INDEX idx_email_tipo (email, tipo),
    INDEX idx_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ÍNDICES PARA OPTIMIZACIÓN
-- =====================================================
CREATE INDEX idx_usuarios_empresa ON proyectos_usuarios(empresa_id);
CREATE INDEX idx_usuarios_rol ON proyectos_usuarios(rol);
CREATE INDEX idx_proyectos_empresa ON proyectos_proyectos(empresa_id);
CREATE INDEX idx_proyectos_estado ON proyectos_proyectos(estado);
CREATE INDEX idx_tareas_proyecto ON proyectos_tareas(proyecto_id);
CREATE INDEX idx_tareas_asignado ON proyectos_tareas(asignado_id);
CREATE INDEX idx_tareas_estado ON proyectos_tareas(estado);
CREATE INDEX idx_subtareas_tarea ON proyectos_subtareas(tarea_id);
CREATE INDEX idx_subtareas_realizado ON proyectos_subtareas(realizado_por);
CREATE INDEX idx_tiempos_subtarea ON proyectos_tiempos(subtarea_id);
CREATE INDEX idx_tiempos_usuario ON proyectos_tiempos(usuario_id);
CREATE INDEX idx_tiempos_fecha ON proyectos_tiempos(fecha);
CREATE INDEX idx_reuniones_fecha ON proyectos_reuniones(fecha);
CREATE INDEX idx_reuniones_proyecto ON proyectos_reuniones(proyecto_id);
CREATE INDEX idx_reuniones_empresa ON proyectos_reuniones(empresa_id);
CREATE INDEX idx_comentarios_entidad ON proyectos_comentarios(tipo_entidad, entidad_id);
CREATE INDEX idx_adjuntos_entidad ON proyectos_adjuntos(tipo_entidad, entidad_id);
CREATE INDEX idx_sesiones_usuario ON proyectos_sesiones(usuario_id);

-- =====================================================
-- DATOS INICIALES
-- =====================================================

-- Usuario administrador por defecto
-- Password: Admin123! (hash bcrypt)
INSERT INTO proyectos_usuarios (nombre, email, password, rol, estado, requiere_cambio_clave) VALUES
('Administrador', 'admin@andprojects.com', '$2y$10$w4Iepm6Nn8Bhl8y0fSYRB.3NzJY7hAqg3sMPOajipFVX1YMlbZEea', 'admin', 1, 0);

-- Empresa de ejemplo
INSERT INTO proyectos_empresas (nombre, nit, estado) VALUES
('Empresa Demo', '900123456-7', 1);

-- Colaborador de ejemplo
INSERT INTO proyectos_usuarios (nombre, email, password, rol, estado, requiere_cambio_clave) VALUES
('Colaborador Demo', 'colaborador@andprojects.com', '$2y$10$w4Iepm6Nn8Bhl8y0fSYRB.3NzJY7hAqg3sMPOajipFVX1YMlbZEea', 'colaborador', 1, 0);

-- Cliente de ejemplo (debe cambiar clave en primer login)
INSERT INTO proyectos_usuarios (empresa_id, nombre, email, password, rol, estado, requiere_cambio_clave) VALUES
(1, 'Cliente Demo', 'cliente@empresa.com', '$2y$10$w4Iepm6Nn8Bhl8y0fSYRB.3NzJY7hAqg3sMPOajipFVX1YMlbZEea', 'cliente', 1, 1);

-- Proyecto de ejemplo
INSERT INTO proyectos_proyectos (empresa_id, nombre, descripcion, fecha_inicio, fecha_fin_estimada, estado, creado_por) VALUES
(1, 'Proyecto Demo', 'Este es un proyecto de demostración para mostrar las funcionalidades del sistema.', CURRENT_DATE(), DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY), 2, 1);

-- Tareas de ejemplo
INSERT INTO proyectos_tareas (proyecto_id, nombre, descripcion, fecha_fin_estimada, estado, prioridad, creado_por) VALUES
(1, 'Análisis de requerimientos', 'Recopilar y documentar los requerimientos del proyecto.', DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY), 3, 3, 1),
(1, 'Diseño de arquitectura', 'Definir la arquitectura técnica del sistema.', DATE_ADD(CURRENT_DATE(), INTERVAL 14 DAY), 2, 3, 1),
(1, 'Desarrollo del módulo principal', 'Implementar las funcionalidades principales.', DATE_ADD(CURRENT_DATE(), INTERVAL 21 DAY), 1, 2, 1);

-- Subtareas de ejemplo
INSERT INTO proyectos_subtareas (tarea_id, nombre, estado, horas_estimadas, orden) VALUES
(1, 'Entrevistas con stakeholders', 3, 4, 1),
(1, 'Documentación de requisitos', 3, 8, 2),
(1, 'Validación con cliente', 3, 2, 3),
(2, 'Diagrama de componentes', 3, 4, 1),
(2, 'Selección de tecnologías', 2, 3, 2),
(2, 'Documentación técnica', 1, 6, 3),
(3, 'Configuración del entorno', 1, 2, 1),
(3, 'Desarrollo de APIs', 1, 16, 2),
(3, 'Pruebas unitarias', 1, 8, 3);
