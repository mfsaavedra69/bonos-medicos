-- Migración: tabla para dependientes de afiliados
-- Ejecutar: mysql -u <user> -p bonos < migrations/20260106_afiliado_dependientes.sql

CREATE TABLE IF NOT EXISTS afiliado_dependientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  afiliado_id INT NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  apellido VARCHAR(120) NOT NULL,
  relacion VARCHAR(50) NOT NULL,
  fecha_nacimiento DATE,
  dni VARCHAR(50),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (afiliado_id) REFERENCES afiliados(id) ON DELETE CASCADE
);
