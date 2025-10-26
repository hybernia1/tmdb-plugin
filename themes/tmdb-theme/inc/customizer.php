<?php
/**
 * Theme Customizer additions for the TMDB Theme.
 *
 * @package TMDB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'tmdb_theme_customize_register' ) ) {
    /**
     * Register Customizer settings for the theme.
     *
     * @param WP_Customize_Manager $wp_customize Theme Customizer object.
     */
    function tmdb_theme_customize_register( WP_Customize_Manager $wp_customize ) {
        $wp_customize->add_section(
            'tmdb_theme_hero_section',
            array(
                'title'       => __( 'Úvodní sekce', 'tmdb-theme' ),
                'description' => __( 'Upravte obsah hero sekce na úvodní stránce.', 'tmdb-theme' ),
                'priority'    => 30,
            )
        );

        $wp_customize->add_setting(
            'tmdb_theme_hero_title',
            array(
                'default'           => __( 'Objevujte svět filmů a seriálů', 'tmdb-theme' ),
                'sanitize_callback' => 'sanitize_text_field',
            )
        );

        $wp_customize->add_control(
            'tmdb_theme_hero_title',
            array(
                'label'   => __( 'Titulek', 'tmdb-theme' ),
                'section' => 'tmdb_theme_hero_section',
                'type'    => 'text',
            )
        );

        $wp_customize->add_setting(
            'tmdb_theme_hero_description',
            array(
                'default'           => __( 'Propojte WordPress s The Movie Database a přineste návštěvníkům aktuální informace o filmech, seriálech i tvůrcích.', 'tmdb-theme' ),
                'sanitize_callback' => 'wp_kses_post',
            )
        );

        $wp_customize->add_control(
            'tmdb_theme_hero_description',
            array(
                'label'   => __( 'Popis', 'tmdb-theme' ),
                'section' => 'tmdb_theme_hero_section',
                'type'    => 'textarea',
            )
        );

        $wp_customize->add_setting(
            'tmdb_theme_hero_button_label',
            array(
                'default'           => __( 'Procházet filmy', 'tmdb-theme' ),
                'sanitize_callback' => 'sanitize_text_field',
            )
        );

        $wp_customize->add_control(
            'tmdb_theme_hero_button_label',
            array(
                'label'   => __( 'Text tlačítka', 'tmdb-theme' ),
                'section' => 'tmdb_theme_hero_section',
                'type'    => 'text',
            )
        );

        $wp_customize->add_setting(
            'tmdb_theme_hero_button_url',
            array(
                'default'           => '',
                'sanitize_callback' => 'esc_url_raw',
            )
        );

        $wp_customize->add_control(
            'tmdb_theme_hero_button_url',
            array(
                'label'       => __( 'URL tlačítka', 'tmdb-theme' ),
                'description' => __( 'Pokud ponecháte prázdné, použije se archiv filmů (pokud je k dispozici).', 'tmdb-theme' ),
                'section'     => 'tmdb_theme_hero_section',
                'type'        => 'url',
            )
        );

        $wp_customize->add_setting(
            'tmdb_theme_hero_background_image',
            array(
                'default'           => '',
                'sanitize_callback' => 'esc_url_raw',
            )
        );

        $wp_customize->add_control(
            new WP_Customize_Image_Control(
                $wp_customize,
                'tmdb_theme_hero_background_image',
                array(
                    'label'    => __( 'Pozadí hero sekce', 'tmdb-theme' ),
                    'section'  => 'tmdb_theme_hero_section',
                    'settings' => 'tmdb_theme_hero_background_image',
                )
            )
        );
    }
}
add_action( 'customize_register', 'tmdb_theme_customize_register' );
