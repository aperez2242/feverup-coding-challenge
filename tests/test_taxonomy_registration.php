<?php

class Test_Taxonomy_Registration extends WP_UnitTestCase
{

    public function test_pokemon_type_taxonomy_exists()
    {
        $taxonomies = get_taxonomies();
        $this->assertArrayHasKey('pokemon_type', $taxonomies, 'Pokémon Type taxonomy should exist.');
    }
}
