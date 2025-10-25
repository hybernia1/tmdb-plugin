<?php
/**
 * Plugin Name:       TMDB Plugin
 * Plugin URI:        https://example.com/tmdb-plugin
 * Description:       Provides integration with The Movie Database (TMDB) API.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            TMDB Plugin Team
 * Author URI:        https://example.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tmdb-plugin
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    // Prevent direct access to the file.
    exit;
}

const TMDB_PLUGIN_VERSION = '0.1.0';
const TMDB_PLUGIN_SLUG    = 'tmdb-plugin';
const TMDB_PLUGIN_FILE    = __FILE__;

require_once __DIR__ . '/includes/admin/class-tmdb-admin-page-config.php';
require_once __DIR__ . '/includes/admin/class-tmdb-admin-page-search.php';
require_once __DIR__ . '/includes/post-types/class-tmdb-post-types.php';
require_once __DIR__ . '/includes/meta/class-tmdb-meta-boxes.php';
require_once __DIR__ . '/includes/class-tmdb-plugin.php';

TMDB\Plugin\TMDB_Plugin::get_instance()->init();
