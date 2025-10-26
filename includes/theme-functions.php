<?php
/**
 * Helper functions used by the bundled TMDB theme templates.
 *
 * @package TMDBPlugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'tmdb_theme_pagination' ) ) {
    /**
     * Outputs Bootstrap-friendly pagination markup.
     */
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

if ( ! function_exists( 'tmdb_theme_nav_menu_item_class' ) ) {
    /**
     * Adds Bootstrap classes to menu items.
     *
     * @param string[] $classes Existing CSS classes for the menu item.
     * @param WP_Post  $item    The current menu item object.
     * @param stdClass $args    An object of wp_nav_menu() arguments.
     *
     * @return string[]
     */
    function tmdb_theme_nav_menu_item_class( array $classes, $item, $args ): array { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.
        if ( ! isset( $args->theme_location ) ) {
            return $classes;
        }

        if ( 'primary' === $args->theme_location || 'footer' === $args->theme_location ) {
            $classes[] = 'nav-item';
        }

        return $classes;
    }
}

if ( ! function_exists( 'tmdb_theme_nav_menu_link_class' ) ) {
    /**
     * Adds Bootstrap classes to menu links.
     *
     * @param array   $atts Link attributes.
     * @param WP_Post $item Menu item.
     * @param stdClass $args Arguments.
     *
     * @return array
     */
    function tmdb_theme_nav_menu_link_class( array $atts, $item, $args ): array { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.
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
