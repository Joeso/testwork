<?php
get_header();
?>
<h1 class="page-title"><?php bloginfo( 'name' ); ?></h1>
<p>Добро пожаловать! Перейдите в <a href="<?php echo esc_url( get_post_type_archive_link( 'doctors' ) ); ?>">архив докторов</a> для просмотра.</p>
<?php
get_footer();
