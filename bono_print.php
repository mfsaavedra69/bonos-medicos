<?php
require_once 'config/db.php';
if (!isset($_GET['id'])) { echo "ID requerido"; exit; }
$id = intval($_GET['id']);
$stmt = $conn->prepare('SELECT b.*, a.nombre AS afiliado_nombre, a.apellido AS afiliado_apellido, a.dni as afiliado_dni FROM bonos b LEFT JOIN afiliados a ON a.id = b.afiliado_id WHERE b.id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$bono = $stmt->get_result()->fetch_assoc();
if (!$bono) { echo "Bono no encontrado"; exit; }
$stmt2 = $conn->prepare('SELECT bi.*, p.descripcion AS practica_descripcion FROM bono_items bi LEFT JOIN practicas p ON p.id = bi.practica_id WHERE bi.bono_id = ?');
$stmt2->bind_param('i', $id);
$stmt2->execute();
$res = $stmt2->get_result(); $items = [];
while ($r = $res->fetch_assoc()) $items[] = $r;
$fecha = htmlspecialchars($bono['fecha']);
$afiliado = htmlspecialchars(trim($bono['afiliado_nombre'] . ' ' . $bono['afiliado_apellido'] . ' (' . $bono['afiliado_dni'] . ')'));
$total = number_format(floatval($bono['total']),2,'.','');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Bono #<?php echo $bono['id']; ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>body{padding:20px;font-family:Arial,Helvetica,sans-serif} .table td, .table th{vertical-align:middle}</style>
</head>
<body>
  <div class="container">
    <div class="row mb-3">
      <div class="col-8">
        <h3>Bono #<?php echo $bono['id']; ?></h3>
        <div>Afiliado: <?php echo $afiliado; ?></div>
        <div>Fecha: <?php echo $fecha; ?></div>
      </div>
      <div class="col-4 text-end">
        <h4>Total: $<?php echo $total; ?></h4>
      </div>
    </div>

    <table class="table table-sm">
      <thead><tr><th>Práctica</th><th>Descripción</th><th class="text-end">Cant</th><th class="text-end">Precio</th><th class="text-end">Subtotal</th></tr></thead>
      <tbody>
        <?php foreach($items as $it): ?>
        <tr>
          <td><?php echo htmlspecialchars($it['practica_descripcion'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($it['descripcion'] ?? ($it['practica_descripcion'] ?? '')); ?></td>
          <td class="text-end"><?php echo intval($it['cantidad']); ?></td>
          <td class="text-end"><?php echo number_format(floatval($it['precio'] ?? ($it['precio_unitario'] ?? 0)),2); ?></td>
          <td class="text-end"><?php echo number_format(floatval($it['subtotal']),2); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="text-end mt-3"><strong>Total: $<?php echo $total; ?></strong></div>
  </div>

  <script>
    window.onload = function(){ window.print(); };
  </script>
</body>
</html>