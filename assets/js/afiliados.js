document.addEventListener('DOMContentLoaded', () => {
  try {
    const tabla = $('#tablaAfiliados').DataTable({
      ajax: { url: 'api/afiliados.php', dataSrc: 'data', error: () => { console.error('Ajax error al cargar afiliados'); showAlert('Error al cargar afiliados'); } },
      columns: [
        { data: 'id' },
        { data: 'dni' },
        { data: 'nombre' },
        { data: 'apellido' },
        { data: 'fecha_nacimiento' },
        { data: 'telefono' },
        { data: 'email' },
        { data: null, orderable: false, searchable: true, render: d => (d.dependientes_text && d.dependientes_text.trim() !== '' ? `<button class="btn btn-sm btn-info btn-view-deps" data-id="${d.id}">Ver</button>` : '') },
        { data: null, orderable: false, render: d => `
          <button class="btn btn-sm btn-primary btn-edit" data-id="${d.id}">Editar</button>
          <button class="btn btn-sm btn-danger btn-delete" data-id="${d.id}">Eliminar</button>` }
      ]
    });

    // Debug: log resultados de la carga AJAX y errores de DataTables
    tabla.on('xhr', function() {
      const json = tabla.ajax.json();
      console.log('AFILIADOS JSON', json);
      if (json && json.data) console.log('Filas en Afiliados:', json.data.length);
    });
    tabla.on('error.dt', function(e, settings, techNote, message) {
      console.error('DataTables error:', message, techNote);
      showAlert('Error DataTables: ' + (message || techNote));
    });


  const modal = new bootstrap.Modal(document.getElementById('modalAfiliado'));
  const modalDep = new bootstrap.Modal(document.getElementById('modalDependiente'));
  const modalViewDeps = new bootstrap.Modal(document.getElementById('modalViewDependientes'));

  // dependientes en memoria mientras se edita el afiliado
  let dependientes = [];

  function renderDependientes() {
    const tbody = document.querySelector('#tablaDependientes tbody');
    tbody.innerHTML = '';
    dependientes.forEach((d, i) => {
      const tr = document.createElement('tr');
      tr.innerHTML = `<td>${d.nombre}</td><td>${d.apellido}</td><td>${d.relacion}</td><td>${d.fecha_nacimiento || ''}</td><td>${d.dni || ''}</td><td><button class="btn btn-sm btn-danger btn-del-dep" data-index="${i}">Eliminar</button></td>`;
      tbody.appendChild(tr);
    });
  }

  document.getElementById('btnNuevoAfiliado').addEventListener('click', () => {
    document.getElementById('formAfiliado').reset();
    document.getElementById('afiliado_id').value = '';
    dependientes = [];
    renderDependientes();
    modal.show();
  });

  document.getElementById('btnAddDependiente').addEventListener('click', () => {
    document.getElementById('dep_index').value = '';
    document.getElementById('formDependiente').reset();
    modalDep.show();
  });

  document.getElementById('formDependiente').addEventListener('submit', function(e) {
    e.preventDefault();
    const idx = document.getElementById('dep_index').value;
    const dep = {
      nombre: document.getElementById('dep_nombre').value,
      apellido: document.getElementById('dep_apellido').value,
      relacion: document.getElementById('dep_relacion').value,
      fecha_nacimiento: document.getElementById('dep_fecha_nacimiento').value,
      dni: document.getElementById('dep_dni').value
    };
    if (idx === '') dependientes.push(dep); else dependientes[parseInt(idx)] = dep;
    renderDependientes();
    modalDep.hide();
  });

  document.querySelector('#tablaDependientes tbody').addEventListener('click', function(e) {
    if (e.target.matches('.btn-del-dep')) {
      const idx = parseInt(e.target.dataset.index, 10);
      dependientes.splice(idx, 1);
      renderDependientes();
    }
  });

  $('#tablaAfiliados tbody').on('click', '.btn-edit', function() {
    const id = this.dataset.id;
    fetch(`api/afiliados.php?id=${id}`).then(r=>r.json()).then(data => {
      document.getElementById('afiliado_id').value = data.id;
      document.getElementById('afiliado_cuil').value = data.dni || '';
      document.getElementById('afiliado_fecha_alta').value = data.fecha_nacimiento || '';
      document.getElementById('afiliado_nombre').value = data.nombre;
      document.getElementById('afiliado_apellido').value = data.apellido;
      document.getElementById('afiliado_telefono').value = data.telefono;
      document.getElementById('afiliado_email').value = data.email;
      dependientes = data.dependientes || [];
      renderDependientes();
      modal.show();
    }).catch(e => { console.error(e); showAlert('Error al cargar afiliado'); });
  });

  $('#tablaAfiliados tbody').on('click', '.btn-delete', function() {
    if (!confirm('¿Confirma eliminar?')) return;
    const id = this.dataset.id;
    fetch('api/afiliados.php', { method: 'DELETE', body: `id=${id}` })
      .then(r=>r.json()).then((res) => { if (res && res.success) tabla.ajax.reload(); else showAlert(res.error || 'Error al eliminar'); })
      .catch(e => { console.error(e); showAlert('Error al eliminar'); });
  });

  // Mostrar dependientes en modal (lectura)
  $('#tablaAfiliados tbody').on('click', '.btn-view-deps', function() {
    const id = this.dataset.id;
    fetch(`api/afiliados.php?id=${id}`).then(r=>r.json()).then(data => {
      const list = document.getElementById('viewDependientesList');
      list.innerHTML = '';
      const deps = data.dependientes || [];
      if (deps.length === 0) {
        const li = document.createElement('li');
        li.className = 'list-group-item';
        li.textContent = 'No hay dependientes';
        list.appendChild(li);
      } else {
        deps.forEach(d => {
          const li = document.createElement('li');
          li.className = 'list-group-item';
          li.textContent = `${d.nombre} ${d.apellido}${d.dni ? ' (' + d.dni + ')' : ''}`;
          list.appendChild(li);
        });
      }
      modalViewDeps.show();
    }).catch(e => { console.error(e); showAlert('Error al cargar dependientes'); });
  });

  document.getElementById('formAfiliado').addEventListener('submit', function(e) {
    e.preventDefault();
    const id = document.getElementById('afiliado_id').value;
    const form = new FormData(this);
    form.set('dependientes', JSON.stringify(dependientes));

    if (id) {
      // PUT via URLSearchParams
      const payload = new URLSearchParams();
      payload.set('id', id);
      for (const pair of form.entries()) payload.set(pair[0], pair[1]);
      fetch('api/afiliados.php', { method: 'PUT', body: payload })
        .then(r=>r.json()).then((res) => { if (res && res.success) { tabla.ajax.reload(); modal.hide(); } else showAlert(res.error || 'Error al actualizar'); })
        .catch(e => { console.error(e); showAlert('Error al actualizar'); });
    } else {
      fetch('api/afiliados.php', { method: 'POST', body: form })
        .then(r=>r.json()).then((res) => { if (res && res.success) { tabla.ajax.reload(); modal.hide(); } else showAlert(res.error || 'Error al crear'); })
        .catch(e => { console.error(e); showAlert('Error al crear'); });
    }
  });

  } catch (e) { console.error('afiliados.js error', e); showAlert('Error en frontend de Afiliados'); }
});