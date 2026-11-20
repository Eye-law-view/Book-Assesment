document.addEventListener('DOMContentLoaded', () => {
  const input = document.getElementById('title-ac');
  const list  = document.getElementById('suggestions');
  if (input && list) {
    let timer, ctrl;
    input.addEventListener('input', () => {
      clearTimeout(timer);
      const q = input.value.trim();
      if (q.length < 2) { list.innerHTML = ''; return; }
      timer = setTimeout(async () => {
        ctrl?.abort(); ctrl = new AbortController();
        try {
          const res  = await fetch(`search.php?q=${encodeURIComponent(q)}`, { signal: ctrl.signal });
          const rows = await res.json();
          list.innerHTML = '';
          rows.forEach(r => {
            const li = document.createElement('li');
            li.textContent = `${r.title} — ${r.author}`;
            li.addEventListener('click', () => {
              input.value = r.title;
              list.innerHTML = '';
            });
            list.appendChild(li);
          });
        } catch (_) {}
      }, 200);
    });
    document.addEventListener('click', ev => {
      if (!list.contains(ev.target) && ev.target !== input) list.innerHTML = '';
    });
  }
});

function previewCover(fileInput) {
  const img = document.getElementById('cover-preview');
  if (!img || !fileInput.files || !fileInput.files.length) return;
  const file = fileInput.files[0];
  if (!file.type.startsWith('image/')) return;
  if (file.size > 2000000) { alert('File too large (max 2 MB).'); fileInput.value = ''; return; }
  const reader = new FileReader();
  reader.onload = e => { img.src = e.target.result; };
  reader.readAsDataURL(file);
}