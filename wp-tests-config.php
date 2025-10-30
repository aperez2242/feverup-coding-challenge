<?php
/* Path to the WordPress codebase you'd like to test. */
define('ABSPATH', '/tmp/wordpress/');

/* Database credentials for the test suite */
define('DB_NAME', 'wp_tests');
define('DB_USER', 'wp_user');
define('DB_PASSWORD', 'wp_pass');
define('DB_HOST', 'db'); // matches the db service in docker-compose.yml

/* Database charset and collate type */
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

/* Test suite language */
define('WPLANG', '');

/* WordPress debugging mode */
define('WP_DEBUG', true);

/* Disable multisite tests */
define('WP_TESTS_MULTISITE', false);

/* Path to WordPress test libraries */
define('WP_TESTS_DIR', '/tmp/wordpress-tests-lib');

/* Prevent deletion of uploads folder */
define('WP_TESTS_DOMAIN', 'localhost');
define('WP_TESTS_EMAIL', 'admin@localhost');
define('WP_TESTS_TITLE', 'Test Blog');
define('WP_PHP_BINARY', 'php');
define('WP_DEBUG_DISPLAY', true);

/* Allow tests to run */
define('WP_TESTS_ALLOW_GLOBALS', true);

/* Bootstrap the WordPress test environment */
$table_prefix  = 'wptests_';
