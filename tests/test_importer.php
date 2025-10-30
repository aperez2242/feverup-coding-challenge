<?php

/**
 * Integration test for the Pokémon importer.
 * Ensures that import_from_pokeapi() creates a valid Pokémon post with all expected meta.
 */

class Test_Importer extends WP_UnitTestCase
{
    private Pokemon_CPT $plugin;

    public function setUp(): void
    {
        parent::setUp();
        $this->plugin = new Pokemon_CPT();
    }

    public function test_import_creates_post()
    {
        // Mock PokéAPI responses
        add_filter('pre_http_request', function ($preempt, $args, $url) {
            // Mock the Pokémon endpoint
            if (strpos($url, 'https://pokeapi.co/api/v2/pokemon/') === 0) {
                return [
                    'headers'  => [],
                    'body'     => json_encode([
                        'id'      => 999,
                        'name'    => 'testmon',
                        'weight'  => 42,
                        'sprites' => [
                            'front_default' => 'https://example.com/img.png'
                        ],
                        'types'   => [
                            ['type' => ['name' => 'grass']],
                            ['type' => ['name' => 'poison']]
                        ],
                        'species' => [
                            'url' => 'https://pokeapi.co/species/1'
                        ],
                        'moves'   => [
                            ['move' => ['name' => 'tackle', 'url' => 'https://pokeapi.co/api/v2/move/1/']],
                            ['move' => ['name' => 'vine-whip', 'url' => 'https://pokeapi.co/api/v2/move/2/']]
                        ]
                    ]),
                    'response' => ['code' => 200, 'message' => 'OK'],
                    'cookies'  => [],
                    'filename' => null
                ];
            }

            // Mock the species endpoint
            if (strpos($url, 'https://pokeapi.co/species/') === 0) {
                return [
                    'headers'  => [],
                    'body'     => json_encode([
                        'pokedex_numbers' => [
                            ['entry_number' => 1, 'pokedex' => ['name' => 'kanto']],
                            ['entry_number' => 151, 'pokedex' => ['name' => 'melemele']]
                        ]
                    ]),
                    'response' => ['code' => 200, 'message' => 'OK'],
                    'cookies'  => [],
                    'filename' => null
                ];
            }

            // Mock each move endpoint
            if (strpos($url, 'https://pokeapi.co/api/v2/move/') === 0) {
                $move_name = (strpos($url, '1') !== false) ? 'tackle' : 'vine-whip';
                return [
                    'headers'  => [],
                    'body'     => json_encode([
                        'name' => $move_name,
                        'effect_entries' => [
                            [
                                'language' => ['name' => 'en'],
                                'short_effect' => 'Inflicts regular damage with no additional effect.'
                            ]
                        ]
                    ]),
                    'response' => ['code' => 200, 'message' => 'OK'],
                    'cookies'  => [],
                    'filename' => null
                ];
            }

            return $preempt;
        }, 10, 3);

        // Run import
        $post_id = $this->plugin->import_from_pokeapi('testmon');

        // Assertions
        $this->assertNotEmpty($post_id, 'Import should return a valid post ID.');
        $this->assertEquals('Testmon', get_post($post_id)->post_title);
        $this->assertEquals(42, (int)get_post_meta($post_id, 'pokemon_weight', true));
        $this->assertContains('grass', wp_get_post_terms($post_id, 'pokemon_type', ['fields' => 'names']));
        $this->assertContains('poison', wp_get_post_terms($post_id, 'pokemon_type', ['fields' => 'names']));

        // Check moves were imported
        $moves = get_post_meta($post_id, 'pokemon_moves', true);
        $this->assertIsArray($moves);
        $this->assertCount(2, $moves, 'Importer should create two moves.');
        $this->assertEquals('tackle', $moves[0]['name']);
        $this->assertEquals('Inflicts regular damage with no additional effect.', $moves[0]['short_effect']);
    }
}
