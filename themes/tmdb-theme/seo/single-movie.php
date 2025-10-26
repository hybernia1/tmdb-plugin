<?php
/**
 * SEO metadata for single movie pages.
 *
 * @package TMDB_Theme
 */

use TMDB\Plugin\Taxonomies\TMDB_Taxonomies;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$post_id = get_the_ID();

if ( ! $post_id ) {
    return;
}

$title       = wp_strip_all_tags( get_the_title( $post_id ) );
$tagline     = wp_strip_all_tags( (string) get_post_meta( $post_id, 'TMDB_tagline', true ) );
$description = '' !== $tagline ? $tagline : wp_strip_all_tags( get_the_excerpt( $post_id ) );

if ( '' === $description ) {
    $fallback = sprintf(
        /* translators: %s is the movie title. */
        __( 'Discover details, cast, crew, and release information for %s.', 'tmdb-theme' ),
        $title
    );

    $description = wp_strip_all_tags( $fallback );
}

$description = trim( preg_replace( '/\s+/', ' ', $description ) );
$permalink   = get_permalink( $post_id );
$site_name   = get_bloginfo( 'name' );
$image_url   = get_the_post_thumbnail_url( $post_id, 'full' );

$image_width  = '';
$image_height = '';

if ( $image_url ) {
    $image_id   = get_post_thumbnail_id( $post_id );
    $image_meta = $image_id ? wp_get_attachment_metadata( $image_id ) : false;

    if ( is_array( $image_meta ) ) {
        if ( ! empty( $image_meta['width'] ) ) {
            $image_width = (string) (int) $image_meta['width'];
        }

        if ( ! empty( $image_meta['height'] ) ) {
            $image_height = (string) (int) $image_meta['height'];
        }
    }
}

$release_date_raw = (string) get_post_meta( $post_id, 'TMDB_release_date', true );
$runtime_minutes  = (int) get_post_meta( $post_id, 'TMDB_runtime', true );
$vote_average     = (float) get_post_meta( $post_id, 'TMDB_vote_average', true );
$vote_count       = (int) get_post_meta( $post_id, 'TMDB_vote_count', true );
$homepage         = (string) get_post_meta( $post_id, 'TMDB_homepage', true );
$primary_language = sanitize_text_field( (string) get_post_meta( $post_id, 'TMDB_language', true ) );
$origin_countries = get_post_meta( $post_id, 'TMDB_origin_countries', true );
$spoken_languages = get_post_meta( $post_id, 'TMDB_spoken_languages', true );
$websites_meta    = get_post_meta( $post_id, 'TMDB_websites', true );
$primary_website  = get_post_meta( $post_id, 'TMDB_primary_website', true );
$external_ids     = get_post_meta( $post_id, 'TMDB_external_ids', true );
$trailer_meta     = get_post_meta( $post_id, 'TMDB_trailer', true );
$cast_meta        = get_post_meta( $post_id, 'TMDB_cast', true );
$director_meta    = get_post_meta( $post_id, 'TMDB_directors', true );

$release_date_iso = '';
$release_timestamp = strtotime( $release_date_raw );

if ( false !== $release_timestamp ) {
    $release_date_iso = gmdate( 'Y-m-d', $release_timestamp );
}

$duration_iso = '';

if ( $runtime_minutes > 0 ) {
    $hours        = (int) floor( $runtime_minutes / 60 );
    $minutes      = $runtime_minutes % 60;
    $duration_iso = 'PT';

    if ( $hours > 0 ) {
        $duration_iso .= $hours . 'H';
    }

    if ( $minutes > 0 ) {
        $duration_iso .= $minutes . 'M';
    }

    if ( 'PT' === $duration_iso ) {
        $duration_iso = '';
    }
}

$genres = [];
$genre_terms = get_the_terms( $post_id, TMDB_Taxonomies::GENRE );

if ( $genre_terms && ! is_wp_error( $genre_terms ) ) {
    foreach ( $genre_terms as $genre_term ) {
        if ( $genre_term instanceof \WP_Term ) {
            $genres[] = wp_strip_all_tags( $genre_term->name );
        }
    }
}

$keyword_terms = get_the_terms( $post_id, TMDB_Taxonomies::KEYWORD );
$keyword_names = [];

