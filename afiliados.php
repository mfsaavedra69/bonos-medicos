<?php
require_once 'auth/check.php';
require_once 'includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2>Afiliados</h2>
  <button class="btn btn-success" id="btnNuevoAfiliado">Nuevo afiliado</button>
</div>

<table id="tablaAfiliados" class="table table-striped" style="width:100%">
  <thead>
    <tr>
      <th>ID</th>
      <th>DNI / CUIL</th>
      <th>Nombre</th>
      <th>Apellido</th>
      <th>Fecha alta</th>
      <th>Teléfono</th>
      <th>Email</th>
      <th>Dependientes</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody></tbody>
</table>

<!-- Modal Afiliado -->
<div class="modal fade" id="modalAfiliado" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="formAfiliado">
        <div class="modal-header">
          <h5 class="modal-title">Afiliado</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="afiliado_id">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label>DNI / CUIL</label>
              <input type="text" name="cuil" id="afiliado_cuil" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
              <label>Fecha de alta</label>
              <input type="date" name="fecha_alta" id="afiliado_fecha_alta" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
              <label>Nombre</label>
              <input type="text" name="nombre" id="afiliado_nombre" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
              <label>Apellido</label>
              <input type="text" name="apellido" id="afiliado_apellido" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
              <label>Teléfono</label>
              <input type="text" name="telefono" id="afiliado_telefono" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
              <label>Email</label>
              <input type="email" name="email" id="afiliado_email" class="form-control">
            </div>
          </div>

          <hr>
          <h5>Dependientes</h5>
          <div class="mb-3">
            <button type="button" class="btn btn-sm btn-secondary" id="btnAddDependiente">Agregar dependiente</button>
          </div>
          <table class="table table-sm" id="tablaDependientes">
            <thead>
              <tr><th>Nombre</th><th>Apellido</th><th>Relación</th><th>FNacimiento</th><th>DNI</th><th>Acciones</th></tr>
            </thead>
            <tbody></tbody>
          </table>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar afiliado</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Dependiente -->
<div class="modal fade" id="modalDependiente" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formDependiente">
        <div class="modal-header">
          <h5 class="modal-title">Dependiente</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="dep_index">
          <div class="mb-3">
            <label>Nombre</label>
            <input type="text" id="dep_nombre" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Apellido</label>
            <input type="text" id="dep_apellido" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Relación</label>
            <select id="dep_relacion" class="form-select">
              <option value="conyuge">Cónyuge</option>
              <option value="hijo">Hijo/a</option>
              <option value="otro">Otro</option>
            </select>
          </div>
          <div class="mb-3">
            <label>Fecha nacimiento</label>
            <input type="date" id="dep_fecha_nacimiento" class="form-control">
          </div>
          <div class="mb-3">
            <label>DNI</label>
            <input type="text" id="dep_dni" class="form-control">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar dependiente</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal para ver dependientes (solo lectura) -->
<div class="modal fade" id="modalViewDependientes" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Dependientes</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <ul id="viewDependientesList" class="list-group"></ul>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
<script src="assets/js/afiliados.js"></script>