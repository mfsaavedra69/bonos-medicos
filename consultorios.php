<?php
require_once 'auth/check.php';
require_once 'includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2>Consultorios</h2>
  <button class="btn btn-success" id="btnNuevoConsultorio">Nuevo consultorio</button>
</div>

<table id="tablaConsultorios" class="table table-striped" style="width:100%">
  <thead>
    <tr>
      <th>ID</th>
      <th>Nombre</th>
      <th>Dirección</th>
      <th>Teléfono</th>
      <th>Email</th>
      <th>Estado</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody></tbody>
</table>

<!-- Modal Consultorio -->
<div class="modal fade" id="modalConsultorio" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formConsultorio">
        <div class="modal-header">
          <h5 class="modal-title">Consultorio</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="consultorio_id">
          <div class="mb-3">
            <label>Nombre</label>
            <input type="text" name="nombre" id="consultorio_nombre" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Dirección</label>
            <input type="text" name="direccion" id="consultorio_direccion" class="form-control">
          </div>
          <div class="mb-3">
            <label>Teléfono</label>
            <input type="text" name="telefono" id="consultorio_telefono" class="form-control">
          </div>
          <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" id="consultorio_email" class="form-control">
          </div>
          <div class="mb-3">
            <label>Estado</label>
            <select name="estado" id="consultorio_estado" class="form-select">
              <option value="1">Activo</option>
              <option value="0">Inactivo</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
<script src="assets/js/consultorios.js"></script>