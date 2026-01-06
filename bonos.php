<?php
require_once 'auth/check.php';
require_once 'includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3"> 
    <div class="d-flex align-items-center"> 
        <button class="btn btn-success me-3" id="btnNuevoBono">Nuevo bono</button> 
        <span><strong>Total hoy:</strong> $<span id="caja_total">0.00</span></span> 
    </div> 
    <h2 class="ms-auto">Bonos</h2> 
</div>

<table id="tablaBonos" class="table table-striped" style="width:100%">
  <thead>
    <tr>
      <th>ID</th>
      <th>Fecha</th>
      <th>Afiliado</th>
      <th>Total</th>
      <th>Items</th>
      <th>Estado</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody></tbody>
</table>

<!-- Modal Bono -->
<div class="modal fade" id="modalBono" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="formBono">
        <div class="modal-header">
          <h5 class="modal-title">Bono</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="bono_id">
          <div class="row mb-3">
            <div class="col-md-6 position-relative">
              <label>Afiliado</label>
              <input type="hidden" name="afiliado_id" id="bono_afiliado">
              <input type="text" id="bono_afiliado_search" class="form-control" placeholder="Buscar por DNI, CUIL o nombre" autocomplete="off" required>
              <div id="bono_afiliado_suggestions" class="list-group position-absolute w-100" style="z-index:1000"></div>
            </div>
            <div class="col-md-3">
              <label>Fecha</label>
              <input type="date" id="bono_fecha" name="fecha" class="form-control" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-3">
              <label>Total</label>
              <input type="text" id="bono_total" class="form-control" readonly value="0.00">
            </div>
          </div>

          <h5>Items</h5>
          <table class="table table-sm" id="tablaBonoItems">
            <thead>
              <tr><th>Práctica</th><th>Descripción</th><th>Cant</th><th>Precio</th><th>Subtotal</th><th></th></tr>
            </thead>
            <tbody></tbody>
          </table>
          <div class="mb-3">
            <button type="button" class="btn btn-sm btn-secondary" id="btnAddBonoItem">Agregar item</button>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar bono</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
<script src="assets/js/bonos.js"></script>
