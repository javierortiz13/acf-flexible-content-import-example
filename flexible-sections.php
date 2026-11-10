<?php
/**
 * Renders the "sections" flexible-content field, one partial per layout.
 * New layouts only need a new case here and a new field-group layout.
 */

if ( ! have_rows( 'sections' ) ) {
	return;
}

while ( have_rows( 'sections' ) ) :
	the_row();

	switch ( get_row_layout() ) :

		case 'hero':
			$image_id = get_sub_field( 'image' );
			?>
			<section class="section section--hero">
				<div class="section__body">
					<h2><?php echo esc_html( get_sub_field( 'heading' ) ); ?></h2>
					<p><?php echo esc_html( get_sub_field( 'text' ) ); ?></p>
				</div>
				<?php
				if ( $image_id ) {
					echo wp_get_attachment_image( $image_id, 'large', false, array( 'class' => 'section__image', 'loading' => 'eager' ) );
				}
				?>
			</section>
			<?php
			break;

		case 'services':
			?>
			<section class="section section--services">
				<h2><?php echo esc_html( get_sub_field( 'heading' ) ); ?></h2>
				<?php if ( have_rows( 'items' ) ) : ?>
					<ul class="services-grid">
						<?php while ( have_rows( 'items' ) ) : the_row(); ?>
							<li class="services-grid__item">
								<h3><?php echo esc_html( get_sub_field( 'title' ) ); ?></h3>
								<p><?php echo esc_html( get_sub_field( 'text' ) ); ?></p>
							</li>
						<?php endwhile; ?>
					</ul>
				<?php endif; ?>
			</section>
			<?php
			break;

		case 'cta':
			$link = get_sub_field( 'link' );
			?>
			<section class="section section--cta">
				<p><?php echo esc_html( get_sub_field( 'text' ) ); ?></p>
				<?php if ( $link ) : ?>
					<a class="button" href="<?php echo esc_url( $link['url'] ); ?>" target="<?php echo esc_attr( $link['target'] ? $link['target'] : '_self' ); ?>">
						<?php echo esc_html( $link['title'] ); ?>
					</a>
				<?php endif; ?>
			</section>
			<?php
			break;

	endswitch;
endwhile;
