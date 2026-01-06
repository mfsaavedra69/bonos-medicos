<?php
require_once __DIR__ . '/../config/db.php';

// Script para identificar contraseñas que NO parecen estar hasheadas con bcrypt
// y reemplazarlas por password_hash().
// EJECUTAR CON PRECAUCIÓN: hacer backup de la BD antes.
// php migrations/hash_plain_passwords.php

$updated = 0;
$res = $conn->query('SELECT id, usuario, password FROM usuarios');
while ($row = $res->fetch_assoc()) {
    $pass = $row['password'];
    // detectar patrón bcrypt ($2y$, $2b$, $2a$) con 60 caracteres típicos
    if (!preg_match('/^\$2[ayb]\$.{56}$/', $pass)) {
        $newHash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE usuarios SET password = ?, updated_at = NOW() WHERE id = ?');
        if ($stmt) {
            $stmt->bind_param('si', $newHash, $row['id']);
            if ($stmt->execute()) {
                $updated++;
                echo "Usuario {$row['usuario']} (id={$row['id']}) migrado.\n";
            } else {
                echo "Error al actualizar id={$row['id']}: {$stmt->error}\n";
            }
        }
    }
}

echo "Migración finalizada. Contraseñas actualizadas: $updated\n";
