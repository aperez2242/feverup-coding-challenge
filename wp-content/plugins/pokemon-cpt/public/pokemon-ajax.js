(function () {
  document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('old-pokedex-btn');
    if (!btn) return;
    btn.addEventListener('click', async () => {
      const postId = btn.dataset.postId;
      const out = document.getElementById('old-pokedex-output');
      if (out) out.textContent = 'Loading...';
      const form = new FormData();
      form.append('action', 'get_old_pokedex');
      form.append('nonce', PokemonAjax.nonce);
      form.append('postId', postId);
      try {
        const res = await fetch(PokemonAjax.ajaxUrl, { method: 'POST', body: form });
        const json = await res.json();
        if (json.success) out.textContent = json.data.formatted;
        else out.textContent = json.data?.message || 'Error';
      } catch {
        if (out) out.textContent = 'Request failed';
      }
    });
  });
})();
