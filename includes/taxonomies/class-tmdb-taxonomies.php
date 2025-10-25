<?php
/**
 * Registers shared taxonomies for TMDB content.
 *
 * @package TMDBPlugin\Taxonomies
 */

namespace TMDB\Plugin\Taxonomies;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles taxonomy registration for TMDB content types.
 */
class TMDB_Taxonomies {
    public const ACTOR    = 'tmdb_actor';
    public const DIRECTOR = 'tmdb_director';
    public const GENRE    = 'tmdb_genre';

    /**
     * Registers plugin taxonomies.
     */
    public static function register(): void {
        register_taxonomy( self::ACTOR, [ 'movie', 'series' ], self::get_actor_args() );
        register_taxonomy( self::DIRECTOR, [ 'movie', 'series' ], self::get_director_args() );
        register_taxonomy( self::GENRE, [ 'movie', 'series' ], self::get_genre_args() );
    }

    /**
     * Returns default taxonomy args merged with overrides.
     *
     * @param string               $singular  Singular label.
     * @param string               $plural    Plural label.
     * @param array<string, mixed> $overrides Overrides for the default arguments.
     */
    private static function get_default_args( string $singular, string $plural, array $overrides = [] ): array {
        $labels = [
            'name'                       => $plural,
            'singular_name'              => $singular,
            'search_items'               => sprintf( __( 'Search %s', 'tmdb-plugin' ), $plural ),
            'popular_items'              => sprintf( __( 'Popular %s', 'tmdb-plugin' ), $plural ),
            'all_items'                  => sprintf( __( 'All %s', 'tmdb-plugin' ), $plural ),
            'edit_item'                  => sprintf( __( 'Edit %s', 'tmdb-plugin' ), $singular ),
            'view_item'                  => sprintf( __( 'View %s', 'tmdb-plugin' ), $singular ),
            'update_item'                => sprintf( __( 'Update %s', 'tmdb-plugin' ), $singular ),
            'add_new_item'               => sprintf( __( 'Add New %s', 'tmdb-plugin' ), $singular ),
            'new_item_name'              => sprintf( __( 'New %s Name', 'tmdb-plugin' ), $singular ),
            'separate_items_with_commas' => sprintf( __( 'Separate %s with commas', 'tmdb-plugin' ), $plural ),
            'add_or_remove_items'        => sprintf( __( 'Add or remove %s', 'tmdb-plugin' ), $plural ),
            'choose_from_most_used'      => sprintf( __( 'Choose from the most used %s', 'tmdb-plugin' ), $plural ),
            'menu_name'                  => $plural,
        ];

        $defaults = [
            'labels'            => $labels,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
        ];

        return array_merge( $defaults, $overrides );
    }

    private static function get_actor_args(): array {
        return self::get_default_args(
            __( 'Actor', 'tmdb-plugin' ),
            __( 'Actors', 'tmdb-plugin' ),
            [
                'hierarchical' => false,
                'rewrite'      => [ 'slug' => 'actors' ],
            ]
        );
    }

    private static function get_director_args(): array {
        return self::get_default_args(
            __( 'Director', 'tmdb-plugin' ),
            __( 'Directors', 'tmdb-plugin' ),
            [
                'hierarchical' => false,
                'rewrite'      => [ 'slug' => 'directors' ],
            ]
        );
    }

    private static function get_genre_args(): array {
        return self::get_default_args(
            __( 'Genre', 'tmdb-plugin' ),
            __( 'Genres', 'tmdb-plugin' ),
            [
                'hierarchical' => true,
                'rewrite'      => [ 'slug' => 'genres' ],
            ]
        );
    }
}
