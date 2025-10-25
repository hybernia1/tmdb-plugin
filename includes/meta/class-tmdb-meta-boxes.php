<?php
/**
 * Handles custom relationships between TMDB content types.
 *
 * @package TMDBPlugin\Meta
 */

namespace TMDB\Plugin\Meta;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registers meta boxes for Season and Episode post types.
 */
class TMDB_Meta_Boxes {
    private const SERIES_META_KEY  = '_tmdb_series_id';
    private const SEASON_META_KEY  = '_tmdb_season_id';

    /**
     * Registers the meta boxes within the WordPress editor.
     */
    public static function register(): void {
        add_meta_box(
            'tmdb-season-details',
            __( 'Season Details', 'tmdb-plugin' ),
            [ self::class, 'render_season_meta_box' ],
            'season',
            'side',
            'default'
        );

        add_meta_box(
            'tmdb-episode-details',
            __( 'Episode Details', 'tmdb-plugin' ),
            [ self::class, 'render_episode_meta_box' ],
            'episode',
            'side',
            'default'
        );
    }

    /**
     * Renders the season meta box allowing editors to assign a series.
     */
    public static function render_season_meta_box( \WP_Post $post ): void {
        wp_nonce_field( 'tmdb_season_meta_nonce', 'tmdb_season_meta_nonce' );

        $selected_series = (int) get_post_meta( $post->ID, self::SERIES_META_KEY, true );
        $series_posts    = self::get_series_posts();
        ?>
        <p>
            <label for="tmdb-season-series"><?php esc_html_e( 'Series', 'tmdb-plugin' ); ?></label>
            <select id="tmdb-season-series" name="tmdb_series_id" class="widefat">
                <option value="0" <?php selected( 0, $selected_series ); ?>><?php esc_html_e( 'Select series', 'tmdb-plugin' ); ?></option>
                <?php foreach ( $series_posts as $series ) : ?>
                    <option value="<?php echo esc_attr( $series->ID ); ?>" <?php selected( $series->ID, $selected_series ); ?>>
                        <?php echo esc_html( $series->post_title ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php
    }

    /**
     * Renders the episode meta box allowing editors to assign a series and season.
     */
    public static function render_episode_meta_box( \WP_Post $post ): void {
        wp_nonce_field( 'tmdb_episode_meta_nonce', 'tmdb_episode_meta_nonce' );

        $selected_series = (int) get_post_meta( $post->ID, self::SERIES_META_KEY, true );
        $selected_season = (int) get_post_meta( $post->ID, self::SEASON_META_KEY, true );
        $series_posts    = self::get_series_posts();
        $season_posts    = self::get_seasons_for_series( $selected_series );
        ?>
        <p>
            <label for="tmdb-episode-series"><?php esc_html_e( 'Series', 'tmdb-plugin' ); ?></label>
            <select id="tmdb-episode-series" name="tmdb_episode_series_id" class="widefat">
                <option value="0" <?php selected( 0, $selected_series ); ?>><?php esc_html_e( 'Select series', 'tmdb-plugin' ); ?></option>
                <?php foreach ( $series_posts as $series ) : ?>
                    <option value="<?php echo esc_attr( $series->ID ); ?>" <?php selected( $series->ID, $selected_series ); ?>>
                        <?php echo esc_html( $series->post_title ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="tmdb-episode-season"><?php esc_html_e( 'Season', 'tmdb-plugin' ); ?></label>
            <select id="tmdb-episode-season" name="tmdb_episode_season_id" class="widefat">
                <option value="0" <?php selected( 0, $selected_season ); ?>><?php esc_html_e( 'Select season', 'tmdb-plugin' ); ?></option>
                <?php foreach ( $season_posts as $season ) : ?>
                    <option value="<?php echo esc_attr( $season->ID ); ?>" <?php selected( $season->ID, $selected_season ); ?>>
                        <?php echo esc_html( $season->post_title ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p class="description">
            <?php esc_html_e( 'Seasons are filtered by the selected series after saving.', 'tmdb-plugin' ); ?>
        </p>
        <?php
    }

    /**
     * Persists season and episode relationships.
     */
    public static function save( int $post_id, \WP_Post $post ): void {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( 'season' === $post->post_type ) {
            self::save_season_meta( $post_id );
        }

        if ( 'episode' === $post->post_type ) {
            self::save_episode_meta( $post_id );
        }
    }

    /**
     * Saves metadata for a season.
     */
    private static function save_season_meta( int $post_id ): void {
        if ( ! isset( $_POST['tmdb_season_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tmdb_season_meta_nonce'] ) ), 'tmdb_season_meta_nonce' ) ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $series_id = isset( $_POST['tmdb_series_id'] ) ? absint( $_POST['tmdb_series_id'] ) : 0;
        update_post_meta( $post_id, self::SERIES_META_KEY, $series_id );
    }

    /**
     * Saves metadata for an episode.
     */
    private static function save_episode_meta( int $post_id ): void {
        if ( ! isset( $_POST['tmdb_episode_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tmdb_episode_meta_nonce'] ) ), 'tmdb_episode_meta_nonce' ) ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $series_id = isset( $_POST['tmdb_episode_series_id'] ) ? absint( $_POST['tmdb_episode_series_id'] ) : 0;
        $season_id = isset( $_POST['tmdb_episode_season_id'] ) ? absint( $_POST['tmdb_episode_season_id'] ) : 0;

        if ( $season_id && $series_id !== (int) get_post_meta( $season_id, self::SERIES_META_KEY, true ) ) {
            $season_id = 0;
        }

        update_post_meta( $post_id, self::SERIES_META_KEY, $series_id );
        update_post_meta( $post_id, self::SEASON_META_KEY, $season_id );
    }

    /**
     * Retrieves all series posts ordered alphabetically.
     *
     * @return array<int, \WP_Post>
     */
    private static function get_series_posts(): array {
        return get_posts(
            [
                'post_type'      => 'series',
                'posts_per_page' => -1,
                'orderby'        => 'title',
                'order'          => 'ASC',
            ]
        );
    }

    /**
     * Retrieves seasons attached to a series.
     *
     * @return array<int, \WP_Post>
     */
    private static function get_seasons_for_series( int $series_id ): array {
        if ( $series_id <= 0 ) {
            return [];
        }

        return get_posts(
            [
                'post_type'      => 'season',
                'posts_per_page' => -1,
                'orderby'        => 'menu_order',
                'order'          => 'ASC',
                'meta_query'     => [
                    [
                        'key'   => self::SERIES_META_KEY,
                        'value' => $series_id,
                    ],
                ],
            ]
        );
    }
}
