<?php
/**
 * TMDB universal API admin page.
 *
 * @package TMDBPlugin\Admin
 */

namespace TMDB\Plugin\Admin;

use TMDB\Plugin\Taxonomies\TMDB_Taxonomies;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Provides an admin interface for searching and importing TMDB movies.
 */
class TMDB_Admin_Page_Search {
    private const MENU_SLUG               = 'tmdb-plugin-search';
    private const POSTER_BASE_URL         = 'https://image.tmdb.org/t/p/';
    private const ORIGINAL_IMAGE_BASE_URL = 'https://image.tmdb.org/t/p/original';
    private const TMDB_UPLOAD_SUBDIR      = 'tmdb';
    private const TMDB_MEDIA_CATEGORY     = 'movies';
    private const TMDB_ACTOR_MEDIA_CATEGORY = 'actors';
    private const TMDB_DIRECTOR_MEDIA_CATEGORY = 'directors';
    private const REQUIRED_TRANSLATION_FIELDS = [ 'title', 'overview' ];
    private const FALLBACK_STRING_FIELDS      = [ 'title', 'overview', 'tagline' ];

    /**
     * Tracks the TMDB identifier used while adjusting the upload directory.
     */
    private static int $current_tmdb_media_id = 0;

    /**
     * Registers the search submenu page.
     */
    public static function register(): void {
        add_submenu_page(
            TMDB_PLUGIN_SLUG,
            __( 'TMDB Movie Search', 'tmdb-plugin' ),
            __( 'Movie Search', 'tmdb-plugin' ),
            'manage_options',
            self::MENU_SLUG,
            [ self::class, 'render' ]
        );
    }

