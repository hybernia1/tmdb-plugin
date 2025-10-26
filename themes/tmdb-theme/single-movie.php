<?php
/**
 * Single movie template optimized for a tabbed Bootstrap layout.
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
                'label' => __( 'Original title', 'tmdb-plugin' ),
                'value' => $original_title,
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

        if ( '' !== $primary_language ) {
            $meta_rows[] = [
                'label' => __( 'Primary language', 'tmdb-plugin' ),
                'value' => strtoupper( sanitize_text_field( $primary_language ) ),
            ];
        }

        if ( is_array( $collection ) && ! empty( $collection['name'] ) ) {
            $meta_rows[] = [
                'label' => __( 'Collection', 'tmdb-plugin' ),
                'value' => sanitize_text_field( (string) $collection['name'] ),
            ];
        }

        $taxonomy_sections = [];

        $director_terms = get_the_terms( $post_id, TMDB_Taxonomies::DIRECTOR );
        $genre_terms    = get_the_terms( $post_id, TMDB_Taxonomies::GENRE );

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

        $trailer = [];

        if ( is_array( $trailer_meta ) && ! empty( $trailer_meta['site'] ) ) {
            $site         = strtolower( (string) $trailer_meta['site'] );
            $key          = isset( $trailer_meta['key'] ) ? (string) $trailer_meta['key'] : '';
            $watch_url    = isset( $trailer_meta['url'] ) ? (string) $trailer_meta['url'] : '';
            $embed_url    = '';
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

                if ( isset( $website_entry['iso_3166_1'] ) && '' !== $website_entry['iso_3166_1'] ) {
                    $details[] = strtoupper( sanitize_text_field( (string) $website_entry['iso_3166_1'] ) );
                }

                if ( isset( $website_entry['site'] ) && '' !== $website_entry['site'] ) {
                    $details[] = sanitize_text_field( (string) $website_entry['site'] );
                }

                $websites[] = [
                    'url'     => esc_url( (string) $website_entry['url'] ),
                    'label'   => sanitize_text_field( $label ),
                    'details' => $details,
                ];
            }
        }

        $external_ids = [];

        if ( is_array( $external_ids_meta ) ) {
            $external_links = [
                'imdb_id'     => 'https://www.imdb.com/title/%s/',
                'facebook_id' => 'https://www.facebook.com/%s',
                'instagram_id'=> 'https://www.instagram.com/%s',
                'twitter_id'  => 'https://twitter.com/%s',
                'wikidata_id' => 'https://www.wikidata.org/wiki/%s',
                'tiktok_id'   => 'https://www.tiktok.com/@%s',
                'youtube_id'  => 'https://www.youtube.com/%s',
            ];

            foreach ( $external_ids_meta as $key => $value ) {
                if ( ! is_string( $key ) || '' === $key ) {
                    continue;
                }

                $normalized_key = sanitize_key( $key );
                $clean_value    = sanitize_text_field( (string) $value );

                if ( '' === $clean_value ) {
                    continue;
                }

                $label = ucwords( str_replace( '_', ' ', $normalized_key ) );
                $link  = '';

                if ( isset( $external_links[ $normalized_key ] ) ) {
                    $link = sprintf( $external_links[ $normalized_key ], rawurlencode( $clean_value ) );
                }

                $external_ids[] = [
                    'label' => $label,
                    'value' => $clean_value,
                    'link'  => $link,
                ];
            }
        }

        $gallery_items = [];

        if ( is_array( $gallery_ids_meta ) ) {
            foreach ( $gallery_ids_meta as $attachment_id ) {
                $attachment_id = (int) $attachment_id;

                if ( $attachment_id <= 0 ) {
                    continue;
                }

                $image_html = wp_get_attachment_image( $attachment_id, 'large', false, [
                    'class'   => 'img-fluid rounded shadow-sm w-100',
                    'loading' => 'lazy',
                ] );

                if ( ! $image_html ) {
                    continue;
                }

                $full_url = wp_get_attachment_url( $attachment_id );
                $caption  = get_post_field( 'post_excerpt', $attachment_id );

                $gallery_items[] = [
                    'html'    => $image_html,
                    'full'    => $full_url ? esc_url( $full_url ) : '',
                    'caption' => $caption ? wp_kses_post( $caption ) : '',
                ];
            }
        }

        if ( empty( $videos ) && ! empty( $trailer ) ) {
            $videos[] = [
                'name'         => $trailer['name'],
                'site'         => $trailer['site'],
                'type'         => '' !== $trailer['type'] ? $trailer['type'] : __( 'Trailer', 'tmdb-plugin' ),
                'country'      => '',
                'language'     => '',
                'official'     => $trailer['official'],
                'published_at' => $trailer['published_at'],
                'video_url'    => $trailer['watch_url'],
                'embed_url'    => $trailer['embed_url'],
            ];
        }

        $has_people   = ! empty( $director_list ) || ! empty( $cast_list );
        $has_videos   = ! empty( $videos );
        $has_gallery  = ! empty( $gallery_items );
        $has_trailer  = ! empty( $trailer );

        $summary_badges = [];

        if ( '' !== $release_date ) {
            $summary_badges[] = [
                'label' => __( 'Release', 'tmdb-theme' ),
                'value' => $release_date,
            ];
        }

        if ( '' !== $runtime ) {
            $summary_badges[] = [
                'label' => __( 'Runtime', 'tmdb-theme' ),
                'value' => $runtime,
            ];
        }

        if ( '' !== $status ) {
            $summary_badges[] = [
                'label' => __( 'Status', 'tmdb-theme' ),
                'value' => $status,
            ];
        }

        if ( '' !== $rating ) {
            $summary_badges[] = [
                'label' => __( 'Rating', 'tmdb-theme' ),
                'value' => $rating,
            ];
        }

        $tabs = [
            'overview' => __( 'Overview', 'tmdb-theme' ),
        ];

        if ( $has_people ) {
            $tabs['people'] = __( 'Cast & Crew', 'tmdb-theme' );
        }

        if ( $has_videos ) {
            $tabs['videos'] = __( 'Videos', 'tmdb-theme' );
        }

        if ( $has_gallery ) {
            $tabs['gallery'] = __( 'Gallery', 'tmdb-theme' );
        }
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class( 'movie-detail card border-0 shadow-sm' ); ?>>
            <div class="card-body p-4 p-lg-5">
                <header class="mb-4 text-center text-lg-start">
                    <h1 class="display-4 fw-bold mb-2"><?php the_title(); ?></h1>
                    <?php if ( '' !== $tagline ) : ?>
                        <p class="lead text-muted mb-0"><?php echo esc_html( $tagline ); ?></p>
                    <?php endif; ?>
                </header>

                <div class="row g-4 align-items-start mb-4">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <div class="col-lg-4 col-xl-3">
                            <div class="ratio ratio-2x3 rounded overflow-hidden shadow-sm">
                                <?php the_post_thumbnail( 'large', [ 'class' => 'w-100 h-100 object-fit-cover' ] ); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="col">
                        <?php if ( $has_trailer ) : ?>
                            <section class="mb-4">
                                <div class="card border-0 shadow-sm overflow-hidden">
                                    <?php if ( '' !== $trailer['embed_url'] ) : ?>
                                        <div class="ratio ratio-16x9">
                                            <iframe src="<?php echo esc_url( $trailer['embed_url'] ); ?>" title="<?php echo esc_attr( $trailer['name'] ); ?>" allowfullscreen loading="lazy"></iframe>
                                        </div>
                                    <?php elseif ( '' !== $trailer['watch_url'] ) : ?>
                                        <div class="card-body">
                                            <a class="btn btn-outline-primary" href="<?php echo esc_url( $trailer['watch_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Watch trailer', 'tmdb-plugin' ); ?></a>
                                        </div>
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <?php if ( '' !== $trailer['name'] ) : ?>
                                            <h2 class="h5 fw-semibold mb-1"><?php echo esc_html( $trailer['name'] ); ?></h2>
                                        <?php endif; ?>
                                        <div class="d-flex flex-wrap gap-2 small text-muted">
                                            <?php if ( '' !== $trailer['type'] ) : ?>
                                                <span class="badge bg-primary-subtle text-primary-emphasis"><?php echo esc_html( $trailer['type'] ); ?></span>
                                            <?php endif; ?>
                                            <?php if ( '' !== $trailer['site'] ) : ?>
                                                <span class="badge bg-secondary-subtle text-secondary"><?php echo esc_html( $trailer['site'] ); ?></span>
                                            <?php endif; ?>
                                            <?php if ( ! empty( $trailer['official'] ) ) : ?>
                                                <span class="badge bg-success-subtle text-success"><?php esc_html_e( 'Official', 'tmdb-plugin' ); ?></span>
                                            <?php endif; ?>
                                            <?php if ( '' !== $trailer['published_at'] ) : ?>
                                                <span><?php echo esc_html( $trailer['published_at'] ); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        <?php endif; ?>

                        <?php if ( ! empty( $summary_badges ) ) : ?>
                            <div class="row g-3 mb-3">
                                <?php foreach ( $summary_badges as $badge ) : ?>
                                    <div class="col-sm-6 col-xl-3">
                                        <div class="tmdb-summary-tile">
                                            <span class="tmdb-summary-label"><?php echo esc_html( $badge['label'] ); ?></span>
                                            <span class="tmdb-summary-value"><?php echo esc_html( $badge['value'] ); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ( '' !== $homepage ) : ?>
                            <a class="btn btn-outline-primary" href="<?php echo esc_url( $homepage ); ?>" target="_blank" rel="noopener noreferrer">
                                <?php esc_html_e( 'Official website', 'tmdb-plugin' ); ?>
                            </a>
                        <?php elseif ( is_array( $primary_website ) && ! empty( $primary_website['url'] ) ) : ?>
                            <a class="btn btn-outline-primary" href="<?php echo esc_url( (string) $primary_website['url'] ); ?>" target="_blank" rel="noopener noreferrer">
                                <?php esc_html_e( 'Official website', 'tmdb-plugin' ); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <ul class="nav nav-tabs" id="movie-detail-tabs" role="tablist">
                    <?php
                    $index = 0;
                    foreach ( $tabs as $slug => $label ) :
                        $is_active = 0 === $index;
                        ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link<?php echo $is_active ? ' active' : ''; ?>" id="<?php echo esc_attr( $slug ); ?>-tab" data-bs-toggle="tab" data-bs-target="#<?php echo esc_attr( $slug ); ?>" type="button" role="tab" aria-controls="<?php echo esc_attr( $slug ); ?>" aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>">
                                <?php echo esc_html( $label ); ?>
                            </button>
                        </li>
                        <?php
                        $index++;
                    endforeach;
                    ?>
                </ul>

                <div class="tab-content pt-4" id="movie-detail-tabs-content">
                    <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                        <?php if ( ! empty( $meta_rows ) ) : ?>
                            <div class="row g-3 mb-4">
                                <?php foreach ( $meta_rows as $row ) : ?>
                                    <div class="col-md-6 col-xl-4">
                                        <div class="tmdb-meta-tile h-100">
                                            <h2 class="h6 text-uppercase text-muted mb-1"><?php echo esc_html( $row['label'] ); ?></h2>
                                            <p class="mb-0 fw-semibold"><?php echo esc_html( $row['value'] ); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ( ! empty( $websites ) ) : ?>
                            <section class="mb-4">
                                <h2 class="h6 text-uppercase text-muted mb-2"><?php esc_html_e( 'Websites', 'tmdb-theme' ); ?></h2>
                                <ul class="list-unstyled tmdb-website-list mb-0">
                                    <?php foreach ( $websites as $website ) : ?>
                                        <li class="tmdb-website-item">
                                            <a class="fw-semibold" href="<?php echo esc_url( $website['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $website['label'] ); ?></a>
                                            <?php if ( ! empty( $website['details'] ) ) : ?>
                                                <span class="text-muted small ms-2"><?php echo esc_html( implode( ' · ', $website['details'] ) ); ?></span>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </section>
                        <?php endif; ?>

                        <div class="entry-content mb-4">
                            <?php the_content(); ?>
                        </div>

                        <?php foreach ( $taxonomy_sections as $section ) :
                            if ( empty( $section['terms'] ) ) {
                                continue;
                            }
                            ?>
                            <section class="mb-4">
                                <h2 class="h6 text-uppercase text-muted fw-semibold mb-2"><?php echo esc_html( $section['label'] ); ?></h2>
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
                                        <a class="badge bg-secondary-subtle text-secondary" href="<?php echo esc_url( $term_link ); ?>"><?php echo esc_html( $term->name ); ?></a>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endforeach; ?>

                        <?php if ( ! empty( $alternative_titles ) ) : ?>
                            <section class="mb-4">
                                <h2 class="h6 text-uppercase text-muted fw-semibold mb-2"><?php esc_html_e( 'Alternative titles', 'tmdb-plugin' ); ?></h2>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ( $alternative_titles as $title ) : ?>
                                        <span class="badge bg-light text-dark border"><?php echo esc_html( $title ); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endif; ?>

                        <?php if ( ! empty( $external_ids ) ) : ?>
                            <section>
                                <h2 class="h6 text-uppercase text-muted fw-semibold mb-2"><?php esc_html_e( 'External IDs', 'tmdb-theme' ); ?></h2>
                                <dl class="row gy-2">
                                    <?php foreach ( $external_ids as $external ) : ?>
                                        <dt class="col-sm-4 col-lg-3 text-muted"><?php echo esc_html( $external['label'] ); ?></dt>
                                        <dd class="col-sm-8 col-lg-9 mb-0">
                                            <?php if ( '' !== $external['link'] ) : ?>
                                                <a href="<?php echo esc_url( $external['link'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $external['value'] ); ?></a>
                                            <?php else : ?>
                                                <?php echo esc_html( $external['value'] ); ?>
                                            <?php endif; ?>
                                        </dd>
                                    <?php endforeach; ?>
                                </dl>
                            </section>
                        <?php endif; ?>
                    </div>

                    <?php if ( isset( $tabs['people'] ) ) : ?>
                        <div class="tab-pane fade" id="people" role="tabpanel" aria-labelledby="people-tab">
                            <?php if ( ! empty( $director_list ) ) : ?>
                                <section class="mb-5">
                                    <h2 class="h5 fw-semibold mb-3"><?php esc_html_e( 'Directors', 'tmdb-plugin' ); ?></h2>
                                    <div class="row g-3">
                                        <?php foreach ( $director_list as $director ) : ?>
                                            <div class="col-sm-6 col-lg-4">
                                                <article class="card h-100 border-0 shadow-sm">
                                                    <div class="card-body d-flex gap-3 align-items-center">
                                                        <?php if ( '' !== $director['image_url'] ) : ?>
                                                            <span class="avatar flex-shrink-0 rounded-circle overflow-hidden">
                                                                <img class="w-100 h-100 object-fit-cover" src="<?php echo esc_url( $director['image_url'] ); ?>" alt="<?php echo esc_attr( $director['name'] ); ?>" loading="lazy" />
                                                            </span>
                                                        <?php endif; ?>
                                                        <div class="flex-grow-1 position-relative">
                                                            <?php if ( '' !== $director['term_link'] ) : ?>
                                                                <a class="stretched-link fw-semibold text-reset text-decoration-none" href="<?php echo esc_url( $director['term_link'] ); ?>"><?php echo esc_html( $director['name'] ); ?></a>
                                                            <?php else : ?>
                                                                <span class="fw-semibold"><?php echo esc_html( $director['name'] ); ?></span>
                                                            <?php endif; ?>
                                                            <?php if ( '' !== $director['original_name'] && $director['original_name'] !== $director['name'] ) : ?>
                                                                <div class="text-muted small"><?php echo esc_html( $director['original_name'] ); ?></div>
                                                            <?php endif; ?>
                                                            <?php if ( '' !== $director['job'] ) : ?>
                                                                <div class="text-muted small"><?php echo esc_html( $director['job'] ); ?></div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </article>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </section>
                            <?php endif; ?>

                            <?php if ( ! empty( $cast_list ) ) : ?>
                                <section>
                                    <h2 class="h5 fw-semibold mb-3"><?php esc_html_e( 'Cast', 'tmdb-plugin' ); ?></h2>
                                    <div class="row g-3">
                                        <?php foreach ( $cast_list as $cast_member ) : ?>
                                            <div class="col-sm-6 col-lg-4">
                                                <article class="card h-100 border-0 shadow-sm">
                                                    <div class="card-body d-flex gap-3 align-items-center">
                                                        <?php if ( '' !== $cast_member['image_url'] ) : ?>
                                                            <span class="avatar flex-shrink-0 rounded-circle overflow-hidden">
                                                                <img class="w-100 h-100 object-fit-cover" src="<?php echo esc_url( $cast_member['image_url'] ); ?>" alt="<?php echo esc_attr( $cast_member['name'] ); ?>" loading="lazy" />
                                                            </span>
                                                        <?php endif; ?>
                                                        <div class="flex-grow-1 position-relative">
                                                            <?php if ( '' !== $cast_member['term_link'] ) : ?>
                                                                <a class="stretched-link fw-semibold text-reset text-decoration-none" href="<?php echo esc_url( $cast_member['term_link'] ); ?>"><?php echo esc_html( $cast_member['name'] ); ?></a>
                                                            <?php else : ?>
                                                                <span class="fw-semibold"><?php echo esc_html( $cast_member['name'] ); ?></span>
                                                            <?php endif; ?>
                                                            <?php if ( '' !== $cast_member['character'] ) : ?>
                                                                <div class="text-muted small"><?php echo esc_html( $cast_member['character'] ); ?></div>
                                                            <?php endif; ?>
                                                            <?php if ( '' !== $cast_member['original_name'] && $cast_member['original_name'] !== $cast_member['name'] ) : ?>
                                                                <div class="text-muted small"><?php echo esc_html( $cast_member['original_name'] ); ?></div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </article>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </section>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( $has_videos ) : ?>
                        <div class="tab-pane fade" id="videos" role="tabpanel" aria-labelledby="videos-tab">
                            <div class="row g-4">
                                <?php foreach ( $videos as $video ) : ?>
                                    <div class="col-lg-6">
                                        <article class="card h-100 border-0 shadow-sm">
                                            <?php if ( '' !== $video['embed_url'] ) : ?>
                                                <div class="ratio ratio-16x9">
                                                    <iframe src="<?php echo esc_url( $video['embed_url'] ); ?>" title="<?php echo esc_attr( $video['name'] ); ?>" allowfullscreen loading="lazy"></iframe>
                                                </div>
                                            <?php elseif ( '' !== $video['video_url'] ) : ?>
                                                <div class="card-body pb-0">
                                                    <a class="btn btn-outline-primary" href="<?php echo esc_url( $video['video_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Watch video', 'tmdb-plugin' ); ?></a>
                                                </div>
                                            <?php endif; ?>
                                            <div class="card-body">
                                                <?php if ( '' !== $video['name'] ) : ?>
                                                    <h3 class="h5 fw-semibold mb-3"><?php echo esc_html( $video['name'] ); ?></h3>
                                                <?php endif; ?>
                                                <dl class="row gy-2 small text-muted mb-0">
                                                    <?php if ( '' !== $video['type'] ) : ?>
                                                        <dt class="col-5"><?php esc_html_e( 'Type', 'tmdb-plugin' ); ?></dt>
                                                        <dd class="col-7 mb-0"><?php echo esc_html( $video['type'] ); ?></dd>
                                                    <?php endif; ?>
                                                    <?php if ( '' !== $video['site'] ) : ?>
                                                        <dt class="col-5"><?php esc_html_e( 'Platform', 'tmdb-plugin' ); ?></dt>
                                                        <dd class="col-7 mb-0"><?php echo esc_html( $video['site'] ); ?></dd>
                                                    <?php endif; ?>
                                                    <?php if ( '' !== $video['country'] ) : ?>
                                                        <dt class="col-5"><?php esc_html_e( 'Country', 'tmdb-plugin' ); ?></dt>
                                                        <dd class="col-7 mb-0"><?php echo esc_html( $video['country'] ); ?></dd>
                                                    <?php endif; ?>
                                                    <?php if ( '' !== $video['language'] ) : ?>
                                                        <dt class="col-5"><?php esc_html_e( 'Language', 'tmdb-plugin' ); ?></dt>
                                                        <dd class="col-7 mb-0"><?php echo esc_html( $video['language'] ); ?></dd>
                                                    <?php endif; ?>
                                                    <?php if ( ! empty( $video['official'] ) ) : ?>
                                                        <dt class="col-5"><?php esc_html_e( 'Official', 'tmdb-plugin' ); ?></dt>
                                                        <dd class="col-7 mb-0"><?php esc_html_e( 'Yes', 'tmdb-plugin' ); ?></dd>
                                                    <?php endif; ?>
                                                    <?php if ( '' !== $video['published_at'] ) : ?>
                                                        <dt class="col-5"><?php esc_html_e( 'Published', 'tmdb-plugin' ); ?></dt>
                                                        <dd class="col-7 mb-0"><?php echo esc_html( $video['published_at'] ); ?></dd>
                                                    <?php endif; ?>
                                                </dl>
                                            </div>
                                        </article>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( $has_gallery ) : ?>
                        <div class="tab-pane fade" id="gallery" role="tabpanel" aria-labelledby="gallery-tab">
                            <div class="row g-4">
                                <?php foreach ( $gallery_items as $item ) : ?>
                                    <div class="col-sm-6 col-lg-4">
                                        <figure class="tmdb-gallery-item">
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
                    <?php endif; ?>
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
