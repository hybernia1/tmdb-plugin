<?php
/**
 * Registers custom post types used by the TMDB plugin.
 *
 * @package TMDBPlugin\PostTypes
 */

namespace TMDB\Plugin\Post_Types;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles registration for plugin post types.
 */
class TMDB_Post_Types {
    /**
     * Registers the plugin post types with WordPress.
     */
    public static function register(): void {
        $post_types = [
            'movie'   => self::get_movie_args(),
            'series'  => self::get_series_args(),
            'season'  => self::get_season_args(),
            'episode' => self::get_episode_args(),
            'tag'     => self::get_tag_args(),
        ];

        foreach ( $post_types as $post_type => $args ) {
            register_post_type( $post_type, $args );
        }
    }

    /**
     * Returns post type arguments common to most content types.
     */
    private static function get_default_args( string $singular, string $plural, array $overrides = [] ): array {
        $labels = [
            'name'               => $plural,
            'singular_name'      => $singular,
            'menu_name'          => $plural,
            'name_admin_bar'     => $singular,
            'add_new'            => __( 'Add New', 'tmdb-plugin' ),
            'add_new_item'       => sprintf( __( 'Add New %s', 'tmdb-plugin' ), $singular ),
            'new_item'           => sprintf( __( 'New %s', 'tmdb-plugin' ), $singular ),
            'edit_item'          => sprintf( __( 'Edit %s', 'tmdb-plugin' ), $singular ),
            'view_item'          => sprintf( __( 'View %s', 'tmdb-plugin' ), $singular ),
            'all_items'          => sprintf( __( 'All %s', 'tmdb-plugin' ), $plural ),
            'search_items'       => sprintf( __( 'Search %s', 'tmdb-plugin' ), $plural ),
            'parent_item_colon'  => sprintf( __( 'Parent %s:', 'tmdb-plugin' ), $singular ),
            'not_found'          => sprintf( __( 'No %s found.', 'tmdb-plugin' ), strtolower( $plural ) ),
            'not_found_in_trash' => sprintf( __( 'No %s found in Trash.', 'tmdb-plugin' ), strtolower( $plural ) ),
        ];

        $defaults = [
            'labels'             => $labels,
            'public'             => true,
            'show_in_rest'       => true,
            'has_archive'        => true,
            'menu_position'      => 20,
            'supports'           => [ 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ],
            'rewrite'            => [ 'slug' => sanitize_title( $plural ) ],
        ];

        return array_merge( $defaults, $overrides );
    }

    private static function get_movie_args(): array {
        return self::get_default_args( __( 'Movie', 'tmdb-plugin' ), __( 'Movies', 'tmdb-plugin' ), [
            'menu_icon' => 'dashicons-format-video',
        ] );
    }

    private static function get_series_args(): array {
        return self::get_default_args( __( 'Series', 'tmdb-plugin' ), __( 'Series', 'tmdb-plugin' ), [
            'menu_icon' => 'dashicons-video-alt3',
        ] );
    }

    private static function get_season_args(): array {
        return self::get_default_args( __( 'Season', 'tmdb-plugin' ), __( 'Seasons', 'tmdb-plugin' ), [
            'menu_icon' => 'dashicons-networking',
        ] );
    }

    private static function get_episode_args(): array {
        return self::get_default_args( __( 'Episode', 'tmdb-plugin' ), __( 'Episodes', 'tmdb-plugin' ), [
            'menu_icon' => 'dashicons-admin-media',
        ] );
    }

    private static function get_tag_args(): array {
        return self::get_default_args( __( 'Tag', 'tmdb-plugin' ), __( 'Tags', 'tmdb-plugin' ), [
            'menu_icon' => 'dashicons-tag',
        ] );
    }
}