    /**
     * Renders the admin page markup.
     */
    public static function render(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $api_key           = sanitize_text_field( (string) get_option( 'tmdb_plugin_api_key', '' ) );
        $has_api_key       = '' !== $api_key;
        $language          = sanitize_text_field( (string) get_option( 'tmdb_plugin_language', 'en-US' ) );
        $fallback_language = sanitize_text_field( (string) get_option( 'tmdb_plugin_fallback_language', 'en-US' ) );
        $initial_query     = self::get_initial_query();
        $current_page      = self::get_requested_page();
        $action_url        = self::get_page_url();

        $notices = [];

        $import_notice = self::maybe_handle_import_request( $api_key, $language, $fallback_language );

        if ( ! empty( $import_notice ) ) {
            $notices[] = $import_notice;
        }

        $search_error  = '';
        $results       = [];
        $total_pages   = 0;
        $used_fallback = false;

        if ( '' !== $initial_query && $has_api_key ) {
            $search_response = self::search_movies( $initial_query, $current_page, $language, $fallback_language, $api_key );

            if ( $search_response['success'] ) {
                $results       = $search_response['results'];
                $total_pages   = (int) $search_response['total_pages'];
                $current_page  = (int) $search_response['page'];
                $used_fallback = (bool) $search_response['used_fallback'];
            } else {
                $search_error = $search_response['message'] ?? __( 'An unexpected error occurred. Please try again.', 'tmdb-plugin' );
            }
        } elseif ( '' !== $initial_query && ! $has_api_key ) {
            $search_error = __( 'TMDB API key is missing. Update the configuration settings first.', 'tmdb-plugin' );
        }

        ?>
        <div class="wrap tmdb-plugin tmdb-plugin__search">
            <h1><?php esc_html_e( 'TMDB Movie Search', 'tmdb-plugin' ); ?></h1>
            <p class="description">
                <?php esc_html_e( 'Search TMDB for movies and import them directly into your WordPress site.', 'tmdb-plugin' ); ?>
            </p>

            <?php if ( ! $has_api_key ) : ?>
                <div class="notice notice-warning">
                    <p><?php esc_html_e( 'Add a valid TMDB API key on the configuration page before performing a search.', 'tmdb-plugin' ); ?></p>
                </div>
            <?php endif; ?>

            <?php foreach ( $notices as $notice ) : ?>
                <?php
                $type         = isset( $notice['type'] ) && 'success' === $notice['type'] ? 'success' : 'error';
                $notice_class = 'notice notice-' . $type;
                ?>
                <div class="<?php echo esc_attr( $notice_class ); ?>">
                    <p>
                        <?php echo esc_html( $notice['message'] ); ?>
                        <?php if ( isset( $notice['post_id'] ) ) :
                            $edit_link = get_edit_post_link( (int) $notice['post_id'] );
                            if ( $edit_link ) :
                                ?>
                                <a class="tmdb-plugin-search__edit-link" href="<?php echo esc_url( $edit_link ); ?>">
                                    <?php esc_html_e( 'Edit movie', 'tmdb-plugin' ); ?>
                                </a>
                                <?php
                            endif;
                        endif;
                        ?>
                    </p>
                </div>
            <?php endforeach; ?>

            <?php if ( '' !== $search_error ) : ?>
                <div class="notice notice-error">
                    <p><?php echo esc_html( $search_error ); ?></p>
                </div>
            <?php endif; ?>

            <?php if ( $used_fallback ) : ?>
                <div class="notice notice-info">
                    <p><?php esc_html_e( 'Results shown using the fallback language.', 'tmdb-plugin' ); ?></p>
                </div>
            <?php endif; ?>

            <form id="tmdb-plugin-search-form" class="tmdb-plugin-search__form" action="<?php echo esc_url( $action_url ); ?>" method="get" novalidate>
                <input type="hidden" name="page" value="<?php echo esc_attr( self::MENU_SLUG ); ?>" />
                <label class="screen-reader-text" for="tmdb-plugin-search-query"><?php esc_html_e( 'Movie title', 'tmdb-plugin' ); ?></label>
                <input
                    type="text"
                    id="tmdb-plugin-search-query"
                    class="regular-text"
                    name="query"
                    placeholder="<?php echo esc_attr__( 'Search for a movie…', 'tmdb-plugin' ); ?>"
                    value="<?php echo esc_attr( $initial_query ); ?>"
                    autocomplete="off"
                />
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Search', 'tmdb-plugin' ); ?></button>
            </form>

            <?php if ( '' === $search_error && '' !== $initial_query && empty( $results ) && $has_api_key ) : ?>
                <p class="tmdb-plugin-search__no-results"><?php esc_html_e( 'No movies matched your search.', 'tmdb-plugin' ); ?></p>
            <?php endif; ?>

            <?php if ( ! empty( $results ) ) : ?>
                <ul class="tmdb-plugin-search__results">
                    <?php foreach ( $results as $result ) : ?>
                        <li class="tmdb-plugin-search__result">
                            <?php if ( '' !== $result['poster_path'] ) : ?>
                                <figure class="tmdb-plugin-search__poster">
                                    <img
                                        src="<?php echo esc_url( self::build_poster_url( $result['poster_path'] ) ); ?>"
                                        alt="<?php echo esc_attr( sprintf( __( 'Poster for %s', 'tmdb-plugin' ), $result['title'] ? $result['title'] : $result['original_title'] ) ); ?>"
                                        loading="lazy"
                                    />
                                </figure>
                            <?php endif; ?>

                            <div class="tmdb-plugin-search__body">
                                <h2 class="tmdb-plugin-search__title">
                                    <?php echo esc_html( $result['title'] ? $result['title'] : $result['original_title'] ); ?>
                                </h2>

                                <?php if ( $result['original_title'] && $result['original_title'] !== $result['title'] ) : ?>
                                    <p class="tmdb-plugin-search__original-title">
                                        <?php
                                        printf(
                                            esc_html__( 'Original title: %s', 'tmdb-plugin' ),
                                            esc_html( $result['original_title'] )
                                        );
                                        ?>
                                    </p>
                                <?php endif; ?>

                                <ul class="tmdb-plugin-search__meta">
                                    <?php if ( $result['release_date'] ) : ?>
                                        <li>
                                            <?php
                                            printf(
                                                esc_html__( 'Release date: %s', 'tmdb-plugin' ),
                                                esc_html( $result['release_date'] )
                                            );
                                            ?>
                                        </li>
                                    <?php endif; ?>

                                    <?php if ( $result['vote_average'] > 0 ) : ?>
                                        <li>
                                            <?php
                                            printf(
                                                esc_html__( 'Rating: %1$s (%2$s votes)', 'tmdb-plugin' ),
                                                esc_html( number_format_i18n( $result['vote_average'], 1 ) ),
                                                esc_html( number_format_i18n( $result['vote_count'] ) )
                                            );
                                            ?>
                                        </li>
                                    <?php endif; ?>

                                    <?php if ( $result['language'] ) : ?>
                                        <li>
                                            <?php
                                            printf(
                                                esc_html__( 'Language: %s', 'tmdb-plugin' ),
                                                esc_html( strtoupper( $result['language'] ) )
                                            );
                                            ?>
                                        </li>
                                    <?php endif; ?>
                                </ul>

                                <?php if ( $result['overview'] ) : ?>
                                    <p class="tmdb-plugin-search__overview"><?php echo esc_html( $result['overview'] ); ?></p>
                                <?php endif; ?>

                                <div class="tmdb-plugin-search__actions">
                                    <form method="post" class="tmdb-plugin-search__import-form" action="<?php echo esc_url( self::get_page_url( [
                                        'query' => $initial_query,
                                        'paged' => $current_page > 1 ? $current_page : false,
                                    ] ) ); ?>">
                                        <?php wp_nonce_field( 'tmdb_plugin_import_movie', 'tmdb_plugin_import_nonce' ); ?>
                                        <input type="hidden" name="tmdb_plugin_import_movie" value="1" />
                                        <input type="hidden" name="page" value="<?php echo esc_attr( self::MENU_SLUG ); ?>" />
                                        <input type="hidden" name="movie_id" value="<?php echo esc_attr( $result['id'] ); ?>" />
                                        <input type="hidden" name="query" value="<?php echo esc_attr( $initial_query ); ?>" />
                                        <input type="hidden" name="paged" value="<?php echo esc_attr( $current_page ); ?>" />
                                        <?php $button_label = $result['existing_post_id'] ? __( 'Update movie', 'tmdb-plugin' ) : __( 'Import movie', 'tmdb-plugin' ); ?>
                                        <button type="submit" class="button button-secondary">
                                            <?php echo esc_html( $button_label ); ?>
                                        </button>
                                    </form>
                                    <?php if ( $result['existing_post_id'] ) :
                                        $edit_link = get_edit_post_link( (int) $result['existing_post_id'] );
                                        if ( $edit_link ) :
                                            ?>
                                            <a class="button-link tmdb-plugin-search__edit-link" href="<?php echo esc_url( $edit_link ); ?>">
                                                <?php esc_html_e( 'Edit existing movie', 'tmdb-plugin' ); ?>
                                            </a>
                                            <?php
                                        endif;
                                    endif;
                                    ?>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if ( $total_pages > 1 ) : ?>
                    <nav class="tmdb-plugin-search__pagination" aria-label="<?php echo esc_attr__( 'Movie results pagination', 'tmdb-plugin' ); ?>">
                        <ul class="tmdb-plugin-search__pagination-list">
                            <li class="tmdb-plugin-search__page-info">
                                <?php
                                printf(
                                    esc_html__( 'Page %1$s of %2$s', 'tmdb-plugin' ),
                                    esc_html( number_format_i18n( $current_page ) ),
                                    esc_html( number_format_i18n( $total_pages ) )
                                );
                                ?>
                            </li>

                            <?php if ( $current_page > 1 ) : ?>
                                <li>
                                    <a class="button tmdb-plugin-search__page-btn" href="<?php echo esc_url( self::build_pagination_url( $initial_query, $current_page - 1 ) ); ?>">
                                        <?php esc_html_e( 'Previous', 'tmdb-plugin' ); ?>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php if ( $current_page < $total_pages ) : ?>
                                <li>
                                    <a class="button tmdb-plugin-search__page-btn" href="<?php echo esc_url( self::build_pagination_url( $initial_query, $current_page + 1 ) ); ?>">
                                        <?php esc_html_e( 'Next', 'tmdb-plugin' ); ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Handles import requests triggered from the search page.
     *
     * @param string $api_key           TMDB API key.
     * @param string $language          Preferred language.
     * @param string $fallback_language Fallback language.
     *
     * @return array<string, mixed>
     */
    private static function maybe_handle_import_request( string $api_key, string $language, string $fallback_language ): array {
        if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
            return [];
        }

        if ( empty( $_POST['tmdb_plugin_import_movie'] ) ) {
            return [];
        }

        check_admin_referer( 'tmdb_plugin_import_movie', 'tmdb_plugin_import_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            return [
                'type'    => 'error',
                'message' => __( 'You do not have permission to perform this request.', 'tmdb-plugin' ),
            ];
        }

        if ( '' === $api_key ) {
            return [
                'type'    => 'error',
                'message' => __( 'TMDB API key is missing. Update the configuration settings first.', 'tmdb-plugin' ),
            ];
        }

        $movie_id = isset( $_POST['movie_id'] ) ? absint( wp_unslash( $_POST['movie_id'] ) ) : 0;

        if ( $movie_id <= 0 ) {
            return [
                'type'    => 'error',
                'message' => __( 'Invalid movie identifier.', 'tmdb-plugin' ),
            ];
        }

        $movie_response = self::fetch_movie_details( $movie_id, $language, $api_key, $fallback_language );

        if ( ! $movie_response['success'] && $fallback_language !== $language ) {
            $movie_response = self::fetch_movie_details( $movie_id, $fallback_language, $api_key, $language );
        }

        if ( ! $movie_response['success'] ) {
            return [
                'type'    => 'error',
                'message' => $movie_response['message'] ?? __( 'Unable to import the selected movie.', 'tmdb-plugin' ),
            ];
        }

        $import = self::import_movie(
            $movie_response['movie'],
            $movie_response['language'],
            $movie_response['fallback_movie'] ?? null
        );

        if ( is_wp_error( $import ) ) {
            return [
                'type'    => 'error',
                'message' => $import->get_error_message(),
            ];
        }

        return [
            'type'    => 'success',
            'message' => __( 'Movie imported successfully.', 'tmdb-plugin' ),
            'post_id' => $import['post_id'],
        ];
    }

    /**
     * Performs a TMDB search request and handles fallback logic.
     *
     * @param string $query             Search query.
     * @param int    $page              Requested page number.
     * @param string $language          Preferred language.
     * @param string $fallback_language Fallback language.
     * @param string $api_key           TMDB API key.
     *
     * @return array<string, mixed>
     */
    private static function search_movies( string $query, int $page, string $language, string $fallback_language, string $api_key ): array {
        $primary = self::perform_movie_search( $query, $page, $language, $api_key );

        if ( ! $primary['success'] ) {
            return $primary;
        }

        $results       = $primary['results'];
        $total_pages   = $primary['total_pages'];
        $used_fallback = false;
        $page_used     = $primary['page'];

        if ( empty( $results ) && $fallback_language !== $language ) {
            $fallback = self::perform_movie_search( $query, 1, $fallback_language, $api_key );

            if ( ! $fallback['success'] ) {
                return $fallback;
            }

            if ( ! empty( $fallback['results'] ) ) {
                $results       = $fallback['results'];
                $total_pages   = $fallback['total_pages'];
                $used_fallback = true;
                $page_used     = $fallback['page'];
            }
        }

        return [
            'success'      => true,
            'results'      => $results,
            'total_pages'  => $total_pages,
            'page'         => $page_used,
            'used_fallback'=> $used_fallback,
        ];
    }

    /**
     * Builds a pagination URL keeping the current query intact.
     *
     * @param string $query Search query.
     * @param int    $page  Target page.
     */
    private static function build_pagination_url( string $query, int $page ): string {
        $args = [
            'query' => $query,
            'paged' => $page > 1 ? $page : false,
        ];

        return self::get_page_url( $args );
    }

    /**
     * Retrieves the base URL for the search page with optional arguments.
     *
     * @param array<string, mixed> $args Optional query arguments.
     */
    private static function get_page_url( array $args = [] ): string {
        $base_url = menu_page_url( self::MENU_SLUG, false );

        if ( ! $base_url ) {
            $base_url = add_query_arg( 'page', self::MENU_SLUG, admin_url( 'admin.php' ) );
        }

        if ( empty( $args ) ) {
            return $base_url;
        }

        $args['page'] = self::MENU_SLUG;

        return add_query_arg( $args, $base_url );
    }

    /**
     * Retrieves the requested results page number.
     */
    private static function get_requested_page(): int {
        if ( isset( $_REQUEST['paged'] ) ) {
            return max( 1, (int) sanitize_text_field( wp_unslash( $_REQUEST['paged'] ) ) );
        }

        return 1;
    }

    /**
     * Performs a TMDB movie search request.
     *
     * @param string $query    Search query.
     * @param int    $page     Requested page.
     * @param string $language Requested language.
     * @param string $api_key  TMDB API key.
     *
     * @return array<string, mixed>
     */
    private static function perform_movie_search( string $query, int $page, string $language, string $api_key ): array {
        $response = self::request_tmdb(
            'https://api.themoviedb.org/3/search/movie',
            [
                'api_key'       => $api_key,
                'language'      => $language,
                'query'         => $query,
                'page'          => $page,
                'include_adult' => 'false',
            ]
        );

        if ( ! $response['success'] ) {
            return $response;
        }

        $data        = $response['data'];
        $raw_results = isset( $data['results'] ) && is_array( $data['results'] ) ? $data['results'] : [];
        $formatted   = self::format_search_results( $raw_results );

        return [
            'success'     => true,
            'results'     => $formatted,
            'total_pages' => isset( $data['total_pages'] ) ? (int) $data['total_pages'] : 1,
            'page'        => isset( $data['page'] ) ? (int) $data['page'] : $page,
        ];
    }

    /**
     * Formats raw TMDB search results and sorts them by vote count.
     *
     * @param array<int, array<string, mixed>> $results Raw TMDB search results.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function format_search_results( array $results ): array {
        $formatted = [];

        foreach ( $results as $result ) {
            if ( ! is_array( $result ) || empty( $result['id'] ) ) {
                continue;
            }

            $vote_count = isset( $result['vote_count'] ) ? (int) $result['vote_count'] : 0;
            $movie_id   = (int) $result['id'];

            $formatted[] = [
                'id'             => $movie_id,
                'title'          => isset( $result['title'] ) ? sanitize_text_field( $result['title'] ) : '',
                'original_title' => isset( $result['original_title'] ) ? sanitize_text_field( $result['original_title'] ) : '',
                'overview'       => isset( $result['overview'] ) ? wp_trim_words( wp_strip_all_tags( $result['overview'] ), 40 ) : '',
                'release_date'   => isset( $result['release_date'] ) ? sanitize_text_field( $result['release_date'] ) : '',
                'vote_average'   => isset( $result['vote_average'] ) ? (float) $result['vote_average'] : 0,
                'vote_count'     => $vote_count,
                'poster_path'    => isset( $result['poster_path'] ) ? sanitize_text_field( ltrim( (string) $result['poster_path'], '/' ) ) : '',
                'language'       => isset( $result['original_language'] ) ? sanitize_text_field( $result['original_language'] ) : '',
                'existing_post_id'=> self::get_existing_movie_post_id( $movie_id ),
            ];
        }

        usort(
            $formatted,
            static function ( array $a, array $b ): int {
                return $b['vote_count'] <=> $a['vote_count'];
            }
        );

        return $formatted;
    }

    /**
     * Fetches detailed information about a movie from TMDB.
     *
     * @param int         $movie_id         Movie identifier.
     * @param string      $language         Requested language.
     * @param string      $api_key          TMDB API key.
     * @param string|null $fallback_language Optional fallback language to include in related payloads.
     *
     * @return array<string, mixed>
     */
    private static function fetch_movie_details( int $movie_id, string $language, string $api_key, ?string $fallback_language = null ): array {
        $response = self::request_tmdb(
            sprintf( 'https://api.themoviedb.org/3/movie/%d', $movie_id ),
            [
                'api_key'            => $api_key,
                'language'           => $language,
                'append_to_response' => 'credits,videos,keywords,websites,external_ids,images,alternative_titles',
                'include_image_language' => self::build_language_list( $language, $fallback_language, true ),
                'include_video_language' => self::build_language_list( $language, $fallback_language, true ),
            ]
        );

        if ( ! $response['success'] ) {
            return $response;
        }

        $movie          = $response['data'];
        $fallback_movie = null;

        if ( null !== $fallback_language && $fallback_language !== $language ) {
            $fallback_response = self::request_tmdb(
                sprintf( 'https://api.themoviedb.org/3/movie/%d', $movie_id ),
                [
                    'api_key'            => $api_key,
                    'language'           => $fallback_language,
                    'append_to_response' => 'credits,videos,keywords,websites,external_ids,images,alternative_titles',
                    'include_image_language' => self::build_language_list( $fallback_language, $language, true ),
                    'include_video_language' => self::build_language_list( $fallback_language, $language, true ),
                ]
            );

            if ( $fallback_response['success'] ) {
                $fallback_movie = $fallback_response['data'];

                if ( self::movie_requires_fallback_enrichment( $movie ) ) {
                    $movie = self::merge_movie_with_fallback( $movie, $fallback_movie );
                }
            }
        }

        return [
            'success'        => true,
            'movie'          => $movie,
            'language'       => $language,
            'fallback_movie' => $fallback_movie,
        ];
    }

    /**
     * Imports a movie and its related entities into WordPress.
     *
     * @param array<string, mixed>      $movie_data     Movie payload retrieved from TMDB.
     * @param string                     $language       Language used for the payload.
     * @param array<string, mixed>|null $fallback_movie Optional fallback payload containing alternate language data.
     *
     * @return array<string, int>|\WP_Error
     */
    private static function import_movie( array $movie_data, string $language, ?array $fallback_movie = null ) {
        $movie_id = isset( $movie_data['id'] ) ? (int) $movie_data['id'] : 0;

        if ( $movie_id <= 0 ) {
            return new \WP_Error( 'tmdb_import_invalid_movie', __( 'TMDB movie data is missing an identifier.', 'tmdb-plugin' ) );
        }

        $title          = isset( $movie_data['title'] ) ? sanitize_text_field( $movie_data['title'] ) : '';
        $original_title = isset( $movie_data['original_title'] ) ? sanitize_text_field( $movie_data['original_title'] ) : '';
        $content        = isset( $movie_data['overview'] ) ? wp_kses_post( $movie_data['overview'] ) : '';

        $fallback_title = '';

        if ( null !== $fallback_movie ) {
            $fallback_title = isset( $fallback_movie['title'] ) ? sanitize_text_field( $fallback_movie['title'] ) : '';

            if ( '' === $fallback_title && isset( $fallback_movie['original_title'] ) ) {
                $fallback_title = sanitize_text_field( $fallback_movie['original_title'] );
            }
        }

        $slug = self::generate_movie_slug( $movie_id, $title, '' !== $original_title ? $original_title : $fallback_title );

        $existing = get_posts(
            [
                'post_type'      => 'movie',
                'posts_per_page' => 1,
                'post_status'    => [ 'publish', 'draft', 'pending', 'future', 'private' ],
                'meta_query'     => [
                    [
                        'key'   => 'TMDB_id',
                        'value' => $movie_id,
                    ],
                ],
                'fields'         => 'ids',
            ]
        );

        $post_data = [
            'post_title'   => wp_slash( $title ),
            'post_content' => wp_slash( $content ),
            'post_type'    => 'movie',
            'post_status'  => 'publish',
        ];

        if ( '' !== $slug ) {
            $post_data['post_name'] = wp_slash( $slug );
        }

        if ( ! empty( $existing ) ) {
            $post_data['ID'] = (int) $existing[0];
            $post_id         = wp_update_post( $post_data, true );
        } else {
            $post_id = wp_insert_post( $post_data, true );
        }

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        $post_id = (int) $post_id;

        update_post_meta( $post_id, 'TMDB_id', $movie_id );
        update_post_meta( $post_id, 'TMDB_language', $language );
        update_post_meta( $post_id, 'TMDB_original_title', $original_title );
        update_post_meta( $post_id, 'TMDB_tagline', isset( $movie_data['tagline'] ) ? sanitize_text_field( $movie_data['tagline'] ) : '' );
        update_post_meta( $post_id, 'TMDB_release_date', isset( $movie_data['release_date'] ) ? sanitize_text_field( $movie_data['release_date'] ) : '' );

        $resolved_runtime = self::resolve_movie_runtime( $movie_data, $fallback_movie );
        update_post_meta( $post_id, 'TMDB_runtime', $resolved_runtime );
        update_post_meta( $post_id, 'TMDB_vote_average', isset( $movie_data['vote_average'] ) ? (float) $movie_data['vote_average'] : 0 );
        update_post_meta( $post_id, 'TMDB_vote_count', isset( $movie_data['vote_count'] ) ? (int) $movie_data['vote_count'] : 0 );
        update_post_meta( $post_id, 'TMDB_homepage', self::resolve_homepage_url( $movie_data, $fallback_movie ) );
        update_post_meta( $post_id, 'TMDB_status', isset( $movie_data['status'] ) ? sanitize_text_field( $movie_data['status'] ) : '' );

        $origin_countries = self::sanitize_origin_countries( $movie_data['origin_country'] ?? [] );

        if ( empty( $origin_countries ) && null !== $fallback_movie ) {
            $origin_countries = self::sanitize_origin_countries( $fallback_movie['origin_country'] ?? [] );
        }

        if ( empty( $origin_countries ) ) {
            delete_post_meta( $post_id, 'TMDB_origin_countries' );
        } else {
            update_post_meta( $post_id, 'TMDB_origin_countries', $origin_countries );
        }

        $spoken_languages = self::sanitize_spoken_languages( $movie_data['spoken_languages'] ?? [] );

        if ( empty( $spoken_languages ) && null !== $fallback_movie ) {
            $spoken_languages = self::sanitize_spoken_languages( $fallback_movie['spoken_languages'] ?? [] );
        }

        if ( empty( $spoken_languages ) ) {
            delete_post_meta( $post_id, 'TMDB_spoken_languages' );
        } else {
            update_post_meta( $post_id, 'TMDB_spoken_languages', $spoken_languages );
        }

        $alternative_titles = self::sanitize_alternative_titles( $movie_data['alternative_titles'] ?? [] );

        if ( empty( $alternative_titles ) && null !== $fallback_movie ) {
            $alternative_titles = self::sanitize_alternative_titles( $fallback_movie['alternative_titles'] ?? [] );
        }

        if ( empty( $alternative_titles ) ) {
            delete_post_meta( $post_id, 'TMDB_alternative_titles' );
        } else {
            update_post_meta( $post_id, 'TMDB_alternative_titles', $alternative_titles );
        }

        $collection_data = self::prepare_collection_data( isset( $movie_data['belongs_to_collection'] ) ? $movie_data['belongs_to_collection'] : null );

        if ( empty( $collection_data ) ) {
            delete_post_meta( $post_id, 'TMDB_collection_id' );
            delete_post_meta( $post_id, 'TMDB_collection' );
        } else {
            update_post_meta( $post_id, 'TMDB_collection_id', $collection_data['id'] );
            update_post_meta( $post_id, 'TMDB_collection', $collection_data );
        }

        $poster_path          = isset( $movie_data['poster_path'] ) ? sanitize_text_field( ltrim( (string) $movie_data['poster_path'], '/' ) ) : '';
        $previous_poster_path = (string) get_post_meta( $post_id, 'TMDB_poster_path', true );

        if ( '' !== $poster_path ) {
            self::set_featured_image( $post_id, $poster_path, $title, $movie_id, $previous_poster_path );
        } else {
            delete_post_meta( $post_id, 'TMDB_poster_size' );
        }

        update_post_meta( $post_id, 'TMDB_poster_path', $poster_path );

        $fallback_cast      = [];
        $fallback_crew      = [];
        $fallback_backdrops = [];

        if ( null !== $fallback_movie && isset( $fallback_movie['credits'] ) && is_array( $fallback_movie['credits'] ) ) {
            $fallback_cast = isset( $fallback_movie['credits']['cast'] ) && is_array( $fallback_movie['credits']['cast'] ) ? $fallback_movie['credits']['cast'] : [];
            $fallback_crew = isset( $fallback_movie['credits']['crew'] ) && is_array( $fallback_movie['credits']['crew'] ) ? $fallback_movie['credits']['crew'] : [];
        }

        if ( null !== $fallback_movie && isset( $fallback_movie['images'] ) && is_array( $fallback_movie['images'] ) ) {
            $fallback_backdrops = isset( $fallback_movie['images']['backdrops'] ) && is_array( $fallback_movie['images']['backdrops'] ) ? $fallback_movie['images']['backdrops'] : [];
        }

        $cast_info   = self::import_cast(
            isset( $movie_data['credits']['cast'] ) && is_array( $movie_data['credits']['cast'] ) ? $movie_data['credits']['cast'] : [],
            (int) $post_id,
            $movie_id,
            $title,
            $fallback_cast
        );
        $crew_info   = self::import_crew(
            isset( $movie_data['credits']['crew'] ) && is_array( $movie_data['credits']['crew'] ) ? $movie_data['credits']['crew'] : [],
            $fallback_crew
        );
        $genre_info  = self::import_genres( isset( $movie_data['genres'] ) && is_array( $movie_data['genres'] ) ? $movie_data['genres'] : [] );
        $keyword_raw = [];

        if ( isset( $movie_data['keywords'] ) && is_array( $movie_data['keywords'] ) ) {
            if ( isset( $movie_data['keywords']['keywords'] ) && is_array( $movie_data['keywords']['keywords'] ) ) {
                $keyword_raw = $movie_data['keywords']['keywords'];
            } elseif ( isset( $movie_data['keywords']['results'] ) && is_array( $movie_data['keywords']['results'] ) ) {
                $keyword_raw = $movie_data['keywords']['results'];
            }
        }

        $keyword_info      = self::import_keywords( $keyword_raw );
        $videos_raw        = isset( $movie_data['videos']['results'] ) && is_array( $movie_data['videos']['results'] ) ? $movie_data['videos']['results'] : [];
        $trailer_info      = self::extract_trailer( $videos_raw );
        $videos_dump_json  = self::serialize_videos_payload( $videos_raw );
        $websites_raw      = self::extract_websites_payload( $movie_data );
        $websites_formatted = self::format_websites( $websites_raw );
        $primary_website    = self::extract_primary_website( $websites_formatted );
        $websites_dump_json  = self::serialize_websites_payload( $websites_raw );
        $external_ids        = self::sanitize_external_ids( isset( $movie_data['external_ids'] ) && is_array( $movie_data['external_ids'] ) ? $movie_data['external_ids'] : [] );
        $primary_backdrops   = isset( $movie_data['images']['backdrops'] ) && is_array( $movie_data['images']['backdrops'] ) ? $movie_data['images']['backdrops'] : [];

        self::import_gallery_images( $post_id, $title, $primary_backdrops, $fallback_backdrops, $movie_id );

        wp_set_object_terms( $post_id, $cast_info['term_ids'], TMDB_Taxonomies::ACTOR, false );
        wp_set_object_terms( $post_id, $crew_info['term_ids'], TMDB_Taxonomies::DIRECTOR, false );
        wp_set_object_terms( $post_id, $genre_info['term_ids'], TMDB_Taxonomies::GENRE, false );
        wp_set_object_terms( $post_id, $keyword_info['term_ids'], TMDB_Taxonomies::KEYWORD, false );

        update_post_meta( $post_id, 'TMDB_actor_ids', $cast_info['term_ids'] );
        update_post_meta( $post_id, 'TMDB_cast', $cast_info['cast'] );
        update_post_meta( $post_id, 'TMDB_director_ids', $crew_info['term_ids'] );
        update_post_meta( $post_id, 'TMDB_directors', $crew_info['directors'] );
        update_post_meta( $post_id, 'TMDB_genre_ids', $genre_info['term_ids'] );
        update_post_meta( $post_id, 'TMDB_genres', $genre_info['genres'] );
        update_post_meta( $post_id, 'TMDB_keyword_ids', $keyword_info['term_ids'] );
        update_post_meta( $post_id, 'TMDB_keywords', $keyword_info['keywords'] );

        if ( empty( $websites_formatted ) ) {
            delete_post_meta( $post_id, 'TMDB_websites' );
        } else {
            update_post_meta( $post_id, 'TMDB_websites', $websites_formatted );
        }

        if ( empty( $primary_website ) ) {
            delete_post_meta( $post_id, 'TMDB_primary_website' );
        } else {
            update_post_meta( $post_id, 'TMDB_primary_website', $primary_website );
        }

        if ( empty( $trailer_info ) ) {
            delete_post_meta( $post_id, 'TMDB_trailer' );
        } else {
            update_post_meta( $post_id, 'TMDB_trailer', $trailer_info );
        }

        if ( '' === $videos_dump_json ) {
            delete_post_meta( $post_id, 'TMDB_videos_payload' );
        } else {
            update_post_meta( $post_id, 'TMDB_videos_payload', $videos_dump_json );
        }

        if ( '' === $websites_dump_json ) {
            delete_post_meta( $post_id, 'TMDB_websites_payload' );
        } else {
            update_post_meta( $post_id, 'TMDB_websites_payload', $websites_dump_json );
        }

        self::sync_external_id_meta( $post_id, $external_ids );

        return [
            'post_id' => $post_id,
        ];
    }

    /**
     * Imports cast members as actor taxonomy terms.
     *
     * @param array<int, array<string, mixed>> $cast          Cast members.
     * @param array<int, array<string, mixed>> $fallback_cast Cast members from the fallback payload.
     *
     * @return array<string, array<int, mixed>>
     */
    private static function import_cast( array $cast, int $movie_post_id, int $movie_tmdb_id, string $movie_title, array $fallback_cast = [] ): array {
        $stored_cast     = [];
        $term_ids        = [];
        $fallback_lookup = self::build_person_lookup( $fallback_cast );

        usort(
            $cast,
            static function ( array $a, array $b ): int {
                return ( $a['order'] ?? 0 ) <=> ( $b['order'] ?? 0 );
            }
        );

        foreach ( $cast as $member ) {
            if ( ! is_array( $member ) ) {
                continue;
            }

            $actor_id = isset( $member['id'] ) ? (int) $member['id'] : 0;

            $names          = self::resolve_person_names( $member, $fallback_lookup[ $actor_id ] ?? null );
            $actor_name     = $names['display'];
            $original_name  = $names['original'];
            $character      = isset( $member['character'] ) ? sanitize_text_field( $member['character'] ) : '';
            $actor_name     = sanitize_text_field( $actor_name );
            $original_name  = sanitize_text_field( $original_name );

            if ( '' === $actor_name ) {
                continue;
            }
            $term_id        = self::upsert_related_term( TMDB_Taxonomies::ACTOR, $actor_id, $actor_name );
            $actor_details  = self::sanitize_actor_payload( $member, $actor_id, $actor_name, $original_name, $character );

            if ( $term_id ) {
                $term_ids[] = $term_id;
                self::update_person_term_meta( $term_id, $original_name );
                self::store_actor_details( $term_id, $actor_details, $actor_name );
            }

            $stored_cast[] = [
                'name'          => $actor_name,
                'original_name' => $original_name,
                'character'     => $character,
                'order'         => isset( $member['order'] ) ? (int) $member['order'] : 0,
            ];

            if ( $term_id ) {
                self::store_actor_role( $term_id, $movie_post_id, $movie_tmdb_id, $movie_title, $character );
            }
        }

        return [
            'term_ids' => array_map( 'intval', array_unique( $term_ids ) ),
            'cast'     => $stored_cast,
        ];
    }

    /**
     * Stores the relationship between an actor and their role for the imported movie.
     */
    private static function store_actor_role( int $term_id, int $movie_post_id, int $movie_tmdb_id, string $movie_title, string $character ): void {
        if ( $term_id <= 0 || '' === $character ) {
            return;
        }

        $movie_tmdb_id = max( 0, $movie_tmdb_id );
        $movie_post_id = max( 0, $movie_post_id );
        $movie_title   = sanitize_text_field( $movie_title );
        $character     = sanitize_text_field( $character );

        if ( $movie_tmdb_id <= 0 && $movie_post_id <= 0 ) {
            return;
        }

        $existing_roles = get_term_meta( $term_id, 'TMDB_roles', true );

        if ( ! is_array( $existing_roles ) ) {
            $existing_roles = [];
        }

        $updated = false;

        foreach ( $existing_roles as &$role ) {
            if ( ! is_array( $role ) ) {
                continue;
            }

            if ( isset( $role['movie_tmdb_id'] ) && (int) $role['movie_tmdb_id'] === $movie_tmdb_id ) {
                $role = [
                    'movie_tmdb_id' => $movie_tmdb_id,
                    'movie_post_id' => $movie_post_id,
                    'movie_title'   => $movie_title,
                    'character'     => $character,
                ];
                $updated = true;
                break;
            }
        }

        unset( $role );

        if ( ! $updated ) {
            $existing_roles[] = [
                'movie_tmdb_id' => $movie_tmdb_id,
                'movie_post_id' => $movie_post_id,
                'movie_title'   => $movie_title,
                'character'     => $character,
            ];
        }

        update_term_meta( $term_id, 'TMDB_roles', array_values( $existing_roles ) );
    }

    /**
     * Stores detailed metadata for an actor term including profile imagery.
     */
    private static function store_actor_details( int $term_id, array $actor_details, string $actor_name ): void {
        self::store_person_details(
            $term_id,
            $actor_details,
            $actor_name,
            'TMDB_actor_data',
            self::TMDB_ACTOR_MEDIA_CATEGORY
        );
    }

    /**
     * Stores detailed metadata for a director term including profile imagery.
     */
    private static function store_director_details( int $term_id, array $director_details, string $director_name ): void {
        self::store_person_details(
            $term_id,
            $director_details,
            $director_name,
            'TMDB_director_data',
            self::TMDB_DIRECTOR_MEDIA_CATEGORY
        );
    }

    /**
     * Stores detailed metadata for a person term including profile imagery.
     *
     * @param array<string, mixed> $person_details Details associated with the person.
     */
    private static function store_person_details( int $term_id, array $person_details, string $person_name, string $meta_key, string $media_category ): void {
        if ( $term_id <= 0 ) {
            return;
        }

        if ( empty( $person_details ) ) {
            delete_term_meta( $term_id, $meta_key );
            self::import_person_profile_image( $term_id, $person_name, 0, '', $media_category );

            return;
        }

        update_term_meta( $term_id, $meta_key, $person_details );

        $profile_path = isset( $person_details['profile_path'] ) ? (string) $person_details['profile_path'] : '';
        $person_id    = isset( $person_details['id'] ) ? (int) $person_details['id'] : 0;

        self::import_person_profile_image( $term_id, $person_name, $person_id, $profile_path, $media_category );
    }

    /**
     * Normalizes the payload returned for a cast member.
     *
     * @param array<string, mixed> $member Actor payload from TMDB.
     *
     * @return array<string, mixed>
     */
    private static function sanitize_actor_payload( array $member, int $actor_id, string $actor_name, string $original_name, string $character ): array {
        $member['id']            = $actor_id;
        $member['name']          = $actor_name;
        $member['original_name'] = $original_name;
        $member['character']     = $character;

        return self::sanitize_person_value( '', $member );
    }

    /**
     * Normalizes the payload returned for a director.
     *
     * @param array<string, mixed> $member Director payload from TMDB.
     *
     * @return array<string, mixed>
     */
    private static function sanitize_director_payload( array $member, int $director_id, string $director_name, string $original_name ): array {
        $member['id']            = $director_id;
        $member['name']          = $director_name;
        $member['original_name'] = $original_name;

        return self::sanitize_person_value( '', $member );
    }

    /**
     * Recursively sanitizes values contained within a person payload.
     *
     * @param string|int $key   Array key currently being sanitized.
     * @param mixed      $value Value associated with the key.
     *
     * @return mixed
     */
    private static function sanitize_person_value( $key, $value ) {
        if ( is_array( $value ) ) {
            $sanitized = [];

            foreach ( $value as $child_key => $child_value ) {
                $sanitized[ $child_key ] = self::sanitize_person_value( $child_key, $child_value );
            }

            return $sanitized;
        }

        if ( null === $value ) {
            return null;
        }

        if ( is_bool( $value ) ) {
            return (bool) $value;
        }

        if ( is_int( $value ) ) {
            return $value;
        }

        if ( is_float( $value ) ) {
            return $value;
        }

        if ( is_numeric( $value ) ) {
            return 0 + $value;
        }

        if ( ! is_string( $value ) ) {
            return $value;
        }

        if ( in_array( (string) $key, [ 'profile_path', 'poster_path', 'backdrop_path' ], true ) ) {
            $value = ltrim( $value, '/' );
        }

        return sanitize_text_field( $value );
    }

    /**
     * Ensures a person profile image is stored locally and linked to the term.
     */
    private static function import_person_profile_image( int $term_id, string $person_name, int $person_id, string $profile_path, string $media_category ): void {
        $profile_path = sanitize_text_field( ltrim( (string) $profile_path, '/' ) );

        if ( '' === $profile_path ) {
            $existing_image = get_term_meta( $term_id, 'TMDB_profile_image', true );
            self::delete_person_profile_image( $existing_image );
            delete_term_meta( $term_id, 'TMDB_profile_image' );
            delete_term_meta( $term_id, 'TMDB_profile_path' );
            delete_term_meta( $term_id, 'TMDB_profile_size' );

            return;
        }

        $current_size   = self::get_configured_profile_size();
        $existing_path  = (string) get_term_meta( $term_id, 'TMDB_profile_path', true );
        $existing_size  = (string) get_term_meta( $term_id, 'TMDB_profile_size', true );
        $existing_image = get_term_meta( $term_id, 'TMDB_profile_image', true );

        if ( $existing_path === $profile_path && $existing_size === $current_size && self::is_person_image_meta_valid( $existing_image, $current_size ) ) {
            return;
        }

        $image_url = self::build_profile_url( $profile_path );

        if ( '' === $image_url ) {
            return;
        }

        $downloaded = self::download_person_profile_image( $image_url, $person_name, $person_id, $media_category );

        if ( null === $downloaded ) {
            return;
        }

        self::delete_person_profile_image( $existing_image );

        update_term_meta( $term_id, 'TMDB_profile_image', $downloaded );
        update_term_meta( $term_id, 'TMDB_profile_path', $profile_path );
        update_term_meta( $term_id, 'TMDB_profile_size', $current_size );
    }

    /**
     * Removes a previously stored person profile image from the filesystem.
     *
     * @param mixed $image_meta Stored image metadata.
     */
    private static function delete_person_profile_image( $image_meta ): void {
        if ( ! is_array( $image_meta ) || empty( $image_meta['path'] ) ) {
            return;
        }

        $upload_dir = wp_upload_dir();

        if ( ! empty( $upload_dir['error'] ) ) {
            return;
        }

        $full_path = trailingslashit( $upload_dir['basedir'] ) . ltrim( (string) $image_meta['path'], '/' );

        if ( file_exists( $full_path ) ) {
            wp_delete_file( $full_path );
        }
    }

    /**
     * Downloads and stores a person profile image in the uploads directory.
     */
    private static function download_person_profile_image( string $image_url, string $person_name, int $person_id, string $media_category ): ?array {
        if ( '' === $image_url ) {
            return null;
        }

        self::ensure_media_dependencies_loaded();

        $temporary_file = download_url( $image_url );

        if ( is_wp_error( $temporary_file ) ) {
            return null;
        }

        $upload_dir = wp_upload_dir();

        if ( ! empty( $upload_dir['error'] ) ) {
            @unlink( $temporary_file );

            return null;
        }

        $subdir     = self::build_person_upload_subdir( $person_name, $media_category );
        $target_dir = trailingslashit( $upload_dir['basedir'] ) . $subdir;

        if ( ! wp_mkdir_p( $target_dir ) ) {
            @unlink( $temporary_file );

            return null;
        }

        $size       = self::get_configured_profile_size();
        $parsed_path = wp_parse_url( $image_url, PHP_URL_PATH );
        $basename    = is_string( $parsed_path ) ? wp_basename( $parsed_path ) : '';

        if ( '' === $basename ) {
            $default   = sprintf( '%s.jpg', rtrim( $media_category, 's' ) );
            $basename = $person_id > 0 ? sprintf( '%d.jpg', $person_id ) : $default;
        }

        $extension = pathinfo( $basename, PATHINFO_EXTENSION );

        if ( '' === $extension ) {
            $extension = 'jpg';
        }

        $proposed  = $person_id > 0 ? sprintf( '%d-%s.%s', $person_id, $size, $extension ) : $basename;
        $filename  = wp_unique_filename( $target_dir, $proposed );
        $new_path  = trailingslashit( $target_dir ) . $filename;

        if ( file_exists( $new_path ) ) {
            wp_delete_file( $new_path );
        }

        $moved = @rename( $temporary_file, $new_path );

        if ( ! $moved ) {
            $moved = @copy( $temporary_file, $new_path );
            @unlink( $temporary_file );
        }

        if ( ! $moved ) {
            @unlink( $temporary_file );

            return null;
        }

        $relative_path = $subdir . '/' . $filename;
        $public_url    = trailingslashit( $upload_dir['baseurl'] ) . $relative_path;

        return [
            'path'     => $relative_path,
            'url'      => $public_url,
            'filename' => $filename,
            'size'     => $size,
            'updated'  => time(),
        ];
    }

    /**
     * Builds the upload subdirectory used for storing person images.
     */
    private static function build_person_upload_subdir( string $person_name, string $media_category ): string {
        $initial = self::extract_person_initial( $person_name );

        return implode( '/', [ self::TMDB_UPLOAD_SUBDIR, $media_category, $initial ] );
    }

    /**
     * Resolves the directory initial based on the person name.
     */
    private static function extract_person_initial( string $person_name ): string {
        $person_name = trim( $person_name );

        if ( '' === $person_name ) {
            return '#';
        }

        if ( function_exists( 'remove_accents' ) ) {
            $person_name = remove_accents( $person_name );
        }

        if ( function_exists( 'mb_strtoupper' ) ) {
            $person_name = mb_strtoupper( $person_name, 'UTF-8' );
        } else {
            $person_name = strtoupper( $person_name );
        }

        $person_name = ltrim( $person_name );

        if ( function_exists( 'mb_substr' ) ) {
            $initial = mb_substr( $person_name, 0, 1, 'UTF-8' );
        } else {
            $initial = substr( $person_name, 0, 1 );
        }

        $initial = preg_replace( '/[^A-Z0-9]/', '', (string) $initial );

        if ( '' === $initial ) {
            return '#';
        }

        return $initial;
    }

    /**
     * Determines whether the stored person image metadata points to a valid file.
     *
     * @param mixed  $image_meta Stored metadata.
     * @param string $expected_size Expected profile size key.
     */
    private static function is_person_image_meta_valid( $image_meta, string $expected_size ): bool {
        if ( ! is_array( $image_meta ) || empty( $image_meta['path'] ) ) {
            return false;
        }

        if ( isset( $image_meta['size'] ) && $image_meta['size'] !== $expected_size ) {
            return false;
        }

        $upload_dir = wp_upload_dir();

        if ( ! empty( $upload_dir['error'] ) ) {
            return false;
        }

        $full_path = trailingslashit( $upload_dir['basedir'] ) . ltrim( (string) $image_meta['path'], '/' );

        return file_exists( $full_path );
    }

    /**
     * Imports crew members focusing on directors.
     *
     * @param array<int, array<string, mixed>> $crew          Crew members.
     * @param array<int, array<string, mixed>> $fallback_crew Crew members from the fallback payload.
     *
     * @return array<string, array<int, mixed>>
     */
    private static function import_crew( array $crew, array $fallback_crew = [] ): array {
        $directors        = [];
        $term_ids         = [];
        $fallback_lookup  = self::build_person_lookup( $fallback_crew );

        foreach ( $crew as $member ) {
            if ( ! is_array( $member ) ) {
                continue;
            }

            $job = isset( $member['job'] ) ? sanitize_text_field( $member['job'] ) : '';

            if ( 'Director' !== $job ) {
                continue;
            }

            $director_id = isset( $member['id'] ) ? (int) $member['id'] : 0;

            $names            = self::resolve_person_names( $member, $fallback_lookup[ $director_id ] ?? null );
            $name             = $names['display'];
            $original         = $names['original'];
            $name             = sanitize_text_field( $name );
            $original         = sanitize_text_field( $original );
            $director_details = self::sanitize_director_payload( $member, $director_id, $name, $original );

            if ( '' === $name ) {
                continue;
            }
            $term_id     = self::upsert_related_term( TMDB_Taxonomies::DIRECTOR, $director_id, $name );

            if ( $term_id ) {
                $term_ids[] = $term_id;
                self::update_person_term_meta( $term_id, $original );
                self::store_director_details( $term_id, $director_details, $name );
            }

            $directors[] = [
                'name'          => $name,
                'original_name' => $original,
                'job'           => $job,
            ];
        }

        return [
            'term_ids'  => array_map( 'intval', array_unique( $term_ids ) ),
            'directors' => $directors,
        ];
    }

    /**
     * Builds a person lookup map keyed by TMDB identifier.
     *
     * @param array<int, array<string, mixed>> $people People payload.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function build_person_lookup( array $people ): array {
        $lookup = [];

        foreach ( $people as $person ) {
            if ( ! is_array( $person ) || ! isset( $person['id'] ) ) {
                continue;
            }

            $person_id = (int) $person['id'];

            if ( $person_id <= 0 ) {
                continue;
            }

            $lookup[ $person_id ] = $person;
        }

        return $lookup;
    }

    /**
     * Determines the display and original names for a TMDB person entry.
     *
     * @param array<string, mixed>      $primary  Primary payload data.
     * @param array<string, mixed>|null $fallback Fallback language payload data.
     *
     * @return array{display:string,original:string}
     */
    private static function resolve_person_names( array $primary, ?array $fallback ): array {
        $primary_name  = isset( $primary['name'] ) ? (string) $primary['name'] : '';
        $fallback_name = ( null !== $fallback && isset( $fallback['name'] ) ) ? (string) $fallback['name'] : '';
        $original_name = isset( $primary['original_name'] ) ? (string) $primary['original_name'] : '';

        if ( '' === $original_name && null !== $fallback && isset( $fallback['original_name'] ) ) {
            $original_name = (string) $fallback['original_name'];
        }

        $display_name = '' !== $fallback_name ? $fallback_name : $primary_name;

        if ( '' === $display_name && '' !== $original_name ) {
            $display_name = $original_name;
        }

        return [
            'display'  => $display_name,
            'original' => $original_name,
        ];
    }

    /**
     * Stores the original name meta for a person term.
     */
    private static function update_person_term_meta( int $term_id, string $original_name ): void {
        if ( $term_id <= 0 ) {
            return;
        }

        if ( '' === $original_name ) {
            delete_term_meta( $term_id, 'TMDB_original_name' );

            return;
        }

        update_term_meta( $term_id, 'TMDB_original_name', $original_name );
    }

    /**
     * Updates the term name and slug when the stored values differ from TMDB data.
     */
    private static function maybe_update_term_name( int $term_id, string $taxonomy, string $name, string $slug ): void {
        if ( $term_id <= 0 ) {
            return;
        }

        $term = get_term( $term_id, $taxonomy );

        if ( ! $term instanceof \WP_Term || is_wp_error( $term ) ) {
            return;
        }

        $update_args = [];

        if ( '' !== $name && $term->name !== $name ) {
            $update_args['name'] = $name;
        }

        if ( '' !== $slug && $term->slug !== $slug ) {
            $update_args['slug'] = $slug;
        }

        if ( empty( $update_args ) ) {
            return;
        }

        wp_update_term( $term_id, $taxonomy, $update_args );
    }

    /**
     * Imports TMDB keywords as taxonomy terms.
     *
     * @param array<int, array<string, mixed>> $keywords Keywords payload.
     *
     * @return array<string, array<int, mixed>>
     */
    private static function import_keywords( array $keywords ): array {
        $term_ids = [];
        $stored   = [];

        foreach ( $keywords as $keyword ) {
            if ( ! is_array( $keyword ) || empty( $keyword['name'] ) ) {
                continue;
            }

            $name    = sanitize_text_field( $keyword['name'] );
            $tmdb_id = isset( $keyword['id'] ) ? (int) $keyword['id'] : 0;
            $term_id = self::upsert_related_term( TMDB_Taxonomies::KEYWORD, $tmdb_id, $name );

            if ( $term_id ) {
                $term_ids[] = $term_id;
            }

            $stored[] = [
                'name' => $name,
            ];
        }

        return [
            'term_ids' => array_map( 'intval', array_unique( $term_ids ) ),
            'keywords' => $stored,
        ];
    }

    /**
     * Extracts the most relevant trailer from a list of TMDB videos.
     *
     * @param array<int, array<string, mixed>> $videos List of video payloads.
     *
     * @return array<string, mixed>
     */
    private static function extract_trailer( array $videos ): array {
        if ( empty( $videos ) ) {
            return [];
        }

        $trailers = [];

        foreach ( $videos as $video ) {
            if ( ! is_array( $video ) ) {
                continue;
            }

            $type = isset( $video['type'] ) ? sanitize_text_field( $video['type'] ) : '';

            if ( 'Trailer' !== $type ) {
                continue;
            }

            $trailers[] = [
                'name'         => isset( $video['name'] ) ? sanitize_text_field( $video['name'] ) : '',
                'key'          => isset( $video['key'] ) ? sanitize_text_field( $video['key'] ) : '',
                'site'         => isset( $video['site'] ) ? sanitize_text_field( $video['site'] ) : '',
                'type'         => $type,
                'official'     => ! empty( $video['official'] ),
                'published_at' => isset( $video['published_at'] ) ? sanitize_text_field( $video['published_at'] ) : '',
            ];
        }

        if ( empty( $trailers ) ) {
            return [];
        }

        $preferred = null;

        foreach ( $trailers as $trailer ) {
            if ( 'YouTube' === $trailer['site'] && $trailer['official'] ) {
                $preferred = $trailer;
                break;
            }
        }

        if ( null === $preferred ) {
            foreach ( $trailers as $trailer ) {
                if ( 'YouTube' === $trailer['site'] ) {
                    $preferred = $trailer;
                    break;
                }
            }
        }

        if ( null === $preferred ) {
            $preferred = $trailers[0];
        }

        $url = '';

        if ( 'YouTube' === $preferred['site'] && '' !== $preferred['key'] ) {
            $url = sprintf( 'https://www.youtube.com/watch?v=%s', rawurlencode( $preferred['key'] ) );
        }

        $preferred['url'] = '' !== $url ? esc_url_raw( $url ) : '';

        return $preferred;
    }

    /**
     * Serializes the entire TMDB videos payload for storage/debugging purposes.
     *
     * @param array<int, array<string, mixed>> $videos Videos payload.
     */
    private static function serialize_videos_payload( array $videos ): string {
        if ( empty( $videos ) ) {
            return '';
        }

        $sanitized = [];

        foreach ( $videos as $video ) {
            if ( ! is_array( $video ) ) {
                continue;
            }

            $sanitized[] = [
                'id'           => isset( $video['id'] ) ? sanitize_text_field( (string) $video['id'] ) : '',
                'iso_639_1'    => isset( $video['iso_639_1'] ) ? sanitize_text_field( (string) $video['iso_639_1'] ) : '',
                'iso_3166_1'   => isset( $video['iso_3166_1'] ) ? sanitize_text_field( (string) $video['iso_3166_1'] ) : '',
                'name'         => isset( $video['name'] ) ? sanitize_text_field( (string) $video['name'] ) : '',
                'key'          => isset( $video['key'] ) ? sanitize_text_field( (string) $video['key'] ) : '',
                'site'         => isset( $video['site'] ) ? sanitize_text_field( (string) $video['site'] ) : '',
                'size'         => isset( $video['size'] ) ? (int) $video['size'] : 0,
                'type'         => isset( $video['type'] ) ? sanitize_text_field( (string) $video['type'] ) : '',
                'official'     => ! empty( $video['official'] ),
                'published_at' => isset( $video['published_at'] ) ? sanitize_text_field( (string) $video['published_at'] ) : '',
            ];
        }

        if ( empty( $sanitized ) ) {
            return '';
        }

        $json = wp_json_encode( $sanitized );

        return is_string( $json ) ? $json : '';
    }

    /**
     * Extracts website payload information from the TMDB movie response.
     *
     * @param array<string, mixed> $movie_data Movie payload retrieved from TMDB.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function extract_websites_payload( array $movie_data ): array {
        if ( ! isset( $movie_data['websites'] ) ) {
            return [];
        }

        $websites = $movie_data['websites'];

        if ( is_array( $websites ) && isset( $websites['results'] ) && is_array( $websites['results'] ) ) {
            $websites = $websites['results'];
        }

        if ( ! is_array( $websites ) ) {
            return [];
        }

        return array_values(
            array_filter(
                $websites,
                static function ( $website ): bool {
                    return is_array( $website );
                }
            )
        );
    }

    /**
     * Formats website information for storage in post meta.
     *
     * @param array<int, array<string, mixed>> $websites Website payload results.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function format_websites( array $websites ): array {
        $formatted = [];

        foreach ( $websites as $website ) {
            if ( ! is_array( $website ) ) {
                continue;
            }

            $url = isset( $website['url'] ) ? esc_url_raw( (string) $website['url'] ) : '';

            if ( '' === $url ) {
                continue;
            }

            $entry = [
                'url'      => $url,
                'official' => ! empty( $website['official'] ),
            ];

            if ( isset( $website['id'] ) && (int) $website['id'] > 0 ) {
                $entry['id'] = (int) $website['id'];
            }

            if ( isset( $website['name'] ) && '' !== $website['name'] ) {
                $entry['name'] = sanitize_text_field( (string) $website['name'] );
            }

            if ( isset( $website['type'] ) && '' !== $website['type'] ) {
                $entry['type'] = sanitize_text_field( (string) $website['type'] );
            }

            if ( isset( $website['site'] ) && '' !== $website['site'] ) {
                $entry['site'] = sanitize_text_field( (string) $website['site'] );
            }

            if ( isset( $website['iso_3166_1'] ) && '' !== $website['iso_3166_1'] ) {
                $entry['iso_3166_1'] = sanitize_text_field( (string) $website['iso_3166_1'] );
            }

            $formatted[] = $entry;
        }

        return $formatted;
    }

    /**
     * Determines the most relevant website entry from the provided payload.
     *
     * @param array<int, array<string, mixed>> $websites Sanitized websites payload.
     *
     * @return array<string, mixed>
     */
    private static function extract_primary_website( array $websites ): array {
        if ( empty( $websites ) ) {
            return [];
        }

        $best_score = -1;
        $best_site  = [];

        foreach ( $websites as $website ) {
            if ( ! is_array( $website ) || empty( $website['url'] ) ) {
                continue;
            }

            $score = 0;

            if ( ! empty( $website['official'] ) ) {
                $score += 4;
            }

            if ( isset( $website['type'] ) ) {
                $type = strtolower( (string) $website['type'] );

                if ( in_array( $type, [ 'official', 'official site', 'official website' ], true ) ) {
                    $score += 2;
                }
            }

            if ( isset( $website['iso_3166_1'] ) && '' !== $website['iso_3166_1'] ) {
                $score += 'US' === strtoupper( (string) $website['iso_3166_1'] ) ? 1 : 0;
            }

            if ( $score > $best_score ) {
                $best_score = $score;
                $best_site  = $website;
            }
        }

        if ( $best_score < 0 ) {
            foreach ( $websites as $website ) {
                if ( is_array( $website ) && ! empty( $website['url'] ) ) {
                    return $website;
                }
            }

            return [];
        }

        return $best_site;
    }

    /**
     * Serializes the TMDB websites payload for storage/debugging purposes.
     *
     * @param array<int, array<string, mixed>> $websites Website payload.
     */
    private static function serialize_websites_payload( array $websites ): string {
        if ( empty( $websites ) ) {
            return '';
        }

        $sanitized = [];

        foreach ( $websites as $website ) {
            if ( ! is_array( $website ) ) {
                continue;
            }

            $sanitized[] = array_filter(
                [
                    'id'         => isset( $website['id'] ) ? (int) $website['id'] : null,
                    'name'       => isset( $website['name'] ) ? sanitize_text_field( (string) $website['name'] ) : null,
                    'type'       => isset( $website['type'] ) ? sanitize_text_field( (string) $website['type'] ) : null,
                    'official'   => isset( $website['official'] ) ? (bool) $website['official'] : null,
                    'url'        => isset( $website['url'] ) ? esc_url_raw( (string) $website['url'] ) : null,
                    'site'       => isset( $website['site'] ) ? sanitize_text_field( (string) $website['site'] ) : null,
                    'iso_3166_1' => isset( $website['iso_3166_1'] ) ? sanitize_text_field( (string) $website['iso_3166_1'] ) : null,
                ],
                static function ( $value ) {
                    if ( is_bool( $value ) ) {
                        return true;
                    }

                    return null !== $value && '' !== $value;
                }
            );
        }

        if ( empty( $sanitized ) ) {
            return '';
        }

        $json = wp_json_encode( $sanitized );

        return is_string( $json ) ? $json : '';
    }

    /**
     * Sanitizes external ID payload values.
     *
     * @param array<string, mixed> $external_ids External IDs payload from TMDB.
     *
     * @return array<string, string>
     */
    private static function sanitize_external_ids( array $external_ids ): array {
        $sanitized = [];

        foreach ( $external_ids as $key => $value ) {
            if ( ! is_string( $key ) ) {
                continue;
            }

            if ( is_array( $value ) ) {
                continue;
            }

            if ( is_bool( $value ) ) {
                $value = $value ? '1' : '0';
            } elseif ( is_scalar( $value ) ) {
                $value = (string) $value;
            } else {
                continue;
            }

            $value = trim( $value );

            if ( '' === $value ) {
                continue;
            }

            $sanitized_key   = sanitize_key( $key );
            $sanitized_value = sanitize_text_field( $value );

            if ( '' === $sanitized_key || '' === $sanitized_value ) {
                continue;
            }

            $sanitized[ $sanitized_key ] = $sanitized_value;
        }

        return $sanitized;
    }

    /**
     * Resolves the runtime for the imported movie using fallback data when available.
     *
     * @param array<string, mixed>      $movie_data     Primary movie payload.
     * @param array<string, mixed>|null $fallback_movie Optional fallback payload.
     */
    private static function resolve_movie_runtime( array $movie_data, ?array $fallback_movie ): int {
        $runtime = isset( $movie_data['runtime'] ) ? (int) $movie_data['runtime'] : 0;

        if ( $runtime > 0 || null === $fallback_movie ) {
            return $runtime;
        }

        return isset( $fallback_movie['runtime'] ) ? max( 0, (int) $fallback_movie['runtime'] ) : 0;
    }

    /**
     * Resolves the homepage URL for the movie using fallback data when required.
     *
     * @param array<string, mixed>      $movie_data     Primary movie payload.
     * @param array<string, mixed>|null $fallback_movie Optional fallback payload.
     */
    private static function resolve_homepage_url( array $movie_data, ?array $fallback_movie ): string {
        $homepage = isset( $movie_data['homepage'] ) ? esc_url_raw( (string) $movie_data['homepage'] ) : '';

        if ( '' !== $homepage || null === $fallback_movie ) {
            return $homepage;
        }

        if ( isset( $fallback_movie['homepage'] ) ) {
            $fallback_homepage = esc_url_raw( (string) $fallback_movie['homepage'] );

            if ( '' !== $fallback_homepage ) {
                return $fallback_homepage;
            }
        }

        return '';
    }

    /**
     * Sanitizes the list of origin countries returned from TMDB.
     *
     * @param mixed $countries Raw origin country payload from TMDB.
     *
     * @return array<int, string>
     */
    private static function sanitize_origin_countries( $countries ): array {
        if ( ! is_array( $countries ) ) {
            return [];
        }

        $sanitized = [];

        foreach ( $countries as $country ) {
            if ( ! is_scalar( $country ) ) {
                continue;
            }

            $code = strtoupper( sanitize_text_field( (string) $country ) );

            if ( '' === $code ) {
                continue;
            }

            $sanitized[] = $code;
        }

        if ( empty( $sanitized ) ) {
            return [];
        }

        return array_values( array_unique( $sanitized ) );
    }

    /**
     * Sanitizes the spoken language payload for storage in post meta.
     *
     * @param mixed $languages Raw spoken language payload.
     *
     * @return array<int, array<string, string>>
     */
    private static function sanitize_spoken_languages( $languages ): array {
        if ( ! is_array( $languages ) ) {
            return [];
        }

        $sanitized = [];

        foreach ( $languages as $language ) {
            if ( ! is_array( $language ) ) {
                continue;
            }

            $entry = [];

            if ( isset( $language['iso_639_1'] ) && '' !== $language['iso_639_1'] ) {
                $entry['iso_639_1'] = sanitize_text_field( (string) $language['iso_639_1'] );
            }

            if ( isset( $language['english_name'] ) && '' !== $language['english_name'] ) {
                $entry['english_name'] = sanitize_text_field( (string) $language['english_name'] );
            }

            if ( isset( $language['name'] ) && '' !== $language['name'] ) {
                $entry['name'] = sanitize_text_field( (string) $language['name'] );
            }

            if ( empty( $entry ) ) {
                continue;
            }

            $sanitized[] = $entry;
        }

        return $sanitized;
    }

    /**
     * Sanitizes the alternative title payload provided by TMDB.
     *
     * @param mixed $payload Raw alternative title payload.
     *
     * @return array<int, array<string, string>>
     */
    private static function sanitize_alternative_titles( $payload ): array {
        if ( is_array( $payload ) && isset( $payload['titles'] ) && is_array( $payload['titles'] ) ) {
            $payload = $payload['titles'];
        }

        if ( ! is_array( $payload ) ) {
            return [];
        }

        $sanitized = [];
        $seen       = [];

        foreach ( $payload as $title ) {
            if ( ! is_array( $title ) ) {
                continue;
            }

            $label = isset( $title['title'] ) ? sanitize_text_field( (string) $title['title'] ) : '';

            if ( '' === $label ) {
                continue;
            }

            $entry = [
                'title' => $label,
            ];

            if ( isset( $title['iso_3166_1'] ) && '' !== $title['iso_3166_1'] ) {
                $entry['iso_3166_1'] = strtoupper( sanitize_text_field( (string) $title['iso_3166_1'] ) );
            }

            if ( isset( $title['iso_639_1'] ) && '' !== $title['iso_639_1'] ) {
                $entry['iso_639_1'] = sanitize_text_field( (string) $title['iso_639_1'] );
            }

            if ( isset( $title['type'] ) && '' !== $title['type'] ) {
                $entry['type'] = sanitize_text_field( (string) $title['type'] );
            }

            $hash = wp_json_encode( $entry );

            if ( ! is_string( $hash ) ) {
                continue;
            }

            if ( isset( $seen[ $hash ] ) ) {
                continue;
            }

            $seen[ $hash ] = true;
            $sanitized[]   = $entry;
        }

        return $sanitized;
    }

    /**
     * Stores the provided external IDs in post meta, maintaining per-ID keys.
     *
     * @param int                  $post_id      WordPress post identifier.
     * @param array<string, string> $external_ids Sanitized external IDs payload.
     */
    private static function sync_external_id_meta( int $post_id, array $external_ids ): void {
        $existing = get_post_meta( $post_id, 'TMDB_external_ids', true );

        if ( ! is_array( $existing ) ) {
            $existing = [];
        }

        if ( empty( $external_ids ) ) {
            delete_post_meta( $post_id, 'TMDB_external_ids' );
        } else {
            update_post_meta( $post_id, 'TMDB_external_ids', $external_ids );
        }

        $all_keys = array_unique( array_merge( array_keys( $existing ), array_keys( $external_ids ) ) );

        foreach ( $all_keys as $key ) {
            $meta_key = self::build_external_id_meta_key( $key );

            if ( isset( $external_ids[ $key ] ) && '' !== $external_ids[ $key ] ) {
                update_post_meta( $post_id, $meta_key, $external_ids[ $key ] );
            } else {
                delete_post_meta( $post_id, $meta_key );
            }

            self::delete_legacy_external_id_meta( $post_id, $key );
        }
    }

    /**
     * Builds a consistent post meta key for a given external identifier name.
     */
    private static function build_external_id_meta_key( string $id_key ): string {
        $normalized = sanitize_key( $id_key );

        if ( '' === $normalized ) {
            $normalized = sanitize_title( (string) $id_key );
        }

        if ( '' === $normalized ) {
            $normalized = 'external_id';
        }

        return 'TMDB_external_ids_' . $normalized;
    }

    /**
     * Removes legacy external ID meta keys stored in previous versions.
     */
    private static function delete_legacy_external_id_meta( int $post_id, string $id_key ): void {
        $legacy_key = self::build_legacy_external_id_meta_key( $id_key );

        delete_post_meta( $post_id, $legacy_key );
    }

    /**
     * Builds the legacy post meta key used for external identifiers.
     */
    private static function build_legacy_external_id_meta_key( string $id_key ): string {
        $normalized = preg_replace( '/[^a-z0-9]+/i', '_', $id_key );
        $normalized = is_string( $normalized ) ? trim( $normalized, '_' ) : '';

        if ( '' === $normalized ) {
            $normalized = sanitize_key( $id_key );
        }

        if ( '' === $normalized ) {
            $normalized = 'EXTERNAL_ID';
        }

        return 'TMDB_' . strtoupper( $normalized ) . '_id';
    }

    /**
     * Imports TMDB genres as taxonomy terms.
     *
     * @param array<int, array<string, mixed>> $genres Genres payload.
     *
     * @return array<string, array<int, mixed>>
     */
    private static function import_genres( array $genres ): array {
        $term_ids = [];
        $stored   = [];

        foreach ( $genres as $genre ) {
            if ( ! is_array( $genre ) || empty( $genre['name'] ) ) {
                continue;
            }

            $name    = sanitize_text_field( $genre['name'] );
            $tmdb_id = isset( $genre['id'] ) ? (int) $genre['id'] : 0;
            $term_id = self::upsert_related_term( TMDB_Taxonomies::GENRE, $tmdb_id, $name );

            if ( $term_id ) {
                $term_ids[] = $term_id;
            }

            $stored[] = [
                'name' => $name,
            ];
        }

        return [
            'term_ids' => array_map( 'intval', array_unique( $term_ids ) ),
            'genres'   => $stored,
        ];
    }

    /**
     * Creates or updates a related taxonomy term (actor, genre, director).
     *
     * @param string $taxonomy Taxonomy name.
     * @param int    $tmdb_id  TMDB identifier.
     * @param string $name     Term name.
     */
    private static function upsert_related_term( string $taxonomy, int $tmdb_id, string $name ): int {
        if ( '' === $name ) {
            return 0;
        }

        $slug = self::generate_term_slug( $taxonomy, $tmdb_id, $name );

        if ( $tmdb_id > 0 ) {
            $existing_by_meta = get_terms(
                [
                    'taxonomy'   => $taxonomy,
                    'hide_empty' => false,
                    'fields'     => 'ids',
                    'number'     => 1,
                    'meta_query' => [
                        [
                            'key'   => 'TMDB_id',
                            'value' => $tmdb_id,
                        ],
                    ],
                ]
            );

            if ( ! is_wp_error( $existing_by_meta ) && ! empty( $existing_by_meta ) ) {
                $term_id = (int) $existing_by_meta[0];

                self::maybe_update_term_name( $term_id, $taxonomy, $name, $slug );

                return $term_id;
            }
        }

        $existing_by_name = term_exists( $name, $taxonomy );

        if ( $existing_by_name && ! is_wp_error( $existing_by_name ) ) {
            $term_id = (int) ( is_array( $existing_by_name ) ? $existing_by_name['term_id'] : $existing_by_name );

            if ( $tmdb_id > 0 ) {
                update_term_meta( $term_id, 'TMDB_id', $tmdb_id );
            }

            self::maybe_update_term_name( $term_id, $taxonomy, $name, $slug );

            return $term_id;
        }

        $insert_args = [];

        if ( '' !== $slug ) {
            $insert_args['slug'] = $slug;
        }

        $created = wp_insert_term( $name, $taxonomy, $insert_args );

        if ( is_wp_error( $created ) ) {
            return 0;
        }

        $term_id = (int) $created['term_id'];

        if ( $tmdb_id > 0 ) {
            update_term_meta( $term_id, 'TMDB_id', $tmdb_id );
        }

        return $term_id;
    }

    /**
     * Generates a slug for related taxonomy terms ensuring TMDB identifiers are included when required.
     */
    private static function generate_term_slug( string $taxonomy, int $tmdb_id, string $name ): string {
        if ( '' === $name ) {
            return '';
        }

        if ( $tmdb_id > 0 && in_array( $taxonomy, [ TMDB_Taxonomies::ACTOR, TMDB_Taxonomies::DIRECTOR, TMDB_Taxonomies::KEYWORD ], true ) ) {
            return sanitize_title( $tmdb_id . '-' . $name );
        }

        return sanitize_title( $name );
    }

    /**
     * Generates a slug for a movie post that includes the TMDB identifier when available.
     */
    private static function generate_movie_slug( int $tmdb_id, string $title, string $fallback_title = '' ): string {
        $base_title = '' !== $title ? $title : $fallback_title;

        if ( '' === $base_title && $tmdb_id <= 0 ) {
            return '';
        }

        if ( '' === $base_title ) {
            return sanitize_title( (string) $tmdb_id );
        }

        $slug_source = $tmdb_id > 0 ? sprintf( '%d-%s', $tmdb_id, $base_title ) : $base_title;

        return sanitize_title( $slug_source );
    }

    /**
     * Normalises collection data returned by TMDB for storage in post meta.
     *
     * @param array<string, mixed>|null $collection Raw collection payload from TMDB.
     *
     * @return array<string, mixed>
     */
    private static function prepare_collection_data( ?array $collection ): array {
        if ( empty( $collection ) ) {
            return [];
        }

        $id            = isset( $collection['id'] ) ? (int) $collection['id'] : 0;
        $name          = isset( $collection['name'] ) ? sanitize_text_field( $collection['name'] ) : '';
        $poster_path   = isset( $collection['poster_path'] ) ? sanitize_text_field( ltrim( (string) $collection['poster_path'], '/' ) ) : '';
        $backdrop_path = isset( $collection['backdrop_path'] ) ? sanitize_text_field( ltrim( (string) $collection['backdrop_path'], '/' ) ) : '';

        if ( 0 === $id && '' === $name && '' === $poster_path && '' === $backdrop_path ) {
            return [];
        }

        return [
            'id'            => $id,
            'name'          => $name,
            'poster_path'   => $poster_path,
            'backdrop_path' => $backdrop_path,
        ];
    }

    /**
     * Sets the featured image for a movie post based on the TMDB poster.
     *
     * @param int         $post_id        WordPress post ID.
     * @param string      $poster_path    TMDB poster path.
     * @param string      $title          Movie title.
     * @param int         $tmdb_id        TMDB identifier for the movie.
     * @param string|null $existing_path  Previously stored TMDB poster path.
     */
    private static function set_featured_image( int $post_id, string $poster_path, string $title, int $tmdb_id, ?string $existing_path = null ): void {
        if ( null === $existing_path ) {
            $existing_path = (string) get_post_meta( $post_id, 'TMDB_poster_path', true );
        }

        $poster_size = self::get_configured_poster_size();
        $existing_size = (string) get_post_meta( $post_id, 'TMDB_poster_size', true );

        if ( $existing_path === $poster_path && $existing_size === $poster_size && has_post_thumbnail( $post_id ) ) {
            return;
        }

        $poster_url = self::build_poster_url( $poster_path );

        if ( '' === $poster_url ) {
            return;
        }

        $description = '' !== $title
            ? sprintf( __( '%s poster', 'tmdb-plugin' ), $title )
            : __( 'TMDB movie poster', 'tmdb-plugin' );

        $attachment_id = self::sideload_tmdb_image( $poster_url, $post_id, $description, $tmdb_id );

        if ( $attachment_id <= 0 ) {
            return;
        }

        set_post_thumbnail( $post_id, $attachment_id );

        if ( '' !== $title ) {
            update_post_meta( $attachment_id, '_wp_attachment_image_alt', wp_strip_all_tags( $title ) );
        }

        update_post_meta( $post_id, 'TMDB_poster_size', $poster_size );
    }

    /**
     * Imports additional gallery images for a movie post.
     *
     * @param int                                $post_id           Post identifier.
     * @param string                             $title             Movie title.
     * @param array<int, array<string, mixed>>   $primary_backdrops   Primary image payload.
     * @param array<int, array<string, mixed>>   $fallback_backdrops  Fallback image payload.
     * @param int                                 $tmdb_id             TMDB identifier for the movie.
     */
    private static function import_gallery_images( int $post_id, string $title, array $primary_backdrops, array $fallback_backdrops, int $tmdb_id ): void {
        $requested_count = self::get_configured_gallery_image_count();
        $existing_map    = get_post_meta( $post_id, 'TMDB_gallery_images', true );

        if ( ! is_array( $existing_map ) ) {
            $existing_map = [];
        }

        $configured_size = self::get_configured_backdrop_size();
        $previous_size   = (string) get_post_meta( $post_id, 'TMDB_gallery_image_size', true );

        if ( $requested_count <= 0 ) {
            self::delete_gallery_attachments( $existing_map );
            delete_post_meta( $post_id, 'TMDB_gallery_images' );
            delete_post_meta( $post_id, 'TMDB_gallery_image_ids' );
            delete_post_meta( $post_id, 'TMDB_gallery_image_size' );

            return;
        }

        $candidates = self::prepare_backdrop_candidates( $primary_backdrops, $fallback_backdrops );

        if ( empty( $candidates ) ) {
            self::delete_gallery_attachments( $existing_map );
            delete_post_meta( $post_id, 'TMDB_gallery_images' );
            delete_post_meta( $post_id, 'TMDB_gallery_image_ids' );
            delete_post_meta( $post_id, 'TMDB_gallery_image_size' );

            return;
        }

        if ( $previous_size !== $configured_size && ! empty( $existing_map ) ) {
            self::delete_gallery_attachments( $existing_map );
            $existing_map = [];
        }

        $selected          = array_slice( $candidates, 0, $requested_count );
        $new_map           = [];
        $attachment_ids    = [];
        $alt_text          = '' !== $title ? $title : __( 'TMDB movie', 'tmdb-plugin' );
        $description_label = __( '%s backdrop', 'tmdb-plugin' );

        foreach ( $selected as $image ) {
            $path          = $image['file_path'];
            $attachment_id = isset( $existing_map[ $path ] ) ? (int) $existing_map[ $path ] : 0;

            if ( $attachment_id > 0 ) {
                $attachment_post = get_post( $attachment_id );

                if ( ! $attachment_post || 'attachment' !== $attachment_post->post_type ) {
                    $attachment_id = 0;
                }
            }

            if ( $attachment_id <= 0 ) {
                $image_url = self::build_backdrop_url( $path );

                if ( '' === $image_url ) {
                    continue;
                }

                $attachment_id = self::sideload_tmdb_image( $image_url, $post_id, sprintf( $description_label, $alt_text ), $tmdb_id );
            }

            if ( $attachment_id <= 0 ) {
                continue;
            }

            $new_map[ $path ]   = $attachment_id;
            $attachment_ids[]   = $attachment_id;

            update_post_meta( $attachment_id, '_wp_attachment_image_alt', wp_strip_all_tags( $alt_text ) );
        }

        if ( empty( $new_map ) ) {
            self::delete_gallery_attachments( $existing_map );
            delete_post_meta( $post_id, 'TMDB_gallery_images' );
            delete_post_meta( $post_id, 'TMDB_gallery_image_ids' );
            delete_post_meta( $post_id, 'TMDB_gallery_image_size' );

            return;
        }

        $previous_ids = array_map( 'intval', array_values( $existing_map ) );
        $removed_ids  = array_diff( $previous_ids, $attachment_ids );

        if ( ! empty( $removed_ids ) ) {
            self::delete_gallery_attachments( $removed_ids );
        }

        update_post_meta( $post_id, 'TMDB_gallery_images', $new_map );
        update_post_meta( $post_id, 'TMDB_gallery_image_ids', $attachment_ids );
        update_post_meta( $post_id, 'TMDB_gallery_image_size', $configured_size );
    }

    /**
     * Normalises backdrop payloads into a list of import candidates.
     *
     * @param array<int, array<string, mixed>> $primary   Backdrop payload for the primary language.
     * @param array<int, array<string, mixed>> $fallback  Backdrop payload for the fallback language.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function prepare_backdrop_candidates( array $primary, array $fallback ): array {
        $candidates = [];

        foreach ( $primary as $backdrop ) {
            $sanitized = self::sanitize_backdrop_entry( $backdrop );

            if ( null === $sanitized ) {
                continue;
            }

            $candidates[ $sanitized['file_path'] ] = $sanitized;
        }

        foreach ( $fallback as $backdrop ) {
            $sanitized = self::sanitize_backdrop_entry( $backdrop );

            if ( null === $sanitized || isset( $candidates[ $sanitized['file_path'] ] ) ) {
                continue;
            }

            $candidates[ $sanitized['file_path'] ] = $sanitized;
        }

        if ( empty( $candidates ) ) {
            return [];
        }

        uasort(
            $candidates,
            static function ( array $a, array $b ): int {
                $vote_comparison = $b['vote_average'] <=> $a['vote_average'];

                if ( 0 !== $vote_comparison ) {
                    return $vote_comparison;
                }

                $count_comparison = $b['vote_count'] <=> $a['vote_count'];

                if ( 0 !== $count_comparison ) {
                    return $count_comparison;
                }

                return $b['width'] <=> $a['width'];
            }
        );

        return array_values( $candidates );
    }

    /**
     * Sanitizes a single backdrop payload entry.
     *
     * @param array<string, mixed> $backdrop Backdrop payload.
     */
    private static function sanitize_backdrop_entry( $backdrop ): ?array {
        if ( ! is_array( $backdrop ) || empty( $backdrop['file_path'] ) ) {
            return null;
        }

        $path = sanitize_text_field( ltrim( (string) $backdrop['file_path'], '/' ) );

        if ( '' === $path ) {
            return null;
        }

        return [
            'file_path'    => $path,
            'vote_average' => isset( $backdrop['vote_average'] ) ? (float) $backdrop['vote_average'] : 0.0,
            'vote_count'   => isset( $backdrop['vote_count'] ) ? (int) $backdrop['vote_count'] : 0,
            'width'        => isset( $backdrop['width'] ) ? (int) $backdrop['width'] : 0,
        ];
    }

    /**
     * Deletes gallery attachments that are no longer required.
     *
     * @param array<int|string, int|string> $attachments Attachment identifiers.
     */
    private static function delete_gallery_attachments( array $attachments ): void {
        foreach ( $attachments as $attachment ) {
            $attachment_id = (int) $attachment;

            if ( $attachment_id <= 0 ) {
                continue;
            }

            if ( 'attachment' !== get_post_type( $attachment_id ) ) {
                continue;
            }

            wp_delete_attachment( $attachment_id, true );
        }
    }

    /**
     * Downloads an image from TMDB and stores it in the media library.
     * @param string $image_url   Remote image URL.
     * @param int    $post_id     WordPress post ID the media should be attached to.
     * @param string $description Attachment description.
     * @param int    $tmdb_id     TMDB identifier used to determine the upload subdirectory.
     */
    private static function sideload_tmdb_image( string $image_url, int $post_id, string $description, int $tmdb_id ): int {
        if ( '' === $image_url ) {
            return 0;
        }

        self::ensure_media_dependencies_loaded();

        self::$current_tmdb_media_id = max( 0, $tmdb_id );
        add_filter( 'upload_dir', [ self::class, 'filter_upload_dir_tmdb' ] );

        $attachment_id = media_sideload_image( $image_url, $post_id, wp_strip_all_tags( $description ), 'id' );

        remove_filter( 'upload_dir', [ self::class, 'filter_upload_dir_tmdb' ] );
        self::$current_tmdb_media_id = 0;

        if ( is_wp_error( $attachment_id ) ) {
            return 0;
        }

        $attachment_id = (int) $attachment_id;

        self::ensure_movie_attachment_location( $attachment_id, $tmdb_id );

        return $attachment_id;
    }

    /**
     * Ensures movie attachments are stored within the TMDB upload subdirectory.
     */
    private static function ensure_movie_attachment_location( int $attachment_id, int $tmdb_id ): void {
        if ( $attachment_id <= 0 ) {
            return;
        }

        $attached_file = get_attached_file( $attachment_id );

        if ( ! $attached_file ) {
            return;
        }

        $attached_file = (string) $attached_file;

        if ( '' === $attached_file || ! file_exists( $attached_file ) ) {
            return;
        }

        $upload_dir = wp_upload_dir();

        if ( ! empty( $upload_dir['error'] ) ) {
            return;
        }

        $target_relative = self::build_movie_upload_subdir( $tmdb_id );
        $target_dir      = trailingslashit( $upload_dir['basedir'] ) . $target_relative;
        $current_dir     = trailingslashit( dirname( $attached_file ) );

        if ( untrailingslashit( $current_dir ) === untrailingslashit( $target_dir ) ) {
            return;
        }

        if ( ! wp_mkdir_p( $target_dir ) ) {
            return;
        }

        $filename   = wp_basename( $attached_file );
        $new_path   = trailingslashit( $target_dir ) . $filename;
        $source_dir = $current_dir;

        if ( ! self::move_file_to_destination( $attached_file, $new_path ) ) {
            return;
        }

        update_attached_file( $attachment_id, $new_path );

        $metadata = wp_get_attachment_metadata( $attachment_id );

        if ( is_array( $metadata ) ) {
            $metadata['file'] = trailingslashit( $target_relative ) . $filename;

            if ( isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
                foreach ( $metadata['sizes'] as $size_key => $size_data ) {
                    if ( ! is_array( $size_data ) || empty( $size_data['file'] ) ) {
                        continue;
                    }

                    $size_filename = (string) $size_data['file'];
                    $old_size_path = trailingslashit( $source_dir ) . $size_filename;
                    $new_size_path = trailingslashit( $target_dir ) . $size_filename;

                    if ( file_exists( $old_size_path ) ) {
                        self::move_file_to_destination( $old_size_path, $new_size_path );
                    }
                }
            }

            wp_update_attachment_metadata( $attachment_id, $metadata );
        }
    }

    /**
     * Builds the relative uploads path for a movie asset.
     */
    private static function build_movie_upload_subdir( int $tmdb_id ): string {
        $segments = [ self::TMDB_UPLOAD_SUBDIR, self::TMDB_MEDIA_CATEGORY ];

        if ( $tmdb_id > 0 ) {
            $segments[] = (string) $tmdb_id;
        }

        return implode( '/', $segments );
    }

    /**
     * Moves a file to the requested destination ensuring directories exist.
     */
    private static function move_file_to_destination( string $source, string $destination ): bool {
        if ( '' === $source || '' === $destination || ! file_exists( $source ) ) {
            return false;
        }

        $destination_dir = dirname( $destination );

        if ( ! is_dir( $destination_dir ) && ! wp_mkdir_p( $destination_dir ) ) {
            return false;
        }

        if ( file_exists( $destination ) ) {
            wp_delete_file( $destination );
        }

        if ( @rename( $source, $destination ) ) {
            return true;
        }

        if ( @copy( $source, $destination ) ) {
            wp_delete_file( $source );

            return true;
        }

        return false;
    }

    /**
     * Ensures WordPress media helper files are loaded before sideloading assets.
     */
    private static function ensure_media_dependencies_loaded(): void {
        static $loaded = false;

        if ( $loaded ) {
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $loaded = true;
    }

    /**
     * Adjusts the upload directory so TMDB media is stored in a dedicated folder.
     *
     * @param array<string, string> $dirs Upload directory configuration.
     *
     * @return array<string, string>
     */
    public static function filter_upload_dir_tmdb( array $dirs ): array {
        $path_segments = [ self::TMDB_UPLOAD_SUBDIR, self::TMDB_MEDIA_CATEGORY ];
        $tmdb_id       = max( 0, self::$current_tmdb_media_id );

        if ( $tmdb_id > 0 ) {
            $path_segments[] = (string) $tmdb_id;
        }

        $relative_path = implode( '/', $path_segments );

        $dirs['path']   = trailingslashit( $dirs['basedir'] ) . $relative_path;
        $dirs['url']    = trailingslashit( $dirs['baseurl'] ) . $relative_path;
        $dirs['subdir'] = '/' . $relative_path;

        if ( ! is_dir( $dirs['path'] ) ) {
            wp_mkdir_p( $dirs['path'] );
        }

        return $dirs;
    }

    /**
     * Builds a language query list for TMDB requests including optional fallbacks.
     */
    private static function build_language_list( string $primary, ?string $secondary = null, bool $include_null = false ): string {
        $languages = [];

        foreach ( [ $primary, $secondary, 'en-US', 'en' ] as $language ) {
            if ( null === $language ) {
                continue;
            }

            $language = sanitize_text_field( (string) $language );
            $language = trim( $language );

            if ( '' === $language ) {
                continue;
            }

            $languages[] = $language;
        }

        if ( $include_null ) {
            $languages[] = 'null';
        }

        $languages = array_values( array_unique( $languages ) );

        return implode( ',', $languages );
    }

    /**
     * Determines whether a fallback fetch is required to complete the movie payload.
     *
     * @param array<string, mixed> $movie_data Movie payload from TMDB.
     */
    private static function movie_requires_fallback_enrichment( array $movie_data ): bool {
        foreach ( self::REQUIRED_TRANSLATION_FIELDS as $field ) {
            if ( self::is_missing_string_field( $movie_data, $field ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Merges translated data from a fallback response into the primary payload.
     *
     * @param array<string, mixed> $primary_payload  Primary language payload.
     * @param array<string, mixed> $fallback_payload Fallback language payload.
     *
     * @return array<string, mixed>
     */
    private static function merge_movie_with_fallback( array $primary_payload, array $fallback_payload ): array {
        foreach ( self::FALLBACK_STRING_FIELDS as $field ) {
            if ( self::is_missing_string_field( $primary_payload, $field ) && ! self::is_missing_string_field( $fallback_payload, $field ) ) {
                $primary_payload[ $field ] = $fallback_payload[ $field ];
            }
        }

        if ( self::is_genre_list_missing( $primary_payload ) && ! self::is_genre_list_missing( $fallback_payload ) ) {
            $primary_payload['genres'] = $fallback_payload['genres'];
        }

        if ( self::is_keywords_missing( $primary_payload ) && ! self::is_keywords_missing( $fallback_payload ) ) {
            $primary_payload['keywords'] = $fallback_payload['keywords'];
        }

        if ( self::is_videos_missing( $primary_payload ) && ! self::is_videos_missing( $fallback_payload ) ) {
            $primary_payload['videos'] = $fallback_payload['videos'];
        }

        if ( self::is_websites_missing( $primary_payload ) && ! self::is_websites_missing( $fallback_payload ) ) {
            $primary_payload['websites'] = $fallback_payload['websites'];
        }

        if ( self::is_external_ids_missing( $primary_payload ) && ! self::is_external_ids_missing( $fallback_payload ) ) {
            $primary_payload['external_ids'] = $fallback_payload['external_ids'];
        }

        if ( self::is_cast_missing( $primary_payload ) && ! self::is_cast_missing( $fallback_payload ) ) {
            if ( ! isset( $primary_payload['credits'] ) || ! is_array( $primary_payload['credits'] ) ) {
                $primary_payload['credits'] = [];
            }

            $primary_payload['credits']['cast'] = $fallback_payload['credits']['cast'];
        }

        if ( self::is_crew_missing( $primary_payload ) && ! self::is_crew_missing( $fallback_payload ) ) {
            if ( ! isset( $primary_payload['credits'] ) || ! is_array( $primary_payload['credits'] ) ) {
                $primary_payload['credits'] = [];
            }

            $primary_payload['credits']['crew'] = $fallback_payload['credits']['crew'];
        }

        return $primary_payload;
    }

    /**
     * Checks if the provided payload is missing a translated string field.
     *
     * @param array<string, mixed> $payload Movie payload.
     */
    private static function is_missing_string_field( array $payload, string $field ): bool {
        if ( ! array_key_exists( $field, $payload ) ) {
            return true;
        }

        $value = $payload[ $field ];

        if ( null === $value ) {
            return true;
        }

        if ( is_string( $value ) ) {
            return '' === trim( $value );
        }

        return false;
    }

    /**
     * Determines if the genre list is missing or empty.
     *
     * @param array<string, mixed> $payload Movie payload.
     */
    private static function is_genre_list_missing( array $payload ): bool {
        if ( ! isset( $payload['genres'] ) || ! is_array( $payload['genres'] ) ) {
            return true;
        }

        foreach ( $payload['genres'] as $genre ) {
            if ( is_array( $genre ) && ! empty( $genre['name'] ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determines whether keywords are missing from the payload.
     *
     * @param array<string, mixed> $payload Movie payload.
     */
    private static function is_keywords_missing( array $payload ): bool {
        return empty( self::extract_keywords( $payload ) );
    }

    /**
     * Retrieves the keyword list from a payload.
     *
     * @param array<string, mixed> $payload Movie payload.
     *
     * @return array<int, mixed>
     */
    private static function extract_keywords( array $payload ): array {
        if ( ! isset( $payload['keywords'] ) || ! is_array( $payload['keywords'] ) ) {
            return [];
        }

        if ( isset( $payload['keywords']['keywords'] ) && is_array( $payload['keywords']['keywords'] ) ) {
            return array_values( array_filter( $payload['keywords']['keywords'], 'is_array' ) );
        }

        if ( isset( $payload['keywords']['results'] ) && is_array( $payload['keywords']['results'] ) ) {
            return array_values( array_filter( $payload['keywords']['results'], 'is_array' ) );
        }

        return [];
    }

    /**
     * Determines whether video results are missing from the payload.
     *
     * @param array<string, mixed> $payload Movie payload.
     */
    private static function is_videos_missing( array $payload ): bool {
        if ( ! isset( $payload['videos'] ) || ! is_array( $payload['videos'] ) ) {
            return true;
        }

        if ( ! isset( $payload['videos']['results'] ) || ! is_array( $payload['videos']['results'] ) ) {
            return true;
        }

        foreach ( $payload['videos']['results'] as $video ) {
            if ( is_array( $video ) && ! empty( $video['key'] ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determines whether website entries are missing from the payload.
     *
     * @param array<string, mixed> $payload Movie payload.
     */
    private static function is_websites_missing( array $payload ): bool {
        if ( ! isset( $payload['websites'] ) || ! is_array( $payload['websites'] ) ) {
            return true;
        }

        $websites = $payload['websites'];

        if ( isset( $websites['results'] ) && is_array( $websites['results'] ) ) {
            $websites = $websites['results'];
        }

        if ( ! is_array( $websites ) ) {
            return true;
        }

        foreach ( $websites as $website ) {
            if ( is_array( $website ) && ! empty( $website['url'] ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determines whether external IDs are missing from the payload.
     *
     * @param array<string, mixed> $payload Movie payload.
     */
    private static function is_external_ids_missing( array $payload ): bool {
        if ( ! isset( $payload['external_ids'] ) || ! is_array( $payload['external_ids'] ) ) {
            return true;
        }

        foreach ( $payload['external_ids'] as $value ) {
            if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determines whether cast credits are missing from the payload.
     *
     * @param array<string, mixed> $payload Movie payload.
     */
    private static function is_cast_missing( array $payload ): bool {
        if ( ! isset( $payload['credits'] ) || ! is_array( $payload['credits'] ) ) {
            return true;
        }

        if ( ! isset( $payload['credits']['cast'] ) || ! is_array( $payload['credits']['cast'] ) ) {
            return true;
        }

        foreach ( $payload['credits']['cast'] as $cast_member ) {
            if ( is_array( $cast_member ) && ! empty( $cast_member['name'] ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determines whether crew credits are missing from the payload.
     *
     * @param array<string, mixed> $payload Movie payload.
     */
    private static function is_crew_missing( array $payload ): bool {
        if ( ! isset( $payload['credits'] ) || ! is_array( $payload['credits'] ) ) {
            return true;
        }

        if ( ! isset( $payload['credits']['crew'] ) || ! is_array( $payload['credits']['crew'] ) ) {
            return true;
        }

        foreach ( $payload['credits']['crew'] as $crew_member ) {
            if ( is_array( $crew_member ) && ! empty( $crew_member['name'] ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Performs a GET request to the TMDB API.
     *
     * @param string               $url  Endpoint URL.
     * @param array<string, mixed> $args Query parameters.
     *
     * @return array<string, mixed>
     */
    private static function request_tmdb( string $url, array $args ): array {
        $response = wp_remote_get(
            add_query_arg( $args, $url ),
            [
                'timeout' => 20,
            ]
        );

        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
                'code'    => 500,
            ];
        }

        $status_code = (int) wp_remote_retrieve_response_code( $response );

        if ( 200 !== $status_code ) {
            $message = wp_remote_retrieve_response_message( $response );

            return [
                'success' => false,
                'message' => sprintf(
                    /* translators: 1: HTTP status code, 2: HTTP status message. */
                    __( 'TMDB returned an error (%1$s %2$s).', 'tmdb-plugin' ),
                    (string) $status_code,
                    $message ? $message : ''
                ),
                'code' => $status_code,
            ];
        }

        $body = wp_remote_retrieve_body( $response );

        if ( '' === $body ) {
            return [
                'success' => false,
                'message' => __( 'TMDB returned an empty response.', 'tmdb-plugin' ),
                'code'    => 500,
            ];
        }

        $decoded = json_decode( $body, true );

        if ( ! is_array( $decoded ) ) {
            return [
                'success' => false,
                'message' => __( 'Unable to decode TMDB response.', 'tmdb-plugin' ),
                'code'    => 500,
            ];
        }

        return [
            'success' => true,
            'data'    => $decoded,
        ];
    }

    /**
     * Builds a profile URL using the configured profile size.
     */
    private static function build_profile_url( string $profile_path ): string {
        $profile_path = ltrim( $profile_path, '/' );

        if ( '' === $profile_path ) {
            return '';
        }

        $size = self::get_configured_profile_size();

        if ( 'original' === $size ) {
            return trailingslashit( self::ORIGINAL_IMAGE_BASE_URL ) . $profile_path;
        }

        return trailingslashit( self::POSTER_BASE_URL . $size ) . $profile_path;
    }

    /**
     * Builds a poster URL using the configured poster size.
     */
    private static function build_poster_url( string $poster_path ): string {
        $poster_path = ltrim( $poster_path, '/' );

        if ( '' === $poster_path ) {
            return '';
        }

        $size = self::get_configured_poster_size();

        if ( 'original' === $size ) {
            return trailingslashit( self::ORIGINAL_IMAGE_BASE_URL ) . $poster_path;
        }

        return trailingslashit( self::POSTER_BASE_URL . $size ) . $poster_path;
    }

    /**
     * Builds a backdrop URL using the configured gallery image size.
     */
    private static function build_backdrop_url( string $backdrop_path ): string {
        $backdrop_path = ltrim( $backdrop_path, '/' );

        if ( '' === $backdrop_path ) {
            return '';
        }

        $size = self::get_configured_backdrop_size();

        if ( 'original' === $size ) {
            return trailingslashit( self::ORIGINAL_IMAGE_BASE_URL ) . $backdrop_path;
        }

        return trailingslashit( self::POSTER_BASE_URL . $size ) . $backdrop_path;
    }

    /**
     * Returns the poster size configured in plugin settings.
     */
    private static function get_configured_poster_size(): string {
        $sizes      = TMDB_Admin_Page_Config::get_poster_sizes();
        $configured = sanitize_text_field( (string) get_option( 'tmdb_plugin_poster_size', TMDB_Admin_Page_Config::DEFAULT_POSTER_SIZE ) );

        if ( isset( $sizes[ $configured ] ) ) {
            return $configured;
        }

        return TMDB_Admin_Page_Config::DEFAULT_POSTER_SIZE;
    }

    /**
     * Returns the configured profile image size.
     */
    private static function get_configured_profile_size(): string {
        $sizes      = TMDB_Admin_Page_Config::get_profile_sizes();
        $configured = sanitize_text_field( (string) get_option( 'tmdb_plugin_profile_size', TMDB_Admin_Page_Config::DEFAULT_PROFILE_SIZE ) );

        if ( isset( $sizes[ $configured ] ) ) {
            return $configured;
        }

        return TMDB_Admin_Page_Config::DEFAULT_PROFILE_SIZE;
    }

    /**
     * Returns the configured gallery image size.
     */
    private static function get_configured_backdrop_size(): string {
        $sizes      = TMDB_Admin_Page_Config::get_backdrop_sizes();
        $configured = sanitize_text_field( (string) get_option( 'tmdb_plugin_backdrop_size', TMDB_Admin_Page_Config::DEFAULT_BACKDROP_SIZE ) );

        if ( isset( $sizes[ $configured ] ) ) {
            return $configured;
        }

        return TMDB_Admin_Page_Config::DEFAULT_BACKDROP_SIZE;
    }

    /**
     * Returns the number of gallery images configured by the user.
     */
    private static function get_configured_gallery_image_count(): int {
        $count = (int) get_option( 'tmdb_plugin_gallery_image_count', TMDB_Admin_Page_Config::DEFAULT_GALLERY_IMAGE_COUNT );

        if ( $count < 0 ) {
            return TMDB_Admin_Page_Config::DEFAULT_GALLERY_IMAGE_COUNT;
        }

        if ( $count > TMDB_Admin_Page_Config::MAX_GALLERY_IMAGE_COUNT ) {
            return TMDB_Admin_Page_Config::MAX_GALLERY_IMAGE_COUNT;
        }

        return $count;
    }

    /**
     * Returns the existing post ID for a TMDB movie when available.
     */
    private static function get_existing_movie_post_id( int $tmdb_id ): int {
        static $cache = [];

        if ( $tmdb_id <= 0 ) {
            return 0;
        }

        if ( isset( $cache[ $tmdb_id ] ) ) {
            return $cache[ $tmdb_id ];
        }

        $existing = get_posts(
            [
                'post_type'      => 'movie',
                'posts_per_page' => 1,
                'post_status'    => [ 'publish', 'draft', 'pending', 'future', 'private' ],
                'meta_query'     => [
                    [
                        'key'   => 'TMDB_id',
                        'value' => $tmdb_id,
                    ],
                ],
                'fields'         => 'ids',
            ]
        );

        $cache[ $tmdb_id ] = ! empty( $existing ) ? (int) $existing[0] : 0;

        return $cache[ $tmdb_id ];
    }

    /**
     * Retrieves the current search query from the request.
     */
    private static function get_initial_query(): string {
        if ( isset( $_REQUEST['query'] ) ) {
            return sanitize_text_field( wp_unslash( $_REQUEST['query'] ) );
        }

        return '';
    }
}
