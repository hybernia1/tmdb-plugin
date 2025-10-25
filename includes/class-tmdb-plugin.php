<?php
/**
 * Main plugin bootstrap class.
 *
 * @package TMDBPlugin
 */

namespace TMDB\Plugin;

use TMDB\Plugin\Admin\TMDB_Admin_Page_Config;
use TMDB\Plugin\Admin\TMDB_Admin_Page_Search;
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
        add_action( 'admin_menu', [ TMDB_Admin_Page_Config::class, 'register' ] );
        add_action( 'admin_menu', [ TMDB_Admin_Page_Search::class, 'register' ] );
        add_action( 'admin_enqueue_scripts', [ TMDB_Admin_Page_Search::class, 'enqueue_assets' ] );
        add_action( 'wp_ajax_tmdb_plugin_search_movie', [ TMDB_Admin_Page_Search::class, 'handle_search' ] );
        add_action( 'wp_ajax_tmdb_plugin_import_movie', [ TMDB_Admin_Page_Search::class, 'handle_import' ] );
    }
}
