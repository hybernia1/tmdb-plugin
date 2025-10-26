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
use TMDB\Plugin\Taxonomies\TMDB_Taxonomies;

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
        add_action( 'init', [ TMDB_Taxonomies::class, 'register' ] );
        add_action( 'add_meta_boxes', [ TMDB_Meta_Boxes::class, 'register' ] );
        add_action( 'save_post', [ TMDB_Meta_Boxes::class, 'save' ], 10, 2 );
        add_action( 'admin_menu', [ TMDB_Admin_Page_Config::class, 'register' ] );
        add_action( 'admin_menu', [ TMDB_Admin_Page_Search::class, 'register' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
        add_filter( 'single_template', [ $this, 'filter_single_template' ] );
        add_filter( 'archive_template', [ $this, 'filter_archive_template' ] );
    }

    /**
     * Loads the plugin single template for TMDB movies when available.
     */
    public function filter_single_template( string $template ): string {
        if ( is_singular( 'movie' ) ) {
            $movie_template = $this->locate_plugin_template( 'single', 'movie' );

            if ( null !== $movie_template ) {
                return $movie_template;
            }
        }

        return $template;
    }

    /**
     * Loads the plugin archive template for TMDB movies when available.
     */
    public function filter_archive_template( string $template ): string {
        if ( is_post_type_archive( 'movie' ) ) {
            $archive_template = $this->locate_plugin_template( 'archive', 'movie' );

            if ( null !== $archive_template ) {
                return $archive_template;
            }
        }

        return $template;
    }

    /**
     * Returns the absolute path to a plugin template if it exists.
     */
    private function locate_plugin_template( string $type, string $name ): ?string {
        $theme_template_path = plugin_dir_path( TMDB_PLUGIN_FILE ) . 'themes/tmdb-theme/' . $type . '-' . $name . '.php';

        if ( file_exists( $theme_template_path ) ) {
            return $theme_template_path;
        }

        $legacy_template_path = plugin_dir_path( TMDB_PLUGIN_FILE ) . 'themes/' . $type . '-' . $name . '.php';

        if ( file_exists( $legacy_template_path ) ) {
            return $legacy_template_path;
        }

        return null;
    }

    /**
     * Enqueues frontend assets for TMDB templates.
     */
    public function enqueue_frontend_assets(): void {
        if ( ! ( is_singular( 'movie' ) || is_post_type_archive( 'movie' ) ) ) {
            return;
        }

        wp_enqueue_style(
            'tmdb-theme-bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
            [],
            '5.3.3'
        );

        $theme_stylesheet_path = plugin_dir_path( TMDB_PLUGIN_FILE ) . 'themes/tmdb-theme/style.css';

        $has_theme_stylesheet = file_exists( $theme_stylesheet_path );

        if ( $has_theme_stylesheet ) {
            wp_enqueue_style(
                'tmdb-theme-style',
                plugin_dir_url( TMDB_PLUGIN_FILE ) . 'themes/tmdb-theme/style.css',
                [ 'tmdb-theme-bootstrap' ],
                (string) filemtime( $theme_stylesheet_path )
            );
        }

        $stylesheet_path = plugin_dir_path( TMDB_PLUGIN_FILE ) . 'themes/assets/tmdb-single-movie.css';

        if ( ! file_exists( $stylesheet_path ) ) {
            $stylesheet_path = null;
        }

        if ( null !== $stylesheet_path ) {
            $dependencies = [ 'tmdb-theme-bootstrap' ];

            if ( $has_theme_stylesheet ) {
                $dependencies[] = 'tmdb-theme-style';
            }

            wp_enqueue_style(
                'tmdb-plugin-single-movie',
                plugin_dir_url( TMDB_PLUGIN_FILE ) . 'themes/assets/tmdb-single-movie.css',
                $dependencies,
                (string) filemtime( plugin_dir_path( TMDB_PLUGIN_FILE ) . 'themes/assets/tmdb-single-movie.css' )
            );
        }

        wp_enqueue_script(
            'tmdb-theme-bootstrap-bundle',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
            [],
            '5.3.3',
            true
        );
    }
}
