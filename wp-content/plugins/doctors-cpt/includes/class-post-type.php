<?php
/**
 * Регистрация кастомного типа записей "Доктор"
 *
 * @package DoctorsCPT
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Doctors_CPT_Post_Type
 */
class Doctors_CPT_Post_Type {

    public function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );
    }

    public function register_post_type() {
        $labels = array(
            'name'                  => _x( 'Доктора', 'Post type general name', 'doctors-cpt' ),
            'singular_name'         => _x( 'Доктор', 'Post type singular name', 'doctors-cpt' ),
            'menu_name'             => _x( 'Доктора', 'Admin Menu text', 'doctors-cpt' ),
            'name_admin_bar'        => _x( 'Доктор', 'Add New on Toolbar', 'doctors-cpt' ),
            'add_new'               => __( 'Добавить нового', 'doctors-cpt' ),
            'add_new_item'          => __( 'Добавить нового доктора', 'doctors-cpt' ),
            'new_item'              => __( 'Новый доктор', 'doctors-cpt' ),
            'edit_item'             => __( 'Редактировать доктора', 'doctors-cpt' ),
            'view_item'             => __( 'Просмотреть доктора', 'doctors-cpt' ),
            'all_items'             => __( 'Все доктора', 'doctors-cpt' ),
            'search_items'          => __( 'Поиск докторов', 'doctors-cpt' ),
            'parent_item_colon'     => __( 'Родительский доктор:', 'doctors-cpt' ),
            'not_found'             => __( 'Докторов не найдено.', 'doctors-cpt' ),
            'not_found_in_trash'    => __( 'В корзине докторов не найдено.', 'doctors-cpt' ),
            'featured_image'        => _x( 'Фото доктора', 'Overrides the "Featured Image"', 'doctors-cpt' ),
            'set_featured_image'    => _x( 'Установить фото', 'Overrides "Set featured image"', 'doctors-cpt' ),
            'remove_featured_image' => _x( 'Удалить фото', 'Overrides "Remove featured image"', 'doctors-cpt' ),
            'use_featured_image'    => _x( 'Использовать как фото', 'Overrides "Use as featured image"', 'doctors-cpt' ),
            'archives'              => _x( 'Архив докторов', 'Post type archive label', 'doctors-cpt' ),
            'insert_into_item'      => _x( 'Вставить в доктора', 'Overrides "Insert into post"', 'doctors-cpt' ),
            'uploaded_to_this_item' => _x( 'Загружено для этого доктора', 'Overrides "Uploaded to this post"', 'doctors-cpt' ),
            'filter_items_list'     => _x( 'Фильтровать список докторов', 'Screen reader text', 'doctors-cpt' ),
            'items_list_navigation' => _x( 'Навигация по списку докторов', 'Screen reader text', 'doctors-cpt' ),
            'items_list'            => _x( 'Список докторов', 'Screen reader text', 'doctors-cpt' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'doctors', 'with_front' => false ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 5,
            'menu_icon'          => 'dashicons-businessman',
            'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
            'show_in_rest'       => true,
        );

        register_post_type( 'doctors', $args );
    }

    public function updated_messages( $messages ) {
        global $post;
        $permalink = get_permalink( $post->ID );

        $messages['doctors'] = array(
            0  => '',
            1  => sprintf( __( 'Доктор обновлен. <a target="_blank" href="%s">Просмотреть</a>', 'doctors-cpt' ), esc_url( $permalink ) ),
            2  => __( 'Поле обновлено.', 'doctors-cpt' ),
            3  => __( 'Поле удалено.', 'doctors-cpt' ),
            4  => __( 'Доктор обновлен.', 'doctors-cpt' ),
            5  => isset( $_GET['revision'] ) ? sprintf( __( 'Доктор восстановлен из ревизии %s', 'doctors-cpt' ), wp_post_revision_title( absint( $_GET['revision'] ), false ) ) : false,
            6  => sprintf( __( 'Доктор опубликован. <a href="%s">Просмотреть</a>', 'doctors-cpt' ), esc_url( $permalink ) ),
            7  => __( 'Доктор сохранен.', 'doctors-cpt' ),
            8  => sprintf( __( 'Доктор отправлен. <a target="_blank" href="%s">Предпросмотр</a>', 'doctors-cpt' ), esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
            9  => sprintf( __( 'Доктор запланирован на: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Предпросмотр</a>', 'doctors-cpt' ), date_i18n( __( 'M j, Y @ G:i', 'doctors-cpt' ), strtotime( $post->post_date ) ), esc_url( $permalink ) ),
            10 => sprintf( __( 'Черновик доктора обновлен. <a target="_blank" href="%s">Предпросмотр</a>', 'doctors-cpt' ), esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
        );

        return $messages;
    }
}
