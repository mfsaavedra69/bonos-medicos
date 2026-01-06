document.addEventListener('DOMContentLoaded', () => {
  const tabla = $('#tablaBonos').DataTable({
    ajax: { url: 'api/bonos.php', dataSrc: 'data', error: () => showAlert('Error al cargar bonos') },
    columns: [
      { data: 'id' },
      { data: 'fecha' },
      { data: 'afiliado' },
      { data: 'total', render: d => parseFloat(d).toFixed(2) },
      { data: 'items_count', render: d => parseInt(d || 0, 10) },
      { data: 'estado', render: d => d == 1 ? 'Activo' : 'Anulado' },
      { data: null, orderable: false, render: d => `
        <button class="btn btn-sm btn-primary btn-edit" data-id="${d.id}">Editar</button>
        <button class="btn btn-sm btn-danger btn-delete" data-id="${d.id}">Eliminar</button>` }
    ]
  });

  const modal = new bootstrap.Modal(document.getElementById('modalBono'));
  const form = document.getElementById('formBono');
  const hiddenAfiliado = document.getElementById('bono_afiliado');
  const inputAfiliadoSearch = document.getElementById('bono_afiliado_search');
  const suggestionsAfiliado = document.getElementById('bono_afiliado_suggestions');
  const tablaItems = document.querySelector('#tablaBonoItems tbody');
  const inputTotal = document.getElementById('bono_total');

  // Search helpers
  function debounce(func, wait=300) {
    let t;
    return function(...args) { clearTimeout(t); t = setTimeout(() => func.apply(this, args), wait); };
  }

  async function searchAfiliados(q) {
    try {
      const res = await fetch('api/afiliados.php?q=' + encodeURIComponent(q));
      const json = await res.json();
      return json.data || [];
    } catch (e) { console.error(e); return []; }
  }

  function showAfiliadoSuggestions(list) {
    suggestionsAfiliado.innerHTML = '';
    list.forEach(a => {
      const el = document.createElement('button');
      el.type = 'button';
      el.className = 'list-group-item list-group-item-action';
      el.textContent = `${a.nombre} ${a.apellido} (${a.dni})`;
      el.dataset.id = a.id;
      el.addEventListener('click', () => {
        hiddenAfiliado.value = a.id;
        inputAfiliadoSearch.value = el.textContent;
        suggestionsAfiliado.innerHTML = '';
      });
      suggestionsAfiliado.appendChild(el);
    });
  }

  const debouncedAfSearch = debounce(async function(e) {
    const q = this.value.trim();
    if (q.length < 2) { suggestionsAfiliado.innerHTML = ''; return; }
    const list = await searchAfiliados(q);
    showAfiliadoSuggestions(list);
  }, 250);

  inputAfiliadoSearch.addEventListener('input', debouncedAfSearch);
  inputAfiliadoSearch.addEventListener('focus', debouncedAfSearch);

  // cerrar sugerencias si se hace click afuera
  document.addEventListener('click', function(e){ if (!suggestionsAfiliado.contains(e.target) && e.target !== inputAfiliadoSearch) suggestionsAfiliado.innerHTML = ''; });

  function renderItems(items) {
    tablaItems.innerHTML = '';
    let total = 0;
    (items||[]).forEach((it, idx) => {
      const tr = document.createElement('tr');
      tr.dataset.index = idx;
      tr.innerHTML = `
        <td class="position-relative"><input class="form-control form-control-sm item-practica" value="${it.practica_descripcion || ''}"><div class="practica-suggestions list-group position-absolute w-100" style="z-index:1000"></div></td>
        <td><input class="form-control form-control-sm item-desc" value="${it.descripcion || ''}"></td>
        <td><input type="number" min="1" class="form-control form-control-sm item-cant" value="${it.cantidad || 1}"></td>
        <td><input type="number" step="0.01" class="form-control form-control-sm item-precio" value="${(it.precio||0).toFixed ? it.precio.toFixed(2) : (parseFloat(it.precio)||0).toFixed(2)}"></td>
        <td class="item-subtotal">${(it.subtotal||0).toFixed(2)}</td>
        <td><button class="btn btn-sm btn-danger btn-del-item">Eliminar</button></td>
      `;
      tablaItems.appendChild(tr);
      total += parseFloat(it.subtotal||0);
    });
    inputTotal.value = total.toFixed(2);
  }

  let currentItems = [];
  renderItems(currentItems);

  document.getElementById('btnNuevoBono').addEventListener('click', () => {
    form.reset();
    document.getElementById('bono_id').value = '';
    currentItems = [];
    renderItems(currentItems);
    modal.show();
  });

  document.getElementById('btnAddBonoItem').addEventListener('click', () => {
    currentItems.push({ descripcion: '', cantidad: 1, precio: 0, subtotal: 0 });
    renderItems(currentItems);
  });

  // delegación: eliminar item y recalcular
  tablaItems.addEventListener('click', function(e) {
    if (e.target.matches('.btn-del-item')) {
      const tr = e.target.closest('tr');
      const idx = parseInt(tr.dataset.index, 10);
      currentItems.splice(idx,1);
      renderItems(currentItems);
    }
  });

  // delegación: cambios en cant/precio/desc/practica y autocomplete de practicas
  tablaItems.addEventListener('input', async function(e) {
    const tr = e.target.closest('tr');
    const idx = parseInt(tr.dataset.index, 10);
    if (isNaN(idx)) return;

    // autocomplete para practica (agrega búsqueda por id y por código exacto)
    if (e.target.matches('.item-practica')) {
      const q = e.target.value.trim();
      const suggestions = tr.querySelector('.practica-suggestions');
      suggestions.innerHTML = '';
      if (q === '') return;

      // helper para rellenar la práctica en la fila
      function fillPractice(id, descripcion, precio) {
        tr.dataset.practica_id = id;
        tr.querySelector('.item-practica').value = descripcion;
        const precioInput = tr.querySelector('.item-precio');
        precioInput.value = parseFloat(precio).toFixed(2);
        precioInput.readOnly = true;
        const cant = parseInt(tr.querySelector('.item-cant').value || 1,10);
        const subtotal = Math.round(cant * parseFloat(precio) * 100)/100;
        tr.querySelector('.item-subtotal').textContent = subtotal.toFixed(2);
        currentItems[idx] = { practica_id: parseInt(id,10), descripcion: tr.querySelector('.item-desc').value || '', practica_descripcion: descripcion, cantidad: cant, precio: parseFloat(precio), subtotal };
        const total = currentItems.reduce((s,it)=>s+parseFloat(it.subtotal||0),0);
        inputTotal.value = total.toFixed(2);
        suggestions.innerHTML = '';
      }

      // 1) si el usuario escribió solo números, intentar buscar por ID exacto
      if (/^\d+$/.test(q)) {
        try {
          const res = await fetch('api/practicas.php?id=' + encodeURIComponent(q));
          const p = await res.json();
          if (p && p.id) { fillPractice(p.id, p.descripcion || p.codigo || '', p.precio || 0); return; }
        } catch (e) { console.error(e); }
      }

      // 2) si el texto no tiene espacios (posible código), intentar buscar por código exacto
      if (/^[\w-]+$/.test(q)) {
        try {
          const res = await fetch('api/practicas.php?codigo=' + encodeURIComponent(q));
          const p = await res.json();
          if (p && p.id) { fillPractice(p.id, p.descripcion || p.codigo || '', p.precio || 0); return; }
        } catch (e) { console.error(e); }
      }

      // 3) fallback: búsqueda por texto para sugerencias (como antes)
      if (q.length < 2) return;
      try {
        const res = await fetch('api/practicas.php?q=' + encodeURIComponent(q));
        const json = await res.json();
        (json.data || []).forEach(p => {
          const btn = document.createElement('button');
          btn.type = 'button'; btn.className = 'list-group-item list-group-item-action';
          btn.textContent = `${p.descripcion} (${p.codigo}) - $${parseFloat(p.precio).toFixed(2)}`;
          btn.dataset.id = p.id; btn.dataset.precio = p.precio; btn.dataset.descripcion = p.descripcion;
          btn.addEventListener('click', () => {
            fillPractice(btn.dataset.id, btn.dataset.descripcion, btn.dataset.precio);
          });
          suggestions.appendChild(btn);
        });
      } catch (e) { console.error(e); }
      return;
    }

    // cambios en cantidad o precio o descripcion
    const cant = parseInt(tr.querySelector('.item-cant').value || 1, 10);
    const precio = parseFloat(tr.querySelector('.item-precio').value || 0);
    const desc = tr.querySelector('.item-desc').value || '';
    const pract = tr.querySelector('.item-practica').value || '';
    const subtotal = Math.round(cant * precio * 100)/100;
    currentItems[idx] = { descripcion: desc, practica_descripcion: pract, cantidad: cant, precio: precio, subtotal, practica_id: tr.dataset.practica_id ? parseInt(tr.dataset.practica_id,10) : null };
    tr.querySelector('.item-subtotal').textContent = subtotal.toFixed(2);
    const total = currentItems.reduce((s,it)=>s+parseFloat(it.subtotal||0),0);
    inputTotal.value = total.toFixed(2);
  });

  // editar
  $('#tablaBonos tbody').on('click', '.btn-edit', function() {
    const id = this.dataset.id;
    fetch(`api/bonos.php?id=${id}`).then(r=>r.json()).then(data => {
      document.getElementById('bono_id').value = data.id;
      // soportar schema con 'fecha' o 'fecha_emision' (extraer parte fecha si es datetime)
      document.getElementById('bono_fecha').value = data.fecha || (data.fecha_emision ? data.fecha_emision.split(' ')[0] : '');
      // set afiliado hidden and search input for clarity
      if (data.afiliado_id) {
        document.getElementById('bono_afiliado').value = data.afiliado_id;
        document.getElementById('bono_afiliado_search').value = (data.afiliado_nombre || '') + ' ' + (data.afiliado_apellido || '') + ' (' + (data.afiliado_dni || '') + ')';
      }
      currentItems = data.items || [];
      renderItems(currentItems);
      modal.show();
    }).catch(e => { console.error(e); showAlert('Error al cargar bono'); });
  });

  // eliminar
  $('#tablaBonos tbody').on('click', '.btn-delete', function() {
    if (!confirm('¿Confirma eliminar?')) return;
    const id = this.dataset.id;
    fetch('api/bonos.php', { method: 'DELETE', body: `id=${id}` }).then(r=>r.json()).then(res => { if (res && res.success) { tabla.ajax.reload(); updateCajaToday(); } else showAlert(res.error || 'Error al eliminar'); }).catch(e => { console.error(e); showAlert('Error al eliminar'); });
  });

  // submit
  form.addEventListener('submit', function(e) {
    // antes de enviar, bloquear boton submit para evitar dobles envios
    const btn = form.querySelector('button[type=submit]');
    if (btn) { btn.disabled = true; }
    e.preventDefault();
    // clear any practica selections (if user modified text) and ensure precio is editable
    Array.from(tablaItems.querySelectorAll('tr')).forEach(tr => {
      if (!tr.querySelector('.item-practica').value.trim()) { delete tr.dataset.practica_id; tr.querySelector('.item-precio').readOnly = false; }
    });
    const id = document.getElementById('bono_id').value;
    const afiliado_id = document.getElementById('bono_afiliado').value;
    const fecha = document.getElementById('bono_fecha').value;
    // ensure items reflect latest inputs
    // already updated by input handler, but rebuild to be safe
    const items = Array.from(tablaItems.querySelectorAll('tr')).map(tr => ({
      practica_id: tr.dataset.practica_id ? parseInt(tr.dataset.practica_id,10) : null,
      practica_descripcion: tr.querySelector('.item-practica').value || '',
      descripcion: tr.querySelector('.item-desc').value || '',
      cantidad: parseInt(tr.querySelector('.item-cant').value || 1,10),
      precio: parseFloat(tr.querySelector('.item-precio').value || 0),
      subtotal: parseFloat(tr.querySelector('.item-subtotal').textContent || 0)
    }));

    if (!afiliado_id || items.length === 0) { showAlert('Complete afiliado e items'); if (btn) btn.disabled = false; return; }

    const payload = new FormData();
    payload.set('afiliado_id', afiliado_id);
    payload.set('fecha', fecha);
    payload.set('items', JSON.stringify(items));

    if (id) {
      const params = new URLSearchParams();
      params.set('id', id);
      params.set('afiliado_id', afiliado_id);
      params.set('fecha', fecha);
      params.set('items', JSON.stringify(items));
      fetch('api/bonos.php', { method: 'PUT', body: params }).then(r=>r.json()).then(res => { if (res && res.success) { tabla.ajax.reload(); modal.hide(); // abrir vista de impresión
            if (res.bono && res.bono.id) window.open(`bono_print.php?id=${res.bono.id}`, '_blank');
            updateCajaToday(); } else showAlert(res.error || 'Error al actualizar'); }).catch(e => { console.error(e); showAlert('Error al actualizar'); }).finally(() => { if (btn) btn.disabled = false; });
    } else {
      fetch('api/bonos.php', { method: 'POST', body: payload }).then(r=>r.json()).then(res => { if (res && res.success) { tabla.ajax.reload(); modal.hide(); // abrir vista de impresión
            if (res.id) window.open(`bono_print.php?id=${res.id}`, '_blank');
            updateCajaToday(); } else showAlert(res.error || 'Error al crear'); }).catch(e => { console.error(e); showAlert('Error al crear'); }).finally(() => { if (btn) btn.disabled = false; });
    }
  });

  // consultar y actualizar total del día
  async function updateCajaToday() {
    try {
      const today = new Date().toISOString().slice(0,10);
      const res = await fetch(`api/bonos.php?summary=1&date=${today}`);
      const json = await res.json();
      if (json && typeof json.total !== 'undefined') document.getElementById('caja_total').textContent = parseFloat(json.total).toFixed(2);
    } catch (e) { console.error('Error updateCajaToday', e); }
  }

  // inicializar total hoy
  updateCajaToday();

});