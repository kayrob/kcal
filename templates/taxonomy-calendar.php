<?php
/**
 * The template for displaying all calendar events.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package fp
 */
if (isset($_GET['act']) && $_GET['act'] == 'ics'):
    $cc = new CalendarController();
    $cc->addToCalendar();

else :


	global $wp_query;
	$cw = new CalendarWidgets();
	$term = get_term_by('slug', get_query_var('calendar'), 'calendar');
	$customFilter = array();

	$paged = (isset($_GET['pg'])) ? $_GET['pg'] : 1;
	if ($paged == 0 || empty($paged)){
		$paged = 1;
	}

	$data = $cw->upcoming_events_archive_filter($paged, $customFilter);

	$eventsPaged = array();
	if (!empty($data) && is_array($data)) {

		$eventsPaged = (count($data) > 10) ? array_chunk($data, 10, true) : array($data);

		$wp_query->post_count = count($data);
		$wp_query->max_num_pages = ceil(count($data)/10);

	}

	$numPosts = $wp_query->post_count;
	$htmlDescription = stripslashes(get_option( 'calendar_description_' . $term->term_id));

	get_header();
?>

	<div class="primary content-area" role="main">
		<header>
			<h1><?php single_term_title( __('Upcoming Events: ', 'kcal'), true) ; ?></h1>
		</header>
		<?php if ( have_posts() && isset($eventsPaged[($paged - 1)]) && !empty($eventsPaged[($paged - 1)]) ) : ?>

			<div class="site-content row">
			<div class="grid_8_of_12" >

			<?php if (!empty($htmlDescription)) : ?>
				<div class="archive-meta"><?php echo $htmlDescription;?></div>
			<?php endif; ?>

			<?php $p = 0; ?>
			<?php
				global $post;
				foreach($eventsPaged[($paged - 1)] as $eventID => $eventData) :
					$postID = explode('-', $eventID);
					$post = get_post($postID[0]);
					setup_postdata($post);
					set_query_var('eventStart', $eventData['start']);
					set_query_var('eventEnd', $eventData['end']);
					set_query_var('eventID', $eventID);
					set_query_var('num_results', $numPosts );
					set_query_var('post_iter', $p);

					include KCAL_HOST_DIR . 'templates/parts/content-event-excerpt.php';

					$p++;
					if ($p == 10) :
						break;
					endif;
				endforeach;
				wp_reset_postdata();
		?>

			<?php kcalShortcodes::calendar_pagination( 'nav-below' );  ?>
			</div><!--end col-lg-8-->
			<?php include_once KCAL_HOST_DIR . 'templates/parts/sidebar-kcal.php';?>
			</div><!--end entry-content/standard content wrapper -->

			<?php else : ?>
				<?php get_template_part( 'template-parts/content', 'none' ); ?>
			<?php endif ?>
	</div><!-- .primary -->

<?php get_footer(); ?>
<?php endif;