if ( $keyword_terms && ! is_wp_error( $keyword_terms ) ) {
    foreach ( $keyword_terms as $keyword_term ) {
        if ( $keyword_term instanceof \WP_Term ) {
            $keyword_names[] = $keyword_term->name;
        }
    }
}

$keywords_string = '';

if ( ! empty( $keyword_names ) ) {
    $keywords_string = implode( ', ', array_unique( array_map( 'wp_strip_all_tags', $keyword_names ) ) );
}

$spoken_language_names = [];

if ( is_array( $spoken_languages ) ) {
    foreach ( $spoken_languages as $language_entry ) {
        if ( ! is_array( $language_entry ) ) {
            continue;
        }

        if ( ! empty( $language_entry['english_name'] ) ) {
            $spoken_language_names[] = wp_strip_all_tags( (string) $language_entry['english_name'] );
        } elseif ( ! empty( $language_entry['name'] ) ) {
            $spoken_language_names[] = wp_strip_all_tags( (string) $language_entry['name'] );
        } elseif ( ! empty( $language_entry['iso_639_1'] ) ) {
            $spoken_language_names[] = strtoupper( sanitize_text_field( (string) $language_entry['iso_639_1'] ) );
        }
    }
}

$cast_list = [];

if ( is_array( $cast_meta ) ) {
    foreach ( $cast_meta as $member ) {
        if ( ! is_array( $member ) ) {
            continue;
        }

        $name = isset( $member['name'] ) ? trim( (string) $member['name'] ) : '';

        if ( '' === $name ) {
            continue;
        }

        $cast_entry = [
            '@type' => 'Person',
            'name'  => wp_strip_all_tags( $name ),
        ];

        if ( ! empty( $member['character'] ) ) {
            $cast_entry['characterName'] = wp_strip_all_tags( (string) $member['character'] );
        }

        $cast_list[] = $cast_entry;
    }
}

$director_list = [];

if ( is_array( $director_meta ) ) {
    foreach ( $director_meta as $director ) {
        if ( ! is_array( $director ) ) {
            continue;
        }

        $name = isset( $director['name'] ) ? trim( (string) $director['name'] ) : '';

        if ( '' === $name ) {
            continue;
        }

        $director_entry = [
            '@type' => 'Person',
            'name'  => wp_strip_all_tags( $name ),
        ];

        if ( ! empty( $director['job'] ) ) {
            $director_entry['jobTitle'] = wp_strip_all_tags( (string) $director['job'] );
        }

        $director_list[] = $director_entry;
    }
}

$country_list = [];

if ( is_array( $origin_countries ) ) {
    foreach ( $origin_countries as $country_code ) {
        $country_code = strtoupper( sanitize_text_field( (string) $country_code ) );

        if ( '' === $country_code ) {
            continue;
        }

        $country_list[] = [
            '@type' => 'Country',
            'name'  => $country_code,
        ];
    }
}

$same_as_urls = [];

$maybe_add_same_as = static function ( $url ) use ( &$same_as_urls ) {
    if ( ! is_string( $url ) || '' === $url ) {
        return;
    }

    $validated = wp_http_validate_url( $url );

    if ( $validated ) {
        $same_as_urls[] = esc_url_raw( $validated );
    }
};

$maybe_add_same_as( $homepage );

if ( is_array( $primary_website ) && ! empty( $primary_website['url'] ) ) {
    $maybe_add_same_as( (string) $primary_website['url'] );
}

if ( is_array( $websites_meta ) ) {
    foreach ( $websites_meta as $website_entry ) {
        if ( ! is_array( $website_entry ) || empty( $website_entry['url'] ) ) {
            continue;
        }

        $maybe_add_same_as( (string) $website_entry['url'] );
    }
}

if ( is_array( $external_ids ) ) {
    foreach ( $external_ids as $external_entry ) {
        if ( ! is_array( $external_entry ) || empty( $external_entry['url'] ) ) {
            continue;
        }

        $maybe_add_same_as( (string) $external_entry['url'] );
    }
}

$same_as_urls = array_values( array_unique( $same_as_urls ) );

$trailer_ld = [];

