<?php
/**
 * TMDB configuration admin page.
 *
 * @package TMDBPlugin\Admin
 */

namespace TMDB\Plugin\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin page for configuring TMDB API access.
 */
class TMDB_Admin_Page_Config {
    /**
     * Registers the configuration admin page.
     */
    public static function register(): void {
        add_menu_page(
            __( 'TMDB Configuration', 'tmdb-plugin' ),
            __( 'TMDB', 'tmdb-plugin' ),
            'manage_options',
            TMDB_PLUGIN_SLUG,
            [ self::class, 'render' ],
            'dashicons-format-video'
        );
    }

    /**
     * Renders the configuration admin page.
     */
    public static function render(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $languages = self::get_available_languages();

        $api_key            = get_option( 'tmdb_plugin_api_key', '' );
        $language           = get_option( 'tmdb_plugin_language', 'en-US' );
        $fallback_language  = get_option( 'tmdb_plugin_fallback_language', 'en-US' );

        if ( isset( $_POST['tmdb_plugin_settings_submit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            self::handle_form_submission( $languages );

            $api_key           = get_option( 'tmdb_plugin_api_key', '' );
            $language          = get_option( 'tmdb_plugin_language', 'en-US' );
            $fallback_language = get_option( 'tmdb_plugin_fallback_language', 'en-US' );
        }

        settings_errors( 'tmdb_plugin_messages' );
        ?>
        <div class="wrap tmdb-plugin tmdb-plugin__config">
            <h1><?php esc_html_e( 'TMDB Configuration', 'tmdb-plugin' ); ?></h1>
            <p class="description">
                <?php esc_html_e( 'Provide your TMDB API credentials and preferred languages for fetching content.', 'tmdb-plugin' ); ?>
            </p>
            <form method="post" action="">
                <?php wp_nonce_field( 'tmdb_plugin_save_settings', 'tmdb_plugin_nonce' ); ?>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="tmdb_plugin_api_key">
                                    <?php esc_html_e( 'API Key', 'tmdb-plugin' ); ?>
                                </label>
                            </th>
                            <td>
                                <input
                                    name="tmdb_plugin_api_key"
                                    type="text"
                                    id="tmdb_plugin_api_key"
                                    value="<?php echo esc_attr( $api_key ); ?>"
                                    class="regular-text"
                                    autocomplete="off"
                                    required
                                />
                                <p class="description">
                                    <?php esc_html_e( 'Enter the API key obtained from your TMDB account.', 'tmdb-plugin' ); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="tmdb_plugin_language">
                                    <?php esc_html_e( 'Primary Language', 'tmdb-plugin' ); ?>
                                </label>
                            </th>
                            <td>
                                <select name="tmdb_plugin_language" id="tmdb_plugin_language">
                                    <?php foreach ( $languages as $code => $label ) : ?>
                                        <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $language, $code ); ?>>
                                            <?php echo esc_html( $label ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">
                                    <?php esc_html_e( 'Choose the language used when requesting data from TMDB.', 'tmdb-plugin' ); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="tmdb_plugin_fallback_language">
                                    <?php esc_html_e( 'Fallback Language', 'tmdb-plugin' ); ?>
                                </label>
                            </th>
                            <td>
                                <select name="tmdb_plugin_fallback_language" id="tmdb_plugin_fallback_language">
                                    <?php foreach ( $languages as $code => $label ) : ?>
                                        <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $fallback_language, $code ); ?>>
                                            <?php echo esc_html( $label ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">
                                    <?php esc_html_e( 'Select a fallback language used when content is unavailable in the primary language.', 'tmdb-plugin' ); ?>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <input type="hidden" name="tmdb_plugin_settings_submit" value="1" />
                <?php submit_button( __( 'Save Settings', 'tmdb-plugin' ) ); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Handles saving configuration values.
     *
     * @param array<string, string> $languages List of supported languages.
     */
    private static function handle_form_submission( array $languages ): void {
        if ( ! isset( $_POST['tmdb_plugin_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['tmdb_plugin_nonce'] ), 'tmdb_plugin_save_settings' ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $api_key  = isset( $_POST['tmdb_plugin_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['tmdb_plugin_api_key'] ) ) : '';
        $language = isset( $_POST['tmdb_plugin_language'] ) ? sanitize_text_field( wp_unslash( $_POST['tmdb_plugin_language'] ) ) : '';
        $fallback = isset( $_POST['tmdb_plugin_fallback_language'] ) ? sanitize_text_field( wp_unslash( $_POST['tmdb_plugin_fallback_language'] ) ) : '';

        if ( ! array_key_exists( $language, $languages ) ) {
            $language = 'en-US';
        }

        if ( ! array_key_exists( $fallback, $languages ) ) {
            $fallback = 'en-US';
        }

        update_option( 'tmdb_plugin_api_key', $api_key );
        update_option( 'tmdb_plugin_language', $language );
        update_option( 'tmdb_plugin_fallback_language', $fallback );

        add_settings_error(
            'tmdb_plugin_messages',
            'tmdb_plugin_message',
            __( 'Settings saved.', 'tmdb-plugin' ),
            'updated'
        );
    }

    /**
     * Returns a list of languages supported by TMDB.
     *
     * @return array<string, string>
     */
    private static function get_available_languages(): array {
        return [
            'cs-CZ' => __( 'Czech', 'tmdb-plugin' ),
            'da-DK' => __( 'Danish', 'tmdb-plugin' ),
            'de-DE' => __( 'German', 'tmdb-plugin' ),
            'en-GB' => __( 'English (United Kingdom)', 'tmdb-plugin' ),
            'en-US' => __( 'English (United States)', 'tmdb-plugin' ),
            'es-ES' => __( 'Spanish (Spain)', 'tmdb-plugin' ),
            'es-MX' => __( 'Spanish (Mexico)', 'tmdb-plugin' ),
            'fi-FI' => __( 'Finnish', 'tmdb-plugin' ),
            'fr-FR' => __( 'French', 'tmdb-plugin' ),
            'hu-HU' => __( 'Hungarian', 'tmdb-plugin' ),
            'it-IT' => __( 'Italian', 'tmdb-plugin' ),
            'ja-JP' => __( 'Japanese', 'tmdb-plugin' ),
            'ko-KR' => __( 'Korean', 'tmdb-plugin' ),
            'nb-NO' => __( 'Norwegian Bokmål', 'tmdb-plugin' ),
            'nl-NL' => __( 'Dutch', 'tmdb-plugin' ),
            'pl-PL' => __( 'Polish', 'tmdb-plugin' ),
            'pt-BR' => __( 'Portuguese (Brazil)', 'tmdb-plugin' ),
            'pt-PT' => __( 'Portuguese (Portugal)', 'tmdb-plugin' ),
            'ru-RU' => __( 'Russian', 'tmdb-plugin' ),
            'sv-SE' => __( 'Swedish', 'tmdb-plugin' ),
            'tr-TR' => __( 'Turkish', 'tmdb-plugin' ),
            'zh-CN' => __( 'Chinese (Simplified)', 'tmdb-plugin' ),
            'zh-TW' => __( 'Chinese (Traditional)', 'tmdb-plugin' ),
        ];
    }
}
