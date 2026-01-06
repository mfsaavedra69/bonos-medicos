<?php
require_once 'auth/check.php';
require_once 'includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2>Prácticas</h2>
  <button class="btn btn-success" id="btnNuevaPractica">Nueva práctica</button>
</div>

<table id="tablaPracticas" class="table table-striped" style="width:100%">
  <thead>
    <tr>
      <th>ID</th>
      <th>Código</th>
      <th>Descripción</th>
      <th>Precio</th>
      <th>Estado</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody></tbody>
</table>

<!-- Modal Práctica -->
<div class="modal fade" id="modalPractica" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="formPractica">
        <div class="modal-header">
          <h5 class="modal-title">Práctica</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="practica_id">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label>Código</label>
              <input type="text" name="codigo" id="practica_codigo" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
              <label>Precio</label>
              <input type="number" step="0.01" name="precio" id="practica_precio" class="form-control" required>
            </div>
            <div class="col-12 mb-3">
              <label>Descripción</label>
              <input type="text" name="descripcion" id="practica_descripcion" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
              <label>Estado</label>
              <select name="estado" id="practica_estado" class="form-select">
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
              </select>
            </div>
            <div class="col-12 mb-3">
              <label>Especialidades (ctrl/cmd + click para seleccionar varias)</label>
              <select name="especialidades" id="practica_especialidades" class="form-select" multiple></select>
            </div>
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
<script src="assets/js/practicas.js"></script>