<?php
/**
 * Plugin Name: Doctors CPT
 * Plugin URI: https://example.com/doctors-cpt
 * Description: Кастомный тип записей "Доктор" с таксономиями и мета-полями
 * Version: 1.0.0
 * Author: Developer
 * Author URI: https://example.com
 * Text Domain: doctors-cpt
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package DoctorsCPT
 */

// Защита от прямого доступа
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Константы плагина
define( 'DOCTORS_CPT_VERSION', '1.0.0' );
define( 'DOCTORS_CPT_PATH', plugin_dir_path( __FILE__ ) );
define( 'DOCTORS_CPT_URL', plugin_dir_url( __FILE__ ) );
define( 'DOCTORS_CPT_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Инициализация плагина
 */
function doctors_cpt_init() {
    require_once DOCTORS_CPT_PATH . 'includes/class-post-type.php';
    require_once DOCTORS_CPT_PATH . 'includes/class-taxonomies.php';
    require_once DOCTORS_CPT_PATH . 'includes/class-meta-boxes.php';
    require_once DOCTORS_CPT_PATH . 'includes/class-archive-filter.php';
    
    new Doctors_CPT_Post_Type();
    new Doctors_CPT_Taxonomies();
    new Doctors_CPT_Meta_Boxes();
    new Doctors_CPT_Archive_Filter();
}
add_action( 'plugins_loaded', 'doctors_cpt_init' );

/**
 * Активация плагина
 */
function doctors_cpt_activate() {
    doctors_cpt_init();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'doctors_cpt_activate' );

/**
 * Деактивация плагина
 */
function doctors_cpt_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'doctors_cpt_deactivate' );

/**
 * Подключение стилей для фронтенда
 */
function doctors_cpt_enqueue_scripts() {
    if ( is_post_type_archive( 'doctors' ) || is_singular( 'doctors' ) || is_tax( array( 'specialization', 'city' ) ) ) {
        wp_enqueue_style(
            'doctors-cpt-styles',
            DOCTORS_CPT_URL . 'assets/css/doctors-frontend.css',
            array(),
            DOCTORS_CPT_VERSION
        );
    }
}
add_action( 'wp_enqueue_scripts', 'doctors_cpt_enqueue_scripts' );

/**
 * Подключение стилей для админки
 */
function doctors_cpt_admin_scripts( $hook ) {
    global $post_type;
    
    if ( 'doctors' === $post_type ) {
        wp_enqueue_style(
            'doctors-cpt-admin-styles',
            DOCTORS_CPT_URL . 'assets/css/doctors-admin.css',
            array(),
            DOCTORS_CPT_VERSION
        );
    }
}
add_action( 'admin_enqueue_scripts', 'doctors_cpt_admin_scripts' );
