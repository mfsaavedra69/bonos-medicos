document.addEventListener('DOMContentLoaded', () => {
  const tabla = $('#tablaPracticas').DataTable({
    ajax: { url: 'api/practicas.php', dataSrc: 'data', error: () => showAlert('Error al cargar prácticas') },
    columns: [
      { data: 'id' },
      { data: 'codigo' },
      { data: 'descripcion' },
      { data: 'precio', render: d => parseFloat(d).toFixed(2) },
      { data: 'estado', render: d => d == 1 ? 'Activo' : 'Inactivo' },
      { data: null, orderable: false, render: d => `
        <button class="btn btn-sm btn-primary btn-edit" data-id="${d.id}">Editar</button>
        <button class="btn btn-sm btn-danger btn-delete" data-id="${d.id}">Eliminar</button>` }
    ]
  });

  const modal = new bootstrap.Modal(document.getElementById('modalPractica'));

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

  document.getElementById('btnNuevaPractica').addEventListener('click', async () => {
    document.getElementById('formPractica').reset();
    document.getElementById('practica_id').value = '';
    await loadEspecialidades(document.getElementById('practica_especialidades'));
    modal.show();
  });

  // Chequear unicidad de código (utilidad)
  async function checkCodigo(codigo) {
    if (!codigo) return { exists: false };
    try {
      const res = await fetch(`api/practicas.php?codigo=${encodeURIComponent(codigo)}`);
      const json = await res.json();
      if (json && json.id) return { exists: true, id: json.id };
      return { exists: false };
    } catch (e) {
      console.error('Error al verificar código', e);
      return { exists: false };
    }
  }

  document.getElementById('practica_codigo').addEventListener('blur', async function() {
    const codigo = this.value.trim();
    if (!codigo) return;
    const chk = await checkCodigo(codigo);
    const currentId = document.getElementById('practica_id').value;
    if (chk.exists && (!currentId || parseInt(currentId) !== parseInt(chk.id))) {
      showAlert('El código ya existe para otra práctica', 'danger');
    }
  });

  $('#tablaPracticas tbody').on('click', '.btn-edit', function() {
    const id = this.dataset.id;
    fetch(`api/practicas.php?id=${id}`).then(r=>r.json()).then(async (data) => {
      document.getElementById('practica_id').value = data.id;
      document.getElementById('practica_codigo').value = data.codigo;
      document.getElementById('practica_descripcion').value = data.descripcion;
      document.getElementById('practica_precio').value = data.precio;
      document.getElementById('practica_estado').value = data.estado ? 1 : 0;
      await loadEspecialidades(document.getElementById('practica_especialidades'), data.especialidades || []);
      modal.show();
    }).catch(e => { console.error(e); showAlert('Error al cargar la práctica'); });
  });

  $('#tablaPracticas tbody').on('click', '.btn-delete', function() {
    if (!confirm('¿Confirma eliminar?')) return;
    const id = this.dataset.id;
    fetch('api/practicas.php', { method: 'DELETE', body: `id=${id}` })
      .then(r=>r.json()).then((res) => { if (res && res.success) tabla.ajax.reload(); else showAlert(res.error || 'Error al eliminar'); })
      .catch(e => { console.error(e); showAlert('Error al eliminar'); });
  });

  document.getElementById('formPractica').addEventListener('submit', async function(e) {
    e.preventDefault();
    const id = document.getElementById('practica_id').value;
    const form = new FormData(this);
    const sel = Array.from(document.getElementById('practica_especialidades').selectedOptions).map(o => o.value).join(',');
    form.set('especialidades', sel);

    // Validar unicidad antes de enviar
    const codigo = form.get('codigo')?.trim();
    const chk = await checkCodigo(codigo);
    if (chk.exists && (!id || parseInt(id) !== parseInt(chk.id))) {
      showAlert('El código ya existe para otra práctica', 'danger');
      return;
    }

    if (id) {
      const payload = new URLSearchParams();
      payload.set('id', id);
      for (const pair of form.entries()) payload.set(pair[0], pair[1]);
      fetch('api/practicas.php', { method: 'PUT', body: payload })
        .then(r=>r.json()).then((res) => { if (res && res.success) { tabla.ajax.reload(); modal.hide(); } else showAlert(res.error || 'Error al actualizar'); })
        .catch(e => { console.error(e); showAlert('Error al actualizar'); });
    } else {
      fetch('api/practicas.php', { method: 'POST', body: form })
        .then(r=>r.json()).then((res) => { if (res && res.success) { tabla.ajax.reload(); modal.hide(); } else showAlert(res.error || 'Error al crear'); })
        .catch(e => { console.error(e); showAlert('Error al crear'); });
    }
  });
});