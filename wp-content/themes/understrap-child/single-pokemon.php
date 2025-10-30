<?php
/**
 * Single template for Pokémon CPT.
 * Shows: photo, name, description, types, newest pokedex number+game, and an AJAX button to reveal the oldest pokedex number+version.
 */

get_header();

while (have_posts()) : the_post();
    $id = get_the_ID();
    $types = get_the_terms($id, 'pokemon_type') ?: [];
    $new_no = get_post_meta($id, 'pokedex_new_number', true);
    $new_ver = get_post_meta($id, 'pokedex_new_version', true);
?>
<div class="wrapper" id="single-wrapper">
  <div class="container" id="content" tabindex="-1">
    <div class="row">
      <main class="site-main col" id="main">
        <article <?php post_class('card mb-4'); ?>>
          <div class="card-body">
            <div class="row">
              <div class="col-md-4 mb-3">
                <?php if (has_post_thumbnail()) the_post_thumbnail('large', ['class' => 'img-fluid']); ?>
              </div>
              <div class="col-md-8">
                <h1 class="card-title"><?php the_title(); ?></h1>
                <div class="mb-3"><?php the_content(); ?></div>

                <p>
                  <strong>Types:</strong>
                  <?php
                    if ($types) {
                        echo esc_html(implode(', ', array_map(fn($t) => $t->name, $types)));
                    } else {
                        echo '—';
                    }
                  ?>
                </p>

                <p>
                  <strong>Newest Pokédex:</strong>
                  <?php echo $new_no ? '#' . (int)$new_no : '—'; ?>
                  <?php echo $new_ver ? '(' . esc_html($new_ver) . ')' : ''; ?>
                </p>

                <div class="d-flex align-items-center gap-3">
                  <button id="old-pokedex-btn" class="btn btn-primary" data-post-id="<?php echo esc_attr($id); ?>">
                    Show oldest Pokédex (AJAX)
                  </button>
                  <span id="old-pokedex-output" class="ms-3"></span>
                </div>
              </div>
            </div>

            <?php
              $moves = get_post_meta($id, 'pokemon_moves', true);
              if (is_array($moves) && $moves) :
            ?>
            <hr>
            <h2>Moves</h2>
            <div class="table-responsive">
              <table class="table table-striped">
                <thead><tr><th>Move</th><th>Description</th></tr></thead>
                <tbody>
                <?php foreach ($moves as $m): ?>
                  <tr>
                    <td><?php echo esc_html($m['name'] ?? ''); ?></td>
                    <td><?php echo esc_html($m['short_effect'] ?? ''); ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>
        </article>
      </main>
    </div>
  </div>
</div>
<?php
endwhile;

get_footer();
