document.addEventListener('DOMContentLoaded', () => {
  const tabla = $('#tablaMedicos').DataTable({
    ajax: {
      url: 'api/medicos.php',
      dataSrc: 'data',
      error: function() { showAlert('Error al cargar médicos', 'danger'); }
    },
    columns: [
      { data: 'id' },
      { data: 'nombre' },
      { data: 'apellido' },
      { data: 'matricula' },
      { data: 'estado', render: d => d == 1 ? 'Activo' : 'Inactivo' },
      { data: null, orderable: false, render: d => `
        <button class="btn btn-sm btn-primary btn-edit" data-id="${d.id}">Editar</button>
        <button class="btn btn-sm btn-danger btn-delete" data-id="${d.id}">Eliminar</button>` }
    ]
  });

  const modal = new bootstrap.Modal(document.getElementById('modalMedico'));

  // cargar opciones de especialidades
  async function loadEspecialidades(selectEl, selected = []) {
    try {
      const res = await fetch('api/especialidades.php');
      const json = await res.json();
      const data = json.data || [];
      selectEl.innerHTML = '';
      data.forEach(e => {
        const opt = document.createElement('option');
        opt.value = e.id;
        opt.textContent = e.nombre;
        if (selected.includes(parseInt(e.id))) opt.selected = true;
        selectEl.appendChild(opt);
      });
    } catch (err) {
      console.error(err);
      showAlert('Error al cargar especialidades');
    }
  }

  document.getElementById('btnNuevoMedico').addEventListener('click', async () => {
    document.getElementById('formMedico').reset();
    document.getElementById('medico_id').value = '';
    await loadEspecialidades(document.getElementById('medico_especialidades'));
    modal.show();
  });

  $('#tablaMedicos tbody').on('click', '.btn-edit', function() {
    const id = this.dataset.id;
    fetch(`api/medicos.php?id=${id}`).then(r=>r.json()).then(async (data) => {
      document.getElementById('medico_id').value = data.id;
      document.getElementById('medico_nombre').value = data.nombre;
      document.getElementById('medico_apellido').value = data.apellido;
      document.getElementById('medico_matricula').value = data.matricula;
      document.getElementById('medico_telefono').value = data.telefono;
      document.getElementById('medico_email').value = data.email;
      document.getElementById('medico_estado').value = data.estado ? 1 : 0;
      await loadEspecialidades(document.getElementById('medico_especialidades'), data.especialidades || []);
      modal.show();
    }).catch(e => { console.error(e); showAlert('Error al cargar el médico'); });
  });

  $('#tablaMedicos tbody').on('click', '.btn-delete', function() {
    if (!confirm('¿Confirma eliminar?')) return;
    const id = this.dataset.id;
    fetch('api/medicos.php', { method: 'DELETE', body: `id=${id}` })
      .then(r=>r.json()).then((res) => {
        if (res && res.success) tabla.ajax.reload();
        else showAlert(res.error || 'Error al eliminar');
      }).catch(e => { console.error(e); showAlert('Error al eliminar'); });
  });

  document.getElementById('formMedico').addEventListener('submit', function(e) {
    e.preventDefault();
    const id = document.getElementById('medico_id').value;
    const form = new FormData(this);
    // collect selected especialidades
    const sel = Array.from(document.getElementById('medico_especialidades').selectedOptions).map(o => o.value).join(',');
    form.set('especialidades', sel);

    if (id) {
      fetch('api/medicos.php', { method: 'PUT', body: new URLSearchParams({ id, nombre: form.get('nombre'), apellido: form.get('apellido'), matricula: form.get('matricula'), telefono: form.get('telefono'), email: form.get('email'), estado: form.get('estado'), especialidades: form.get('especialidades') }) })
        .then(r=>r.json()).then((res) => {
          if (res && res.success) { tabla.ajax.reload(); modal.hide(); }
          else showAlert(res.error || 'Error al actualizar');
        }).catch(e => { console.error(e); showAlert('Error al actualizar'); });
    } else {
      fetch('api/medicos.php', { method: 'POST', body: form })
        .then(r=>r.json()).then((res) => {
          if (res && res.success) { tabla.ajax.reload(); modal.hide(); }
          else showAlert(res.error || 'Error al crear');
        }).catch(e => { console.error(e); showAlert('Error al crear'); });
    }
  });
});