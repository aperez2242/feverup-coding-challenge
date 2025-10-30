<?php
/**
 * Plugin Name: Pokémon CPT
 * Description: Registers the pokemon custom post type, taxonomies, meta fields, AJAX, importer from PokéAPI and REST API endpoints.
 * Requires PHP: 8.0
 * Author: Alvaro Perez Blanco
 */

if (!defined('ABSPATH'))
    exit;

final class Pokemon_CPT
{
    const CPT = 'pokemon';
    const TAX_TYPE = 'pokemon_type';
    const NONCE_ACTION = 'pokemon_old_pokedex_nonce';

    public function __construct()
    {
        add_action('init', [$this, 'register_cpt_and_tax']);
        add_action('init', [$this, 'register_meta_fields']);
        add_action('add_meta_boxes', [$this, 'register_metaboxes']);
        add_action('save_post_' . self::CPT, [$this, 'save_meta'], 10, 2);

        // AJAX
        add_action('wp_ajax_get_old_pokedex', [$this, 'ajax_get_old_pokedex']);
        add_action('wp_ajax_nopriv_get_old_pokedex', [$this, 'ajax_get_old_pokedex']);

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_front_assets']);

        // REST
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Importer
        add_action('admin_post_pokemon_import', [$this, 'handle_admin_import']);
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::add_command('pokemon import', [$this, 'cli_import_command']);
        }

        // Show random pokemon from the system
        add_action('init', [$this, 'register_random_route']);
        add_action('template_redirect', [$this, 'handle_random_redirect']);

