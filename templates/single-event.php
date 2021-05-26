<?php
/**
 * The Template for displaying all single events.
 *
 * @package kCal
 * @since Quark 1.0
 */
if (isset($_GET["act"]) && $_GET["act"] == "ics") :
	$cc = new CalendarController();
	$cc->addToCalendar();
else :
get_header();
?>
    <div id="primary" class="site-content row" role="main">
		<header>
			<h1><?php echo get_the_title(); ?></h1>
		</header>
		<div class="grid_8_of_12">

				<?php while ( have_posts() ) : the_post(); ?>

						<?php include KCAL_HOST_DIR . 'templates/parts/content-event.php'; ?>

				<?php endwhile; // end of the loop. ?>

		</div> <!-- /.col.grid_8_of_12 -->
		<?php include_once KCAL_HOST_DIR . 'templates/parts/sidebar-kcal.php'; ?>
    </div> <!-- /#primary.site-content.row -->

<?php get_footer();
endif;
