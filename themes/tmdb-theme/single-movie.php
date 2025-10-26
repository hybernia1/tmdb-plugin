<?php
/**
 * Single movie template.
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

        $post_id            = get_the_ID();
        $tagline            = (string) get_post_meta( $post_id, 'TMDB_tagline', true );
        $original_title     = (string) get_post_meta( $post_id, 'TMDB_original_title', true );
        $release_date_raw   = (string) get_post_meta( $post_id, 'TMDB_release_date', true );
        $runtime_minutes    = (int) get_post_meta( $post_id, 'TMDB_runtime', true );
        $status             = (string) get_post_meta( $post_id, 'TMDB_status', true );
        $vote_average       = (float) get_post_meta( $post_id, 'TMDB_vote_average', true );
        $vote_count         = (int) get_post_meta( $post_id, 'TMDB_vote_count', true );
        $homepage           = (string) get_post_meta( $post_id, 'TMDB_homepage', true );
        $primary_language   = (string) get_post_meta( $post_id, 'TMDB_language', true );
        $origin_countries   = get_post_meta( $post_id, 'TMDB_origin_countries', true );
        $spoken_languages   = get_post_meta( $post_id, 'TMDB_spoken_languages', true );
        $alternative        = get_post_meta( $post_id, 'TMDB_alternative_titles', true );
        $collection         = get_post_meta( $post_id, 'TMDB_collection', true );
        $websites_meta      = get_post_meta( $post_id, 'TMDB_websites', true );
        $primary_website    = get_post_meta( $post_id, 'TMDB_primary_website', true );
        $external_ids_meta  = get_post_meta( $post_id, 'TMDB_external_ids', true );
        $gallery_ids_meta   = get_post_meta( $post_id, 'TMDB_gallery_image_ids', true );
        $videos_payload     = get_post_meta( $post_id, 'TMDB_videos_payload', true );
        $trailer_meta       = get_post_meta( $post_id, 'TMDB_trailer', true );
        $cast_meta          = get_post_meta( $post_id, 'TMDB_cast', true );
        $actor_term_ids     = get_post_meta( $post_id, 'TMDB_actor_ids', true );
        $director_meta      = get_post_meta( $post_id, 'TMDB_directors', true );
        $director_term_ids  = get_post_meta( $post_id, 'TMDB_director_ids', true );

        $release_date = '';

        if ( '' !== $release_date_raw ) {
            $timestamp = strtotime( $release_date_raw );

            if ( false !== $timestamp ) {
                $release_date = wp_date( get_option( 'date_format' ), $timestamp );
            } else {
                $release_date = $release_date_raw;
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
                'key'   => 'original_title',
                'label' => __( 'Original title', 'tmdb-plugin' ),
                'value' => $original_title,
            ];
        }

        if ( '' !== $release_date ) {
            $meta_rows[] = [
                'key'   => 'release_date',
                'label' => __( 'Release date', 'tmdb-plugin' ),
                'value' => $release_date,
            ];
        }

        if ( '' !== $runtime ) {
            $meta_rows[] = [
                'key'   => 'runtime',
                'label' => __( 'Runtime', 'tmdb-plugin' ),
                'value' => $runtime,
            ];
        }

        if ( '' !== $status ) {
            $meta_rows[] = [
                'key'   => 'status',
                'label' => __( 'Status', 'tmdb-plugin' ),
                'value' => $status,
            ];
        }

        if ( '' !== $rating ) {
            $meta_rows[] = [
                'key'   => 'rating',
                'label' => __( 'Rating', 'tmdb-plugin' ),
                'value' => $rating,
            ];
        }

        if ( '' !== $origin_display ) {
            $meta_rows[] = [
                'key'   => 'origin_countries',
                'label' => __( 'Origin countries', 'tmdb-plugin' ),
                'value' => $origin_display,
            ];
        }

        if ( '' !== $language_display ) {
            $meta_rows[] = [
                'key'   => 'spoken_languages',
                'label' => __( 'Spoken languages', 'tmdb-plugin' ),
                'value' => $language_display,
            ];
        }

        if ( '' !== $primary_language ) {
            $meta_rows[] = [
                'key'   => 'primary_language',
                'label' => __( 'Primary language', 'tmdb-plugin' ),
                'value' => strtoupper( sanitize_text_field( $primary_language ) ),
            ];
        }

        if ( is_array( $collection ) && ! empty( $collection['name'] ) ) {
            $meta_rows[] = [
                'key'   => 'collection',
                'label' => __( 'Collection', 'tmdb-plugin' ),
                'value' => sanitize_text_field( (string) $collection['name'] ),
            ];
        }

        $hero_genres = [];

        $genre_terms = get_the_terms( $post_id, TMDB_Taxonomies::GENRE );

        if ( $genre_terms && ! is_wp_error( $genre_terms ) ) {
            $hero_genres = array_values(
                array_filter(
                    $genre_terms,
                    static function ( $term ) {
                        return $term instanceof \WP_Term;
                    }
                )
            );
        }

        $taxonomy_sections = [];

        if ( ! empty( $hero_genres ) ) {
            $taxonomy_sections[] = [
                'label' => __( 'Genres', 'tmdb-plugin' ),
                'terms' => $hero_genres,
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

        if ( empty( $director_list ) ) {
            $director_terms = get_the_terms( $post_id, TMDB_Taxonomies::DIRECTOR );

            if ( $director_terms && ! is_wp_error( $director_terms ) ) {
                $taxonomy_sections[] = [
                    'label' => __( 'Directors', 'tmdb-plugin' ),
                    'terms' => $director_terms,
                ];
            }
        }

        $trailer = [];

        if ( is_array( $trailer_meta ) && ! empty( $trailer_meta['site'] ) ) {
            $site          = strtolower( (string) $trailer_meta['site'] );
            $key           = isset( $trailer_meta['key'] ) ? (string) $trailer_meta['key'] : '';
            $watch_url     = isset( $trailer_meta['url'] ) ? (string) $trailer_meta['url'] : '';
            $embed_url     = '';
            $sanitized_key = '' !== $key ? rawurlencode( $key ) : '';

            if ( 'youtube' === $site && '' !== $sanitized_key ) {
                $embed_url = sprintf( 'https://www.youtube.com/embed/%s', $sanitized_key );

                if ( '' === $watch_url ) {
                    $watch_url = sprintf( 'https://www.youtube.com/watch?v=%s', $sanitized_key );
                }
            } elseif ( 'vimeo' === $site && '' !== $sanitized_key ) {
                $embed_url = sprintf( 'https://player.vimeo.com/video/%s', $sanitized_key );

                if ( '' === $watch_url ) {
                    $watch_url = sprintf( 'https://vimeo.com/%s', $sanitized_key );
                }
            }

            $published_display = '';

            if ( ! empty( $trailer_meta['published_at'] ) ) {
                $timestamp = strtotime( (string) $trailer_meta['published_at'] );

                if ( false !== $timestamp ) {
                    $published_display = wp_date( get_option( 'date_format' ), $timestamp );
                }
            }

            $trailer = [
                'name'         => isset( $trailer_meta['name'] ) ? sanitize_text_field( (string) $trailer_meta['name'] ) : '',
                'site'         => isset( $trailer_meta['site'] ) ? sanitize_text_field( (string) $trailer_meta['site'] ) : '',
                'type'         => isset( $trailer_meta['type'] ) ? sanitize_text_field( (string) $trailer_meta['type'] ) : '',
                'official'     => ! empty( $trailer_meta['official'] ),
                'published_at' => $published_display,
                'embed_url'    => esc_url_raw( $embed_url ),
                'watch_url'    => esc_url_raw( $watch_url ),
            ];
        }

        $videos = [];

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

        $websites = [];

        if ( is_array( $websites_meta ) ) {
            foreach ( $websites_meta as $website_entry ) {
                if ( ! is_array( $website_entry ) || empty( $website_entry['url'] ) ) {
                    continue;
                }

                $label = '';

                if ( isset( $website_entry['name'] ) && '' !== $website_entry['name'] ) {
                    $label = (string) $website_entry['name'];
                } elseif ( isset( $website_entry['type'] ) && '' !== $website_entry['type'] ) {
                    $label = (string) $website_entry['type'];
                } elseif ( isset( $website_entry['site'] ) && '' !== $website_entry['site'] ) {
                    $label = (string) $website_entry['site'];
                } else {
                    $label = (string) $website_entry['url'];
                }

                $details = [];

                if ( ! empty( $website_entry['official'] ) ) {
                    $details[] = __( 'Official', 'tmdb-theme' );
                }

                if ( isset( $website_entry['type'] ) && '' !== $website_entry['type'] ) {
                    $details[] = sanitize_text_field( (string) $website_entry['type'] );
                }

                $websites[] = [
                    'label'   => sanitize_text_field( $label ),
                    'url'     => esc_url_raw( (string) $website_entry['url'] ),
                    'details' => $details,
                ];
            }
        }

        $external_ids = [];

        if ( is_array( $external_ids_meta ) ) {
            foreach ( $external_ids_meta as $external_entry ) {
                if ( ! is_array( $external_entry ) || empty( $external_entry['id'] ) ) {
                    continue;
                }

                $label = isset( $external_entry['name'] ) ? (string) $external_entry['name'] : '';
                $value = (string) $external_entry['id'];
                $link  = isset( $external_entry['url'] ) ? (string) $external_entry['url'] : '';

                $external_ids[] = [
                    'label' => '' !== $label ? sanitize_text_field( $label ) : __( 'External ID', 'tmdb-theme' ),
                    'value' => sanitize_text_field( $value ),
                    'link'  => esc_url_raw( $link ),
                ];
            }
        }

        $gallery_items = [];

        if ( is_array( $gallery_ids_meta ) ) {
            foreach ( $gallery_ids_meta as $image_id ) {
                $image_id = (int) $image_id;

                if ( $image_id <= 0 ) {
                    continue;
                }

                $image_html = wp_get_attachment_image( $image_id, 'large', false, [ 'class' => 'img-fluid rounded' ] );
                $image_full = wp_get_attachment_url( $image_id );
                $caption    = wp_get_attachment_caption( $image_id );

                if ( ! $image_html ) {
                    continue;
                }

                $gallery_items[] = [
                    'html'    => $image_html,
                    'full'    => $image_full ? esc_url_raw( $image_full ) : '',
                    'caption' => $caption ? wp_kses_post( $caption ) : '',
                ];
            }
        }

        $has_people    = ! empty( $director_list ) || ! empty( $cast_list );
        $has_videos    = ! empty( $videos );
        $has_gallery   = ! empty( $gallery_items );
        $has_websites  = ! empty( $websites );
        $has_externals = ! empty( $external_ids );

        $primary_official_url = '';

        if ( '' !== $homepage ) {
            $primary_official_url = $homepage;
        } elseif ( is_array( $primary_website ) && ! empty( $primary_website['url'] ) ) {
            $primary_official_url = (string) $primary_website['url'];
        }

        $release_year = '';

        if ( '' !== $release_date_raw ) {
            $timestamp = strtotime( $release_date_raw );

            if ( false !== $timestamp ) {
                $release_year = wp_date( 'Y', $timestamp );
            } elseif ( preg_match( '/^(\d{4})/', $release_date_raw, $matches ) ) {
                $release_year = $matches[1];
            }
        }

        $genre_names = [];

        if ( ! empty( $hero_genres ) ) {
            foreach ( $hero_genres as $genre ) {
                if ( $genre instanceof \WP_Term ) {
                    $genre_names[] = $genre->name;
                }
            }
        }

        $rating_percentage = 0;

        if ( $vote_average > 0 ) {
            $rating_percentage = (int) round( $vote_average * 10 );
        }
    ?>
    <article id="post-<?php the_ID(); ?>" <?php post_class( 'card mb-4' ); ?>>
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <h1 class="h2 mb-1">
                        <?php the_title(); ?>
                        <?php if ( '' !== $release_year ) : ?>
                            <span class="text-muted fw-normal">(<?php echo esc_html( $release_year ); ?>)</span>
                        <?php endif; ?>
                    </h1>
                    <?php if ( ! empty( $genre_names ) ) : ?>
                        <div class="text-muted">
                            <?php echo esc_html( implode( ', ', $genre_names ) ); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if ( $rating_percentage > 0 ) : ?>
                    <div class="tmdb-score-box border rounded d-flex flex-column justify-content-center align-items-center text-center">
                        <span class="fs-2 fw-bold mb-1"><?php echo esc_html( $rating_percentage ); ?>%</span>
                        <?php if ( $vote_count > 0 ) : ?>
                            <span class="text-muted small"><?php printf( esc_html__( '%s ratings', 'tmdb-theme' ), number_format_i18n( $vote_count ) ); ?></span>
                        <?php else : ?>
                            <span class="text-muted small"><?php esc_html_e( 'No ratings yet', 'tmdb-theme' ); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ( '' !== $tagline ) : ?>
                <p class="text-muted mb-4"><?php echo esc_html( $tagline ); ?></p>
            <?php endif; ?>

            <div class="row g-4 align-items-start">
                <div class="col-md-4">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <?php the_post_thumbnail( 'large', [ 'class' => 'img-fluid rounded' ] ); ?>
                    <?php else : ?>
                        <div class="border rounded p-4 text-center text-muted">
                            <?php esc_html_e( 'No poster available', 'tmdb-theme' ); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
                    <?php if ( ! empty( $director_list ) ) : ?>
                        <p class="mb-3">
                            <span class="fw-semibold me-2"><?php esc_html_e( 'Režie:', 'tmdb-theme' ); ?></span>
                            <?php
                            $director_links = [];

                            foreach ( $director_list as $director ) {
                                if ( '' === $director['name'] ) {
                                    continue;
                                }

                                if ( '' !== $director['term_link'] ) {
                                    $director_links[] = sprintf(
                                        '<a href="%1$s">%2$s</a>',
                                        esc_url( $director['term_link'] ),
                                        esc_html( $director['name'] )
                                    );
                                } else {
                                    $director_links[] = esc_html( $director['name'] );
                                }
                            }

                            echo wp_kses_post( implode( ', ', $director_links ) );
                            ?>
                        </p>
                    <?php endif; ?>

                    <?php if ( ! empty( $cast_list ) ) : ?>
                        <p class="mb-0">
                            <span class="fw-semibold me-2"><?php esc_html_e( 'Hlavní herci:', 'tmdb-theme' ); ?></span>
                            <?php
                            $main_cast  = array_slice( $cast_list, 0, 5 );
                            $cast_links = [];

                            foreach ( $main_cast as $cast_member ) {
                                if ( '' === $cast_member['name'] ) {
                                    continue;
                                }

                                if ( '' !== $cast_member['term_link'] ) {
                                    $cast_links[] = sprintf(
                                        '<a href="%1$s">%2$s</a>',
                                        esc_url( $cast_member['term_link'] ),
                                        esc_html( $cast_member['name'] )
                                    );
                                } else {
                                    $cast_links[] = esc_html( $cast_member['name'] );
                                }
                            }

                            echo wp_kses_post( implode( ', ', $cast_links ) );
                            ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </article>

    <section class="card mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3"><?php esc_html_e( 'Obsah', 'tmdb-theme' ); ?></h2>
            <div class="entry-content">
                <?php the_content(); ?>
            </div>
        </div>
    </section>

    <?php
        $tabs = [];

        if ( ! empty( $meta_rows ) || '' !== $primary_official_url || $has_websites || ! empty( $taxonomy_sections ) || ! empty( $alternative_titles ) || $has_externals ) {
            ob_start();

            if ( ! empty( $meta_rows ) ) {
                echo '<dl class="row small">';

                foreach ( $meta_rows as $row ) {
                    echo '<dt class="col-sm-4 text-uppercase text-muted">' . esc_html( $row['label'] ) . '</dt>';
                    echo '<dd class="col-sm-8 mb-2">' . esc_html( $row['value'] ) . '</dd>';
                }

                echo '</dl>';
            }

            if ( '' !== $primary_official_url ) {
                echo '<p><a class="btn btn-outline-primary" href="' . esc_url( $primary_official_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Official website', 'tmdb-plugin' ) . '</a></p>';
            }

            if ( $has_websites ) {
                echo '<h3 class="h6 text-uppercase text-muted mt-4">' . esc_html__( 'Websites', 'tmdb-theme' ) . '</h3>';
                echo '<ul class="list-unstyled mb-0">';

                foreach ( $websites as $website ) {
                    echo '<li class="mb-2">';
                    echo '<a href="' . esc_url( $website['url'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $website['label'] ) . '</a>';

                    if ( ! empty( $website['details'] ) ) {
                        echo '<span class="text-muted small ms-2">' . esc_html( implode( ' · ', $website['details'] ) ) . '</span>';
                    }

                    echo '</li>';
                }

                echo '</ul>';
            }

            if ( $has_externals ) {
                echo '<h3 class="h6 text-uppercase text-muted mt-4">' . esc_html__( 'External IDs', 'tmdb-theme' ) . '</h3>';
                echo '<ul class="list-group list-group-flush mb-0">';

                foreach ( $external_ids as $external ) {
                    echo '<li class="list-group-item px-0">';

                    if ( '' !== $external['link'] ) {
                        echo '<a href="' . esc_url( $external['link'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $external['label'] ) . '</a>';
                    } else {
                        echo '<span class="fw-semibold">' . esc_html( $external['label'] ) . '</span>';
                    }

                    echo '<div class="text-muted small">' . esc_html( $external['value'] ) . '</div>';
                    echo '</li>';
                }

                echo '</ul>';
            }

            if ( ! empty( $taxonomy_sections ) ) {
                foreach ( $taxonomy_sections as $section ) {
                    if ( empty( $section['terms'] ) ) {
                        continue;
                    }

                    echo '<h3 class="h6 text-uppercase text-muted mt-4">' . esc_html( $section['label'] ) . '</h3>';
                    echo '<p>';

                    foreach ( $section['terms'] as $term ) {
                        if ( ! $term instanceof \WP_Term ) {
                            continue;
                        }

                        $term_link = get_term_link( $term );

                        if ( is_wp_error( $term_link ) ) {
                            continue;
                        }

                        echo '<a class="badge text-bg-light border me-1" href="' . esc_url( $term_link ) . '">' . esc_html( $term->name ) . '</a>';
                    }

                    echo '</p>';
                }
            }

            if ( ! empty( $alternative_titles ) ) {
                echo '<h3 class="h6 text-uppercase text-muted mt-4">' . esc_html__( 'Alternative titles', 'tmdb-plugin' ) . '</h3>';
                echo '<p class="mb-0">' . esc_html( implode( ', ', $alternative_titles ) ) . '</p>';
            }

            $tabs[] = [
                'id'      => 'details',
                'label'   => __( 'Detaily', 'tmdb-theme' ),
                'content' => ob_get_clean(),
            ];
        }

        if ( $has_people ) {
            ob_start();

            if ( ! empty( $director_list ) ) {
                echo '<h3 class="h6 text-uppercase text-muted">' . esc_html__( 'Directors', 'tmdb-theme' ) . '</h3>';
                echo '<ul class="list-unstyled mb-4">';

                foreach ( $director_list as $director ) {
                    echo '<li class="mb-2">';

                    if ( '' !== $director['term_link'] ) {
                        echo '<a href="' . esc_url( $director['term_link'] ) . '">' . esc_html( $director['name'] ) . '</a>';
                    } else {
                        echo '<span class="fw-semibold">' . esc_html( $director['name'] ) . '</span>';
                    }

                    if ( '' !== $director['job'] ) {
                        echo '<span class="text-muted small ms-2">' . esc_html( $director['job'] ) . '</span>';
                    }

                    echo '</li>';
                }

                echo '</ul>';
            }

            if ( ! empty( $cast_list ) ) {
                echo '<h3 class="h6 text-uppercase text-muted">' . esc_html__( 'Cast', 'tmdb-theme' ) . '</h3>';
                echo '<div class="row g-3">';

                foreach ( $cast_list as $cast_member ) {
                    echo '<div class="col-sm-6 col-lg-4">';
                    echo '<div class="border rounded p-3 h-100">';

                    if ( '' !== $cast_member['image_url'] ) {
                        echo '<img class="img-fluid rounded mb-3" src="' . esc_url( $cast_member['image_url'] ) . '" alt="' . esc_attr( $cast_member['name'] ) . '">';
                    }

                    if ( '' !== $cast_member['term_link'] ) {
                        echo '<a class="fw-semibold d-block" href="' . esc_url( $cast_member['term_link'] ) . '">' . esc_html( $cast_member['name'] ) . '</a>';
                    } else {
                        echo '<span class="fw-semibold d-block">' . esc_html( $cast_member['name'] ) . '</span>';
                    }

                    if ( '' !== $cast_member['character'] ) {
                        echo '<span class="text-muted small d-block">' . esc_html( $cast_member['character'] ) . '</span>';
                    }

                    if ( '' !== $cast_member['original_name'] && $cast_member['original_name'] !== $cast_member['name'] ) {
                        echo '<span class="text-muted small d-block">' . esc_html( $cast_member['original_name'] ) . '</span>';
                    }

                    echo '</div>';
                    echo '</div>';
                }

                echo '</div>';
            }

            $tabs[] = [
                'id'      => 'people',
                'label'   => __( 'Obsazení', 'tmdb-theme' ),
                'content' => ob_get_clean(),
            ];
        }

        if ( $has_videos || ! empty( $trailer ) ) {
            ob_start();

            if ( ! empty( $trailer ) ) {
                echo '<div class="mb-4">';
                echo '<h3 class="h6 text-uppercase text-muted">' . esc_html__( 'Trailer', 'tmdb-theme' ) . '</h3>';

                if ( '' !== $trailer['embed_url'] ) {
                    echo '<div class="ratio ratio-16x9 mb-2">';
                    echo '<iframe src="' . esc_url( $trailer['embed_url'] ) . '" title="' . esc_attr( $trailer['name'] ) . '" allowfullscreen loading="lazy"></iframe>';
                    echo '</div>';
                } elseif ( '' !== $trailer['watch_url'] ) {
                    echo '<a class="btn btn-primary" href="' . esc_url( $trailer['watch_url'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Watch trailer', 'tmdb-theme' ) . '</a>';
                }

                echo '<div class="small text-muted">';

                if ( '' !== $trailer['type'] ) {
                    echo '<span class="me-2">' . esc_html( $trailer['type'] ) . '</span>';
                }

                if ( '' !== $trailer['site'] ) {
                    echo '<span class="me-2">' . esc_html( $trailer['site'] ) . '</span>';
                }

                if ( ! empty( $trailer['official'] ) ) {
                    echo '<span class="me-2">' . esc_html__( 'Official', 'tmdb-plugin' ) . '</span>';
                }

                if ( '' !== $trailer['published_at'] ) {
                    echo '<span>' . esc_html( $trailer['published_at'] ) . '</span>';
                }

                echo '</div>';
                echo '</div>';
            }

            if ( $has_videos ) {
                echo '<div class="row g-4">';

                foreach ( $videos as $video ) {
                    echo '<div class="col-lg-6">';
                    echo '<div class="border rounded p-3 h-100">';

                    if ( '' !== $video['embed_url'] ) {
                        echo '<div class="ratio ratio-16x9 mb-3">';
                        echo '<iframe src="' . esc_url( $video['embed_url'] ) . '" title="' . esc_attr( $video['name'] ) . '" allowfullscreen loading="lazy"></iframe>';
                        echo '</div>';
                    } elseif ( '' !== $video['video_url'] ) {
                        echo '<a class="btn btn-outline-primary mb-3" href="' . esc_url( $video['video_url'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Watch video', 'tmdb-plugin' ) . '</a>';
                    }

                    if ( '' !== $video['name'] ) {
                        echo '<h3 class="h6 mb-2">' . esc_html( $video['name'] ) . '</h3>';
                    }

                    echo '<ul class="list-unstyled small text-muted mb-0">';

                    if ( '' !== $video['type'] ) {
                        echo '<li>' . esc_html__( 'Type', 'tmdb-plugin' ) . ': ' . esc_html( $video['type'] ) . '</li>';
                    }

                    if ( '' !== $video['site'] ) {
                        echo '<li>' . esc_html__( 'Platform', 'tmdb-plugin' ) . ': ' . esc_html( $video['site'] ) . '</li>';
                    }

                    if ( '' !== $video['country'] ) {
                        echo '<li>' . esc_html__( 'Country', 'tmdb-plugin' ) . ': ' . esc_html( $video['country'] ) . '</li>';
                    }

                    if ( '' !== $video['language'] ) {
                        echo '<li>' . esc_html__( 'Language', 'tmdb-plugin' ) . ': ' . esc_html( $video['language'] ) . '</li>';
                    }

                    if ( ! empty( $video['official'] ) ) {
                        echo '<li>' . esc_html__( 'Official', 'tmdb-plugin' ) . '</li>';
                    }

                    if ( '' !== $video['published_at'] ) {
                        echo '<li>' . esc_html__( 'Published', 'tmdb-plugin' ) . ': ' . esc_html( $video['published_at'] ) . '</li>';
                    }

                    echo '</ul>';
                    echo '</div>';
                    echo '</div>';
                }

                echo '</div>';
            }

            $tabs[] = [
                'id'      => 'videos',
                'label'   => __( 'Videa', 'tmdb-theme' ),
                'content' => ob_get_clean(),
            ];
        }

        if ( $has_gallery ) {
            ob_start();

            echo '<div class="row g-4">';

            foreach ( $gallery_items as $item ) {
                echo '<div class="col-sm-6 col-lg-4">';
                echo '<figure class="mb-0">';

                if ( '' !== $item['full'] ) {
                    echo '<a href="' . esc_url( $item['full'] ) . '" target="_blank" rel="noopener noreferrer">' . $item['html'] . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                } else {
                    echo $item['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                }

                if ( '' !== $item['caption'] ) {
                    echo '<figcaption class="small text-muted mt-2">' . $item['caption'] . '</figcaption>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                }

                echo '</figure>';
                echo '</div>';
            }

            echo '</div>';

            $tabs[] = [
                'id'      => 'gallery',
                'label'   => __( 'Galerie', 'tmdb-theme' ),
                'content' => ob_get_clean(),
            ];
        }
    ?>

    <?php if ( ! empty( $tabs ) ) : ?>
        <section class="card mb-4">
            <div class="card-body">
                <ul class="nav nav-tabs" id="movieTabs" role="tablist">
                    <?php foreach ( $tabs as $index => $tab ) :
                        $is_active = 0 === $index;
                        ?>
                        <li class="nav-item" role="presentation">
                            <button
                                class="nav-link<?php echo $is_active ? ' active' : ''; ?>"
                                id="tab-<?php echo esc_attr( $tab['id'] ); ?>"
                                data-bs-toggle="tab"
                                data-bs-target="#pane-<?php echo esc_attr( $tab['id'] ); ?>"
                                type="button"
                                role="tab"
                                aria-controls="pane-<?php echo esc_attr( $tab['id'] ); ?>"
                                aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
                            >
                                <?php echo esc_html( $tab['label'] ); ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="tab-content pt-4" id="movieTabsContent">
                    <?php foreach ( $tabs as $index => $tab ) :
                        $is_active = 0 === $index;
                        ?>
                        <div
                            class="tab-pane fade<?php echo $is_active ? ' show active' : ''; ?>"
                            id="pane-<?php echo esc_attr( $tab['id'] ); ?>"
                            role="tabpanel"
                            aria-labelledby="tab-<?php echo esc_attr( $tab['id'] ); ?>"
                        >
                            <?php echo $tab['content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
    <?php
        if ( comments_open() || get_comments_number() ) {
            echo '<div class="card card-body mb-4">';
            comments_template();
            echo '</div>';
        }
    endwhile;
    ?>
</div>

<?php
get_footer();