        // Spawn a random pokemon from PokéAPI
        add_action('init', [$this, 'register_generate_route']);
        add_action('template_redirect', [$this, 'handle_generate_route']);

    }

    /* -------------------- CPT & Taxonomy -------------------- */
    public function register_cpt_and_tax(): void
    {
        register_post_type(self::CPT, [
            'labels' => [
                'name' => 'Pokémon',
                'singular_name' => 'Pokémon',
                'add_new_item' => 'Add New Pokémon',
                'edit_item' => 'Edit Pokémon',
            ],
            'public' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-shield',
            'supports' => ['title', 'editor', 'thumbnail'],
            'has_archive' => true,
            'rewrite' => ['slug' => 'pokemon'],
        ]);

        register_taxonomy(self::TAX_TYPE, [self::CPT], [
            'labels' => ['name' => 'Types', 'singular_name' => 'Type'],
            'public' => true,
            'hierarchical' => false,
            'show_in_rest' => true,
        ]);
    }

    /* -------------------- Meta -------------------- */
    public function register_meta_fields(): void
    {
        $metas = [
            'pokemon_weight' => 'number',
            'pokedex_old_number' => 'integer',
            'pokedex_old_version' => 'string',
            'pokedex_new_number' => 'integer',
            'pokedex_new_version' => 'string',
        ];
        foreach ($metas as $key => $type) {
            register_post_meta(self::CPT, $key, [
                'type' => $type,
                'single' => true,
                'show_in_rest' => true,
                'auth_callback' => fn() => current_user_can('edit_posts'),
            ]);
        }

        register_post_meta(self::CPT, 'pokemon_moves', [
            'type' => 'array',
            'single' => true,
            'show_in_rest' => [
                'schema' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'short_effect' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
            'auth_callback' => fn() => current_user_can('edit_posts'),
        ]);
    }

    /* -------------------- Admin UI -------------------- */
    public function register_metaboxes(): void
    {
        add_meta_box('pokemon_meta', 'Pokémon Details', [$this, 'render_meta_box'], self::CPT);
    }

    public function render_meta_box(\WP_Post $post): void
    {
        wp_nonce_field('pokemon_meta_save', 'pokemon_meta_nonce');
        $fields = [
            'pokemon_weight' => 'Weight (hectograms)',
            'pokedex_old_number' => 'Old Pokédex #',
            'pokedex_old_version' => 'Old Version',
            'pokedex_new_number' => 'New Pokédex #',
            'pokedex_new_version' => 'New Version',
        ];
        foreach ($fields as $key => $label) {
            $val = esc_attr(get_post_meta($post->ID, $key, true));
            echo "<p><label>{$label}: <input type='text' name='{$key}' value='{$val}'></label></p>";
        }
    }

    public function save_meta(int $post_id, \WP_Post $post): void
    {
        if (!isset($_POST['pokemon_meta_nonce']) || !wp_verify_nonce($_POST['pokemon_meta_nonce'], 'pokemon_meta_save'))
            return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;
        if (!current_user_can('edit_post', $post_id))
            return;

        $keys = ['pokemon_weight', 'pokedex_old_number', 'pokedex_old_version', 'pokedex_new_number', 'pokedex_new_version'];
        foreach ($keys as $k) {
            if (isset($_POST[$k]))
                update_post_meta($post_id, $k, sanitize_text_field($_POST[$k]));
        }
    }

    /* -------------------- Frontend -------------------- */
    public function enqueue_front_assets(): void
    {
        if (!is_singular(self::CPT))
            return;
        wp_enqueue_script('pokemon-ajax', plugin_dir_url(__FILE__) . 'public/pokemon-ajax.js', ['jquery'], '1.0', true);
        wp_localize_script('pokemon-ajax', 'PokemonAjax', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(self::NONCE_ACTION),
        ]);
    }

    public function ajax_get_old_pokedex(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $id = (int) ($_POST['postId'] ?? 0);
        if (!$id || get_post_type($id) !== self::CPT)
            wp_send_json_error(['message' => 'Invalid Pokémon ID'], 400);
        $no = get_post_meta($id, 'pokedex_old_number', true);
        $ver = get_post_meta($id, 'pokedex_old_version', true);
        if (!$no)
            wp_send_json_error(['message' => 'No data found'], 404);
        wp_send_json_success([
            'number' => (int) $no,
            'version' => $ver,
            'formatted' => "Oldest Pokédex: #{$no} ({$ver})",
        ]);
    }

    /* -------------------- REST API -------------------- */
    public function register_rest_routes(): void
    {
        register_rest_route('pokemon/v1', '/list', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_list_pokemon'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('pokemon/v1', '/pokemon/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_pokemon'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function rest_list_pokemon(\WP_REST_Request $req): \WP_REST_Response
    {
        $posts = get_posts([
            'post_type' => self::CPT,
            'numberposts' => -1,
            'post_status' => 'publish',
        ]);

        $data = [];

        foreach ($posts as $p) {
            $new_num = (int) get_post_meta($p->ID, 'pokedex_new_number', true);
            $thumb_url = wp_get_attachment_image_url(get_post_thumbnail_id($p->ID), 'medium');

            $data[] = [
                'id' => $new_num ?: $p->ID, // Pokédex ID first
                'name' => $p->post_title,
                'link' => get_permalink($p),
                'type' => wp_get_post_terms($p->ID, self::TAX_TYPE, ['fields' => 'names']),
                'image' => $thumb_url,
                'pokedex_new_version' => get_post_meta($p->ID, 'pokedex_new_version', true),
            ];
        }

        return rest_ensure_response($data);
    }

    public function rest_get_pokemon(\WP_REST_Request $req): \WP_REST_Response
    {
        $id = (int) $req['id'];

        $post = get_posts([
            'post_type' => self::CPT,
            'meta_key' => 'pokedex_new_number',
            'meta_value' => $id,
            'numberposts' => 1,
            'post_status' => 'publish',
        ]);

        if (!$post) {
            return new \WP_REST_Response(['message' => 'Pokémon not found'], 404);
        }

        $p = $post[0];

        $data = [
            'id' => $id,
            'name' => $p->post_title,
            'description' => wp_strip_all_tags($p->post_content),
            'photo' => get_the_post_thumbnail_url($p->ID, 'large'),
            'types' => wp_get_post_terms($p->ID, self::TAX_TYPE, ['fields' => 'names']),
            'weight' => (float) get_post_meta($p->ID, 'pokemon_weight', true),
            'pokedex_old_number' => (int) get_post_meta($p->ID, 'pokedex_old_number', true),
            'pokedex_old_version' => get_post_meta($p->ID, 'pokedex_old_version', true),
            'pokedex_new_number' => (int) get_post_meta($p->ID, 'pokedex_new_number', true),
            'pokedex_new_version' => get_post_meta($p->ID, 'pokedex_new_version', true),
            'moves' => get_post_meta($p->ID, 'pokemon_moves', true) ?: [],
        ];

        return rest_ensure_response($data);
    }

    /* -------------------- Importer -------------------- */
    public function handle_admin_import(): void
    {
        if (!current_user_can('publish_posts'))
            wp_die('Forbidden', 403);
        $name = sanitize_text_field($_GET['name'] ?? '');
        if (!$name)
            wp_die('Missing ?name=', 400);
        try {
            $id = $this->import_from_pokeapi($name);
            wp_redirect(get_edit_post_link($id, ''));
            exit;
        } catch (\Throwable $e) {
            wp_die('Import failed: ' . esc_html($e->getMessage()), 500);
        }
    }

    public function cli_import_command($args, $assoc_args): void
    {
        if (empty($args[0]))
            \WP_CLI::error('Usage: wp pokemon import <name-or-id>');
        try {
            $id = $this->import_from_pokeapi($args[0]);
            \WP_CLI::success("Imported post $id → " . get_permalink($id));
        } catch (\Throwable $e) {
            \WP_CLI::error($e->getMessage());
        }
    }

    public function import_from_pokeapi(string $name): int
    {
        $url = 'https://pokeapi.co/api/v2/pokemon/' . rawurlencode($name);
        $res = wp_remote_get($url, ['timeout' => 20]);
        if (is_wp_error($res) || wp_remote_retrieve_response_code($res) !== 200) {
            throw new \RuntimeException('PokéAPI request failed for Pokémon data');
        }

        $body = json_decode(wp_remote_retrieve_body($res), true);
        if (empty($body['id'])) {
            throw new \RuntimeException('Invalid Pokémon response from PokéAPI');
        }

        // Fetch species info for description + Pokédex versions
        $species_url = $body['species']['url'] ?? '';
        $species = [];
        if ($species_url) {
            $sres = wp_remote_get($species_url, ['timeout' => 20]);
            if (!is_wp_error($sres) && wp_remote_retrieve_response_code($sres) === 200) {
                $species = json_decode(wp_remote_retrieve_body($sres), true);
            }
        }

        // Prepare data
        $title = ucfirst($body['name']);
        $types = array_map(fn($t) => $t['type']['name'], $body['types']);
        $photo = $body['sprites']['other']['official-artwork']['front_default'] ?? ($body['sprites']['front_default'] ?? '');
        $weight = (int) ($body['weight'] ?? 0);

        // Description: first English flavor text
        $description = 'Imported from PokéAPI.';
        if (!empty($species['flavor_text_entries'])) {
            foreach ($species['flavor_text_entries'] as $entry) {
                if ($entry['language']['name'] === 'en') {
                    $description = str_replace(["\n", "\f"], ' ', $entry['flavor_text']);
                    break;
                }
            }
        }

        // Create the Pokémon post
        $post_id = wp_insert_post([
            'post_type' => self::CPT,
            'post_status' => 'publish',
            'post_title' => $title,
            'post_content' => wp_kses_post($description),
        ]);

        // Attach featured image
        if ($photo)
            $this->attach_image($photo, $post_id);

        // Assign taxonomy terms
        foreach ($types as $t) {
            if (!term_exists($t, self::TAX_TYPE))
                wp_insert_term($t, self::TAX_TYPE);
        }
        wp_set_object_terms($post_id, $types, self::TAX_TYPE);

        // Weight
        update_post_meta($post_id, 'pokemon_weight', $weight);

        // Pokédex numbers (old/new)
        if (!empty($species['pokedex_numbers'])) {
            $entries = $species['pokedex_numbers'];

            // Deterministic order based on real generation / release timeline
            $dex_order = [
                'national' => 1,
                'kanto' => 2,
                'original-johto' => 3,
                'hoenn' => 4,
                'sinnoh' => 5,
                'unova' => 6,
                'kalos-central' => 7,
                'kalos-coastal' => 8,
                'kalos-mountain' => 9,
                'updated-alola' => 10,
                'original-alola' => 11,
                'updated-melemele' => 12,
                'updated-poni' => 13,
                'galar' => 14,
                'isle-of-armor' => 15,
                'crown-tundra' => 16,
                'hisui' => 17,
                'paldea' => 18,
                'blueberry' => 19, // latest DLC region
            ];

            // Sort by our mapping; unknown dexes go to the end
            usort($entries, function ($a, $b) use ($dex_order) {
                $orderA = $dex_order[$a['pokedex']['name']] ?? 999;
                $orderB = $dex_order[$b['pokedex']['name']] ?? 999;
                return $orderA <=> $orderB;
            });

            $old = $entries[0];
            $new = end($entries);

            update_post_meta($post_id, 'pokedex_old_number', (int) $old['entry_number']);
            update_post_meta($post_id, 'pokedex_old_version', sanitize_text_field($old['pokedex']['name']));
            update_post_meta($post_id, 'pokedex_new_number', (int) $new['entry_number']);
            update_post_meta($post_id, 'pokedex_new_version', sanitize_text_field($new['pokedex']['name']));
        } else {
            // fallback if species data missing
            update_post_meta($post_id, 'pokedex_old_number', $body['id']);
            update_post_meta($post_id, 'pokedex_old_version', 'base');
            update_post_meta($post_id, 'pokedex_new_number', $body['id']);
            update_post_meta($post_id, 'pokedex_new_version', 'latest');
        }

        // Fetch first 10 moves (with English short_effect)
        $moves = [];
        foreach (array_slice($body['moves'], 0, 10) as $mv) {
            $murl = $mv['move']['url'] ?? '';
            if (!$murl)
                continue;

            $mres = wp_remote_get($murl, ['timeout' => 10]);
            if (is_wp_error($mres) || wp_remote_retrieve_response_code($mres) !== 200)
                continue;

            $mdata = json_decode(wp_remote_retrieve_body($mres), true);
            $short_effect = '';
            foreach ($mdata['effect_entries'] ?? [] as $e) {
                if ($e['language']['name'] === 'en') {
                    $short_effect = $e['short_effect'];
                    break;
                }
            }

            $moves[] = [
                'name' => $mv['move']['name'],
                'short_effect' => $short_effect ?: 'No description available.',
            ];
        }

        update_post_meta($post_id, 'pokemon_moves', $moves);

        return $post_id;
    }

    private function attach_image(string $url, int $post_id): void
    {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $tmp = download_url($url);
        if (is_wp_error($tmp))
            return;
        $file = ['name' => basename($url), 'tmp_name' => $tmp];
        $id = media_handle_sideload($file, $post_id);
        if (is_wp_error($id))
            @unlink($tmp);
        else
            set_post_thumbnail($post_id, $id);
    }

    /* -------------------- Random Pokémon Redirect -------------------- */
    public function register_random_route(): void
    {
        add_rewrite_rule(
            '^random/?$',
            'index.php?pokemon_random=1',
            'top'
        );

        add_rewrite_tag('%pokemon_random%', '1');
    }

    public function handle_random_redirect(): void
    {
        if (!get_query_var('pokemon_random'))
            return;

        $posts = get_posts([
            'post_type' => self::CPT,
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'orderby' => 'rand',
        ]);

        if (empty($posts)) {
            wp_die('No Pokémon found in the database.', 'Not Found', ['response' => 404]);
        }

        $url = get_permalink($posts[0]->ID);
        wp_safe_redirect($url);
        exit;
    }

    /* -------------------- Generate Random Pokémon Route -------------------- */
    public function register_generate_route(): void
    {
        add_rewrite_rule(
            '^generate/?$',
            'index.php?pokemon_generate=1',
            'top'
        );
        add_rewrite_tag('%pokemon_generate%', '1');
    }

    public function handle_generate_route(): void
    {
        if (!get_query_var('pokemon_generate'))
            return;

        if (!is_user_logged_in() || !current_user_can('publish_posts')) {
            wp_die('Unauthorized. You must be logged in with post creation permissions.', 'Forbidden', ['response' => 403]);
        }

        // Step 1: Pick a random Pokémon name from the PokéAPI
        $random_id = rand(1, 151); // Limit to first generation for simplicity
        $api_url = "https://pokeapi.co/api/v2/pokemon/{$random_id}";
        $res = wp_remote_get($api_url, ['timeout' => 15]);

        if (is_wp_error($res) || wp_remote_retrieve_response_code($res) !== 200) {
            wp_die('Failed to fetch random Pokémon from PokéAPI.', 'Error', ['response' => 500]);
        }

        $body = json_decode(wp_remote_retrieve_body($res), true);
        $name = $body['name'] ?? null;

        if (!$name) {
            wp_die('Invalid Pokémon data received.', 'Error', ['response' => 500]);
        }

        // Step 2: Use existing importer
        try {
            $new_id = $this->import_from_pokeapi($name);
        } catch (\Throwable $e) {
            wp_die('Pokémon generation failed: ' . esc_html($e->getMessage()), 'Error', ['response' => 500]);
        }

        // Step 3: Redirect to new Pokémon page
        wp_safe_redirect(get_permalink($new_id));
        exit;
    }

}

new Pokemon_CPT();
