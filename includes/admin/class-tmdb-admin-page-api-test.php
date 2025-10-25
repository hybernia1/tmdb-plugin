<?php
/**
 * TMDB API test admin page.
 *
 * @package TMDBPlugin\Admin
 */

namespace TMDB\Plugin\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin page for testing TMDB API connectivity.
 */
class TMDB_Admin_Page_Api_Test {
    /**
     * Menu slug for the API test page.
     */
    private const MENU_SLUG = 'tmdb-plugin-api-test';

    /**
     * Registers the API test submenu page.
     */
    public static function register(): void {
        add_submenu_page(
            TMDB_PLUGIN_SLUG,
            __( 'TMDB API Test', 'tmdb-plugin' ),
            __( 'API Test', 'tmdb-plugin' ),
            'manage_options',
            self::MENU_SLUG,
            [ self::class, 'render' ]
        );
    }

    /**
     * Enqueues scripts for the API test page.
     *
     * @param string $hook Current admin page hook suffix.
     */
    public static function enqueue_assets( string $hook ): void {
        if ( 'tmdb-plugin_page_' . self::MENU_SLUG !== $hook ) {
            return;
        }

        wp_enqueue_script(
            'tmdb-plugin-api-test',
            plugins_url( 'includes/admin/js/api-test.js', TMDB_PLUGIN_FILE ),
            [],
            TMDB_PLUGIN_VERSION,
            true
        );

        wp_localize_script(
            'tmdb-plugin-api-test',
            'tmdbPluginApiTest',
            [
                'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
                'nonce'        => wp_create_nonce( 'tmdb_plugin_api_test' ),
                'imageBaseUrl' => 'https://image.tmdb.org/t/p/w92',
                'strings'      => [
                    'noResults'      => __( 'No results found for your query.', 'tmdb-plugin' ),
                    'missingQuery'   => __( 'Please enter a search term before testing the API.', 'tmdb-plugin' ),
                    'fetching'       => __( 'Contacting TMDB…', 'tmdb-plugin' ),
                    'unexpected'     => __( 'An unexpected error occurred. Please try again.', 'tmdb-plugin' ),
                    'missingApiKey'  => __( 'Please provide an API key in the TMDB configuration settings.', 'tmdb-plugin' ),
                    'fallbackNotice' => __( 'Results shown using the fallback language.', 'tmdb-plugin' ),
                    'fallbackNoticeLanguage' => __( 'Results shown using the fallback language (%s).', 'tmdb-plugin' ),
                ],
            ]
        );
    }

