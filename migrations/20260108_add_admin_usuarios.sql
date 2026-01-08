-- Migración: agregar menú 'Usuarios' y asignarlo al admin
-- Ejecutar: mysql -u <user> -p bonos < migrations/20260108_add_admin_usuarios.sql

INSERT INTO menus (clave, etiqueta, ruta, orden) VALUES
  ('admin_usuarios', 'Administración - Usuarios', 'usuarios.php', 5)
ON DUPLICATE KEY UPDATE etiqueta=VALUES(etiqueta), ruta=VALUES(ruta), orden=VALUES(orden);

-- Asignar al usuario 'admin' si existe
INSERT INTO usuario_menu (usuario_id, menu_id)
  SELECT u.id, m.id FROM usuarios u CROSS JOIN menus m WHERE u.usuario = 'admin' AND m.clave = 'admin_usuarios'
ON DUPLICATE KEY UPDATE usuario_id = usuario_id;
