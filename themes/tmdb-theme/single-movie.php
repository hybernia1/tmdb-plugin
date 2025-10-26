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
    ?>
    <article id="post-<?php the_ID(); ?>" <?php post_class( 'card mb-4' ); ?>>
        <div class="card-body">
            <div class="row g-4 align-items-start">
                <div class="col-md-4">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <?php the_post_thumbnail( 'large', [ 'class' => 'img-fluid rounded' ] ); ?>
                    <?php else : ?>
                        <div class="border rounded p-4 text-center text-muted">
                            <?php esc_html_e( 'No poster available', 'tmdb-theme' ); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( $vote_average > 0 ) : ?>
                        <div class="mt-4">
                            <h2 class="h6"><?php esc_html_e( 'User score', 'tmdb-theme' ); ?></h2>
                            <p class="mb-1 fw-semibold"><?php printf( esc_html__( '%s out of 10', 'tmdb-theme' ), number_format_i18n( $vote_average, 1 ) ); ?></p>
                            <?php if ( $vote_count > 0 ) : ?>
                                <p class="text-muted small mb-0"><?php printf( esc_html__( 'Based on %s votes', 'tmdb-theme' ), number_format_i18n( $vote_count ) ); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( $has_externals ) : ?>
                        <div class="mt-4">
                            <h2 class="h6"><?php esc_html_e( 'External IDs', 'tmdb-theme' ); ?></h2>
                            <ul class="list-group list-group-flush">
                                <?php foreach ( $external_ids as $external ) : ?>
                                    <li class="list-group-item px-0">
                                        <?php if ( '' !== $external['link'] ) : ?>
                                            <a href="<?php echo esc_url( $external['link'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $external['label'] ); ?></a>
                                        <?php else : ?>
                                            <span class="fw-semibold"><?php echo esc_html( $external['label'] ); ?></span>
                                        <?php endif; ?>
                                        <div class="text-muted small"><?php echo esc_html( $external['value'] ); ?></div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
                    <h1 class="h2 mb-2"><?php the_title(); ?></h1>
                    <?php if ( '' !== $tagline ) : ?>
                        <p class="text-muted mb-3"><?php echo esc_html( $tagline ); ?></p>
                    <?php endif; ?>

                    <?php if ( ! empty( $hero_genres ) ) : ?>
                        <p>
                            <?php foreach ( $hero_genres as $genre ) :
                                if ( ! $genre instanceof \WP_Term ) {
                                    continue;
                                }

                                $genre_link = get_term_link( $genre );

                                if ( is_wp_error( $genre_link ) ) {
                                    $genre_link = '';
                                }

                                if ( '' !== $genre_link ) : ?>
                                    <a class="badge text-bg-secondary me-1" href="<?php echo esc_url( $genre_link ); ?>"><?php echo esc_html( $genre->name ); ?></a>
                                <?php else : ?>
                                    <span class="badge text-bg-secondary me-1"><?php echo esc_html( $genre->name ); ?></span>
                                <?php endif;
                            endforeach; ?>
                        </p>
                    <?php endif; ?>

                    <?php if ( ! empty( $meta_rows ) ) : ?>
                        <dl class="row small">
                            <?php foreach ( $meta_rows as $row ) : ?>
                                <dt class="col-sm-4 text-uppercase text-muted"><?php echo esc_html( $row['label'] ); ?></dt>
                                <dd class="col-sm-8 mb-2"><?php echo esc_html( $row['value'] ); ?></dd>
                            <?php endforeach; ?>
                        </dl>
                    <?php endif; ?>

                    <?php if ( '' !== $primary_official_url ) : ?>
                        <a class="btn btn-outline-primary me-2" href="<?php echo esc_url( $primary_official_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Official website', 'tmdb-plugin' ); ?></a>
                    <?php endif; ?>

                    <?php if ( ! empty( $trailer ) ) : ?>
                        <div class="mt-4">
                            <h2 class="h6"><?php esc_html_e( 'Trailer', 'tmdb-plugin' ); ?></h2>
                            <?php if ( '' !== $trailer['embed_url'] ) : ?>
                                <div class="ratio ratio-16x9 mb-2">
                                    <iframe src="<?php echo esc_url( $trailer['embed_url'] ); ?>" title="<?php echo esc_attr( $trailer['name'] ); ?>" allowfullscreen loading="lazy"></iframe>
                                </div>
                            <?php elseif ( '' !== $trailer['watch_url'] ) : ?>
                                <a class="btn btn-primary" href="<?php echo esc_url( $trailer['watch_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Watch trailer', 'tmdb-plugin' ); ?></a>
                            <?php endif; ?>
                            <div class="small text-muted">
                                <?php if ( '' !== $trailer['type'] ) : ?>
                                    <span class="me-2"><?php echo esc_html( $trailer['type'] ); ?></span>
                                <?php endif; ?>
                                <?php if ( '' !== $trailer['site'] ) : ?>
                                    <span class="me-2"><?php echo esc_html( $trailer['site'] ); ?></span>
                                <?php endif; ?>
                                <?php if ( ! empty( $trailer['official'] ) ) : ?>
                                    <span class="me-2"><?php esc_html_e( 'Official', 'tmdb-plugin' ); ?></span>
                                <?php endif; ?>
                                <?php if ( '' !== $trailer['published_at'] ) : ?>
                                    <span><?php echo esc_html( $trailer['published_at'] ); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </article>

    <section class="card mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3"><?php esc_html_e( 'Overview', 'tmdb-theme' ); ?></h2>
            <div class="entry-content mb-4">
                <?php the_content(); ?>
            </div>

            <?php if ( $has_websites ) : ?>
                <h3 class="h6 text-uppercase text-muted"><?php esc_html_e( 'Websites', 'tmdb-theme' ); ?></h3>
                <ul class="list-unstyled mb-4">
                    <?php foreach ( $websites as $website ) : ?>
                        <li class="mb-2">
                            <a href="<?php echo esc_url( $website['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $website['label'] ); ?></a>
                            <?php if ( ! empty( $website['details'] ) ) : ?>
                                <span class="text-muted small ms-2"><?php echo esc_html( implode( ' · ', $website['details'] ) ); ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ( ! empty( $taxonomy_sections ) ) : ?>
                <?php foreach ( $taxonomy_sections as $section ) :
                    if ( empty( $section['terms'] ) ) {
                        continue;
                    }
                    ?>
                    <h3 class="h6 text-uppercase text-muted mt-4"><?php echo esc_html( $section['label'] ); ?></h3>
                    <p>
                        <?php foreach ( $section['terms'] as $term ) :
                            if ( ! $term instanceof \WP_Term ) {
                                continue;
                            }

                            $term_link = get_term_link( $term );

                            if ( is_wp_error( $term_link ) ) {
                                continue;
                            }
                            ?>
                            <a class="badge text-bg-light border me-1" href="<?php echo esc_url( $term_link ); ?>"><?php echo esc_html( $term->name ); ?></a>
                        <?php endforeach; ?>
                    </p>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if ( ! empty( $alternative_titles ) ) : ?>
                <h3 class="h6 text-uppercase text-muted mt-4"><?php esc_html_e( 'Alternative titles', 'tmdb-plugin' ); ?></h3>
                <p class="mb-0"><?php echo esc_html( implode( ', ', $alternative_titles ) ); ?></p>
            <?php endif; ?>
        </div>
    </section>

    <?php if ( $has_people ) : ?>
        <section class="card mb-4">
            <div class="card-body">
                <h2 class="h5 mb-3"><?php esc_html_e( 'Cast & Crew', 'tmdb-theme' ); ?></h2>

                <?php if ( ! empty( $director_list ) ) : ?>
                    <h3 class="h6 text-uppercase text-muted"><?php esc_html_e( 'Directors', 'tmdb-theme' ); ?></h3>
                    <ul class="list-unstyled mb-4">
                        <?php foreach ( $director_list as $director ) : ?>
                            <li class="mb-2">
                                <?php if ( '' !== $director['term_link'] ) : ?>
                                    <a href="<?php echo esc_url( $director['term_link'] ); ?>"><?php echo esc_html( $director['name'] ); ?></a>
                                <?php else : ?>
                                    <span class="fw-semibold"><?php echo esc_html( $director['name'] ); ?></span>
                                <?php endif; ?>
                                <?php if ( '' !== $director['job'] ) : ?>
                                    <span class="text-muted small ms-2"><?php echo esc_html( $director['job'] ); ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if ( ! empty( $cast_list ) ) : ?>
                    <h3 class="h6 text-uppercase text-muted"><?php esc_html_e( 'Cast', 'tmdb-theme' ); ?></h3>
                    <div class="row g-3">
                        <?php foreach ( $cast_list as $cast_member ) : ?>
                            <div class="col-sm-6 col-lg-4">
                                <div class="border rounded p-3 h-100">
                                    <?php if ( '' !== $cast_member['image_url'] ) : ?>
                                        <img class="img-fluid rounded mb-3" src="<?php echo esc_url( $cast_member['image_url'] ); ?>" alt="<?php echo esc_attr( $cast_member['name'] ); ?>">
                                    <?php endif; ?>
                                    <?php if ( '' !== $cast_member['term_link'] ) : ?>
                                        <a class="fw-semibold d-block" href="<?php echo esc_url( $cast_member['term_link'] ); ?>"><?php echo esc_html( $cast_member['name'] ); ?></a>
                                    <?php else : ?>
                                        <span class="fw-semibold d-block"><?php echo esc_html( $cast_member['name'] ); ?></span>
                                    <?php endif; ?>
                                    <?php if ( '' !== $cast_member['character'] ) : ?>
                                        <span class="text-muted small d-block"><?php echo esc_html( $cast_member['character'] ); ?></span>
                                    <?php endif; ?>
                                    <?php if ( '' !== $cast_member['original_name'] && $cast_member['original_name'] !== $cast_member['name'] ) : ?>
                                        <span class="text-muted small d-block"><?php echo esc_html( $cast_member['original_name'] ); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ( $has_videos ) : ?>
        <section class="card mb-4">
            <div class="card-body">
                <h2 class="h5 mb-3"><?php esc_html_e( 'Videos', 'tmdb-theme' ); ?></h2>
                <div class="row g-4">
                    <?php foreach ( $videos as $video ) : ?>
                        <div class="col-lg-6">
                            <div class="border rounded p-3 h-100">
                                <?php if ( '' !== $video['embed_url'] ) : ?>
                                    <div class="ratio ratio-16x9 mb-3">
                                        <iframe src="<?php echo esc_url( $video['embed_url'] ); ?>" title="<?php echo esc_attr( $video['name'] ); ?>" allowfullscreen loading="lazy"></iframe>
                                    </div>
                                <?php elseif ( '' !== $video['video_url'] ) : ?>
                                    <a class="btn btn-outline-primary mb-3" href="<?php echo esc_url( $video['video_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Watch video', 'tmdb-plugin' ); ?></a>
                                <?php endif; ?>
                                <?php if ( '' !== $video['name'] ) : ?>
                                    <h3 class="h6 mb-2"><?php echo esc_html( $video['name'] ); ?></h3>
                                <?php endif; ?>
                                <ul class="list-unstyled small text-muted mb-0">
                                    <?php if ( '' !== $video['type'] ) : ?>
                                        <li><?php esc_html_e( 'Type', 'tmdb-plugin' ); ?>: <?php echo esc_html( $video['type'] ); ?></li>
                                    <?php endif; ?>
                                    <?php if ( '' !== $video['site'] ) : ?>
                                        <li><?php esc_html_e( 'Platform', 'tmdb-plugin' ); ?>: <?php echo esc_html( $video['site'] ); ?></li>
                                    <?php endif; ?>
                                    <?php if ( '' !== $video['country'] ) : ?>
                                        <li><?php esc_html_e( 'Country', 'tmdb-plugin' ); ?>: <?php echo esc_html( $video['country'] ); ?></li>
                                    <?php endif; ?>
                                    <?php if ( '' !== $video['language'] ) : ?>
                                        <li><?php esc_html_e( 'Language', 'tmdb-plugin' ); ?>: <?php echo esc_html( $video['language'] ); ?></li>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $video['official'] ) ) : ?>
                                        <li><?php esc_html_e( 'Official', 'tmdb-plugin' ); ?></li>
                                    <?php endif; ?>
                                    <?php if ( '' !== $video['published_at'] ) : ?>
                                        <li><?php esc_html_e( 'Published', 'tmdb-plugin' ); ?>: <?php echo esc_html( $video['published_at'] ); ?></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ( $has_gallery ) : ?>
        <section class="card mb-4">
            <div class="card-body">
                <h2 class="h5 mb-3"><?php esc_html_e( 'Gallery', 'tmdb-theme' ); ?></h2>
                <div class="row g-4">
                    <?php foreach ( $gallery_items as $item ) : ?>
                        <div class="col-sm-6 col-lg-4">
                            <figure class="mb-0">
                                <?php if ( '' !== $item['full'] ) : ?>
                                    <a href="<?php echo esc_url( $item['full'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo $item['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                                <?php else : ?>
                                    <?php echo $item['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <?php endif; ?>
                                <?php if ( '' !== $item['caption'] ) : ?>
                                    <figcaption class="small text-muted mt-2"><?php echo $item['caption']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></figcaption>
                                <?php endif; ?>
                            </figure>
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
