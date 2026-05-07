-- Estructura de la base de datos para Caja Diaria
CREATE TABLE IF NOT EXISTS `movimientos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` enum('ingreso','egreso') NOT NULL,
  `concepto` varchar(255) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
