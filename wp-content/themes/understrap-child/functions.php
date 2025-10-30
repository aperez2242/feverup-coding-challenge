<?php
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('understrap-child', get_stylesheet_uri(), [], wp_get_theme()->get('Version'));
    if (is_singular('pokemon')) {
        wp_enqueue_script('pokemon-ajax-ts', get_stylesheet_directory_uri() . '/assets/js/pokemon.js', [], '1.0', true);
        wp_localize_script('pokemon-ajax-ts', 'PokemonAjax', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pokemon_old_pokedex_nonce'),
        ]);
    }
}, 20);

// --- Pokémon Grid: enqueue and shortcode ---
add_action('wp_enqueue_scripts', function () {
    if (!is_singular() || !has_shortcode(get_post()->post_content ?? '', 'pokemon_grid')) return;

    wp_enqueue_script(
        'pokemon-grid',
        get_stylesheet_directory_uri() . '/assets/js/pokemon-grid.js',
        [],
        filemtime(get_stylesheet_directory() . '/assets/js/pokemon-grid.js'),
        true
    );

    // Inline minimal styles
    $css = "
    .poke-toolbar { display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:1rem; }
    .poke-btn { border:1px solid #ddd; background:#fff; padding:.4rem .7rem; cursor:pointer; border-radius:6px; }
    .poke-btn.active { background:#007bff; color:#fff; border-color:#007bff; }
    .poke-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:1rem; }
    .poke-card { border:1px solid #eee; border-radius:10px; padding:.75rem; text-align:center; background:#fff; }
    .poke-card img { width:100%; height:140px; object-fit:contain; }
    .poke-name { font-weight:600; margin:.5rem 0 .25rem; }
    .poke-types { font-size:.9rem; color:#666; }
    .poke-pager { display:flex; gap:.5rem; justify-content:center; margin-top:1rem; }
    .poke-page-btn { border:1px solid #ddd; background:#fff; padding:.35rem .6rem; border-radius:6px; cursor:pointer; }
    .poke-page-btn[disabled] { opacity:.5; cursor:not-allowed; }
    ";
    wp_add_inline_style('wp-block-library', $css);

    wp_localize_script('pokemon-grid', 'PokemonGridConfig', [
        'restListUrl' => home_url('/wp-json/pokemon/v1/list'),
        'pokeApiTypesUrl' => 'https://pokeapi.co/api/v2/type',
    ]);
});

add_shortcode('pokemon_grid', function () {
    return '
    <div id="pokemon-grid-root">
      <div class="poke-toolbar" id="poke-toolbar"></div>
      <div class="poke-grid" id="poke-grid"></div>
      <div class="poke-pager" id="poke-pager">
        <button class="poke-page-btn" id="poke-prev">Prev</button>
        <span id="poke-page-info"></span>
        <button class="poke-page-btn" id="poke-next">Next</button>
      </div>
    </div>';
});