if ( is_array( $trailer_meta ) && ! empty( $trailer_meta['name'] ) ) {
    $watch_url = isset( $trailer_meta['url'] ) ? (string) $trailer_meta['url'] : '';
    $embed_url = '';
    $site      = isset( $trailer_meta['site'] ) ? strtolower( (string) $trailer_meta['site'] ) : '';
    $key       = isset( $trailer_meta['key'] ) ? (string) $trailer_meta['key'] : '';

    if ( '' === $watch_url && '' !== $site && '' !== $key ) {
        $encoded_key = rawurlencode( $key );

        if ( 'youtube' === $site ) {
            $watch_url = sprintf( 'https://www.youtube.com/watch?v=%s', $encoded_key );
            $embed_url = sprintf( 'https://www.youtube.com/embed/%s', $encoded_key );
        } elseif ( 'vimeo' === $site ) {
            $watch_url = sprintf( 'https://vimeo.com/%s', $encoded_key );
            $embed_url = sprintf( 'https://player.vimeo.com/video/%s', $encoded_key );
        }
    }

    if ( '' !== $watch_url ) {
        $trailer_ld = [
            '@type'       => 'VideoObject',
            'name'        => wp_strip_all_tags( (string) $trailer_meta['name'] ),
            'description' => $description,
            'url'         => esc_url_raw( $watch_url ),
        ];

        if ( '' !== $embed_url ) {
            $trailer_ld['embedUrl'] = esc_url_raw( $embed_url );
        }

        if ( ! empty( $trailer_meta['published_at'] ) ) {
            $published_ts = strtotime( (string) $trailer_meta['published_at'] );

            if ( false !== $published_ts ) {
                $trailer_ld['uploadDate'] = gmdate( 'c', $published_ts );
            }
        }
    }
}

$json_ld = [
    '@context'        => 'https://schema.org',
    '@type'           => 'Movie',
    'name'            => $title,
    'url'             => $permalink,
    'description'     => $description,
    'image'           => $image_url ? [ $image_url ] : [],
    'datePublished'   => $release_date_iso,
    'duration'        => $duration_iso,
    'genre'           => $genres,
    'keywords'        => $keywords_string,
    'inLanguage'      => '' !== $primary_language ? strtoupper( $primary_language ) : '',
    'countryOfOrigin' => $country_list,
    'actor'           => $cast_list,
    'director'        => $director_list,
    'sameAs'          => $same_as_urls,
];

if ( ! empty( $spoken_language_names ) ) {
    $json_ld['subtitleLanguage'] = $spoken_language_names;
}

if ( ! empty( $trailer_ld ) ) {
    $json_ld['trailer'] = $trailer_ld;
}

if ( $vote_average > 0 && $vote_count > 0 ) {
    $json_ld['aggregateRating'] = [
        '@type'       => 'AggregateRating',
        'ratingValue' => number_format( $vote_average, 1, '.', '' ),
        'ratingCount' => $vote_count,
    ];
}

$json_ld = tmdb_theme_filter_recursive( $json_ld );
?>
<meta name="description" content="<?php echo esc_attr( $description ); ?>">
<?php if ( '' !== $keywords_string ) : ?>
<meta name="keywords" content="<?php echo esc_attr( $keywords_string ); ?>">
<?php endif; ?>
<meta property="og:type" content="video.movie">
<meta property="og:site_name" content="<?php echo esc_attr( $site_name ); ?>">
<meta property="og:title" content="<?php echo esc_attr( $title ); ?>">
<meta property="og:description" content="<?php echo esc_attr( $description ); ?>">
<meta property="og:url" content="<?php echo esc_url( $permalink ); ?>">
<?php if ( $image_url ) : ?>
<meta property="og:image" content="<?php echo esc_url( $image_url ); ?>">
<?php if ( '' !== $image_width ) : ?>
<meta property="og:image:width" content="<?php echo esc_attr( $image_width ); ?>">
<?php endif; ?>
<?php if ( '' !== $image_height ) : ?>
<meta property="og:image:height" content="<?php echo esc_attr( $image_height ); ?>">
<?php endif; ?>
<?php endif; ?>
<meta name="twitter:card" content="<?php echo $image_url ? 'summary_large_image' : 'summary'; ?>">
<meta name="twitter:title" content="<?php echo esc_attr( $title ); ?>">
<meta name="twitter:description" content="<?php echo esc_attr( $description ); ?>">
<meta name="twitter:url" content="<?php echo esc_url( $permalink ); ?>">
<?php if ( $image_url ) : ?>
<meta name="twitter:image" content="<?php echo esc_url( $image_url ); ?>">
<?php endif; ?>
<script type="application/ld+json">
<?php echo wp_json_encode( $json_ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ); ?>
</script>
