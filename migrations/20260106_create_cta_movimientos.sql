-- Migration: crea la tabla cta_movimientos para registrar cargos y pagos por afiliado

CREATE TABLE IF NOT EXISTS `cta_movimientos` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `afiliado_id` INT NOT NULL,
  `bono_id` INT DEFAULT NULL,
  `tipo` ENUM('cargo','pago') NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `fecha` DATE NOT NULL,
  `descripcion` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`afiliado_id`),
  INDEX (`bono_id`),
  CONSTRAINT `fk_cta_afiliado` FOREIGN KEY (`afiliado_id`) REFERENCES `afiliados` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
