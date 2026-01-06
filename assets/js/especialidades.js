document.addEventListener('DOMContentLoaded', () => {
  const tabla = $('#tablaEspecialidades').DataTable({
    ajax: {
      url: 'api/especialidades.php',
      dataSrc: 'data',
      error: function(xhr, error, thrown) {
        console.error('DataTables Ajax error', {xhr, error, thrown});
        showAlert('Error al cargar especialidades', 'danger');
      }
    },
    columns: [
      { data: 'id' },
      { data: 'nombre' },
      { data: 'estado', render: d => d == 1 ? 'Activo' : 'Inactivo' },
      { data: null, orderable: false, render: d => `
        <button class="btn btn-sm btn-primary btn-edit" data-id="${d.id}">Editar</button>
        <button class="btn btn-sm btn-danger btn-delete" data-id="${d.id}">Eliminar</button>` }
    ]
  });

  // Verificación y depuración: registrar la respuesta Ajax y forzar una recarga al iniciar
  $('#tablaEspecialidades').on('xhr.dt', function (e, settings, json, xhr) {
    console.log('DataTables xhr.dt', json);
  });
  // Forzar recarga para evitar resultados cacheados del navegador
  $('#tablaEspecialidades').DataTable().ajax.reload(null, false);

  const modal = new bootstrap.Modal(document.getElementById('modalEspecialidad'));

  document.getElementById('btnNuevo').addEventListener('click', () => {
    document.getElementById('formEspecialidad').reset();
    document.getElementById('especialidad_id').value = '';
    modal.show();
  });

  $('#tablaEspecialidades tbody').on('click', '.btn-edit', function() {
    const id = this.dataset.id;
    fetch(`api/especialidades.php?id=${id}`).then(r=>r.json()).then(data => {
      document.getElementById('especialidad_id').value = data.id;
      document.getElementById('nombre').value = data.nombre;
      document.getElementById('estado').value = data.estado ? 1 : 0;
      modal.show();
    }).catch(e => showAlert('Error al cargar la especialidad'));
  });

  $('#tablaEspecialidades tbody').on('click', '.btn-delete', function() {
    if (!confirm('¿Confirma eliminar?')) return;
    const id = this.dataset.id;
    fetch('api/especialidades.php', { method: 'DELETE', body: `id=${id}` })
      .then(r=>r.json()).then((res) => {
        if (res && res.success) tabla.ajax.reload();
        else showAlert(res.error || 'Error al eliminar');
      }).catch(e => { console.error(e); showAlert('Error al eliminar'); });
  });

  document.getElementById('formEspecialidad').addEventListener('submit', function(e) {
    e.preventDefault();
    const id = document.getElementById('especialidad_id').value;
    const form = new FormData(this);
    if (id) {
      // PUT
      fetch('api/especialidades.php', { method: 'PUT', body: new URLSearchParams({ id, nombre: form.get('nombre'), estado: form.get('estado') }) })
        .then(r=>r.json()).then((res) => {
          if (res && res.success) { tabla.ajax.reload(); modal.hide(); }
          else showAlert(res.error || 'Error al actualizar');
        })
        .catch(e => { console.error(e); showAlert('Error al actualizar'); });
    } else {
      // POST
      fetch('api/especialidades.php', { method: 'POST', body: form })
        .then(r=>r.json()).then((res) => {
          if (res && res.success) { tabla.ajax.reload(); modal.hide(); }
          else showAlert(res.error || 'Error al crear');
        })
        .catch(e => { console.error(e); showAlert('Error al crear'); });
    }
  });
});