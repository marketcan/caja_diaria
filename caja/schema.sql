-- Estructura de la base de datos para Caja Diaria (con prefijo para base de datos compartida)
CREATE TABLE IF NOT EXISTS `caja_movimientos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` enum('ingreso','egreso') NOT NULL,
  `concepto` varchar(255) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
