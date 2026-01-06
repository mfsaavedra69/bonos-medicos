<?php
require_once 'auth/check.php';
require_once 'config/db.php';
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    if (strlen($password) < 6) $mensaje = 'La contraseña debe tener al menos 6 caracteres';
    elseif ($password !== $password2) $mensaje = 'Las contraseñas no coinciden';
    else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE usuarios SET password = ?, primer_ingreso = 0 WHERE id = ?');
        $stmt->bind_param('si', $hash, $_SESSION['user_id']);
        if ($stmt->execute()) {
            $mensaje = 'Contraseña actualizada. Redirigiendo...';
            header('Refresh:2; url=index.php');
            exit;
        } else {
            $mensaje = 'Error al actualizar: ' . $stmt->error;
        }
    }
}
require_once 'includes/header.php';
?>
<h2>Cambiar contraseña</h2>
<?php if ($mensaje): ?>
  <div class="alert alert-info"><?php echo htmlspecialchars($mensaje); ?></div>
<?php endif; ?>
<form method="POST" class="w-50">
  <div class="mb-3">
    <label>Nueva contraseña</label>
    <input type="password" name="password" class="form-control" required>
  </div>
  <div class="mb-3">
    <label>Repetir contraseña</label>
    <input type="password" name="password2" class="form-control" required>
  </div>
  <button class="btn btn-primary">Guardar</button>
</form>

<?php require_once 'includes/footer.php'; ?>