<?php
/**
 * Single movie template optimized for Bootstrap layout.
 *
 * @package TMDB_Theme
 */

use TMDB\Plugin\Taxonomies\TMDB_Taxonomies;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<div class="container py-5">
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
        $actor_term_ids   = get_post_meta( $post_id, 'TMDB_actor_ids', true );
        $director_meta    = get_post_meta( $post_id, 'TMDB_directors', true );
        $director_term_ids = get_post_meta( $post_id, 'TMDB_director_ids', true );
        $videos_payload   = get_post_meta( $post_id, 'TMDB_videos_payload', true );

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

        $actor_term_ids    = is_array( $actor_term_ids ) ? array_values( array_map( 'intval', $actor_term_ids ) ) : [];
        $director_term_ids = is_array( $director_term_ids ) ? array_values( array_map( 'intval', $director_term_ids ) ) : [];

        $cast_list = [];

        if ( is_array( $cast_meta ) ) {
            foreach ( $cast_meta as $index => $member ) {
                if ( ! is_array( $member ) ) {
                    continue;
                }

                $name          = isset( $member['name'] ) ? (string) $member['name'] : '';
                $character     = isset( $member['character'] ) ? (string) $member['character'] : '';
                $original_name = isset( $member['original_name'] ) ? (string) $member['original_name'] : '';

                if ( '' === $name ) {
                    continue;
                }

                $term_id   = isset( $actor_term_ids[ $index ] ) ? (int) $actor_term_ids[ $index ] : 0;
                $term_link = '';
                $image_url = '';

                if ( $term_id > 0 ) {
                    $term = get_term( $term_id, TMDB_Taxonomies::ACTOR );

                    if ( $term instanceof \WP_Term && ! is_wp_error( $term ) ) {
                        $link = get_term_link( $term );

                        if ( ! is_wp_error( $link ) ) {
                            $term_link = (string) $link;
                        }

                        $image_meta = get_term_meta( $term->term_id, 'TMDB_profile_image', true );

                        if ( is_array( $image_meta ) && ! empty( $image_meta['url'] ) ) {
                            $image_url = (string) $image_meta['url'];
                        }
                    }
                }

                $cast_list[] = [
                    'name'          => sanitize_text_field( $name ),
                    'character'     => sanitize_text_field( $character ),
                    'original_name' => sanitize_text_field( $original_name ),
                    'term_link'     => esc_url_raw( $term_link ),
                    'image_url'     => esc_url_raw( $image_url ),
                ];
            }
        }

        $director_list = [];

        if ( is_array( $director_meta ) ) {
            foreach ( $director_meta as $index => $director ) {
                if ( ! is_array( $director ) ) {
                    continue;
                }

                $name          = isset( $director['name'] ) ? (string) $director['name'] : '';
                $original_name = isset( $director['original_name'] ) ? (string) $director['original_name'] : '';
                $job           = isset( $director['job'] ) ? (string) $director['job'] : '';

                if ( '' === $name ) {
                    continue;
                }

                $term_id   = isset( $director_term_ids[ $index ] ) ? (int) $director_term_ids[ $index ] : 0;
                $term_link = '';
                $image_url = '';

                if ( $term_id > 0 ) {
                    $term = get_term( $term_id, TMDB_Taxonomies::DIRECTOR );

                    if ( $term instanceof \WP_Term && ! is_wp_error( $term ) ) {
                        $link = get_term_link( $term );

                        if ( ! is_wp_error( $link ) ) {
                            $term_link = (string) $link;
                        }

                        $image_meta = get_term_meta( $term->term_id, 'TMDB_profile_image', true );

                        if ( is_array( $image_meta ) && ! empty( $image_meta['url'] ) ) {
                            $image_url = (string) $image_meta['url'];
                        }
                    }
                }

                $director_list[] = [
                    'name'          => sanitize_text_field( $name ),
                    'original_name' => sanitize_text_field( $original_name ),
                    'job'           => sanitize_text_field( $job ),
                    'term_link'     => esc_url_raw( $term_link ),
                    'image_url'     => esc_url_raw( $image_url ),
                ];
            }
        }

        if ( empty( $director_list ) && $director_terms && ! is_wp_error( $director_terms ) ) {
            $taxonomy_sections[] = [
                'label' => __( 'Directors', 'tmdb-plugin' ),
                'terms' => $director_terms,
            ];
        }

        $alternative_titles = [];
        $videos             = [];

        if ( is_string( $videos_payload ) && '' !== $videos_payload ) {
            $decoded_videos = json_decode( $videos_payload, true );

            if ( is_array( $decoded_videos ) ) {
                foreach ( $decoded_videos as $video ) {
                    if ( ! is_array( $video ) ) {
                        continue;
                    }

                    $name         = isset( $video['name'] ) ? (string) $video['name'] : '';
                    $site         = isset( $video['site'] ) ? (string) $video['site'] : '';
                    $key          = isset( $video['key'] ) ? (string) $video['key'] : '';
                    $type         = isset( $video['type'] ) ? (string) $video['type'] : '';
                    $country      = isset( $video['iso_3166_1'] ) ? (string) $video['iso_3166_1'] : '';
                    $language     = isset( $video['iso_639_1'] ) ? (string) $video['iso_639_1'] : '';
                    $published_at = isset( $video['published_at'] ) ? (string) $video['published_at'] : '';
                    $official     = ! empty( $video['official'] );

                    if ( '' === $name && '' === $key ) {
                        continue;
                    }

                    $video_url  = '';
                    $embed_url  = '';
                    $lower_site = strtolower( $site );

                    if ( 'youtube' === $lower_site && '' !== $key ) {
                        $sanitized_key = rawurlencode( $key );
                        $video_url     = sprintf( 'https://www.youtube.com/watch?v=%s', $sanitized_key );
                        $embed_url     = sprintf( 'https://www.youtube.com/embed/%s', $sanitized_key );
                    } elseif ( 'vimeo' === $lower_site && '' !== $key ) {
                        $sanitized_key = rawurlencode( $key );
                        $video_url     = sprintf( 'https://vimeo.com/%s', $sanitized_key );
                        $embed_url     = sprintf( 'https://player.vimeo.com/video/%s', $sanitized_key );
                    }

                    $formatted_country  = '' !== $country ? strtoupper( sanitize_text_field( $country ) ) : '';
                    $formatted_language = '' !== $language ? strtoupper( sanitize_text_field( $language ) ) : '';
                    $formatted_type     = sanitize_text_field( $type );
                    $formatted_site     = sanitize_text_field( $site );
                    $formatted_name     = sanitize_text_field( $name );
                    $published_display  = '';

                    if ( '' !== $published_at ) {
                        $timestamp = strtotime( $published_at );

                        if ( false !== $timestamp ) {
                            $published_display = wp_date( get_option( 'date_format' ), $timestamp );
                        }
                    }

                    if ( '' === $formatted_name && '' === $formatted_type && '' === $formatted_site && '' === $video_url && '' === $embed_url ) {
                        continue;
                    }

                    $videos[] = [
                        'name'         => $formatted_name,
                        'site'         => $formatted_site,
                        'type'         => $formatted_type,
                        'country'      => $formatted_country,
                        'language'     => $formatted_language,
                        'official'     => $official,
                        'published_at' => $published_display,
                        'video_url'    => esc_url_raw( $video_url ),
                        'embed_url'    => esc_url_raw( $embed_url ),
                    ];
                }
            }
        }

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
        <article id="post-<?php the_ID(); ?>" <?php post_class( 'card bg-transparent border-0' ); ?>>
            <header class="card-body pb-0">
                <h1 class="display-4 fw-bold text-light"><?php the_title(); ?></h1>
                <?php if ( '' !== $tagline ) : ?>
                    <p class="lead text-muted"><?php echo esc_html( $tagline ); ?></p>
                <?php endif; ?>
            </header>

            <div class="card-body">
                <div class="row g-5 align-items-start">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <div class="col-lg-4">
                            <div class="ratio ratio-2x3 rounded overflow-hidden shadow-sm">
                                <?php the_post_thumbnail( 'large', [ 'class' => 'w-100 h-100 object-fit-cover' ] ); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="col-lg-8">
                        <?php if ( ! empty( $meta_rows ) ) : ?>
                            <div class="row g-3 mb-4">
                                <?php foreach ( $meta_rows as $row ) : ?>
                                    <div class="col-sm-6">
                                        <div class="p-3 border border-secondary rounded-3 h-100">
                                            <h2 class="h6 text-info text-uppercase mb-1"><?php echo esc_html( $row['label'] ); ?></h2>
                                            <p class="mb-0"><?php echo esc_html( $row['value'] ); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ( '' !== $homepage ) : ?>
                            <p class="mb-4">
                                <a class="btn btn-outline-info" href="<?php echo esc_url( $homepage ); ?>" target="_blank" rel="noopener noreferrer">
                                    <?php esc_html_e( 'Official website', 'tmdb-plugin' ); ?>
                                </a>
                            </p>
                        <?php endif; ?>

                        <div class="entry-content mb-5">
                            <?php the_content(); ?>
                        </div>

                        <?php foreach ( $taxonomy_sections as $section ) :
                            if ( empty( $section['terms'] ) ) {
                                continue;
                            }
                            ?>
                            <section class="mb-4">
                                <h2 class="h5 text-info text-uppercase fw-semibold mb-3"><?php echo esc_html( $section['label'] ); ?></h2>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ( $section['terms'] as $term ) :
                                        if ( ! $term instanceof \WP_Term ) {
                                            continue;
                                        }

                                        $term_link = get_term_link( $term );

                                        if ( is_wp_error( $term_link ) ) {
                                            continue;
                                        }
                                        ?>
                                        <a class="badge bg-info text-dark" href="<?php echo esc_url( $term_link ); ?>"><?php echo esc_html( $term->name ); ?></a>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endforeach; ?>

                        <?php if ( ! empty( $director_list ) ) : ?>
                            <section class="mb-5">
                                <h2 class="h5 text-info text-uppercase fw-semibold mb-3"><?php esc_html_e( 'Directors', 'tmdb-plugin' ); ?></h2>
                                <div class="row g-3">
                                    <?php foreach ( $director_list as $director ) : ?>
                                        <div class="col-md-6">
                                            <div class="card h-100 bg-dark-subtle bg-opacity-10 border-0">
                                                <div class="card-body d-flex align-items-center gap-3">
                                                    <?php if ( '' !== $director['image_url'] ) : ?>
                                                        <span class="avatar flex-shrink-0 rounded-circle overflow-hidden">
                                                            <img class="w-100 h-100 object-fit-cover" src="<?php echo esc_url( $director['image_url'] ); ?>" alt="<?php echo esc_attr( $director['name'] ); ?>" loading="lazy" />
                                                        </span>
                                                    <?php endif; ?>
                                                    <div>
                                                        <?php if ( '' !== $director['term_link'] ) : ?>
                                                            <a class="stretched-link text-info fw-semibold" href="<?php echo esc_url( $director['term_link'] ); ?>"><?php echo esc_html( $director['name'] ); ?></a>
                                                        <?php else : ?>
                                                            <span class="text-info fw-semibold"><?php echo esc_html( $director['name'] ); ?></span>
                                                        <?php endif; ?>
                                                        <?php if ( '' !== $director['original_name'] && $director['original_name'] !== $director['name'] ) : ?>
                                                            <div class="text-muted small"><?php echo esc_html( $director['original_name'] ); ?></div>
                                                        <?php endif; ?>
                                                        <?php if ( '' !== $director['job'] ) : ?>
                                                            <div class="text-muted small"><?php echo esc_html( $director['job'] ); ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endif; ?>

                        <?php if ( ! empty( $cast_list ) ) : ?>
                            <section class="mb-5">
                                <h2 class="h5 text-info text-uppercase fw-semibold mb-3"><?php esc_html_e( 'Cast', 'tmdb-plugin' ); ?></h2>
                                <div class="row g-3">
                                    <?php foreach ( $cast_list as $cast_member ) : ?>
                                        <div class="col-md-6 col-xl-4">
                                            <div class="card h-100 bg-dark-subtle bg-opacity-10 border-0 position-relative">
                                                <div class="card-body d-flex gap-3 align-items-center">
                                                    <?php if ( '' !== $cast_member['image_url'] ) : ?>
                                                        <span class="avatar flex-shrink-0 rounded-circle overflow-hidden">
                                                            <img class="w-100 h-100 object-fit-cover" src="<?php echo esc_url( $cast_member['image_url'] ); ?>" alt="<?php echo esc_attr( $cast_member['name'] ); ?>" loading="lazy" />
                                                        </span>
                                                    <?php endif; ?>
                                                    <div>
                                                        <?php if ( '' !== $cast_member['term_link'] ) : ?>
                                                            <a class="stretched-link text-info fw-semibold" href="<?php echo esc_url( $cast_member['term_link'] ); ?>"><?php echo esc_html( $cast_member['name'] ); ?></a>
                                                        <?php else : ?>
                                                            <span class="text-info fw-semibold"><?php echo esc_html( $cast_member['name'] ); ?></span>
                                                        <?php endif; ?>
                                                        <?php if ( '' !== $cast_member['character'] ) : ?>
                                                            <div class="text-muted small"><?php echo esc_html( $cast_member['character'] ); ?></div>
                                                        <?php endif; ?>
                                                        <?php if ( '' !== $cast_member['original_name'] && $cast_member['original_name'] !== $cast_member['name'] ) : ?>
                                                            <div class="text-muted small"><?php echo esc_html( $cast_member['original_name'] ); ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endif; ?>

                        <?php if ( ! empty( $videos ) ) : ?>
                            <section class="mb-5">
                                <h2 class="h5 text-info text-uppercase fw-semibold mb-3"><?php esc_html_e( 'Videos', 'tmdb-plugin' ); ?></h2>
                                <div class="row g-4">
                                    <?php foreach ( $videos as $video ) : ?>
                                        <div class="col-lg-6">
                                            <article class="card h-100 bg-dark-subtle bg-opacity-10 border-0">
                                                <?php if ( '' !== $video['embed_url'] ) : ?>
                                                    <div class="ratio ratio-16x9">
                                                        <iframe src="<?php echo esc_url( $video['embed_url'] ); ?>" title="<?php echo esc_attr( $video['name'] ); ?>" allowfullscreen loading="lazy"></iframe>
                                                    </div>
                                                <?php elseif ( '' !== $video['video_url'] ) : ?>
                                                    <div class="card-body pb-0">
                                                        <a class="btn btn-outline-info" href="<?php echo esc_url( $video['video_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Watch video', 'tmdb-plugin' ); ?></a>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="card-body">
                                                    <?php if ( '' !== $video['name'] ) : ?>
                                                        <h3 class="h5 text-light"><?php echo esc_html( $video['name'] ); ?></h3>
                                                    <?php endif; ?>
                                                    <dl class="row gy-2 small text-muted mb-0">
                                                        <?php if ( '' !== $video['type'] ) : ?>
                                                            <dt class="col-5"><?php esc_html_e( 'Type', 'tmdb-plugin' ); ?></dt>
                                                            <dd class="col-7"><?php echo esc_html( $video['type'] ); ?></dd>
                                                        <?php endif; ?>
                                                        <?php if ( '' !== $video['site'] ) : ?>
                                                            <dt class="col-5"><?php esc_html_e( 'Platform', 'tmdb-plugin' ); ?></dt>
                                                            <dd class="col-7"><?php echo esc_html( $video['site'] ); ?></dd>
                                                        <?php endif; ?>
                                                        <?php if ( '' !== $video['country'] ) : ?>
                                                            <dt class="col-5"><?php esc_html_e( 'Country', 'tmdb-plugin' ); ?></dt>
                                                            <dd class="col-7"><?php echo esc_html( $video['country'] ); ?></dd>
                                                        <?php endif; ?>
                                                        <?php if ( '' !== $video['language'] ) : ?>
                                                            <dt class="col-5"><?php esc_html_e( 'Language', 'tmdb-plugin' ); ?></dt>
                                                            <dd class="col-7"><?php echo esc_html( $video['language'] ); ?></dd>
                                                        <?php endif; ?>
                                                        <?php if ( ! empty( $video['official'] ) ) : ?>
                                                            <dt class="col-5"><?php esc_html_e( 'Official', 'tmdb-plugin' ); ?></dt>
                                                            <dd class="col-7"><?php esc_html_e( 'Yes', 'tmdb-plugin' ); ?></dd>
                                                        <?php endif; ?>
                                                        <?php if ( '' !== $video['published_at'] ) : ?>
                                                            <dt class="col-5"><?php esc_html_e( 'Published', 'tmdb-plugin' ); ?></dt>
                                                            <dd class="col-7"><?php echo esc_html( $video['published_at'] ); ?></dd>
                                                        <?php endif; ?>
                                                    </dl>
                                                </div>
                                            </article>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endif; ?>

                        <?php if ( ! empty( $alternative_titles ) ) : ?>
                            <section class="mb-5">
                                <h2 class="h5 text-info text-uppercase fw-semibold mb-3"><?php esc_html_e( 'Alternative titles', 'tmdb-plugin' ); ?></h2>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ( $alternative_titles as $title ) : ?>
                                        <span class="badge bg-secondary text-uppercase"><?php echo esc_html( $title ); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </article>
        <?php
        if ( comments_open() || get_comments_number() ) {
            echo '<div class="mt-5">';
            comments_template();
            echo '</div>';
        }
    endwhile;
    ?>
</div>

<?php
get_footer();
