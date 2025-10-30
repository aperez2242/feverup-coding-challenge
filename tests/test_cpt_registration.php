<?php

class Test_CPT_Registration extends WP_UnitTestCase
{

    public function test_pokemon_cpt_is_registered()
    {
        $post_types = get_post_types();
        $this->assertArrayHasKey('pokemon', $post_types, 'Pokémon CPT should be registered.');
    }
}
