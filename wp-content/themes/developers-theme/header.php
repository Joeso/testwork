<?php
/**
 * Header Template
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">
    <header class="site-header">
        <div class="site-container">
            <h1 class="site-title">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
            </h1>
            <nav class="main-navigation">
                <?php
                wp_nav_menu( array(
                    'theme_location' => 'primary',
                    'container'      => false,
                    'fallback_cb'    => function() {
                        echo '<ul>';
                        echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">Главная</a></li>';
                        echo '<li><a href="' . esc_url( get_post_type_archive_link( 'doctors' ) ) . '">Доктора</a></li>';
                        echo '</ul>';
                    },
                ) );
                ?>
            </nav>
        </div>
    </header>
    <main id="primary" class="site-main">
        <div class="site-container">
