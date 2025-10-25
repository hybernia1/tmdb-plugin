<?php
/**
 * Main plugin bootstrap class.
 *
 * @package TMDBPlugin
 */

namespace TMDB\Plugin;

use TMDB\Plugin\Meta\TMDB_Meta_Boxes;
use TMDB\Plugin\Post_Types\TMDB_Post_Types;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class responsible for bootstrapping plugin functionality.
 */
class TMDB_Plugin {
    /**
     * Holds the singleton instance.
     *
     * @var TMDB_Plugin|null
     */
    private static ?TMDB_Plugin $instance = null;

    /**
     * Retrieves a singleton instance of the plugin bootstrap.
     */
    public static function get_instance(): TMDB_Plugin {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Initialises hooks for the plugin.
     */
    public function init(): void {
        add_action( 'init', [ TMDB_Post_Types::class, 'register' ] );
        add_action( 'add_meta_boxes', [ TMDB_Meta_Boxes::class, 'register' ] );
        add_action( 'save_post', [ TMDB_Meta_Boxes::class, 'save' ], 10, 2 );
        add_action( 'admin_menu', [ $this, 'register_admin_page' ] );
    }

    /**
     * Registers the plugin's introductory admin page.
     */
    public function register_admin_page(): void {
        add_menu_page(
            __( 'TMDB Plugin', 'tmdb-plugin' ),
            __( 'TMDB Plugin', 'tmdb-plugin' ),
            'manage_options',
            TMDB_PLUGIN_SLUG,
            [ $this, 'render_admin_page' ],
            'dashicons-format-video'
        );
    }

    /**
     * Renders the plugin admin page content.
     */
    public function render_admin_page(): void {
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
}
