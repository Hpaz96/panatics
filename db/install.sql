-- =============================================================
--  PANATICS ERP - Instalación de Base de Datos
--  Proyecto listo para instalarse con AUTO_INCREMENT, FK,
--  equipo.observaciones tipo texto, tecnico con credenciales
--  de acceso (User/pass) y rol (admin/tecnico) para el login
--  del sistema. Incluye tabla bitácora de modificaciones.
-- =============================================================

CREATE DATABASE IF NOT EXISTS `pana`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_0900_ai_ci;

USE `pana`;

-- ----------------------------------------------------------------
-- Tabla: cliente
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cliente` (
  `Id_cliente` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(40) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `telefono` varchar(15) NOT NULL,
  `correo` varchar(30) NOT NULL,
  `fecha` datetime NOT NULL,
  `sucursal` varchar(30) NOT NULL,
  `rfc` varchar(30) NOT NULL,
  PRIMARY KEY (`Id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------
-- Tabla: tecnico
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tecnico` (
  `Id_tecnico` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(30) NOT NULL,
  `apellido` varchar(30) NOT NULL,
  `telefono` varchar(15) NOT NULL,
  `correo` varchar(30) NOT NULL,
  `puesto` varchar(30) NOT NULL,
  `zona` varchar(30) NOT NULL,
  `User` varchar(30) NOT NULL,
  `pass` varchar(30) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'tecnico',
  PRIMARY KEY (`Id_tecnico`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------
-- Tabla: equipo
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `equipo` (
  `Id_equipo` int NOT NULL AUTO_INCREMENT,
  `Id_cliente` int NOT NULL,
  `tipo_equipo` varchar(30) NOT NULL,
  `marca` varchar(30) NOT NULL,
  `modelo` varchar(30) NOT NULL,
  `serie` varchar(30) NOT NULL,
  `color` varchar(30) NOT NULL,
  `accesorios` varchar(30) NOT NULL,
  `estado_fisico` varchar(30) NOT NULL,
  `observaciones` text NOT NULL,
  PRIMARY KEY (`Id_equipo`),
  UNIQUE KEY `Id_equipo` (`Id_equipo`),
  KEY `Id_cliente` (`Id_cliente`),
  CONSTRAINT `equipo_ibfk_1` FOREIGN KEY (`Id_cliente`)
    REFERENCES `cliente` (`Id_cliente`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------
-- Tabla: reparacion
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reparacion` (
  `Id_reparacion` int NOT NULL AUTO_INCREMENT,
  `Id_equipo` int NOT NULL,
  `Id_tecnico` int NOT NULL,
  `fecha_recepcion` datetime NOT NULL,
  `falla_reportada` text NOT NULL,
  `diagnostico` text NOT NULL,
  `trabajo_realizado` text NOT NULL,
  `estatus` varchar(30) NOT NULL,
  `zona_falla_h` text NOT NULL,
  `zona_falla_s` text NOT NULL,
  `costo_estimado` float NOT NULL DEFAULT '0',
  `costo_total` float NOT NULL,
  `observaciones` text NOT NULL,
  PRIMARY KEY (`Id_reparacion`),
  KEY `Id_equipo` (`Id_equipo`),
  KEY `Id_tecnico` (`Id_tecnico`),
  CONSTRAINT `reparacion_ibfk_1` FOREIGN KEY (`Id_equipo`)
    REFERENCES `equipo` (`Id_equipo`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `reparacion_ibfk_2` FOREIGN KEY (`Id_tecnico`)
    REFERENCES `tecnico` (`Id_tecnico`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------
-- Tabla: garantias (60 días a partir de la fecha de entrega)
-- La fecha de entrega al cliente vive aquí, no en reparacion.
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `garantias` (
  `Id_reparacion` int NOT NULL,
  `Id_tecnico` int NOT NULL,
  `fecha_entrega` datetime NOT NULL,
  `fecha_termino_garantia` datetime NOT NULL,
  `motivo_garantia` text NOT NULL,
  `zona_falla_h` text NOT NULL,
  `zona_falla_s` text NOT NULL,
  PRIMARY KEY (`Id_reparacion`),
  KEY `Id_tecnico` (`Id_tecnico`),
  CONSTRAINT `garantias_ibfk_1` FOREIGN KEY (`Id_reparacion`)
    REFERENCES `reparacion` (`Id_reparacion`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `garantias_ibfk_2` FOREIGN KEY (`Id_tecnico`)
    REFERENCES `tecnico` (`Id_tecnico`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------
-- Tabla: precios (lista de servicios consultable)
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `precios` (
  `servicio` text NOT NULL,
  `mano_de_obra` int NOT NULL,
  `categoria` varchar(15) NOT NULL,
  `tipo` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `precios` (`servicio`, `mano_de_obra`, `categoria`, `tipo`) VALUES
('Formateo e instalación de sistema operativo', 470, 'Software', 'pc'),
('Limpieza interna (desarme, aspirado y reensamblado)', 250, 'Hardware', 'pc'),
('Cambio de pasta térmica', 180, 'Hardware', 'pc'),
('Instalación de controladores y programas', 350, 'Software', 'pc'),
('Diagnóstico general', 250, 'Hardware', 'pc'),
('Instalación de disco duro o SSD', 250, 'Hardware', 'pc'),
('Cambio de pantalla', 350, 'Hardware', 'pc'),
('Cambio de teclado o touchpad', 250, 'Hardware', 'pc'),
('Cambio de bisagras o carcasas', 300, 'Hardware', 'pc'),
('Cambio de batería', 200, 'Hardware', 'pc'),
('Cambio de puerto de carga (Jack DC)', 350, 'Hardware', 'pc'),
('Cambio de fuente de alimentación', 300, 'Hardware', 'pc'),
('Cambio de ventilador o disipador', 250, 'Hardware', 'pc'),
('Respaldo y recuperación de información', 400, 'Software', 'pc'),
('Eliminación de virus y malware', 400, 'Software', 'pc'),
('Configuración de red e internet', 250, 'Software', 'pc'),
('Limpieza y mantenimiento de consola', 350, 'Hardware', 'videojuegos'),
('Cambio de pasta térmica / metal líquido en consola', 400, 'Hardware', 'videojuegos'),
('Cambio de ventilador de consola', 350, 'Hardware', 'videojuegos'),
('Cambio de unidad lectora', 400, 'Hardware', 'videojuegos'),
('Cambio de puerto HDMI', 300, 'Hardware', 'videojuegos'),
('Cambio de chip de video', 600, 'Hardware', 'videojuegos'),
('Formateo y actualización de consola (software)', 350, 'Software', 'videojuegos'),
('Cambio de palancas analógicas', 200, 'Hardware', 'controles'),
('Cambio de gatillos o botones', 180, 'Hardware', 'controles'),
('Cambio de almohadillas de goma', 150, 'Hardware', 'controles'),
('Cambio de placa de circuito en control', 400, 'Hardware', 'controles'),
('Cambio de batería en control', 200, 'Hardware', 'controles'),
('Sincronización y calibración de control', 150, 'Software', 'controles'),
('Mapeo de botones en control', 150, 'Software', 'controles'),
('Cambio de componente interno', 250, 'Hardware', 'otro'),
('Cambio de sistema (software)', 250, 'Software', 'otro');

-- ----------------------------------------------------------------
-- Tabla: pago
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pago` (
  `Id_pago` int NOT NULL AUTO_INCREMENT,
  `Id_reparacion` int NOT NULL,
  `tipo_pago` varchar(30) NOT NULL,
  `monto` float NOT NULL,
  `anticipo` float NOT NULL,
  `saldo_pendiente` float NOT NULL,
  `referencia` varchar(30) NOT NULL,
  `fecha_pago` datetime NOT NULL,
  PRIMARY KEY (`Id_pago`),
  KEY `Id_reparacion` (`Id_reparacion`),
  CONSTRAINT `pago_ibfk_1` FOREIGN KEY (`Id_reparacion`)
    REFERENCES `reparacion` (`Id_reparacion`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------
-- Tabla: fotos
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fotos` (
  `id_foto` int NOT NULL AUTO_INCREMENT,
  `Id_reparacion` int NOT NULL,
  `ruta_imagen` varchar(100) NOT NULL,
  `tipo_foto` int NOT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id_foto`),
  KEY `Id_reparacion` (`Id_reparacion`),
  CONSTRAINT `fotos_ibfk_1` FOREIGN KEY (`Id_reparacion`)
    REFERENCES `reparacion` (`Id_reparacion`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------
-- Tabla: bitacora (registro de modificaciones del sistema)
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bitacora` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario` varchar(60) NOT NULL DEFAULT '',
  `modulo` varchar(20) NOT NULL DEFAULT '',
  `accion` varchar(20) NOT NULL DEFAULT '',
  `id_registro` int NOT NULL DEFAULT 0,
  `descripcion` varchar(255) NOT NULL DEFAULT '',
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------
-- Usuario administrador por defecto (User: admin, pass: admin)
-- Se crea solo si no existe ya un técnico con ese usuario.
-- Se recomienda cambiar la contraseña después del primer acceso.
-- ----------------------------------------------------------------
INSERT INTO `tecnico` (`nombre`, `apellido`, `telefono`, `correo`, `puesto`, `zona`, `User`, `pass`, `role`)
SELECT 'Administrador', 'Admin', '555-000-0000', 'admin@panatics.com', 'Administrador', 'San Andres', 'admin', 'admin', 'admin'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `tecnico` WHERE LOWER(`User`) = 'admin');

-- =============================================================
--  NOTAS:
--  • La columna `role` en la tabla `tecnico` controla permisos:
--    'admin' ve todo y gestiona técnicos; 'tecnico' solo ve sus
--    propias reparaciones y no puede eliminar registros.
--  • La tabla `bitacora` registra cada alta, edición o baja
--    para que el administrador pueda auditar modificaciones.
--  • `reparacion` ya NO tiene `fecha_entrega`: la fecha de entrega
--    y la garantía (60 días) viven en la tabla `garantias`
--    (una por reparación reparada, PK = Id_reparacion).
--  • `reparacion` incluye `zona_falla_h`, `zona_falla_s` y
--    `costo_estimado` (precio estimado de la reparación).
--  • La tabla `precios` es la lista de servicios consultable
--    (categoría Hardware/Software; tipo pc/videojuegos/controles/otro).
--  • La columna de pagos usa el nombre correcto
--    'saldo_pendiente' (se corrigió el typo 'salfo_pendiente').
-- =============================================================
