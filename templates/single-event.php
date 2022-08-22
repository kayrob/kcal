<?php
/**
 * The Template for displaying all single events.
 *
 * @package kcal
 */

if ( isset( $_GET['act'] ) && 'ics' === $_GET['act'] ) : //phpcs:ignore
	$cc = new CalendarController();
	$cc->add_to_calendar();
else :
	get_header();
	?>
	<div id="primary" class="site-content row" role="main">
		<header>
			<h1><?php echo wp_kses( get_the_title(), 'post' ); ?></h1>
		</header>
		<div class="grid_8_of_12">

				<?php
				while ( have_posts() ) :
					the_post();
					include KCAL_HOST_DIR . 'templates/parts/content-event.php';

				endwhile; // end of the loop.
				?>

		</div> <!-- /.col.grid_8_of_12 -->
		<?php include_once KCAL_HOST_DIR . 'templates/parts/sidebar-kcal.php'; ?>
	</div> <!-- /#primary.site-content.row -->

	<?php
	get_footer();
endif;
