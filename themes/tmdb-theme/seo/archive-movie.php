<?php
/**
 * SEO metadata for the movie archive page.
 *
 * @package TMDB_Theme
 */

use TMDB\Plugin\Taxonomies\TMDB_Taxonomies;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$archive_title = wp_strip_all_tags( post_type_archive_title( '', false ) );
$archive_url   = get_post_type_archive_link( 'movie' );
$site_name     = get_bloginfo( 'name' );
$site_tagline  = get_bloginfo( 'description' );

$post_type_object = get_post_type_object( 'movie' );
$description      = '';

if ( ! $archive_title && $post_type_object ) {
    $archive_title = wp_strip_all_tags( $post_type_object->labels->name );
}

if ( ! $archive_url ) {
    $archive_url = home_url( '/' );
}

if ( $post_type_object && ! empty( $post_type_object->description ) ) {
    $description = wp_strip_all_tags( $post_type_object->description );
}

if ( '' === $description && '' !== $site_tagline ) {
    $description = wp_strip_all_tags( $site_tagline );
}

if ( '' === $description ) {
    $description = sprintf(
        /* translators: %s is the site name. */
        __( 'Browse curated movie releases, cast information, and reviews from %s.', 'tmdb-theme' ),
        $site_name
    );
}

$description = trim( preg_replace( '/\s+/', ' ', $description ) );

$genre_terms = get_terms(
    [
        'taxonomy'   => TMDB_Taxonomies::GENRE,
        'orderby'    => 'count',
        'order'      => 'DESC',
        'number'     => 10,
        'hide_empty' => false,
    ]
);

$genre_keywords = [];

if ( is_array( $genre_terms ) ) {
    foreach ( $genre_terms as $term ) {
        if ( $term instanceof \WP_Term ) {
            $genre_keywords[] = $term->name;
        }
    }
}

$keywords_string = '';

if ( ! empty( $genre_keywords ) ) {
    $keywords_string = implode( ', ', array_unique( array_map( 'wp_strip_all_tags', $genre_keywords ) ) );
}

global $wp_query;

$item_list   = [];
$item_number = 0;

if ( isset( $wp_query ) && $wp_query instanceof \WP_Query && ! empty( $wp_query->posts ) ) {
    foreach ( $wp_query->posts as $post_obj ) {
        if ( ! $post_obj instanceof \WP_Post ) {
            continue;
        }

        $movie_title = wp_strip_all_tags( get_the_title( $post_obj ) );
        $movie_url   = get_permalink( $post_obj );
        $movie_image = get_the_post_thumbnail_url( $post_obj, 'full' );

        $item_number++;

        $movie_entity = [
            '@type' => 'Movie',
            'name'  => $movie_title,
            'url'   => $movie_url,
        ];

        if ( $movie_image ) {
            $movie_entity['image'] = $movie_image;
        }

        $item_list[] = [
            '@type'    => 'ListItem',
            'position' => $item_number,
            'url'      => $movie_url,
            'item'     => $movie_entity,
        ];

        if ( $item_number >= 10 ) {
            break;
        }
    }
}

$json_ld = [
    '@context'    => 'https://schema.org',
    '@type'       => 'CollectionPage',
    'name'        => $archive_title,
    'url'         => $archive_url,
    'description' => $description,
    'isPartOf'    => [
        '@type' => 'WebSite',
        'name'  => $site_name,
        'url'   => home_url( '/' ),
    ],
    'about'       => [
        '@type' => 'Thing',
        'name'  => $archive_title,
    ],
    'keywords'    => $keywords_string,
];

if ( ! empty( $item_list ) ) {
    $json_ld['mainEntity'] = [
        '@type'           => 'ItemList',
        'itemListElement' => $item_list,
        'numberOfItems'   => count( $item_list ),
    ];
}

$json_ld = tmdb_theme_filter_recursive( $json_ld );
?>
<meta name="description" content="<?php echo esc_attr( $description ); ?>">
<?php if ( '' !== $keywords_string ) : ?>
<meta name="keywords" content="<?php echo esc_attr( $keywords_string ); ?>">
<?php endif; ?>
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?php echo esc_attr( $site_name ); ?>">
<meta property="og:title" content="<?php echo esc_attr( $archive_title ); ?>">
<meta property="og:description" content="<?php echo esc_attr( $description ); ?>">
<meta property="og:url" content="<?php echo esc_url( $archive_url ); ?>">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="<?php echo esc_attr( $archive_title ); ?>">
<meta name="twitter:description" content="<?php echo esc_attr( $description ); ?>">
<meta name="twitter:url" content="<?php echo esc_url( $archive_url ); ?>">
<script type="application/ld+json">
<?php echo wp_json_encode( $json_ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ); ?>
</script>
