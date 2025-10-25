<?php
/**
 * Default single template for TMDB movies.
 *
 * @package TMDBPlugin\Themes
 */

use TMDB\Plugin\Taxonomies\TMDB_Taxonomies;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<main id="primary" class="site-main tmdb-single-movie">
    <?php
    while ( have_posts() ) :
        the_post();

        $post_id          = get_the_ID();
        $tagline          = (string) get_post_meta( $post_id, 'TMDB_tagline', true );
        $original_title   = (string) get_post_meta( $post_id, 'TMDB_original_title', true );
        $release_date_raw = (string) get_post_meta( $post_id, 'TMDB_release_date', true );
        $runtime_minutes  = (int) get_post_meta( $post_id, 'TMDB_runtime', true );
        $status           = (string) get_post_meta( $post_id, 'TMDB_status', true );
        $vote_average     = (float) get_post_meta( $post_id, 'TMDB_vote_average', true );
        $vote_count       = (int) get_post_meta( $post_id, 'TMDB_vote_count', true );
        $homepage         = (string) get_post_meta( $post_id, 'TMDB_homepage', true );
        $origin_countries = get_post_meta( $post_id, 'TMDB_origin_countries', true );
        $spoken_languages = get_post_meta( $post_id, 'TMDB_spoken_languages', true );
        $alternative      = get_post_meta( $post_id, 'TMDB_alternative_titles', true );
        $cast_meta        = get_post_meta( $post_id, 'TMDB_cast', true );

        $release_date = '';

        if ( '' !== $release_date_raw ) {
            $timestamp = strtotime( $release_date_raw );

            if ( false !== $timestamp ) {
                $release_date = wp_date( get_option( 'date_format' ), $timestamp );
            }
        }

        $runtime = '';

        if ( $runtime_minutes > 0 ) {
            $hours   = (int) floor( $runtime_minutes / 60 );
            $minutes = $runtime_minutes % 60;
            $parts   = [];

            if ( $hours > 0 ) {
                $parts[] = sprintf( _n( '%s hour', '%s hours', $hours, 'tmdb-plugin' ), number_format_i18n( $hours ) );
            }

            if ( $minutes > 0 ) {
                $parts[] = sprintf( _n( '%s minute', '%s minutes', $minutes, 'tmdb-plugin' ), number_format_i18n( $minutes ) );
            }

            $runtime = implode( ' ', $parts );
        }

        $rating = '';

        if ( $vote_average > 0 ) {
            $rating = sprintf(
                /* translators: %1$s is the average rating. */
                __( '%1$s / 10', 'tmdb-plugin' ),
                number_format_i18n( $vote_average, 1 )
            );

            if ( $vote_count > 0 ) {
                $rating .= ' ' . sprintf(
                    /* translators: %s is the number of votes. */
                    _n( '(based on %s vote)', '(based on %s votes)', $vote_count, 'tmdb-plugin' ),
                    number_format_i18n( $vote_count )
                );
            }
        }

        $origin_display = '';

        if ( is_array( $origin_countries ) && ! empty( $origin_countries ) ) {
            $origin_display = implode(
                ', ',
                array_map(
                    'strtoupper',
                    array_map( 'sanitize_text_field', $origin_countries )
                )
            );
        }

        $language_display = '';

        if ( is_array( $spoken_languages ) && ! empty( $spoken_languages ) ) {
            $language_labels = [];

            foreach ( $spoken_languages as $language ) {
                if ( ! is_array( $language ) ) {
                    continue;
                }

                $label = '';

                if ( isset( $language['english_name'] ) && '' !== $language['english_name'] ) {
                    $label = $language['english_name'];
                } elseif ( isset( $language['name'] ) && '' !== $language['name'] ) {
                    $label = $language['name'];
                } elseif ( isset( $language['iso_639_1'] ) && '' !== $language['iso_639_1'] ) {
                    $label = strtoupper( $language['iso_639_1'] );
                }

                if ( '' !== $label ) {
                    $language_labels[] = sanitize_text_field( $label );
                }
            }

            if ( ! empty( $language_labels ) ) {
                $language_display = implode( ', ', $language_labels );
            }
        }

        $meta_rows = [];

        if ( '' !== $original_title && $original_title !== get_the_title() ) {
            $meta_rows[] = [
                'label' => __( 'Original title', 'tmdb-plugin' ),
                'value' => $original_title,
            ];
        }

        if ( '' !== $tagline ) {
            $meta_rows[] = [
                'label' => __( 'Tagline', 'tmdb-plugin' ),
                'value' => $tagline,
            ];
        }

        if ( '' !== $release_date ) {
            $meta_rows[] = [
                'label' => __( 'Release date', 'tmdb-plugin' ),
                'value' => $release_date,
            ];
        }

        if ( '' !== $runtime ) {
            $meta_rows[] = [
                'label' => __( 'Runtime', 'tmdb-plugin' ),
                'value' => $runtime,
            ];
        }

        if ( '' !== $status ) {
            $meta_rows[] = [
                'label' => __( 'Status', 'tmdb-plugin' ),
                'value' => $status,
            ];
        }

        if ( '' !== $rating ) {
            $meta_rows[] = [
                'label' => __( 'Rating', 'tmdb-plugin' ),
                'value' => $rating,
            ];
        }

        if ( '' !== $origin_display ) {
            $meta_rows[] = [
                'label' => __( 'Origin countries', 'tmdb-plugin' ),
                'value' => $origin_display,
            ];
        }

        if ( '' !== $language_display ) {
            $meta_rows[] = [
                'label' => __( 'Spoken languages', 'tmdb-plugin' ),
                'value' => $language_display,
            ];
        }

        $taxonomy_sections = [];

        $director_terms = get_the_terms( $post_id, TMDB_Taxonomies::DIRECTOR );

        if ( $director_terms && ! is_wp_error( $director_terms ) ) {
            $taxonomy_sections[] = [
                'label' => __( 'Directors', 'tmdb-plugin' ),
                'terms' => $director_terms,
            ];
        }

        $genre_terms = get_the_terms( $post_id, TMDB_Taxonomies::GENRE );

        if ( $genre_terms && ! is_wp_error( $genre_terms ) ) {
            $taxonomy_sections[] = [
                'label' => __( 'Genres', 'tmdb-plugin' ),
                'terms' => $genre_terms,
            ];
        }

        $keyword_terms = get_the_terms( $post_id, TMDB_Taxonomies::KEYWORD );

        if ( $keyword_terms && ! is_wp_error( $keyword_terms ) ) {
            $taxonomy_sections[] = [
                'label' => __( 'Keywords', 'tmdb-plugin' ),
                'terms' => $keyword_terms,
            ];
        }

        $cast_list = [];

        if ( is_array( $cast_meta ) ) {
            foreach ( $cast_meta as $member ) {
                if ( ! is_array( $member ) ) {
                    continue;
                }

                $name      = isset( $member['name'] ) ? (string) $member['name'] : '';
                $character = isset( $member['character'] ) ? (string) $member['character'] : '';

                if ( '' === $name ) {
                    continue;
                }

                $cast_list[] = [
                    'name'      => sanitize_text_field( $name ),
                    'character' => sanitize_text_field( $character ),
                ];
            }
        }

        $alternative_titles = [];

        if ( is_array( $alternative ) ) {
            foreach ( $alternative as $entry ) {
                if ( ! is_array( $entry ) || empty( $entry['title'] ) ) {
                    continue;
                }

                $title = (string) $entry['title'];

                if ( isset( $entry['iso_3166_1'] ) && '' !== $entry['iso_3166_1'] ) {
                    $title = sprintf( '%1$s (%2$s)', $title, strtoupper( (string) $entry['iso_3166_1'] ) );
                }

                $alternative_titles[] = sanitize_text_field( $title );
            }
        }
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class( 'tmdb-single-movie__article' ); ?>>
            <header class="entry-header">
                <h1 class="entry-title"><?php the_title(); ?></h1>
            </header>

            <div class="tmdb-single-movie__wrapper">
                <?php if ( has_post_thumbnail() ) : ?>
                    <div class="tmdb-single-movie__poster">
                        <?php the_post_thumbnail( 'large' ); ?>
                    </div>
                <?php endif; ?>

                <div class="tmdb-single-movie__details">
                    <?php if ( ! empty( $meta_rows ) ) : ?>
                        <dl class="tmdb-single-movie__meta-list">
                            <?php foreach ( $meta_rows as $row ) : ?>
                                <div class="tmdb-single-movie__meta-item">
                                    <dt><?php echo esc_html( $row['label'] ); ?></dt>
                                    <dd><?php echo esc_html( $row['value'] ); ?></dd>
                                </div>
                            <?php endforeach; ?>
                        </dl>
                    <?php endif; ?>

                    <?php if ( '' !== $homepage ) : ?>
                        <p class="tmdb-single-movie__homepage">
                            <a href="<?php echo esc_url( $homepage ); ?>" class="tmdb-single-movie__homepage-link" target="_blank" rel="noopener noreferrer">
                                <?php esc_html_e( 'Official website', 'tmdb-plugin' ); ?>
                            </a>
                        </p>
                    <?php endif; ?>

                    <div class="entry-content tmdb-single-movie__content">
                        <?php the_content(); ?>
                    </div>

                    <?php foreach ( $taxonomy_sections as $section ) : ?>
                        <?php if ( empty( $section['terms'] ) ) {
                            continue;
                        }
                        ?>
                        <section class="tmdb-single-movie__section">
                            <h2 class="tmdb-single-movie__section-title"><?php echo esc_html( $section['label'] ); ?></h2>
                            <ul class="tmdb-single-movie__taxonomy-list">
                                <?php
                                foreach ( $section['terms'] as $term ) {
                                    if ( ! $term instanceof \WP_Term ) {
                                        continue;
                                    }

                                    $term_link = get_term_link( $term );

                                    if ( is_wp_error( $term_link ) ) {
                                        continue;
                                    }
                                    ?>
                                    <li>
                                        <a href="<?php echo esc_url( $term_link ); ?>"><?php echo esc_html( $term->name ); ?></a>
                                    </li>
                                    <?php
                                }
                                ?>
                            </ul>
                        </section>
                    <?php endforeach; ?>

                    <?php if ( ! empty( $cast_list ) ) : ?>
                        <section class="tmdb-single-movie__section">
                            <h2 class="tmdb-single-movie__section-title"><?php esc_html_e( 'Cast', 'tmdb-plugin' ); ?></h2>
                            <ul class="tmdb-single-movie__cast-list">
                                <?php foreach ( $cast_list as $cast_member ) : ?>
                                    <li class="tmdb-single-movie__cast-member">
                                        <span class="tmdb-single-movie__cast-name"><?php echo esc_html( $cast_member['name'] ); ?></span>
                                        <?php if ( '' !== $cast_member['character'] ) : ?>
                                            <span class="tmdb-single-movie__cast-character"><?php echo esc_html( $cast_member['character'] ); ?></span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </section>
                    <?php endif; ?>

                    <?php if ( ! empty( $alternative_titles ) ) : ?>
                        <section class="tmdb-single-movie__section">
                            <h2 class="tmdb-single-movie__section-title"><?php esc_html_e( 'Alternative titles', 'tmdb-plugin' ); ?></h2>
                            <ul class="tmdb-single-movie__alternative-list">
                                <?php foreach ( $alternative_titles as $title ) : ?>
                                    <li><?php echo esc_html( $title ); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </section>
                    <?php endif; ?>
                </div>
            </div>
        </article>
        <?php
        if ( comments_open() || get_comments_number() ) {
            comments_template();
        }
    endwhile;
    ?>
</main>

<?php get_footer(); ?>
