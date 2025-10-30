<?php
/**
 * Archive template for Pokémon CPT.
 * Shows a simple grid of featured images and titles. Pagination at 6 per page.
 */
add_action('pre_get_posts', function ($q) {
    if (!is_admin() && $q->is_main_query() && is_post_type_archive('pokemon')) {
        $q->set('posts_per_page', 6);
    }
});
get_header();
?>
<div class="wrapper" id="archive-wrapper">
  <div class="container" id="content" tabindex="-1">
    <h1 class="mb-4">Pokémon</h1>
    <div class="row">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
      <div class="col-6 col-md-4 mb-4">
        <a href="<?php the_permalink(); ?>" class="card h-100 text-decoration-none">
          <?php if (has_post_thumbnail()) the_post_thumbnail('medium', ['class' => 'card-img-top']); ?>
          <div class="card-body">
            <h2 class="h5 card-title"><?php the_title(); ?></h2>
          </div>
        </a>
      </div>
    <?php endwhile; endif; ?>
    </div>
    <div class="my-4">
      <?php the_posts_pagination(); ?>
    </div>
  </div>
</div>
<?php get_footer(); ?>
