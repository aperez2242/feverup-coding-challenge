const onReady = () => {
  const btn = document.getElementById('old-pokedex-btn') as HTMLButtonElement | null;
  if (!btn) return;

  btn.addEventListener('click', async () => {
    const postId = btn.getAttribute('data-post-id');
    if (!postId) return;

    const out = document.getElementById('old-pokedex-output');
    if (out) out.textContent = 'Loading...';

    const form = new FormData();
    form.append('action', 'get_old_pokedex');
    form.append('nonce', (window as any).PokemonAjax?.nonce ?? '');
    form.append('postId', postId);

    try {
      const resp = await fetch((window as any).PokemonAjax.ajaxUrl, {
        method: 'POST',
        body: form,
        credentials: 'same-origin',
      });
      const json = await resp.json();
      if (json.success) {
        if (out) out.textContent = json.data.formatted;
      } else {
        if (out) out.textContent = 'Error: ' + (json.data?.message ?? 'Unknown');
      }
    } catch (e) {
      if (out) out.textContent = 'Request failed';
      console.error(e);
    }
  });
};

document.addEventListener('DOMContentLoaded', onReady);
