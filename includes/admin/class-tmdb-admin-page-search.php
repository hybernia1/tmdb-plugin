<?php
/**
 * TMDB universal API admin page.
 *
 * @package TMDBPlugin\Admin
 */

namespace TMDB\Plugin\Admin;

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
     * Cached nonce shared between the rendered page and AJAX handlers.
     *
     * @var string
     */
    private static string $nonce = '';

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
     * Enqueues JavaScript assets for the admin page.
     *
     * @param string $hook Current admin page hook suffix.
     */
    public static function enqueue_assets( string $hook ): void {
        if ( 'tmdb-plugin_page_' . self::MENU_SLUG !== $hook ) {
            return;
        }

        wp_enqueue_script(
            'tmdb-plugin-search',
            plugins_url( 'includes/admin/js/search.js', TMDB_PLUGIN_FILE ),
            [],
            TMDB_PLUGIN_VERSION,
            true
        );

        wp_localize_script(
            'tmdb-plugin-search',
            'tmdbPluginSearch',
            [
                'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
                'nonce'        => self::get_nonce(),
                'imageBaseUrl' => self::IMAGE_BASE_URL,
                'hasApiKey'    => '' !== sanitize_text_field( (string) get_option( 'tmdb_plugin_api_key', '' ) ),
                'initialQuery' => self::get_initial_query(),
                'strings'      => [
                    'missingQuery'      => __( 'Please enter a movie title to search.', 'tmdb-plugin' ),
                    'missingApiKey'     => __( 'Please configure your TMDB API key before searching.', 'tmdb-plugin' ),
                    'searching'         => __( 'Searching TMDB…', 'tmdb-plugin' ),
                    'noResults'         => __( 'No movies matched your search.', 'tmdb-plugin' ),
                    'unexpected'        => __( 'An unexpected error occurred. Please try again.', 'tmdb-plugin' ),
                    'import'            => __( 'Import movie', 'tmdb-plugin' ),
                    'importing'         => __( 'Importing…', 'tmdb-plugin' ),
                    'importSuccess'     => __( 'Movie imported successfully.', 'tmdb-plugin' ),
                    'importError'       => __( 'Unable to import the selected movie.', 'tmdb-plugin' ),
                    'paginationPrevious'=> __( 'Previous', 'tmdb-plugin' ),
                    'paginationNext'    => __( 'Next', 'tmdb-plugin' ),
                    'fallbackNotice'    => __( 'Results shown using the fallback language.', 'tmdb-plugin' ),
                    'posterAlt'         => __( 'Poster for %s', 'tmdb-plugin' ),
                    'votesLabel'        => __( 'votes', 'tmdb-plugin' ),
                ],
            ]
        );
    }

    /**
     * Renders the admin page markup.
     */
    public static function render(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $api_key       = sanitize_text_field( (string) get_option( 'tmdb_plugin_api_key', '' ) );
        $has_api_key   = '' !== $api_key;
        $initial_query = self::get_initial_query();
        $action_url    = menu_page_url( self::MENU_SLUG, false );

        if ( ! $action_url ) {
            $action_url = add_query_arg( 'page', self::MENU_SLUG, admin_url( 'admin.php' ) );
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

            <div id="tmdb-plugin-search-status" class="tmdb-plugin-search__status" data-has-api-key="<?php echo $has_api_key ? '1' : '0'; ?>" aria-live="polite"></div>

            <ul id="tmdb-plugin-search-results" class="tmdb-plugin-search__results"></ul>

            <nav id="tmdb-plugin-search-pagination" class="tmdb-plugin-search__pagination" aria-label="<?php echo esc_attr__( 'Movie results pagination', 'tmdb-plugin' ); ?>"></nav>

            <noscript>
                <p><?php esc_html_e( 'JavaScript is required to use the TMDB search interface.', 'tmdb-plugin' ); ?></p>
            </noscript>
        </div>
        <?php
    }

    /**
     * Handles the AJAX request for searching TMDB movies.
     */
    public static function handle_search(): void {
        check_ajax_referer( 'tmdb_plugin_api_search', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error(
                [ 'message' => __( 'You do not have permission to perform this request.', 'tmdb-plugin' ) ],
                403
            );
        }

        $query = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';
        $page  = isset( $_POST['page'] ) ? max( 1, (int) $_POST['page'] ) : 1;

        if ( '' === $query ) {
            wp_send_json_error(
                [ 'message' => __( 'Please provide a search term.', 'tmdb-plugin' ) ],
                400
            );
        }

        $api_key = sanitize_text_field( (string) get_option( 'tmdb_plugin_api_key', '' ) );

        if ( '' === $api_key ) {
            wp_send_json_error(
                [ 'message' => __( 'TMDB API key is missing. Update the configuration settings first.', 'tmdb-plugin' ) ],
                400
            );
        }

        $language          = sanitize_text_field( (string) get_option( 'tmdb_plugin_language', 'en-US' ) );
        $fallback_language = sanitize_text_field( (string) get_option( 'tmdb_plugin_fallback_language', 'en-US' ) );

        $primary = self::perform_movie_search( $query, $page, $language, $api_key );

        if ( ! $primary['success'] ) {
            wp_send_json_error(
                [ 'message' => $primary['message'] ],
                $primary['code'] ?? 500
            );
        }

        $results       = $primary['results'];
        $total_pages   = $primary['total_pages'];
        $language_used = $language;
        $used_fallback = false;
        $page_used     = $primary['page'];

        if ( empty( $results ) && $fallback_language !== $language ) {
            $fallback = self::perform_movie_search( $query, 1, $fallback_language, $api_key );

            if ( ! $fallback['success'] ) {
                wp_send_json_error(
                    [ 'message' => $fallback['message'] ],
                    $fallback['code'] ?? 500
                );
            }

            if ( ! empty( $fallback['results'] ) ) {
                $results       = $fallback['results'];
                $total_pages   = $fallback['total_pages'];
                $language_used = $fallback_language;
                $used_fallback = true;
                $page_used     = $fallback['page'];
            }
        }

        wp_send_json_success(
            [
                'results'      => $results,
                'totalPages'   => $total_pages,
                'page'         => $page_used,
                'language'     => $language_used,
                'usedFallback' => $used_fallback,
            ]
        );
    }

    /**
     * Handles the AJAX request for importing a movie into WordPress.
     */
    public static function handle_import(): void {
        check_ajax_referer( 'tmdb_plugin_api_search', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error(
                [ 'message' => __( 'You do not have permission to perform this request.', 'tmdb-plugin' ) ],
                403
            );
        }

        $movie_id = isset( $_POST['movieId'] ) ? absint( $_POST['movieId'] ) : 0;

        if ( $movie_id <= 0 ) {
            wp_send_json_error(
                [ 'message' => __( 'Invalid movie identifier.', 'tmdb-plugin' ) ],
                400
            );
        }

        $api_key = sanitize_text_field( (string) get_option( 'tmdb_plugin_api_key', '' ) );

        if ( '' === $api_key ) {
            wp_send_json_error(
                [ 'message' => __( 'TMDB API key is missing. Update the configuration settings first.', 'tmdb-plugin' ) ],
                400
            );
        }

        $language          = sanitize_text_field( (string) get_option( 'tmdb_plugin_language', 'en-US' ) );
        $fallback_language = sanitize_text_field( (string) get_option( 'tmdb_plugin_fallback_language', 'en-US' ) );

        $movie_response = self::fetch_movie_details( $movie_id, $language, $api_key );

        if ( ! $movie_response['success'] && $fallback_language !== $language ) {
            $movie_response = self::fetch_movie_details( $movie_id, $fallback_language, $api_key );
        }

        if ( ! $movie_response['success'] ) {
            wp_send_json_error(
                [ 'message' => $movie_response['message'] ],
                $movie_response['code'] ?? 500
            );
        }

        $import = self::import_movie( $movie_response['movie'], $movie_response['language'] );

        if ( is_wp_error( $import ) ) {
            wp_send_json_error(
                [ 'message' => $import->get_error_message() ],
                500
            );
        }

        wp_send_json_success(
            [
                'postId'   => $import['post_id'],
                'message'  => __( 'Movie imported successfully.', 'tmdb-plugin' ),
                'language' => $movie_response['language'],
            ]
        );
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

        $cast_info = self::import_cast( isset( $movie_data['credits']['cast'] ) && is_array( $movie_data['credits']['cast'] ) ? $movie_data['credits']['cast'] : [] );
        $crew_info = self::import_crew( isset( $movie_data['credits']['crew'] ) && is_array( $movie_data['credits']['crew'] ) ? $movie_data['credits']['crew'] : [] );
        $genre_ids = self::import_genres( isset( $movie_data['genres'] ) && is_array( $movie_data['genres'] ) ? $movie_data['genres'] : [] );

        update_post_meta( $post_id, 'TMDB_actor_ids', $cast_info['actor_ids'] );
        update_post_meta( $post_id, 'TMDB_cast', $cast_info['cast'] );
        update_post_meta( $post_id, 'TMDB_director_ids', $crew_info['director_ids'] );
        update_post_meta( $post_id, 'TMDB_directors', $crew_info['directors'] );
        update_post_meta( $post_id, 'TMDB_genre_ids', $genre_ids['genre_ids'] );
        update_post_meta( $post_id, 'TMDB_genres', $genre_ids['genres'] );

        return [
            'post_id' => $post_id,
        ];
    }

    /**
     * Imports cast members as actor posts.
     *
     * @param array<int, array<string, mixed>> $cast Cast members.
     *
     * @return array<string, array<int, mixed>>
     */
    private static function import_cast( array $cast ): array {
        $stored_cast = [];
        $actor_ids   = [];

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
            $post_id    = self::upsert_related_post( 'actor', $actor_id, $actor_name );

            if ( $post_id ) {
                $actor_ids[] = $post_id;
            }

            $stored_cast[] = [
                'name'      => $actor_name,
                'character' => isset( $member['character'] ) ? sanitize_text_field( $member['character'] ) : '',
                'order'     => isset( $member['order'] ) ? (int) $member['order'] : 0,
            ];
        }

        return [
            'actor_ids' => array_map( 'intval', array_unique( $actor_ids ) ),
            'cast'      => $stored_cast,
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
        $directors    = [];
        $director_ids = [];

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
            $post_id     = self::upsert_related_post( 'director', $director_id, $name );

            if ( $post_id ) {
                $director_ids[] = $post_id;
            }

            $directors[] = [
                'name' => $name,
                'job'  => $job,
            ];
        }

        return [
            'director_ids' => array_map( 'intval', array_unique( $director_ids ) ),
            'directors'    => $directors,
        ];
    }

    /**
     * Imports TMDB genres as WordPress posts.
     *
     * @param array<int, array<string, mixed>> $genres Genres payload.
     *
     * @return array<string, array<int, mixed>>
     */
    private static function import_genres( array $genres ): array {
        $genre_ids = [];
        $stored    = [];

        foreach ( $genres as $genre ) {
            if ( ! is_array( $genre ) || empty( $genre['name'] ) ) {
                continue;
            }

            $name     = sanitize_text_field( $genre['name'] );
            $tmdb_id  = isset( $genre['id'] ) ? (int) $genre['id'] : 0;
            $post_id  = self::upsert_related_post( 'genre', $tmdb_id, $name );

            if ( $post_id ) {
                $genre_ids[] = $post_id;
            }

            $stored[] = [
                'name' => $name,
            ];
        }

        return [
            'genre_ids' => array_map( 'intval', array_unique( $genre_ids ) ),
            'genres'    => $stored,
        ];
    }

    /**
     * Creates or updates a related post (actor, genre, director).
     *
     * @param string $post_type Related post type.
     * @param int    $tmdb_id   TMDB identifier.
     * @param string $title     Post title.
     */
    private static function upsert_related_post( string $post_type, int $tmdb_id, string $title ): int {
        if ( '' === $title ) {
            return 0;
        }

        $existing = [];

        if ( $tmdb_id > 0 ) {
            $existing = get_posts(
                [
                    'post_type'      => $post_type,
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
        }

        $post_data = [
            'post_title'  => wp_slash( $title ),
            'post_type'   => $post_type,
            'post_status' => 'publish',
        ];

        if ( ! empty( $existing ) ) {
            $post_data['ID'] = (int) $existing[0];
            $post_id         = wp_update_post( $post_data, true );
        } else {
            $post_id = wp_insert_post( $post_data, true );
        }

        if ( is_wp_error( $post_id ) ) {
            return 0;
        }

        $post_id = (int) $post_id;

        if ( $tmdb_id > 0 ) {
            update_post_meta( $post_id, 'TMDB_id', $tmdb_id );
        }

        return $post_id;
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
        return isset( $_GET['query'] ) ? sanitize_text_field( wp_unslash( $_GET['query'] ) ) : '';
    }

    /**
     * Retrieves or creates the nonce used by the admin page and AJAX handlers.
     */
    private static function get_nonce(): string {
        if ( '' === self::$nonce ) {
            self::$nonce = wp_create_nonce( 'tmdb_plugin_api_search' );
        }

        return self::$nonce;
    }
}
