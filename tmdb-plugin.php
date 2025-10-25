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

/**
 * Registers admin hooks for the plugin.
 */
function tmdb_plugin_init(): void {
    add_action( 'admin_menu', 'tmdb_plugin_register_admin_page' );
}
add_action( 'plugins_loaded', 'tmdb_plugin_init' );

/**
 * Registers the introductory admin page.
 */
function tmdb_plugin_register_admin_page(): void {
    add_menu_page(
        __( 'TMDB Plugin', 'tmdb-plugin' ),
        __( 'TMDB Plugin', 'tmdb-plugin' ),
        'manage_options',
        TMDB_PLUGIN_SLUG,
        'tmdb_plugin_render_admin_page',
        'dashicons-format-video'
    );
}

/**
 * Renders the introductory admin page.
 */
function tmdb_plugin_render_admin_page(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap tmdb-plugin">
        <h1><?php esc_html_e( 'TMDB Plugin', 'tmdb-plugin' ); ?></h1>
        <p class="description">
            <?php esc_html_e( 'Welcome to the TMDB Plugin! This plugin will help you fetch and display data from The Movie Database (TMDB).', 'tmdb-plugin' ); ?>
        </p>
        <p>
            <?php esc_html_e( 'This introductory page will guide you through the upcoming features and provide quick tips on getting started.', 'tmdb-plugin' ); ?>
        </p>
        <div class="tmdb-plugin__future">
            <h2><?php esc_html_e( 'What\'s coming next?', 'tmdb-plugin' ); ?></h2>
            <ul>
                <li><?php esc_html_e( 'Configuration options for TMDB API credentials.', 'tmdb-plugin' ); ?></li>
                <li><?php esc_html_e( 'Widgets and shortcodes to display movie and series data.', 'tmdb-plugin' ); ?></li>
                <li><?php esc_html_e( 'Caching mechanisms to improve performance.', 'tmdb-plugin' ); ?></li>
            </ul>
        </div>
    </div>
    <?php
}
