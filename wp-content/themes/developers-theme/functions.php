<?php
/**
 * Developers Theme Functions
 *
 * @package DevelopersTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function developers_theme_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_image_size( 'doctor-card', 400, 300, true );
    add_image_size( 'doctor-single', 600, 800, false );
    
    register_nav_menus( array(
        'primary' => __( 'Главное меню', 'developers-theme' ),
    ) );
    
    add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' ) );
}
add_action( 'after_setup_theme', 'developers_theme_setup' );

function developers_theme_scripts() {
    wp_enqueue_style( 'developers-theme-style', get_stylesheet_uri(), array(), wp_get_theme()->get( 'Version' ) );
}
add_action( 'wp_enqueue_scripts', 'developers_theme_scripts' );

function developers_breadcrumbs() {
    if ( is_front_page() ) {
        return;
    }
    
    echo '<nav class="breadcrumbs">';
    echo '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Главная', 'developers-theme' ) . '</a>';
    echo '<span class="separator">-></span>';
    
    if ( is_post_type_archive( 'doctors' ) ) {
        echo '<span class="current">' . esc_html__( 'Доктора', 'developers-theme' ) . '</span>';
    } elseif ( is_singular( 'doctors' ) ) {
        echo '<a href="' . esc_url( get_post_type_archive_link( 'doctors' ) ) . '">' . esc_html__( 'Доктора', 'developers-theme' ) . '</a>';
        echo '<span class="separator">-></span>';
        echo '<span class="current">' . esc_html( get_the_title() ) . '</span>';
    } elseif ( is_tax( 'specialization' ) || is_tax( 'city' ) ) {
        echo '<a href="' . esc_url( get_post_type_archive_link( 'doctors' ) ) . '">' . esc_html__( 'Доктора', 'developers-theme' ) . '</a>';
        echo '<span class="separator">-></span>';
        echo '<span class="current">' . esc_html( single_term_title( '', false ) ) . '</span>';
    }
    
    echo '</nav>';
}
