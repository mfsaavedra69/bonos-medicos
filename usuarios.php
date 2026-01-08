<?php
require_once 'auth/check.php';
require_once 'includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2>Usuarios</h2>
  <button class="btn btn-success" id="btnNuevoUsuario">Nuevo usuario</button>
</div>

<table id="tablaUsuarios" class="table table-striped" style="width:100%">
  <thead>
    <tr>
      <th>ID</th>
      <th>Nombre</th>
      <th>Usuario</th>
      <th>Rol</th>
      <th>Estado</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody></tbody>
</table>

<!-- Modal -->
<div class="modal fade" id="modalUsuario" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="formUsuario">
        <div class="modal-header">
          <h5 class="modal-title">Usuario</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="usuario_id">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label>Nombre</label>
              <input type="text" name="nombre" id="usuario_nombre" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
              <label>Usuario</label>
              <input type="text" name="usuario" id="usuario_usuario" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
              <label>Contraseña (dejar en blanco para no cambiar)</label>
              <input type="password" name="password" id="usuario_password" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
              <label>Rol</label>
              <select name="rol" id="usuario_rol" class="form-select">
                <option value="admin">admin</option>
                <option value="user">user</option>
              </select>
            </div>
            <div class="col-md-3 mb-3">
              <label>Activo</label>
              <select name="activo" id="usuario_activo" class="form-select">
                <option value="1">Sí</option>
                <option value="0">No</option>
              </select>
            </div>

            <div class="col-12 mt-3">
              <label>Permisos por menú</label>
              <div id="menu_checklist" class="border rounded p-3" style="max-height:250px; overflow:auto;"></div>
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
<script src="assets/js/usuarios.js"></script>
