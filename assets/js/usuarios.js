document.addEventListener('DOMContentLoaded', () => {
  const tabla = $('#tablaUsuarios').DataTable({
    ajax: {
      url: 'api/usuarios.php',
      dataSrc: 'data',
      error: function(xhr, error, thrown) {
        console.error('DataTables Ajax error', {xhr, error, thrown});
        showAlert('Error al cargar usuarios', 'danger');
      }
    },
    columns: [
      { data: 'id' },
      { data: 'nombre' },
      { data: 'usuario' },
      { data: 'rol' },
      { data: 'activo', render: d => d == 1 ? 'Sí' : 'No' },
      { data: null, orderable: false, render: d => `
        <button class="btn btn-sm btn-primary btn-edit" data-id="${d.id}">Editar</button>
        <button class="btn btn-sm btn-danger btn-delete" data-id="${d.id}">Eliminar</button>` }
    ]
  });

  const modal = new bootstrap.Modal(document.getElementById('modalUsuario'));

  function loadMenusChecklist(selectedIds = []) {
    fetch('api/menus.php').then(r=>r.json()).then(res => {
      const menus = res.data || [];
      const container = document.getElementById('menu_checklist');
      container.innerHTML = '';
      menus.forEach(m => {
        const id = m.id;
        const checked = selectedIds.includes(id);
        const div = document.createElement('div');
        div.className = 'form-check';
        div.innerHTML = `<input class="form-check-input" type="checkbox" value="${id}" id="menu_${id}" ${checked ? 'checked' : ''}>
                         <label class="form-check-label" for="menu_${id}">${m.etiqueta}</label>`;
        container.appendChild(div);
      });
    }).catch(e => { console.error(e); showAlert('Error al cargar menús'); });
  }

  document.getElementById('btnNuevoUsuario').addEventListener('click', () => {
    document.getElementById('formUsuario').reset();
    document.getElementById('usuario_id').value = '';
    loadMenusChecklist([]);
    modal.show();
  });

  $('#tablaUsuarios tbody').on('click', '.btn-edit', function() {
    const id = this.dataset.id;
    fetch(`api/usuarios.php?id=${id}`).then(r=>r.json()).then(data => {
      document.getElementById('usuario_id').value = data.id;
      document.getElementById('usuario_nombre').value = data.nombre;
      document.getElementById('usuario_usuario').value = data.usuario;
      document.getElementById('usuario_rol').value = data.rol || 'user';
      document.getElementById('usuario_activo').value = data.activo ? 1 : 0;
      loadMenusChecklist(data.menus || []);
      modal.show();
    }).catch(e => showAlert('Error al cargar usuario'));
  });

  $('#tablaUsuarios tbody').on('click', '.btn-delete', function() {
    if (!confirm('¿Confirma eliminar?')) return;
    const id = this.dataset.id;
    fetch('api/usuarios.php', { method: 'DELETE', body: `id=${id}` })
      .then(r=>r.json()).then((res) => {
        if (res && res.success) tabla.ajax.reload();
        else showAlert(res.error || 'Error al eliminar');
      }).catch(e => { console.error(e); showAlert('Error al eliminar'); });
  });

  document.getElementById('formUsuario').addEventListener('submit', function(e) {
    e.preventDefault();
    const id = document.getElementById('usuario_id').value;
    const form = new FormData(this);
    // Recolectar menus
    const checks = Array.from(document.querySelectorAll('#menu_checklist input[type=checkbox]:checked')).map(ch => ch.value);
    const menus = checks.join(',');

    const payload = new URLSearchParams();
    payload.append('nombre', form.get('nombre'));
    payload.append('usuario', form.get('usuario'));
    if (form.get('password')) payload.append('password', form.get('password'));
    payload.append('rol', form.get('rol'));
    payload.append('activo', form.get('activo'));
    payload.append('menus', menus);

    if (id) {
      // PUT
      payload.append('id', id);
      fetch('api/usuarios.php', { method: 'PUT', body: payload })
        .then(r=>r.json()).then((res) => {
          if (res && res.success) { tabla.ajax.reload(); modal.hide(); }
          else showAlert(res.error || 'Error al actualizar');
        })
        .catch(e => { console.error(e); showAlert('Error al actualizar'); });
    } else {
      // POST
      fetch('api/usuarios.php', { method: 'POST', body: payload })
        .then(r=>r.json()).then((res) => {
          if (res && res.success) { tabla.ajax.reload(); modal.hide(); }
          else showAlert(res.error || 'Error al crear');
        })
        .catch(e => { console.error(e); showAlert('Error al crear'); });
    }
  });
});