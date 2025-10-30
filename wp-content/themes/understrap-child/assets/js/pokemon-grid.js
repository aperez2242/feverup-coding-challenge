(() => {
  const PAGE_SIZE = 6;
  const cfg = window.PokemonGridConfig || {};
  const grid = document.getElementById('poke-grid');
  const toolbar = document.getElementById('poke-toolbar');
  const pagerPrev = document.getElementById('poke-prev');
  const pagerNext = document.getElementById('poke-next');
  const pagerInfo = document.getElementById('poke-page-info');

  let all = [];
  let filtered = [];
  let types = [];
  let activeType = null;
  let page = 1;

  async function init() {
    try {
      [types, all] = await Promise.all([fetchTypes(), fetchPokemon()]);
      renderToolbar();
      applyFilter(null);
      bindPager();
    } catch (e) {
      console.error('Pokémon grid failed:', e);
      grid.innerHTML = '<p>Could not load Pokémon data.</p>';
    }
  }

  async function fetchPokemon() {
    const res = await fetch(cfg.restListUrl);
    const data = await res.json();
    return data.map(p => ({
      id: p.id,
      name: p.name,
      link: p.link,
      type: (p.type || []).map(t => t.toLowerCase()),
      image: p.image || '',
    }));
  }

  async function fetchTypes() {
    const res = await fetch(cfg.pokeApiTypesUrl);
    const data = await res.json();
    const all = data.results.map(r => r.name.toLowerCase());
    const skip = ['unknown', 'shadow'];
    return all.filter(t => !skip.includes(t)).slice(0, 5);
  }

  function renderToolbar() {
    toolbar.innerHTML = '';
    const makeBtn = (label, value) => {
      const btn = document.createElement('button');
      btn.textContent = label;
      btn.className = 'poke-btn';
      btn.addEventListener('click', () => applyFilter(value));
      toolbar.appendChild(btn);
    };
    makeBtn('All', null);
    types.forEach(t => makeBtn(cap(t), t));
  }

  function applyFilter(type) {
    activeType = type;
    toolbar.querySelectorAll('.poke-btn').forEach(btn => {
      const label = btn.textContent.toLowerCase();
      const isAll = label === 'all';
      btn.classList.toggle('active', (isAll && !type) || label === type);
    });
    filtered = !type ? [...all] : all.filter(p => p.type.includes(type));
    page = 1;
    renderPage();
  }

  function renderPage() {
    const total = filtered.length;
    const pages = Math.max(1, Math.ceil(total / PAGE_SIZE));
    const start = (page - 1) * PAGE_SIZE;
    const visible = filtered.slice(start, start + PAGE_SIZE);

    grid.innerHTML = visible.map(p => cardHTML(p)).join('');
    pagerInfo.textContent = `Page ${page} of ${pages}`;
    pagerPrev.disabled = page <= 1;
    pagerNext.disabled = page >= pages;
  }

  function bindPager() {
    pagerPrev.addEventListener('click', () => {
      if (page > 1) {
        page--;
        renderPage();
      }
    });
    pagerNext.addEventListener('click', () => {
      const pages = Math.ceil(filtered.length / PAGE_SIZE);
      if (page < pages) {
        page++;
        renderPage();
      }
    });
  }

  function cardHTML(p) {
    const types = (p.type || []).map(cap).join(' / ');
    const img = p.image
      ? `<img src="${p.image}" alt="${p.name}">`
      : `<div style="height:140px;display:flex;align-items:center;justify-content:center;">No image</div>`;
    return `
      <article class="poke-card">
        <a href="${p.link}" aria-label="${p.name}">${img}</a>
        <div class="poke-name">${p.name}</div>
        <div class="poke-types">${types}</div>
      </article>
    `;
  }

  const cap = str => str.charAt(0).toUpperCase() + str.slice(1);
  document.addEventListener('DOMContentLoaded', init);
})();
