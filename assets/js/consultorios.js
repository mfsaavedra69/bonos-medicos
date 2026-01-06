document.addEventListener('DOMContentLoaded', () => {
  const tabla = $('#tablaConsultorios').DataTable({
    ajax: { url: 'api/consultorios.php', dataSrc: 'data', error: () => showAlert('Error al cargar consultorios') },
    columns: [
      { data: 'id' },
      { data: 'nombre' },
      { data: 'direccion' },
      { data: 'telefono' },
      { data: 'email' },
      { data: 'estado', render: d => d == 1 ? 'Activo' : 'Inactivo' },
      { data: null, orderable: false, render: d => `
        <button class="btn btn-sm btn-primary btn-edit" data-id="${d.id}">Editar</button>
        <button class="btn btn-sm btn-danger btn-delete" data-id="${d.id}">Eliminar</button>` }
    ]
  });

  const modal = new bootstrap.Modal(document.getElementById('modalConsultorio'));

  document.getElementById('btnNuevoConsultorio').addEventListener('click', () => {
    document.getElementById('formConsultorio').reset();
    document.getElementById('consultorio_id').value = '';
    modal.show();
  });

  $('#tablaConsultorios tbody').on('click', '.btn-edit', function() {
    const id = this.dataset.id;
    fetch(`api/consultorios.php?id=${id}`).then(r=>r.json()).then(data => {
      document.getElementById('consultorio_id').value = data.id;
      document.getElementById('consultorio_nombre').value = data.nombre;
      document.getElementById('consultorio_direccion').value = data.direccion;
      document.getElementById('consultorio_telefono').value = data.telefono;
      document.getElementById('consultorio_email').value = data.email;
      document.getElementById('consultorio_estado').value = data.estado ? 1 : 0;
      modal.show();
    }).catch(e => { console.error(e); showAlert('Error al cargar consultorio'); });
  });

  $('#tablaConsultorios tbody').on('click', '.btn-delete', function() {
    if (!confirm('¿Confirma eliminar?')) return;
    const id = this.dataset.id;
    fetch('api/consultorios.php', { method: 'DELETE', body: `id=${id}` })
      .then(r=>r.json()).then((res) => { if (res && res.success) tabla.ajax.reload(); else showAlert(res.error || 'Error al eliminar'); })
      .catch(e => { console.error(e); showAlert('Error al eliminar'); });
  });

  document.getElementById('formConsultorio').addEventListener('submit', function(e) {
    e.preventDefault();
    const id = document.getElementById('consultorio_id').value;
    const form = new FormData(this);

    if (id) {
      const payload = new URLSearchParams();
      payload.set('id', id);
      for (const pair of form.entries()) payload.set(pair[0], pair[1]);
      fetch('api/consultorios.php', { method: 'PUT', body: payload })
        .then(r=>r.json()).then((res) => { if (res && res.success) { tabla.ajax.reload(); modal.hide(); } else showAlert(res.error || 'Error al actualizar'); })
        .catch(e => { console.error(e); showAlert('Error al actualizar'); });
    } else {
      fetch('api/consultorios.php', { method: 'POST', body: form })
        .then(r=>r.json()).then((res) => { if (res && res.success) { tabla.ajax.reload(); modal.hide(); } else showAlert(res.error || 'Error al crear'); })
        .catch(e => { console.error(e); showAlert('Error al crear'); });
    }
  });
});