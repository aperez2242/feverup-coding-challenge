<?php
/**
 * Bootstrap for WordPress plugin tests in Docker.
 */

// 1. Ensure WordPress core exists
$_wp_dir = '/tmp/wordpress';
if (!file_exists("{$_wp_dir}/wp-settings.php")) {
    echo "Downloading WordPress core build...\n";
    @mkdir($_wp_dir, 0777, true);
    shell_exec("svn co --quiet https://core.svn.wordpress.org/tags/6.8.3/ {$_wp_dir}");
}

// 2. Ensure WordPress test library exists
$_tests_dir = '/tmp/wordpress-tests-lib';
if (!file_exists("{$_tests_dir}/includes/functions.php")) {
    echo "Installing WordPress test library...\n";
    shell_exec("svn co --quiet https://develop.svn.wordpress.org/tags/6.8.3/tests/phpunit/includes/ $_tests_dir/includes");
    shell_exec("svn co --quiet https://develop.svn.wordpress.org/tags/6.8.3/tests/phpunit/data/ $_tests_dir/data");
}

// 3. Load the Yoast PHPUnit Polyfills if available
if (file_exists('/root/.composer/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php')) {
    require_once '/root/.composer/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';
}

// 4. Load WordPress test functions
require_once $_tests_dir . '/includes/functions.php';

// 5. Load the plugin under test *before* WP bootstrap
tests_add_filter('muplugins_loaded', function () {
    require dirname(__DIR__) . '/wp-content/plugins/pokemon-cpt/pokemon-cpt.php';
});

// 6. Now load the WordPress testing environment
require_once $_tests_dir . '/includes/bootstrap.php';
