// helpers mínimos para llamadas ajax
async function apiFetch(url, opts = {}) {
  const res = await fetch(url, opts);
  const json = await res.json();
  if (!res.ok) throw json;
  return json;
}

function showAlert(message, type = 'danger') {
  const div = document.createElement('div');
  div.className = `alert alert-${type}`;
  div.textContent = message;
  document.querySelector('.container').prepend(div);
  setTimeout(() => div.remove(), 4000);
}
