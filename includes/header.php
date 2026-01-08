<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() == PHP_SESSION_NONE) session_start();
$usuario_nombre = $_SESSION['nombre'] ?? null;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sistema - Bonos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="assets/css/custom.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">Bonos</a>

    <!-- Botón toggler para pantallas pequeñas -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
      aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Contenido del menú -->
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">

        <?php
        // Cargar menús permitidos desde la sesión y renderizar dinámicamente
        $menus_permitidos = $_SESSION['menus'] ?? [];
        $res = $conn->query('SELECT * FROM menus ORDER BY orden ASC');
        $admin_items = [];
        $top_items = [];
        while ($m = $res->fetch_assoc()) {
          if (!in_array($m['clave'], $menus_permitidos)) continue;
          if (strpos($m['clave'], 'admin_') === 0) $admin_items[] = $m;
          else $top_items[] = $m;
        }
        // Renderizar dropdown Administración si tiene items
        if (count($admin_items) > 0): ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Administración
          </a>
          <ul class="dropdown-menu" aria-labelledby="adminDropdown">
            <?php foreach ($admin_items as $mi): ?>
              <li><a class="dropdown-item" href="<?= htmlspecialchars($mi['ruta']) ?>"><?= htmlspecialchars($mi['etiqueta']) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </li>
        <?php endif; ?>

        <?php foreach ($top_items as $ti): ?>
          <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($ti['ruta']) ?>"><?= htmlspecialchars($ti['etiqueta']) ?></a></li>
        <?php endforeach; ?>

      </ul>

      <!-- Usuario y logout -->
      <span class="navbar-text text-white me-3"><?php echo htmlspecialchars($usuario_nombre); ?></span>
      <a href="auth/logout.php" class="btn btn-outline-light">Salir</a>
    </div>
  </div>
</nav>
<div class="container mt-4">