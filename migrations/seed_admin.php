<?php
require_once __DIR__ . '/../config/db.php';

// Crea un usuario admin con contraseña 'admin123' si no existe
// Ejecutá: php migrations/seed_admin.php desde la raíz del proyecto

$usuario = 'admin';
$nombre = 'Administrador';
$password = password_hash('admin123', PASSWORD_DEFAULT);

$stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
$stmt->bind_param('s', $usuario);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
    echo "El usuario 'admin' ya existe.\n";
    exit;
}

$stmt = $conn->prepare("INSERT INTO usuarios (nombre, usuario, password, rol, activo, primer_ingreso) VALUES (?, ?, ?, 'admin', 1, 0)");
$stmt->bind_param('sss', $nombre, $usuario, $password);
if ($stmt->execute()) {
    echo "Usuario 'admin' creado con contraseña 'admin123'. Por favor, cambiá la contraseña en el primer ingreso.\n";
} else {
    echo "Error al crear admin: " . $conn->error . "\n";
}