    /**
     * Handles the AJAX request that performs the API search.
     */
    public static function handle_search(): void {
        check_ajax_referer( 'tmdb_plugin_api_test', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error(
                [ 'message' => __( 'You do not have permission to perform this request.', 'tmdb-plugin' ) ],
                403
            );
        }

        $query = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';

        if ( '' === $query ) {
            wp_send_json_error(
                [ 'message' => __( 'Please enter a search query.', 'tmdb-plugin' ) ],
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

        $primary_response = self::perform_search_request( $query, $api_key, $language );

        if ( isset( $primary_response['error'] ) ) {
            wp_send_json_error(
                [ 'message' => $primary_response['error'] ],
                $primary_response['code'] ?? 500
            );
        }

        $results        = $primary_response['results'];
        $language_used  = $language;
        $used_fallback  = false;

        if ( empty( $results ) && $fallback_language !== $language ) {
            $fallback_response = self::perform_search_request( $query, $api_key, $fallback_language );

            if ( isset( $fallback_response['error'] ) ) {
                wp_send_json_error(
                    [ 'message' => $fallback_response['error'] ],
                    $fallback_response['code'] ?? 500
                );
            }

            if ( ! empty( $fallback_response['results'] ) ) {
                $results       = $fallback_response['results'];
                $language_used = $fallback_language;
                $used_fallback = true;
            }
        }

        wp_send_json_success(
            [
                'results'      => $results,
                'language'     => $language_used,
                'usedFallback' => $used_fallback,
            ]
        );
    }

    /**
     * Renders the API test admin page.
     */
    public static function render(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $api_key     = sanitize_text_field( (string) get_option( 'tmdb_plugin_api_key', '' ) );
        $has_api_key = '' !== $api_key;
        ?>
        <div class="wrap tmdb-plugin tmdb-plugin__api-test">
            <h1><?php esc_html_e( 'TMDB API Test', 'tmdb-plugin' ); ?></h1>
            <p class="description">
                <?php esc_html_e( 'Use this tool to verify that your TMDB credentials work and preview basic search results without reloading the page.', 'tmdb-plugin' ); ?>
            </p>

            <?php if ( ! $has_api_key ) : ?>
                <div class="notice notice-warning">
                    <p>
                        <?php esc_html_e( 'Provide a valid TMDB API key on the configuration page before running a test.', 'tmdb-plugin' ); ?>
                    </p>
                </div>
            <?php endif; ?>

            <form id="tmdb-plugin-api-test-form">
                <label for="tmdb-plugin-api-test-query" class="screen-reader-text">
                    <?php esc_html_e( 'Search query', 'tmdb-plugin' ); ?>
                </label>
                <input
                    type="text"
                    name="query"
                    id="tmdb-plugin-api-test-query"
                    class="regular-text"
                    placeholder="<?php echo esc_attr__( 'Search for a movie, series, or person…', 'tmdb-plugin' ); ?>"
                    autocomplete="off"
                />
                <button type="submit" class="button button-primary">
                    <?php esc_html_e( 'Run Test', 'tmdb-plugin' ); ?>
                </button>
            </form>

            <div
                id="tmdb-plugin-api-test-status"
                class="tmdb-plugin-api-test__status"
                data-has-api-key="<?php echo $has_api_key ? '1' : '0'; ?>"
                aria-live="polite"
            ></div>
            <ul id="tmdb-plugin-api-test-results" class="tmdb-plugin-api-test__results"></ul>
        </div>
        <?php
    }

    /**
     * Performs the TMDB search request.
     *
     * @param string $query    Search query.
     * @param string $api_key  TMDB API key.
     * @param string $language Language code.
     *
     * @return array<string, mixed>
     */
    private static function perform_search_request( string $query, string $api_key, string $language ): array {
        $url = add_query_arg(
            [
                'api_key'       => $api_key,
                'language'      => $language,
                'query'         => $query,
                'include_adult' => 'false',
                'page'          => 1,
            ],
            'https://api.themoviedb.org/3/search/multi'
        );

        $response = wp_remote_get(
            $url,
            [
                'timeout' => 15,
            ]
        );

        if ( is_wp_error( $response ) ) {
            return [
                'error' => $response->get_error_message(),
            ];
        }

        $status_code = wp_remote_retrieve_response_code( $response );

        if ( 200 !== $status_code ) {
            $message = wp_remote_retrieve_response_message( $response );

            return [
                'error' => sprintf(
                    /* translators: 1: HTTP status code, 2: HTTP status message. */
                    __( 'TMDB returned an error (%1$s %2$s).', 'tmdb-plugin' ),
                    (string) $status_code,
                    $message ? $message : ''
                ),
                'code'  => $status_code,
            ];
        }

        $body = wp_remote_retrieve_body( $response );

        if ( '' === $body ) {
            return [
                'error' => __( 'TMDB returned an empty response.', 'tmdb-plugin' ),
            ];
        }

        $decoded = json_decode( $body, true );

        if ( ! is_array( $decoded ) ) {
            return [
                'error' => __( 'Unable to decode TMDB response.', 'tmdb-plugin' ),
            ];
        }

        $raw_results = isset( $decoded['results'] ) && is_array( $decoded['results'] ) ? $decoded['results'] : [];

        $formatted_results = [];

        foreach ( array_slice( $raw_results, 0, 10 ) as $item ) {
            if ( ! is_array( $item ) ) {
                continue;
            }

            $media_type  = isset( $item['media_type'] ) ? sanitize_key( $item['media_type'] ) : '';
            $title_field = 'person' === $media_type ? 'name' : ( isset( $item['title'] ) ? 'title' : 'name' );
            $title       = isset( $item[ $title_field ] ) ? sanitize_text_field( $item[ $title_field ] ) : '';

            $overview_source = '';

            if ( isset( $item['overview'] ) && is_string( $item['overview'] ) ) {
                $overview_source = $item['overview'];
            } elseif ( isset( $item['known_for_department'] ) && is_string( $item['known_for_department'] ) ) {
                $overview_source = $item['known_for_department'];
            }

            $formatted_results[] = [
                'id'           => isset( $item['id'] ) ? (int) $item['id'] : 0,
                'media_type'   => $media_type,
                'title'        => $title,
                'original'     => isset( $item['original_title'] ) ? sanitize_text_field( $item['original_title'] ) : ( isset( $item['original_name'] ) ? sanitize_text_field( $item['original_name'] ) : '' ),
                'overview'     => $overview_source ? wp_trim_words( wp_strip_all_tags( $overview_source ), 40 ) : '',
                'release_date' => isset( $item['release_date'] ) ? sanitize_text_field( $item['release_date'] ) : ( isset( $item['first_air_date'] ) ? sanitize_text_field( $item['first_air_date'] ) : '' ),
                'language'     => isset( $item['original_language'] ) ? sanitize_text_field( $item['original_language'] ) : '',
                'poster_path'  => isset( $item['poster_path'] ) ? sanitize_text_field( ltrim( $item['poster_path'], '/' ) ) : ( isset( $item['profile_path'] ) ? sanitize_text_field( ltrim( $item['profile_path'], '/' ) ) : '' ),
                'vote_average' => isset( $item['vote_average'] ) ? (float) $item['vote_average'] : null,
            ];
        }

        return [
            'results' => $formatted_results,
        ];
    }
}
