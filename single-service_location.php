<?php
/**
 * Single template for the service_location post type.
 */

get_header();

while ( have_posts() ) :
	the_post();

	$city    = get_field( 'city' );
	$address = get_field( 'address' );
	$phone   = get_field( 'phone' );
	?>
	<main class="service-location">
		<header class="service-location__header">
			<h1><?php the_title(); ?></h1>

			<?php if ( $city ) : ?>
				<p class="service-location__city"><?php echo esc_html( $city ); ?></p>
			<?php endif; ?>

			<?php if ( $address ) : ?>
				<address><?php echo wp_kses( $address, array( 'br' => array() ) ); ?></address>
			<?php endif; ?>

			<?php if ( $phone ) : ?>
				<a class="service-location__phone" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>">
					<?php echo esc_html( $phone ); ?>
				</a>
			<?php endif; ?>

			<?php if ( have_rows( 'hours' ) ) : ?>
				<dl class="service-location__hours">
					<?php while ( have_rows( 'hours' ) ) : the_row(); ?>
						<dt><?php echo esc_html( get_sub_field( 'days' ) ); ?></dt>
						<dd><?php echo esc_html( get_sub_field( 'time' ) ); ?></dd>
					<?php endwhile; ?>
				</dl>
			<?php endif; ?>
		</header>

		<?php get_template_part( 'template-parts/flexible-sections' ); ?>
	</main>
	<?php
endwhile;

get_footer();
