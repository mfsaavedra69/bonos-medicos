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
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="especialidades.php">Especialidades</a></li>
        <li class="nav-item"><a class="nav-link" href="medicos.php">Médicos</a></li>
        <li class="nav-item"><a class="nav-link" href="consultorios.php">Consultorios</a></li>
        <li class="nav-item"><a class="nav-link" href="afiliados.php">Afiliados</a></li>
        <li class="nav-item"><a class="nav-link" href="practicas.php">Prácticas</a></li>
        <li class="nav-item"><a class="nav-link" href="bonos.php">Bonos</a></li>
      </ul>
      <span class="navbar-text text-white me-3"><?php echo htmlspecialchars($usuario_nombre); ?></span>
      <a href="auth/logout.php" class="btn btn-outline-light">Salir</a>
    </div>
  </div>
</nav>
<div class="container mt-4">