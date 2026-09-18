<!doctype html>
<html <?php language_attributes(); ?>>
<head><meta charset="<?php bloginfo( 'charset' ); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class(); ?>><?php wp_body_open(); ?>
<a class="screen-reader-text" href="#main-content">Skip to content</a>
<main id="main-content"><?php while ( have_posts() ) : the_post(); the_content(); endwhile; ?></main>
<?php wp_footer(); ?></body></html>
