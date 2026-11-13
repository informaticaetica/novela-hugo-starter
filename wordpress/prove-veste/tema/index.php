<?php /* Ricalca, in piccolo, ciò che fa il tema Koji: insegna, menu principale, elenco di pagine come menu del telefono, ricerca, articoli. */ ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head><?php wp_head(); ?></head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a id="insegna" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
<ul id="menu"><?php wp_nav_menu( array( 'container' => '', 'items_wrap' => '%3$s', 'theme_location' => 'primary-menu' ) ); ?></ul>
<ul id="telefono"><?php wp_list_pages( array( 'container' => '', 'title_li' => '' ) ); ?></ul>
<?php get_search_form(); ?>
<main>
<?php
while ( have_posts() ) :
	the_post();
	?>
	<article data-id="<?php the_ID(); ?>">
		<h2><?php the_title(); ?></h2>
		<time><?php echo esc_html( get_the_date( 'F Y' ) ); ?></time>
		<span class="categorie"><?php the_category( ', ' ); ?></span>
		<?php if ( is_singular() ) : ?>
			<div class="testo"><?php the_content(); ?></div>
			<nav id="vicini"><span class="prima"><?php previous_post_link( '%link' ); ?></span><span class="dopo"><?php next_post_link( '%link' ); ?></span></nav>
		<?php endif; ?>
	</article>
	<?php
endwhile;
?>
</main>
<aside id="recenti"><?php foreach ( get_posts( array( 'numberposts' => 20 ) ) as $recente ) : ?><i data-id="<?php echo (int) $recente->ID; ?>"></i><?php endforeach; ?></aside>
<?php wp_footer(); ?>
</body>
</html>
