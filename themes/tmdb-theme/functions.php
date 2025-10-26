<?php
/**
 * TMDB Theme functions and definitions.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'tmdb_theme_setup' ) ) {
    /**
     * Set up theme defaults and registers support for various WordPress features.
     */
    function tmdb_theme_setup() {
        load_theme_textdomain( 'tmdb-theme', get_template_directory() . '/languages' );

        add_theme_support( 'automatic-feed-links' );
        add_theme_support( 'title-tag' );
        add_theme_support( 'post-thumbnails' );

        register_nav_menus(
            array(
                'primary' => __( 'Primary Menu', 'tmdb-theme' ),
                'footer'  => __( 'Footer Menu', 'tmdb-theme' ),
            )
        );

        add_theme_support(
            'html5',
            array(
                'search-form',
                'comment-form',
                'comment-list',
                'gallery',
                'caption',
                'style',
                'script',
            )
        );

        add_theme_support(
            'custom-logo',
            array(
                'height'      => 80,
                'width'       => 80,
                'flex-height' => true,
                'flex-width'  => true,
            )
        );
    }
}
add_action( 'after_setup_theme', 'tmdb_theme_setup' );

/**
 * Enqueue scripts and styles.
 */
function tmdb_theme_enqueue_assets() {
    wp_enqueue_style(
        'tmdb-theme-bootstrap',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
        array(),
        '5.3.3'
    );

    wp_enqueue_style(
        'tmdb-theme-style',
        get_stylesheet_uri(),
        array( 'tmdb-theme-bootstrap' ),
        wp_get_theme()->get( 'Version' )
    );

    wp_enqueue_script(
        'tmdb-theme-bootstrap-bundle',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
        array(),
        '5.3.3',
        true
    );
}
add_action( 'wp_enqueue_scripts', 'tmdb_theme_enqueue_assets' );

/**
 * Register widget areas.
 */
function tmdb_theme_widgets_init() {
    register_sidebar(
        array(
            'name'          => __( 'Sidebar', 'tmdb-theme' ),
            'id'            => 'sidebar-1',
            'description'   => __( 'Add widgets here to appear in your sidebar.', 'tmdb-theme' ),
            'before_widget' => '<section id="%1$s" class="widget %2$s mb-4">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title h5 mb-3">',
            'after_title'   => '</h2>',
        )
    );
}
add_action( 'widgets_init', 'tmdb_theme_widgets_init' );

/**
 * Add Bootstrap classes to menu items and links.
 *
 * @param string[] $classes Existing CSS classes for the menu item.
 * @param WP_Post  $item    The current menu item object.
 * @param stdClass $args    An object of wp_nav_menu() arguments.
 *
 * @return string[]
 */
if ( ! function_exists( 'tmdb_theme_nav_menu_item_class' ) ) {
    function tmdb_theme_nav_menu_item_class( $classes, $item, $args ) {
        if ( ! isset( $args->theme_location ) ) {
            return $classes;
        }

        if ( 'primary' === $args->theme_location ) {
            $classes[] = 'nav-item';
        }

        if ( 'footer' === $args->theme_location ) {
            $classes[] = 'nav-item';
        }

        return $classes;
    }
}
add_filter( 'nav_menu_css_class', 'tmdb_theme_nav_menu_item_class', 10, 3 );

/**
 * Add Bootstrap class to nav menu links.
 *
 * @param array   $atts Link attributes.
 * @param WP_Post $item Menu item.
 * @param stdClass $args Arguments.
 *
 * @return array
 */
if ( ! function_exists( 'tmdb_theme_nav_menu_link_class' ) ) {
    function tmdb_theme_nav_menu_link_class( $atts, $item, $args ) {
        if ( ! isset( $args->theme_location ) ) {
            return $atts;
        }

        if ( 'primary' === $args->theme_location ) {
            $atts['class'] = isset( $atts['class'] ) ? $atts['class'] . ' nav-link' : 'nav-link';
        }

        if ( 'footer' === $args->theme_location ) {
            $atts['class'] = isset( $atts['class'] ) ? $atts['class'] . ' nav-link px-2 text-muted' : 'nav-link px-2 text-muted';
        }

        return $atts;
    }
}
add_filter( 'nav_menu_link_attributes', 'tmdb_theme_nav_menu_link_class', 10, 3 );

/**
 * Outputs Bootstrap-friendly pagination markup.
 */
if ( ! function_exists( 'tmdb_theme_pagination' ) ) {
    function tmdb_theme_pagination(): void {
        $links = paginate_links(
            array(
                'type'      => 'array',
                'mid_size'  => 2,
                'prev_text' => __( '&laquo; Previous', 'tmdb-theme' ),
                'next_text' => __( 'Next &raquo;', 'tmdb-theme' ),
            )
        );

        if ( empty( $links ) ) {
            return;
        }

        echo '<nav class="tmdb-pagination" aria-label="' . esc_attr__( 'Pagination', 'tmdb-theme' ) . '"><ul class="pagination justify-content-center gap-2">';

        foreach ( $links as $link ) {
            $item_class = 'page-item';

            if ( false !== strpos( $link, 'current' ) ) {
                $item_class .= ' active';
            }

            if ( false !== strpos( $link, 'dots' ) ) {
                $item_class .= ' disabled';
            }

            $link = str_replace( 'page-numbers', 'page-link', $link );
            $link = str_replace( 'page-link current', 'page-link active', $link );
            $link = str_replace( 'page-link dots', 'page-link disabled', $link );

            echo '<li class="' . esc_attr( trim( $item_class ) ) . '">' . wp_kses_post( $link ) . '</li>';
        }

        echo '</ul></nav>';
    }
}
