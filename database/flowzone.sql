-- ============================================================
-- FlowZone - Base de Datos Completa v2.0
-- Sistema de turismo para Ortega, Tolima, Colombia
-- INCLUYE: usuarios, empresas, notificaciones_admin + todas las tablas originales
-- ============================================================

DROP DATABASE IF EXISTS flowzone;
CREATE DATABASE flowzone CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE flowzone;

-- ------------------------------------------------------------
-- TABLA: usuarios
-- rol: 'admin' | 'usuario' | 'empresa'
-- estado: 'activo' | 'pendiente' | 'bloqueado'
-- ------------------------------------------------------------
CREATE TABLE usuarios (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(100)  NOT NULL,
    correo          VARCHAR(150)  NOT NULL,
    password        VARCHAR(255)  NOT NULL,
    rol             ENUM('admin','usuario','empresa') NOT NULL DEFAULT 'usuario',
    estado          ENUM('activo','pendiente','bloqueado') NOT NULL DEFAULT 'activo',
    avatar          VARCHAR(255)  DEFAULT NULL,
    telefono        VARCHAR(20)   DEFAULT NULL,
    creado_en       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_correo (correo),
    INDEX idx_correo (correo),
    INDEX idx_rol (rol),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: empresas
-- Datos extendidos para usuarios de tipo 'empresa'.
-- aprobado: 0 = pendiente, 1 = aprobada por admin.
-- ------------------------------------------------------------
CREATE TABLE empresas (
    id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    usuario_id      INT           NOT NULL,
    nombre          VARCHAR(200)  NOT NULL,
    telefono        VARCHAR(30)   DEFAULT NULL,
    direccion       VARCHAR(400)  DEFAULT NULL,
    aprobado        TINYINT(1)    NOT NULL DEFAULT 0,
    creado_en       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_empresas_usuario (usuario_id),
    CONSTRAINT fk_empresas_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_aprobado (aprobado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: notificaciones_admin
-- Registra cambios/solicitudes de empresas para revisión.
-- ------------------------------------------------------------
CREATE TABLE notificaciones_admin (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    empresa_id  INT UNSIGNED  NOT NULL,
    mensaje     TEXT          NOT NULL,
    leido       TINYINT(1)    NOT NULL DEFAULT 0,
    creado_en   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_notif_empresa
        FOREIGN KEY (empresa_id) REFERENCES empresas (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_leido (leido),
    INDEX idx_empresa_id (empresa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla lugares
CREATE TABLE lugares (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(150) NOT NULL,
    descripcion     TEXT,
    ubicacion       VARCHAR(200),
    latitud         DECIMAL(10,8),
    longitud        DECIMAL(11,8),
    categoria       VARCHAR(100),
    imagen          VARCHAR(255),
    precio_entrada  DECIMAL(10,2) DEFAULT 0,
    horario         VARCHAR(100),
    creado_en       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_categoria (categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla hoteles
CREATE TABLE hoteles (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(150) NOT NULL,
    descripcion     TEXT,
    precio          DECIMAL(10,2) NOT NULL,
    ubicacion       VARCHAR(200),
    latitud         DECIMAL(10,8),
    longitud        DECIMAL(11,8),
    imagen          VARCHAR(255),
    servicios       TEXT,
    capacidad       INT,
    disponibilidad  BOOLEAN DEFAULT TRUE,
    telefono        VARCHAR(20),
    email           VARCHAR(150),
    creado_en       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_disponibilidad (disponibilidad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla eventos
CREATE TABLE eventos (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(150) NOT NULL,
    descripcion     TEXT,
    fecha           DATE NOT NULL,
    hora            TIME,
    ubicacion       VARCHAR(200),
    categoria       VARCHAR(100),
    imagen          VARCHAR(255),
    precio          DECIMAL(10,2) DEFAULT 0,
    organizador     VARCHAR(150),
    contacto        VARCHAR(150),
    creado_en       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla gastronomia
CREATE TABLE gastronomia (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(150) NOT NULL,
    descripcion     TEXT,
    tipo            VARCHAR(100),
    precio_promedio DECIMAL(10,2),
    restaurante     VARCHAR(150),
    direccion       VARCHAR(200),
    telefono        VARCHAR(20),
    imagen          VARCHAR(255),
    ingredientes    TEXT,
    creado_en       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla reservas
CREATE TABLE reservas (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT NOT NULL,
    hotel_id        INT NOT NULL,
    fecha_entrada   DATE NOT NULL,
    fecha_salida    DATE NOT NULL,
    num_personas    INT NOT NULL,
    precio_total    DECIMAL(10,2) NOT NULL,
    estado          ENUM('pendiente','confirmada','cancelada') DEFAULT 'pendiente',
    creado_en       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (hotel_id) REFERENCES hoteles(id) ON DELETE CASCADE,
    INDEX idx_usuario (usuario_id),
    INDEX idx_hotel (hotel_id),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla comentarios
CREATE TABLE comentarios (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT NOT NULL,
    lugar_id    INT NOT NULL,
    comentario  TEXT NOT NULL,
    fecha       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (lugar_id) REFERENCES lugares(id) ON DELETE CASCADE,
    INDEX idx_lugar (lugar_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla calificaciones
CREATE TABLE calificaciones (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id    INT NOT NULL,
    tipo          ENUM('lugar','hotel') NOT NULL,
    item_id       INT NOT NULL,
    calificacion  INT NOT NULL CHECK (calificacion BETWEEN 1 AND 5),
    fecha         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_calificacion (usuario_id, tipo, item_id),
    INDEX idx_item (tipo, item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla favoritos
CREATE TABLE favoritos (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT NOT NULL,
    tipo        ENUM('lugar','hotel') NOT NULL,
    item_id     INT NOT NULL,
    fecha       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorito (usuario_id, tipo, item_id),
    INDEX idx_usuario_tipo (usuario_id, tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATOS DE EJEMPLO
-- Contraseña de todos los usuarios de prueba: admin123
-- ============================================================
INSERT INTO usuarios (nombre, correo, password, rol, estado) VALUES
('Administrador', 'admin@flowzone.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'activo'),
('Juan Pérez', 'juan@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'usuario', 'activo'),
('Hotel El Paraíso S.A.S', 'empresa@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'empresa', 'activo');

INSERT INTO empresas (usuario_id, nombre, telefono, direccion, aprobado) VALUES
(3, 'Hotel El Paraíso S.A.S', '3201234567', 'Km 2 Vía Ortega-Chaparral', 1);

INSERT INTO lugares (nombre, descripcion, ubicacion, latitud, longitud, categoria, imagen, precio_entrada, horario) VALUES
('Cascada La Chorrera', 'Hermosa cascada natural rodeada de vegetación exuberante. Ideal para senderismo y fotografía.', 'Vereda El Bosque, Ortega', 3.8234567, -75.2345678, 'Naturaleza', 'https://images.unsplash.com/photo-1432405972618-c60b0225b8f9', 5000, '8:00 AM - 5:00 PM'),
('Mirador El Cielo', 'Punto panorámico con vista espectacular del valle. Perfecto para atardeceres.', 'Alto de La Cruz, Ortega', 3.8345678, -75.2456789, 'Mirador', 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4', 0, '24 horas'),
('Parque Principal', 'Centro histórico de Ortega con arquitectura colonial y ambiente tradicional.', 'Centro, Ortega', 3.8456789, -75.2567890, 'Cultural', 'https://images.unsplash.com/photo-1541417904950-b855846fe074', 0, '24 horas'),
('Río Ortega', 'Río cristalino ideal para nadar y hacer picnic en familia.', 'Sector El Río, Ortega', 3.8567890, -75.2678901, 'Naturaleza', 'https://images.unsplash.com/photo-1439066615861-d1af74d74000', 0, '7:00 AM - 6:00 PM'),
('Iglesia San Juan Bautista', 'Iglesia colonial del siglo XVIII con arquitectura religiosa tradicional.', 'Parque Principal, Ortega', 3.8678901, -75.2789012, 'Religioso', 'https://images.unsplash.com/photo-1548625149-fc4a29cf7092', 0, '6:00 AM - 7:00 PM');

INSERT INTO hoteles (nombre, descripcion, precio, ubicacion, latitud, longitud, imagen, servicios, capacidad, disponibilidad, telefono, email) VALUES
('Hotel Campestre El Paraíso', 'Hotel campestre con piscina, zonas verdes y restaurante. Ambiente familiar y acogedor.', 120000, 'Km 2 Vía Ortega-Chaparral', 3.8123456, -75.2234567, 'https://images.unsplash.com/photo-1566073771259-6a8506099945', 'WiFi, Piscina, Restaurante, Parqueadero, Zona BBQ', 50, TRUE, '3201234567', 'paraiso@hotel.com'),
('Posada Turística La Montaña', 'Posada con vista panorámica, habitaciones cómodas y desayuno incluido.', 80000, 'Vereda La Montaña, Ortega', 3.8234567, -75.2345678, 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb', 'WiFi, Desayuno, Parqueadero, Mirador', 30, TRUE, '3109876543', 'montana@posada.com'),
('Hotel Colonial Centro', 'Hotel en el centro histórico con arquitectura colonial restaurada.', 95000, 'Calle 5 #3-45, Centro', 3.8345678, -75.2456789, 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa', 'WiFi, Restaurante, Parqueadero, TV Cable', 25, TRUE, '3187654321', 'colonial@hotel.com'),
('Finca Hotel El Descanso', 'Finca hotel con actividades ecológicas, cabalgatas y senderismo.', 150000, 'Vereda El Descanso, Ortega', 3.8456789, -75.2567890, 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4', 'WiFi, Piscina, Cabalgatas, Senderismo, Restaurante', 40, TRUE, '3156789012', 'descanso@finca.com');

INSERT INTO eventos (nombre, descripcion, fecha, hora, ubicacion, categoria, imagen, precio, organizador, contacto) VALUES
('Festival del Café y la Panela', 'Celebración anual de los productos tradicionales de la región con muestras gastronómicas.', '2026-06-15', '09:00:00', 'Parque Principal', 'Gastronómico', 'https://images.unsplash.com/photo-1514933651103-005eec06c04b', 0, 'Alcaldía de Ortega', '3201234567'),
('Cabalgata Turística', 'Recorrido a caballo por los principales atractivos naturales del municipio.', '2026-04-20', '07:00:00', 'Parque Principal', 'Deportivo', 'https://images.unsplash.com/photo-1553284965-83fd3e82fa5a', 30000, 'Club Ecuestre Ortega', '3109876543'),
('Noche de Música Andina', 'Concierto de música tradicional colombiana con artistas locales.', '2026-05-10', '19:00:00', 'Casa de la Cultura', 'Cultural', 'https://images.unsplash.com/photo-1511735111819-9a3f7709049c', 10000, 'Casa de la Cultura', '3187654321'),
('Feria Artesanal', 'Exposición y venta de artesanías locales y productos típicos.', '2026-07-25', '10:00:00', 'Plaza de Mercado', 'Comercial', 'https://images.unsplash.com/photo-1452860606245-08befc0ff44b', 0, 'Asociación de Artesanos', '3156789012');

INSERT INTO gastronomia (nombre, descripcion, tipo, precio_promedio, restaurante, direccion, telefono, imagen, ingredientes) VALUES
('Lechona Tolimense', 'Plato tradicional del Tolima preparado con cerdo relleno de arroz, arveja y especias.', 'Plato Principal', 15000, 'Restaurante Doña María', 'Calle 4 #5-23, Centro', '3201234567', 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1', 'Cerdo, arroz, arveja, cebolla, especias'),
('Tamal Tolimense', 'Tamal envuelto en hoja de plátano con carne de cerdo, pollo, arroz y verduras.', 'Plato Principal', 12000, 'Tamales La Abuela', 'Carrera 3 #6-12', '3109876543', 'https://images.unsplash.com/photo-1562059390-a761a084768e', 'Masa de maíz, cerdo, pollo, zanahoria, arveja'),
('Trucha al Ajillo', 'Trucha fresca de río preparada con ajo y mantequilla, acompañada de patacones.', 'Plato Principal', 18000, 'Restaurante El Río', 'Vía al Río, Km 1', '3187654321', 'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2', 'Trucha, ajo, mantequilla, limón, plátano verde'),
('Pandebono', 'Pan tradicional de queso y almidón de yuca, ideal para el desayuno.', 'Panadería', 2000, 'Panadería El Horno', 'Calle 5 #4-15', '3156789012', 'https://images.unsplash.com/photo-1509440159596-0249088772ff', 'Queso, almidón de yuca, huevo, mantequilla'),
('Café Orgánico', 'Café cultivado en la región con métodos orgánicos, de sabor suave y aromático.', 'Bebida', 3000, 'Café del Pueblo', 'Parque Principal', '3145678901', 'https://images.unsplash.com/photo-1447933601403-0c6688de566e', 'Café 100% colombiano');

INSERT INTO comentarios (usuario_id, lugar_id, comentario) VALUES
(2, 1, 'Increíble experiencia! La cascada es hermosa y el sendero está bien mantenido.'),
(2, 2, 'El mejor lugar para ver el atardecer en Ortega. Totalmente recomendado.');

INSERT INTO calificaciones (usuario_id, tipo, item_id, calificacion) VALUES
(2, 'lugar', 1, 5),
(2, 'lugar', 2, 5),
(2, 'hotel', 1, 4);

INSERT INTO favoritos (usuario_id, tipo, item_id) VALUES
(2, 'lugar', 1),
(2, 'hotel', 1);
