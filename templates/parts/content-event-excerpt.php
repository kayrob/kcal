<?php
/**
 * Events archive content.
 *
 * @package kcal
 */

$post_iter   = get_query_var( 'post_iter', 0 );
$num_posts   = get_query_var( 'num_results', 0 );
$the_post_id = get_the_ID();

$meta = get_post_meta( $the_post_id );

$event_start = get_query_var( 'eventStart', $meta['_kcal_eventStartDate'][0] );
$event_end   = get_query_var( 'eventEnd', $meta['_kcal_eventEndDate'][0] );
$event_id    = get_query_var( 'eventID', $the_post_id );

$event_link = $meta['_kcal_eventURL'][0];

try {
	$timezone = $meta['_kcal_timezone'][0];
} catch ( \Exception $e ) {
	$timezone = get_option( 'gmt_offset' );
}

$date_time = new \DateTime( 'now', new DateTimeZone( $timezone ) );
$today_dst = $date_time->format( 'I' );

$date_format_option = get_option( 'date_format' );
$time_format_option = get_option( 'time_format' );

// Start Date.
if ( (int) $today_dst !== (int) $date_time->format( 'I' ) ) {
	if ( 0 === (int) $today_dst ) {
		// Today is standard time. Set the display back one hour if event is in DST.
		$date_time->setTimestamp( $event_start - ( 60 * 60 ) );
	} else {
		// Today is in DST. Set the display forward one hour if event is in Standard.
		$date_time->setTimestamp( $event_start - ( 60 * 60 ) );
	}
} else {
	$date_time->setTimestamp( $event_start );
}
$event_start_date = $date_time->format( $date_format_option );
$event_start_time = $date_time->format( $time_format_option );

// End date.
if ( (int) $today_dst !== (int) $date_time->format( 'I' ) ) {
	if ( 0 === (int) $today_dst ) {
		// Today is standard time. Set the display back one hour if event is in DST.
		$date_time->setTimestamp( $event_end + ( 60 * 60 ) );
	} else {
		// Today is in DST. Set the display forward one hour if event is in Standard.
		$date_time->setTimestamp( $event_end + ( 60 * 60 ) );
	}
} else {
	$date_time->setTimestamp( $event_end );
}
$event_end_date = $date_time->format( $date_format_option );
$event_end_time = $date_time->format( $time_format_option );

$event_time = '';
if ( $event_start_date !== $event_end_date ) :
	$event_start_date .= ' - ' . $event_end_date;
else :
	$event_time = ( ! isset( $meta['_kcal_allDay'][0] ) || 0 === (int) $meta['_kcal_allDay'][0] ) ? $event_start_time . '-' . $event_end_time : esc_attr__( 'All Day Event', 'kcal' );
endif;

$all_ids = explode( '-', $event_id );

$link_href = '';
$location  = '';

if ( ! empty( $meta['_kcal_location'][0] ) ) :
	$link_href = 'https://maps.google.com?q=' . str_replace( ' ', '+', str_replace( ',', '', $meta['_kcal_location'][0] ) );
endif;

$calendar   = get_term_by( 'slug', get_query_var( 'calendar' ), 'calendar' );
$cal_colour = get_option( 'calendar_' . get_query_var( 'calendar' ) );
$cal_text   = get_option( 'calendar_text_' . get_query_var( 'calendar' ) );

if ( '#fff' !== $cal_text ) :
	$cal_text = '#000';
endif;
$permalink = get_permalink();

if ( isset( $all_ids[1] ) ) :
	$permalink .= '?r=' . $all_ids[1];
endif;

$post_class = ( 0 === $post_iter ) ? 'no-border' : '';

?>
<article id="post-<?php echo (int) $event_id; ?>" <?php post_class( $post_class ); ?>>
<div class='events-wrapper entry-summary'>
<h2><a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_attr( get_the_title() ); ?></a></h2>
<?php
if ( 0 < $num_posts ) :
	?>
	<p class='post-meta event-meta'>
		<span class='highlight uppercase' style='background-color:#<?php echo esc_attr( $cal_colour ); ?>;color:<?php echo esc_attr( $cal_text ); ?>'><?php echo esc_attr( $event_start_date ); ?></span>
		<?php if ( ! empty( $event_time ) ) : ?>
			<span class='pipe'>&#8226;</span>
			<?php echo esc_attr( $event_time ); ?>
		<?php endif; ?>
	</p>
<?php endif; ?>
<div id='event-interact' class='post-meta'>
<?php if ( ! empty( $link_href ) ) : ?>
<div>
	<a href="<?php echo $link_href; //phpcs:ignore ?>">
	<span class='k-icon-mapmarker'></span>
	<span class='text'><?php echo $meta['_kcal_location'][0]; //phpcs:ignore ?></span>
	</a>
</div>
<?php endif; ?>

<?php if ( ! empty( $calendar ) ) : ?>
<div>
	<a target="_blank" class="alt kcal-ics" href="<?php echo esc_url( get_term_link( $calendar ) ); ?>?act=ics&calID=<?php echo (int) $calendar->term_id; ?>&eID=<?php echo (int) $event_id; ?>" aria-label="<?php esc_attr_e( 'Opens in a new window', 'kcal' ); ?>">
	<span class='k-icon-calendar'></span>
	<span class='text'><?php esc_attr_e( 'Add to Calendar', 'kcal' ); ?></span>
	</a>
</div>
<?php endif; ?>

<?php if ( ! empty( $event_link ) ) : ?>
<div>
	<a target="_blank" href="<?php echo esc_url( $event_link ); ?>" aria-label="<?php esc_attr_e( 'Opens in a new window', 'kcal' ); ?>">
		<span class='k-icon-info'></span>
		<span class='text'><?php esc_attr_e( 'More Info/Register', 'kcal' ); ?></span>
	</a>
</div>
<?php endif; ?>

</div><!--end interact -->

</div>
</article>
<div class='clearfix'></div>
<?php
if ( isset( $post_iter ) && $post_iter < ( $num_posts - 1 ) ) :
	?>
	<div class='event-spacer'></div>
	<?php
endif;
