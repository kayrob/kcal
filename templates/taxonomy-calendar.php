<?php
/**
 * The template for displaying all calendar events.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package kcal
 */

if ( isset( $_GET['act'] ) && 'ics' === $_GET['act'] ) : //phpcs:ignore
	$cc = new CalendarController();
	$cc->add_to_calendar();
else :

	global $wp_query;
	$the_term_id   = get_query_var( 'calendar' );
	$cw            = new CalendarWidgets();
	$the_term      = get_term_by( 'slug', $the_term_i, 'calendar' );
	$custom_filter = array();

	$the_page = ( isset( $_GET['pg'] ) ) ? $_GET['pg'] : 1; //phpcs:ignore
	if ( 0 === (int) $the_page || empty( $the_page ) ) :
		$the_page = 1;
	endif;

	$data = $cw->upcoming_events_archive_filter( $the_page, $custom_filter );

	$events_paged = array();
	if ( ! empty( $data ) && is_array( $data ) ) :

		$events_paged = ( 10 < count( $data ) ) ? array_chunk( $data, 10, true ) : array( $data );

		$wp_query->post_count    = count( $data );
		$wp_query->max_num_pages = ceil( count( $data ) / 10 );

	endif;

	$num_posts        = $wp_query->post_count;
	$html_description = stripslashes( get_option( 'calendar_description_' . $the_term_id ) );

	get_header();
	?>

	<div class="primary content-area" role="main">
		<header>
			<h1><?php single_term_title( __( 'Upcoming Events: ', 'kcal' ), true ); ?></h1>
		</header>
		<?php if ( have_posts() && isset( $events_paged[ ( $the_page - 1 ) ] ) && ! empty( $events_paged[ ( $the_page - 1 ) ] ) ) : ?>

			<div class="site-content row">
			<div class="grid_8_of_12" >

			<?php if ( ! empty( $html_description ) ) : ?>
				<div class="archive-meta"><?php echo wp_kses( $html_description, 'post' ); ?></div>
			<?php endif; ?>

			<?php $p = 0; ?>
			<?php
			global $post;
			foreach ( $events_paged[ ( $the_page - 1 ) ] as $event_id => $event_data ) :
				$the_post_id = explode( '-', $event_id );
				$post        = get_post( $the_post_id[0] ); //phpcs:ignore
				setup_postdata( $post );
				set_query_var( 'eventStart', $event_data['start'] );
				set_query_var( 'eventEnd', $event_data['end'] );
				set_query_var( 'event_id', $event_id );
				set_query_var( 'num_results', $num_posts );
				set_query_var( 'post_iter', $p );

				include KCAL_HOST_DIR . 'templates/parts/content-event-excerpt.php';

				$p++;
				if ( 10 === $p ) :
					break;
				endif;
			endforeach;
			wp_reset_postdata();
			?>

			<?php Kcal_Shortcodes::calendar_pagination( 'nav-below' ); ?>
			</div><!--end col-lg-8-->
			<?php get_sidebar( 'kcal' ); ?>
			</div><!--end entry-content/standard content wrapper -->

			<?php else : ?>
				<?php get_template_part( 'template-parts/content', 'none' ); ?>
			<?php endif ?>
	</div><!-- .primary -->

	<?php
	get_footer();
endif;
