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
    private const MENU_SLUG              = 'tmdb-plugin-search';
    private const IMAGE_BASE_URL         = 'https://image.tmdb.org/t/p/w185';
    private const ORIGINAL_IMAGE_BASE_URL = 'https://image.tmdb.org/t/p/original';

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
                                        src="<?php echo esc_url( trailingslashit( self::IMAGE_BASE_URL ) . ltrim( $result['poster_path'], '/' ) ); ?>"
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
                                        <button type="submit" class="button button-secondary">
                                            <?php esc_html_e( 'Import movie', 'tmdb-plugin' ); ?>
                                        </button>
                                    </form>
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

        $movie_response = self::fetch_movie_details( $movie_id, $language, $api_key );

        if ( ! $movie_response['success'] && $fallback_language !== $language ) {
            $movie_response = self::fetch_movie_details( $movie_id, $fallback_language, $api_key );
        }

        if ( ! $movie_response['success'] ) {
            return [
                'type'    => 'error',
                'message' => $movie_response['message'] ?? __( 'Unable to import the selected movie.', 'tmdb-plugin' ),
            ];
        }

        $import = self::import_movie( $movie_response['movie'], $movie_response['language'] );

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

            $formatted[] = [
                'id'             => (int) $result['id'],
                'title'          => isset( $result['title'] ) ? sanitize_text_field( $result['title'] ) : '',
                'original_title' => isset( $result['original_title'] ) ? sanitize_text_field( $result['original_title'] ) : '',
                'overview'       => isset( $result['overview'] ) ? wp_trim_words( wp_strip_all_tags( $result['overview'] ), 40 ) : '',
                'release_date'   => isset( $result['release_date'] ) ? sanitize_text_field( $result['release_date'] ) : '',
                'vote_average'   => isset( $result['vote_average'] ) ? (float) $result['vote_average'] : 0,
                'vote_count'     => $vote_count,
                'poster_path'    => isset( $result['poster_path'] ) ? sanitize_text_field( ltrim( (string) $result['poster_path'], '/' ) ) : '',
                'language'       => isset( $result['original_language'] ) ? sanitize_text_field( $result['original_language'] ) : '',
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
     * @param int    $movie_id Movie identifier.
     * @param string $language Requested language.
     * @param string $api_key  TMDB API key.
     *
     * @return array<string, mixed>
     */
    private static function fetch_movie_details( int $movie_id, string $language, string $api_key ): array {
        $response = self::request_tmdb(
            sprintf( 'https://api.themoviedb.org/3/movie/%d', $movie_id ),
            [
                'api_key'            => $api_key,
                'language'           => $language,
                'append_to_response' => 'credits',
            ]
        );

        if ( ! $response['success'] ) {
            return $response;
        }

        return [
            'success'  => true,
            'movie'    => $response['data'],
            'language' => $language,
        ];
    }

    /**
     * Imports a movie and its related entities into WordPress.
     *
     * @param array<string, mixed> $movie_data Movie payload retrieved from TMDB.
     * @param string               $language   Language used for the payload.
     *
     * @return array<string, int>|\WP_Error
     */
    private static function import_movie( array $movie_data, string $language ) {
        $movie_id = isset( $movie_data['id'] ) ? (int) $movie_data['id'] : 0;

        if ( $movie_id <= 0 ) {
            return new \WP_Error( 'tmdb_import_invalid_movie', __( 'TMDB movie data is missing an identifier.', 'tmdb-plugin' ) );
        }

        $title   = isset( $movie_data['title'] ) ? sanitize_text_field( $movie_data['title'] ) : '';
        $content = isset( $movie_data['overview'] ) ? wp_kses_post( $movie_data['overview'] ) : '';

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
        update_post_meta( $post_id, 'TMDB_original_title', isset( $movie_data['original_title'] ) ? sanitize_text_field( $movie_data['original_title'] ) : '' );
        update_post_meta( $post_id, 'TMDB_tagline', isset( $movie_data['tagline'] ) ? sanitize_text_field( $movie_data['tagline'] ) : '' );
        update_post_meta( $post_id, 'TMDB_release_date', isset( $movie_data['release_date'] ) ? sanitize_text_field( $movie_data['release_date'] ) : '' );
        update_post_meta( $post_id, 'TMDB_runtime', isset( $movie_data['runtime'] ) ? (int) $movie_data['runtime'] : 0 );
        update_post_meta( $post_id, 'TMDB_vote_average', isset( $movie_data['vote_average'] ) ? (float) $movie_data['vote_average'] : 0 );
        update_post_meta( $post_id, 'TMDB_vote_count', isset( $movie_data['vote_count'] ) ? (int) $movie_data['vote_count'] : 0 );
        update_post_meta( $post_id, 'TMDB_homepage', isset( $movie_data['homepage'] ) ? esc_url_raw( $movie_data['homepage'] ) : '' );
        update_post_meta( $post_id, 'TMDB_status', isset( $movie_data['status'] ) ? sanitize_text_field( $movie_data['status'] ) : '' );

        $poster_path          = isset( $movie_data['poster_path'] ) ? sanitize_text_field( ltrim( (string) $movie_data['poster_path'], '/' ) ) : '';
        $previous_poster_path = (string) get_post_meta( $post_id, 'TMDB_poster_path', true );

        if ( '' !== $poster_path ) {
            self::set_featured_image( $post_id, $poster_path, $title, $previous_poster_path );
        }

        update_post_meta( $post_id, 'TMDB_poster_path', $poster_path );

        $cast_info  = self::import_cast( isset( $movie_data['credits']['cast'] ) && is_array( $movie_data['credits']['cast'] ) ? $movie_data['credits']['cast'] : [] );
        $crew_info  = self::import_crew( isset( $movie_data['credits']['crew'] ) && is_array( $movie_data['credits']['crew'] ) ? $movie_data['credits']['crew'] : [] );
        $genre_info = self::import_genres( isset( $movie_data['genres'] ) && is_array( $movie_data['genres'] ) ? $movie_data['genres'] : [] );

        wp_set_object_terms( $post_id, $cast_info['term_ids'], TMDB_Taxonomies::ACTOR, false );
        wp_set_object_terms( $post_id, $crew_info['term_ids'], TMDB_Taxonomies::DIRECTOR, false );
        wp_set_object_terms( $post_id, $genre_info['term_ids'], TMDB_Taxonomies::GENRE, false );

        update_post_meta( $post_id, 'TMDB_actor_ids', $cast_info['term_ids'] );
        update_post_meta( $post_id, 'TMDB_cast', $cast_info['cast'] );
        update_post_meta( $post_id, 'TMDB_director_ids', $crew_info['term_ids'] );
        update_post_meta( $post_id, 'TMDB_directors', $crew_info['directors'] );
        update_post_meta( $post_id, 'TMDB_genre_ids', $genre_info['term_ids'] );
        update_post_meta( $post_id, 'TMDB_genres', $genre_info['genres'] );

        return [
            'post_id' => $post_id,
        ];
    }

    /**
     * Imports cast members as actor taxonomy terms.
     *
     * @param array<int, array<string, mixed>> $cast Cast members.
     *
     * @return array<string, array<int, mixed>>
     */
    private static function import_cast( array $cast ): array {
        $stored_cast = [];
        $term_ids    = [];

        usort(
            $cast,
            static function ( array $a, array $b ): int {
                return ( $a['order'] ?? 0 ) <=> ( $b['order'] ?? 0 );
            }
        );

        foreach ( array_slice( $cast, 0, 10 ) as $member ) {
            if ( ! is_array( $member ) || empty( $member['name'] ) ) {
                continue;
            }

            $actor_name = sanitize_text_field( $member['name'] );
            $actor_id   = isset( $member['id'] ) ? (int) $member['id'] : 0;
            $term_id    = self::upsert_related_term( TMDB_Taxonomies::ACTOR, $actor_id, $actor_name );

            if ( $term_id ) {
                $term_ids[] = $term_id;
            }

            $stored_cast[] = [
                'name'      => $actor_name,
                'character' => isset( $member['character'] ) ? sanitize_text_field( $member['character'] ) : '',
                'order'     => isset( $member['order'] ) ? (int) $member['order'] : 0,
            ];
        }

        return [
            'term_ids' => array_map( 'intval', array_unique( $term_ids ) ),
            'cast'     => $stored_cast,
        ];
    }

    /**
     * Imports crew members focusing on directors.
     *
     * @param array<int, array<string, mixed>> $crew Crew members.
     *
     * @return array<string, array<int, mixed>>
     */
    private static function import_crew( array $crew ): array {
        $directors = [];
        $term_ids  = [];

        foreach ( $crew as $member ) {
            if ( ! is_array( $member ) ) {
                continue;
            }

            $job = isset( $member['job'] ) ? sanitize_text_field( $member['job'] ) : '';

            if ( 'Director' !== $job ) {
                continue;
            }

            if ( empty( $member['name'] ) ) {
                continue;
            }

            $name        = sanitize_text_field( $member['name'] );
            $director_id = isset( $member['id'] ) ? (int) $member['id'] : 0;
            $term_id     = self::upsert_related_term( TMDB_Taxonomies::DIRECTOR, $director_id, $name );

            if ( $term_id ) {
                $term_ids[] = $term_id;
            }

            $directors[] = [
                'name' => $name,
                'job'  => $job,
            ];
        }

        return [
            'term_ids'  => array_map( 'intval', array_unique( $term_ids ) ),
            'directors' => $directors,
        ];
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

        $term_id = 0;

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
            }
        }

        if ( 0 === $term_id ) {
            $existing_by_name = term_exists( $name, $taxonomy );

            if ( $existing_by_name && ! is_wp_error( $existing_by_name ) ) {
                $term_id = (int) ( is_array( $existing_by_name ) ? $existing_by_name['term_id'] : $existing_by_name );
            }
        }

        if ( 0 === $term_id ) {
            $created = wp_insert_term( $name, $taxonomy );

            if ( is_wp_error( $created ) ) {
                return 0;
            }

            $term_id = (int) $created['term_id'];
        } else {
            $updated = wp_update_term( $term_id, $taxonomy, [ 'name' => $name ] );

            if ( ! is_wp_error( $updated ) && isset( $updated['term_id'] ) ) {
                $term_id = (int) $updated['term_id'];
            }
        }

        if ( $tmdb_id > 0 ) {
            update_term_meta( $term_id, 'TMDB_id', $tmdb_id );
        }

        return $term_id;
    }

    /**
     * Sets the featured image for a movie post based on the TMDB poster.
     *
     * @param int         $post_id        WordPress post ID.
     * @param string      $poster_path    TMDB poster path.
     * @param string      $title          Movie title.
     * @param string|null $existing_path  Previously stored TMDB poster path.
     */
    private static function set_featured_image( int $post_id, string $poster_path, string $title, ?string $existing_path = null ): void {
        if ( null === $existing_path ) {
            $existing_path = (string) get_post_meta( $post_id, 'TMDB_poster_path', true );
        }

        if ( $existing_path === $poster_path && has_post_thumbnail( $post_id ) ) {
            return;
        }

        $poster_url = trailingslashit( self::ORIGINAL_IMAGE_BASE_URL ) . ltrim( $poster_path, '/' );

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachment_id = media_sideload_image( $poster_url, $post_id, wp_strip_all_tags( $title ), 'id' );

        if ( is_wp_error( $attachment_id ) ) {
            return;
        }

        set_post_thumbnail( $post_id, (int) $attachment_id );
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
     * Retrieves the current search query from the request.
     */
    private static function get_initial_query(): string {
        if ( isset( $_REQUEST['query'] ) ) {
            return sanitize_text_field( wp_unslash( $_REQUEST['query'] ) );
        }

        return '';
    }
}
