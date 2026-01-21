<?php
/**
 * Регистрация таксономий для CPT Doctors
 *
 * @package DoctorsCPT
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Doctors_CPT_Taxonomies
 */
class Doctors_CPT_Taxonomies {

    public function __construct() {
        add_action( 'init', array( $this, 'register_taxonomies' ) );
    }

    public function register_taxonomies() {
        $this->register_specialization_taxonomy();
        $this->register_city_taxonomy();
    }

    /**
     * Регистрация таксономии "Специализация"
     * Hierarchical (как рубрики) - для древовидной структуры
     */
    private function register_specialization_taxonomy() {
        $labels = array(
            'name'                       => _x( 'Специализации', 'taxonomy general name', 'doctors-cpt' ),
            'singular_name'              => _x( 'Специализация', 'taxonomy singular name', 'doctors-cpt' ),
            'search_items'               => __( 'Поиск специализаций', 'doctors-cpt' ),
            'popular_items'              => __( 'Популярные специализации', 'doctors-cpt' ),
            'all_items'                  => __( 'Все специализации', 'doctors-cpt' ),
            'parent_item'                => __( 'Родительская специализация', 'doctors-cpt' ),
            'parent_item_colon'          => __( 'Родительская специализация:', 'doctors-cpt' ),
            'edit_item'                  => __( 'Редактировать специализацию', 'doctors-cpt' ),
            'update_item'                => __( 'Обновить специализацию', 'doctors-cpt' ),
            'add_new_item'               => __( 'Добавить новую специализацию', 'doctors-cpt' ),
            'new_item_name'              => __( 'Название новой специализации', 'doctors-cpt' ),
            'separate_items_with_commas' => __( 'Разделяйте специализации запятыми', 'doctors-cpt' ),
            'add_or_remove_items'        => __( 'Добавить или удалить специализации', 'doctors-cpt' ),
            'choose_from_most_used'      => __( 'Выбрать из часто используемых', 'doctors-cpt' ),
            'not_found'                  => __( 'Специализаций не найдено.', 'doctors-cpt' ),
            'menu_name'                  => __( 'Специализации', 'doctors-cpt' ),
            'back_to_items'              => __( 'Назад к специализациям', 'doctors-cpt' ),
        );

        $args = array(
            'hierarchical'          => true,
            'labels'                => $labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'query_var'             => true,
            'rewrite'               => array( 'slug' => 'specialization' ),
            'show_in_rest'          => true,
        );

        register_taxonomy( 'specialization', array( 'doctors' ), $args );
    }

    /**
     * Регистрация таксономии "Город"
     * 
     * ВЫБОР: Hierarchical (как рубрики)
     * 
     * ОБОСНОВАНИЕ:
     * 1. Возможность группировки по регионам (Россия > Москва)
     * 2. Единообразие написания - выбор из списка
     * 3. Нет дубликатов (Москва, москва, г. Москва)
     * 4. Удобство фильтрации по регионам
     */
    private function register_city_taxonomy() {
        $labels = array(
            'name'                       => _x( 'Города', 'taxonomy general name', 'doctors-cpt' ),
            'singular_name'              => _x( 'Город', 'taxonomy singular name', 'doctors-cpt' ),
            'search_items'               => __( 'Поиск городов', 'doctors-cpt' ),
            'popular_items'              => __( 'Популярные города', 'doctors-cpt' ),
            'all_items'                  => __( 'Все города', 'doctors-cpt' ),
            'parent_item'                => __( 'Регион/Страна', 'doctors-cpt' ),
            'parent_item_colon'          => __( 'Регион/Страна:', 'doctors-cpt' ),
            'edit_item'                  => __( 'Редактировать город', 'doctors-cpt' ),
            'update_item'                => __( 'Обновить город', 'doctors-cpt' ),
            'add_new_item'               => __( 'Добавить новый город', 'doctors-cpt' ),
            'new_item_name'              => __( 'Название нового города', 'doctors-cpt' ),
            'separate_items_with_commas' => __( 'Разделяйте города запятыми', 'doctors-cpt' ),
            'add_or_remove_items'        => __( 'Добавить или удалить города', 'doctors-cpt' ),
            'choose_from_most_used'      => __( 'Выбрать из часто используемых', 'doctors-cpt' ),
            'not_found'                  => __( 'Городов не найдено.', 'doctors-cpt' ),
            'menu_name'                  => __( 'Города', 'doctors-cpt' ),
            'back_to_items'              => __( 'Назад к городам', 'doctors-cpt' ),
        );

        $args = array(
            'hierarchical'          => true,
            'labels'                => $labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'query_var'             => true,
            'rewrite'               => array( 'slug' => 'city' ),
            'show_in_rest'          => true,
        );

        register_taxonomy( 'city', array( 'doctors' ), $args );
    }
}
