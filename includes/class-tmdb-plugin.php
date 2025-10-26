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
    private const THEME_SLUG = 'tmdb-theme';
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
        add_action( 'plugins_loaded', [ $this, 'register_bundled_theme' ] );
        add_action( 'init', [ TMDB_Post_Types::class, 'register' ] );
        add_action( 'init', [ TMDB_Taxonomies::class, 'register' ] );
        add_action( 'add_meta_boxes', [ TMDB_Meta_Boxes::class, 'register' ] );
        add_action( 'save_post', [ TMDB_Meta_Boxes::class, 'save' ], 10, 2 );
        add_action( 'admin_menu', [ TMDB_Admin_Page_Config::class, 'register' ] );
        add_action( 'admin_menu', [ TMDB_Admin_Page_Search::class, 'register' ] );
        add_action( 'after_setup_theme', [ $this, 'setup_theme_support' ] );
        add_action( 'widgets_init', [ $this, 'register_widget_areas' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );

        add_filter( 'nav_menu_css_class', 'tmdb_theme_nav_menu_item_class', 10, 3 );
        add_filter( 'nav_menu_link_attributes', 'tmdb_theme_nav_menu_link_class', 10, 3 );

        add_filter( 'pre_option_template', [ $this, 'filter_pre_option_template' ] );
        add_filter( 'pre_option_stylesheet', [ $this, 'filter_pre_option_stylesheet' ] );
        add_filter( 'template', [ $this, 'filter_active_template' ] );
        add_filter( 'stylesheet', [ $this, 'filter_active_stylesheet' ] );

        add_filter( 'template_directory', [ $this, 'filter_theme_directory' ], 10, 3 );
        add_filter( 'stylesheet_directory', [ $this, 'filter_theme_directory' ], 10, 3 );
        add_filter( 'template_directory_uri', [ $this, 'filter_theme_directory_uri' ], 10, 3 );
        add_filter( 'stylesheet_directory_uri', [ $this, 'filter_theme_directory_uri' ], 10, 3 );
        add_filter( 'theme_file_path', [ $this, 'filter_theme_file_path' ], 10, 4 );
        add_filter( 'theme_file_uri', [ $this, 'filter_theme_file_uri' ], 10, 4 );

        add_filter( 'template_include', [ $this, 'filter_template_include' ], PHP_INT_MAX );
        add_filter( 'comments_template', [ $this, 'filter_comments_template' ], PHP_INT_MAX );
    }

    /**
     * Registers the bundled theme directory so WordPress recognises the TMDB theme.
     */
    public function register_bundled_theme(): void {
        $theme_root = untrailingslashit( wp_normalize_path( dirname( $this->get_theme_directory() ) ) );

        if ( ! is_dir( $theme_root ) ) {
            return;
        }

        global $wp_theme_directories;

        $registered_directories = array_map(
            static function ( $directory ): string {
                return untrailingslashit( wp_normalize_path( $directory ) );
            },
            (array) $wp_theme_directories
        );

        if ( in_array( $theme_root, $registered_directories, true ) ) {
            return;
        }

        register_theme_directory( $theme_root );

        if ( function_exists( 'wp_clean_themes_cache' ) ) {
            wp_clean_themes_cache();
        }

        if ( function_exists( 'wp_get_theme' ) ) {
            wp_get_theme( self::THEME_SLUG );
        }
    }

    /**
     * Returns the absolute path to a plugin template if it exists.
     */
    private function locate_plugin_template( string $template_name ): ?string {
        $template_name = ltrim( $template_name, '/' );
        $paths          = [
            'themes/tmdb-theme/' . $template_name,
            'themes/' . $template_name,
        ];

        $basename = wp_basename( $template_name );

        if ( $basename !== $template_name ) {
            $paths[] = 'themes/tmdb-theme/' . $basename;
            $paths[] = 'themes/' . $basename;
        }

        foreach ( $paths as $relative_path ) {
            $absolute_path = plugin_dir_path( TMDB_PLUGIN_FILE ) . $relative_path;

            if ( file_exists( $absolute_path ) ) {
                return $absolute_path;
            }
        }

        return null;
    }

    /**
     * Enqueues frontend assets for TMDB templates.
     */
    public function enqueue_frontend_assets(): void {
        if ( ! $this->should_override_request() ) {
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

    /**
     * Registers theme supports to mirror the bundled TMDB theme.
     */
    public function setup_theme_support(): void {
        if ( ! $this->should_override_request() ) {
            return;
        }

        load_theme_textdomain( 'tmdb-theme', $this->get_theme_directory() . '/languages' );

        add_theme_support( 'automatic-feed-links' );
        add_theme_support( 'title-tag' );
        add_theme_support( 'post-thumbnails' );

        register_nav_menus(
            [
                'primary' => __( 'Primary Menu', 'tmdb-theme' ),
                'footer'  => __( 'Footer Menu', 'tmdb-theme' ),
            ]
        );

        add_theme_support(
            'html5',
            [
                'search-form',
                'comment-form',
                'comment-list',
                'gallery',
                'caption',
                'style',
                'script',
            ]
        );

        add_theme_support(
            'custom-logo',
            [
                'height'      => 80,
                'width'       => 80,
                'flex-height' => true,
                'flex-width'  => true,
            ]
        );
    }

    /**
     * Registers widget areas expected by the TMDB theme.
     */
    public function register_widget_areas(): void {
        if ( ! $this->should_override_request() ) {
            return;
        }

        register_sidebar(
            [
                'name'          => __( 'Sidebar', 'tmdb-theme' ),
                'id'            => 'sidebar-1',
                'description'   => __( 'Add widgets here to appear in your sidebar.', 'tmdb-theme' ),
                'before_widget' => '<section id="%1$s" class="widget %2$s mb-4">',
                'after_widget'  => '</section>',
                'before_title'  => '<h2 class="widget-title h5 mb-3">',
                'after_title'   => '</h2>',
            ]
        );
    }

    /**
     * Forces WordPress to treat the TMDB theme as the active template option.
     *
     * @param mixed $value Value of the template option before fetching from the database.
     *
     * @return mixed
     */
    public function filter_pre_option_template( $value ) {
        if ( ! $this->should_override_request() ) {
            return $value;
        }

        return self::THEME_SLUG;
    }

    /**
     * Forces WordPress to treat the TMDB theme as the active stylesheet option.
     *
     * @param mixed $value Value of the stylesheet option before fetching from the database.
     *
     * @return mixed
     */
    public function filter_pre_option_stylesheet( $value ) {
        if ( ! $this->should_override_request() ) {
            return $value;
        }

        return self::THEME_SLUG;
    }

    /**
     * Ensures WordPress reports the TMDB theme as the active template.
     */
    public function filter_active_template( string $template ): string {
        if ( ! $this->should_override_request() ) {
            return $template;
        }

        return self::THEME_SLUG;
    }

    /**
     * Ensures WordPress reports the TMDB theme as the active stylesheet.
     */
    public function filter_active_stylesheet( string $stylesheet ): string {
        if ( ! $this->should_override_request() ) {
            return $stylesheet;
        }

        return self::THEME_SLUG;
    }

    /**
     * Ensures WordPress loads templates from the plugin's theme directory.
     */
    public function filter_theme_directory( string $directory, string $template = '', string $theme_root = '' ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        if ( ! $this->should_override_request() ) {
            return $directory;
        }

        return $this->get_theme_directory();
    }

    /**
     * Ensures WordPress loads theme URIs from the plugin's theme directory.
     */
    public function filter_theme_directory_uri( string $uri, string $template = '', string $theme_root_uri = '' ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        if ( ! $this->should_override_request() ) {
            return $uri;
        }

        return $this->get_theme_directory_uri();
    }

    /**
     * Adjusts theme file paths to point to the plugin's theme directory.
     */
    public function filter_theme_file_path( string $path, string $file = '', string $theme = '', string $original_path = '' ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        if ( ! $this->should_override_request() ) {
            return $path;
        }

        $candidate = $this->locate_plugin_template( $file );

        if ( null !== $candidate ) {
            return $candidate;
        }

        return $path;
    }

    /**
     * Adjusts theme file URIs to point to the plugin's theme directory.
     */
    public function filter_theme_file_uri( string $uri, string $file = '', string $theme = '', string $original_uri = '' ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        if ( ! $this->should_override_request() ) {
            return $uri;
        }

        $file = ltrim( $file, '/' );

        $relative_paths = [
            'themes/tmdb-theme/' . $file,
            'themes/' . $file,
        ];

        foreach ( $relative_paths as $relative_path ) {
            $absolute_path = plugin_dir_path( TMDB_PLUGIN_FILE ) . $relative_path;

            if ( file_exists( $absolute_path ) ) {
                return plugin_dir_url( TMDB_PLUGIN_FILE ) . $relative_path;
            }
        }

        $basename = wp_basename( $file );

        foreach ( $relative_paths as $relative_path ) {
            $absolute_path = plugin_dir_path( TMDB_PLUGIN_FILE ) . dirname( $relative_path ) . '/' . $basename;

            if ( file_exists( $absolute_path ) ) {
                return plugin_dir_url( TMDB_PLUGIN_FILE ) . dirname( $relative_path ) . '/' . $basename;
            }
        }

        return $uri;
    }

    /**
     * Loads the plugin-provided templates in place of the active theme templates.
     */
    public function filter_template_include( string $template ): string {
        if ( ! $this->should_override_request() ) {
            return $template;
        }

        $candidates = $this->build_template_candidates( $template );

        foreach ( $candidates as $candidate ) {
            $plugin_template = $this->locate_plugin_template( $candidate );

            if ( null !== $plugin_template ) {
                return $plugin_template;
            }
        }

        $fallback = $this->locate_plugin_template( 'index.php' );

        return $fallback ?? $template;
    }

    /**
     * Replaces the comments template with the plugin's version when available.
     */
    public function filter_comments_template( string $template ): string {
        if ( ! $this->should_override_request() ) {
            return $template;
        }

        $plugin_template = $this->locate_plugin_template( 'comments.php' );

        return $plugin_template ?? $template;
    }

    /**
     * Determines whether the current request should be served by the plugin theme.
     */
    private function should_override_request(): bool {
        if ( is_admin() || wp_doing_ajax() ) {
            return false;
        }

        if ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) {
            return false;
        }

        if ( function_exists( 'wp_is_jsonp_request' ) && wp_is_jsonp_request() ) {
            return false;
        }

        return true;
    }

    /**
     * Returns the absolute path to the bundled theme directory.
     */
    private function get_theme_directory(): string {
        return plugin_dir_path( TMDB_PLUGIN_FILE ) . 'themes/tmdb-theme';
    }

    /**
     * Returns the URI to the bundled theme directory.
     */
    private function get_theme_directory_uri(): string {
        return plugin_dir_url( TMDB_PLUGIN_FILE ) . 'themes/tmdb-theme';
    }

    /**
     * Builds a list of candidate templates following the WordPress template hierarchy.
     */
    private function build_template_candidates( string $template ): array {
        $candidates = [];

        if ( is_404() ) {
            $candidates[] = '404.php';
        } elseif ( is_search() ) {
            $candidates[] = 'search.php';
        } elseif ( is_front_page() ) {
            $candidates[] = 'front-page.php';

            if ( is_home() ) {
                $candidates[] = 'home.php';
            }
        } elseif ( is_home() ) {
            $candidates[] = 'home.php';
        } elseif ( is_page() ) {
            $page = get_queried_object();

            if ( $page instanceof \WP_Post ) {
                $template_slug = get_page_template_slug( $page );

                if ( ! empty( $template_slug ) && 'default' !== $template_slug ) {
                    $candidates[] = $template_slug;
                }

                if ( ! empty( $page->post_name ) ) {
                    $candidates[] = 'page-' . $page->post_name . '.php';
                }

                $candidates[] = 'page-' . $page->ID . '.php';
            }

            $candidates[] = 'page.php';
        } elseif ( is_singular() ) {
            $post = get_queried_object();

            if ( $post instanceof \WP_Post ) {
                $candidates[] = 'single-' . $post->post_type . '.php';
            }

            $candidates[] = 'single.php';
        } elseif ( is_category() ) {
            $category = get_queried_object();

            if ( $category instanceof \WP_Term ) {
                $candidates[] = 'category-' . $category->slug . '.php';
                $candidates[] = 'category-' . $category->term_id . '.php';
            }

            $candidates[] = 'category.php';
            $candidates[] = 'archive.php';
        } elseif ( is_tag() ) {
            $tag = get_queried_object();

            if ( $tag instanceof \WP_Term ) {
                $candidates[] = 'tag-' . $tag->slug . '.php';
                $candidates[] = 'tag-' . $tag->term_id . '.php';
            }

            $candidates[] = 'tag.php';
            $candidates[] = 'archive.php';
        } elseif ( is_tax() ) {
            $term = get_queried_object();

            if ( $term instanceof \WP_Term ) {
                $taxonomy = $term->taxonomy;
                $candidates[] = 'taxonomy-' . $taxonomy . '-' . $term->slug . '.php';
                $candidates[] = 'taxonomy-' . $taxonomy . '.php';
            }

            $candidates[] = 'taxonomy.php';
            $candidates[] = 'archive.php';
        } elseif ( is_post_type_archive() ) {
            $post_type = get_query_var( 'post_type' );

            if ( is_array( $post_type ) ) {
                $post_type = reset( $post_type );
            }

            if ( is_string( $post_type ) && '' !== $post_type ) {
                $candidates[] = 'archive-' . $post_type . '.php';
            }

            $candidates[] = 'archive.php';
        } elseif ( is_author() ) {
            $author = get_queried_object();

            if ( $author instanceof \WP_User ) {
                $candidates[] = 'author-' . $author->user_nicename . '.php';
                $candidates[] = 'author-' . $author->ID . '.php';
            }

            $candidates[] = 'author.php';
            $candidates[] = 'archive.php';
        } elseif ( is_date() ) {
            $candidates[] = 'date.php';
            $candidates[] = 'archive.php';
        } elseif ( is_archive() ) {
            $candidates[] = 'archive.php';
        }

        $candidates[] = wp_basename( $template );
        $candidates[] = 'index.php';

        return array_values( array_unique( array_filter( $candidates ) ) );
    }
}
