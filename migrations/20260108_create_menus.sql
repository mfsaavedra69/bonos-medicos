-- Migración: crear tablas para permisos por menú
-- Ejecutar: mysql -u <user> -p bonos < migrations/20260108_create_menus.sql

CREATE TABLE IF NOT EXISTS menus (
  id INT AUTO_INCREMENT PRIMARY KEY,
  clave VARCHAR(100) UNIQUE NOT NULL,
  etiqueta VARCHAR(150) NOT NULL,
  ruta VARCHAR(255) NOT NULL,
  orden INT DEFAULT 0,
  activo BOOLEAN DEFAULT TRUE,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS usuario_menu (
  usuario_id INT NOT NULL,
  menu_id INT NOT NULL,
  PRIMARY KEY (usuario_id, menu_id),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE
);

-- Seed básico de menús (ajustá etiquetas/rutas según UI)
INSERT INTO menus (clave, etiqueta, ruta, orden) VALUES
  ('admin_especialidades', 'Administración - Especialidades', 'especialidades.php', 10),
  ('admin_medicos', 'Administración - Médicos', 'medicos.php', 20),
  ('admin_consultorios', 'Administración - Consultorios', 'consultorios.php', 30),
  ('admin_practicas', 'Administración - Prácticas', 'practicas.php', 40),
  ('afiliados', 'Afiliados', 'afiliados.php', 50),
  ('bonos', 'Bonos', 'bonos.php', 60)
ON DUPLICATE KEY UPDATE etiqueta=VALUES(etiqueta), ruta=VALUES(ruta), orden=VALUES(orden);

-- Asignar todos los menús al usuario 'admin' si existe
INSERT INTO usuario_menu (usuario_id, menu_id)
  SELECT u.id, m.id FROM usuarios u CROSS JOIN menus m WHERE u.usuario = 'admin'
ON DUPLICATE KEY UPDATE usuario_id = usuario_id;
