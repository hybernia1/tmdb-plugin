<?php
/**
 * Front page template for the TMDB theme.
 *
 * @package TMDB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<?php
$movie_archive = post_type_exists( 'movie' ) ? get_post_type_archive_link( 'movie' ) : home_url( '/' );
$blog_page_id  = (int) get_option( 'page_for_posts' );
$blog_link     = $blog_page_id > 0 ? get_permalink( $blog_page_id ) : home_url( '/' );

$hero_title        = get_theme_mod( 'tmdb_theme_hero_title', __( 'Objevujte svět filmů a seriálů', 'tmdb-theme' ) );
$hero_description  = get_theme_mod( 'tmdb_theme_hero_description', __( 'Propojte WordPress s The Movie Database a přineste návštěvníkům aktuální informace o filmech, seriálech i tvůrcích.', 'tmdb-theme' ) );
$hero_button_label = get_theme_mod( 'tmdb_theme_hero_button_label', __( 'Procházet filmy', 'tmdb-theme' ) );
$hero_button_url   = get_theme_mod( 'tmdb_theme_hero_button_url' );
$hero_background   = get_theme_mod( 'tmdb_theme_hero_background_image' );

if ( empty( $hero_button_url ) ) {
    $hero_button_url = $movie_archive;
}

$hero_classes = 'tmdb-hero text-center text-md-start';

if ( ! empty( $hero_background ) ) {
    $hero_classes .= ' has-custom-bg';
}

$hero_style = '';

if ( ! empty( $hero_background ) ) {
    $hero_style = sprintf(
        ' style="background-image: linear-gradient(135deg, rgba(14, 165, 233, 0.75), rgba(14, 116, 144, 0.75)), url(%s);"',
        esc_url( $hero_background )
    );
}
?>

<section class="<?php echo esc_attr( $hero_classes ); ?>"<?php echo $hero_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <h1><?php echo esc_html( $hero_title ); ?></h1>
                <?php if ( ! empty( $hero_description ) ) : ?>
                    <p class="lead mb-4"><?php echo wp_kses_post( $hero_description ); ?></p>
                <?php endif; ?>
                <?php if ( ! empty( $hero_button_label ) ) : ?>
                    <a class="btn btn-lg btn-info text-dark fw-semibold" href="<?php echo esc_url( $hero_button_url ); ?>">
                        <?php echo esc_html( $hero_button_label ); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <?php if ( post_type_exists( 'movie' ) ) : ?>
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                <h2 class="h3 mb-0 text-uppercase text-info fw-semibold"><?php esc_html_e( 'Nejnovější filmy', 'tmdb-theme' ); ?></h2>
                <a class="text-decoration-none" href="<?php echo esc_url( $movie_archive ); ?>">
                    <?php esc_html_e( 'Zobrazit vše', 'tmdb-theme' ); ?>
                </a>
            </div>
            <div class="row g-4">
                <?php
                $movies = new WP_Query(
                    array(
                        'post_type'      => 'movie',
                        'posts_per_page' => 6,
                    )
                );

                if ( $movies->have_posts() ) :
                    while ( $movies->have_posts() ) :
                        $movies->the_post();
                        ?>
                        <div class="col-md-6 col-xl-4">
                            <article <?php post_class( 'card h-100 shadow-sm' ); ?>>
                                <?php if ( has_post_thumbnail() ) : ?>
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_post_thumbnail( 'large', [ 'class' => 'card-img-top' ] ); ?>
                                    </a>
                                <?php endif; ?>
                                <div class="card-body d-flex flex-column">
                                    <h3 class="card-title h4">
                                        <a class="stretched-link text-light" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h3>
                                    <div class="mt-auto text-muted small">
                                        <?php echo esc_html( get_the_date() ); ?>
                                    </div>
                                </div>
                            </article>
                        </div>
                        <?php
                    endwhile;
                    wp_reset_postdata();
                else :
                    ?>
                    <div class="col-12">
                        <div class="alert alert-warning mb-0">
                            <?php esc_html_e( 'Zatím nebyly přidány žádné filmy.', 'tmdb-theme' ); ?>
                        </div>
                    </div>
                    <?php
                endif;
                ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="py-5 bg-opacity-25 bg-black">
    <div class="container">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
            <h2 class="h3 mb-0 text-uppercase text-info fw-semibold"><?php esc_html_e( 'Nejnovější články', 'tmdb-theme' ); ?></h2>
            <a class="text-decoration-none" href="<?php echo esc_url( $blog_link ); ?>">
                <?php esc_html_e( 'Blog', 'tmdb-theme' ); ?>
            </a>
        </div>

        <div class="row g-4">
            <?php
            $blog_posts = new WP_Query(
                array(
                    'post_type'           => 'post',
                    'posts_per_page'      => 3,
                    'ignore_sticky_posts' => true,
                )
            );

            if ( $blog_posts->have_posts() ) :
                while ( $blog_posts->have_posts() ) :
                    $blog_posts->the_post();
                    ?>
                    <div class="col-md-4">
                        <article <?php post_class( 'card h-100 shadow-sm' ); ?>>
                            <?php if ( has_post_thumbnail() ) : ?>
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail( 'large', [ 'class' => 'card-img-top' ] ); ?>
                                </a>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h3 class="card-title h4">
                                    <a class="stretched-link text-light" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h3>
                                <div class="card-text text-muted small mb-3">
                                    <?php echo esc_html( get_the_date() ); ?>
                                </div>
                                <div class="card-text">
                                    <?php the_excerpt(); ?>
                                </div>
                            </div>
                        </article>
                    </div>
                    <?php
                endwhile;
                wp_reset_postdata();
            else :
                ?>
                <div class="col-12">
                    <div class="alert alert-info mb-0">
                        <?php esc_html_e( 'Zatím nebyly publikovány žádné články.', 'tmdb-theme' ); ?>
                    </div>
                </div>
                <?php
            endif;
            ?>
        </div>
    </div>
</section>

<?php
get_footer();
