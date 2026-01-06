<?php
require_once 'auth/check.php';
require_once 'includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2>Especialidades</h2>
  <button class="btn btn-success" id="btnNuevo">Nueva especialidad</button>
</div>

<table id="tablaEspecialidades" class="table table-striped" style="width:100%">
  <thead>
    <tr>
      <th>ID</th>
      <th>Nombre</th>
      <th>Estado</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody></tbody>
</table>

<!-- Modal -->
<div class="modal fade" id="modalEspecialidad" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formEspecialidad">
        <div class="modal-header">
          <h5 class="modal-title">Especialidad</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="especialidad_id">
          <div class="mb-3">
            <label>Nombre</label>
            <input type="text" name="nombre" id="nombre" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Estado</label>
            <select name="estado" id="estado" class="form-select">
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
<script src="assets/js/especialidades.js"></script>