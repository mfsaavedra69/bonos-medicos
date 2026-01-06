<?php
require_once 'auth/check.php';
require_once 'includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2>Médicos</h2>
  <button class="btn btn-success" id="btnNuevoMedico">Nuevo médico</button>
</div>

<table id="tablaMedicos" class="table table-striped" style="width:100%">
  <thead>
    <tr>
      <th>ID</th>
      <th>Nombre</th>
      <th>Apellido</th>
      <th>Matricula</th>
      <th>Estado</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody></tbody>
</table>

<!-- Modal -->
<div class="modal fade" id="modalMedico" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="formMedico">
        <div class="modal-header">
          <h5 class="modal-title">Médico</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="medico_id">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label>Nombre</label>
              <input type="text" name="nombre" id="medico_nombre" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
              <label>Apellido</label>
              <input type="text" name="apellido" id="medico_apellido" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
              <label>Matricula</label>
              <input type="text" name="matricula" id="medico_matricula" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
              <label>Teléfono</label>
              <input type="text" name="telefono" id="medico_telefono" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
              <label>Email</label>
              <input type="email" name="email" id="medico_email" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
              <label>Estado</label>
              <select name="estado" id="medico_estado" class="form-select">
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
              </select>
            </div>
            <div class="col-12 mb-3">
              <label>Especialidades (ctrl/cmd + click para seleccionar varias)</label>
              <select name="especialidades" id="medico_especialidades" class="form-select" multiple></select>
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
<script src="assets/js/medicos.js"></script>