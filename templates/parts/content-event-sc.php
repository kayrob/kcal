<?php
/**
 * Events archive content.
 *
 * @package kcal
 */

if ( ! is_singular( 'event' ) ) :
	$event = get_posts(
		array(
			'post_type'      => 'event',
			'posts_per_page' => 1,
			'number_posts'   => 1,
			'post_status'    => 'publish',
		),
	);
	if ( isset( $event[0]->ID ) ) :
		$the_post_id = $event[0]->ID;
	endif;
elseif ( is_singular( 'event' ) ) :
	$the_post_id = get_the_ID();
endif;


if ( isset( $the_post_id ) ) :
	$meta     = get_post_meta( $the_post_id );
	$the_post = get_post( $the_post_id );

	if ( isset( $_GET['r'] ) && 0 < (int) $_GET['r'] ) : //phpcs:ignore
		$cw          = new CalendarWidgets();
		$recur_data  = $cw->retrieve_one_event( get_the_ID(), (int) $_GET['r'] ); //phpcs:ignore
		$recur_meta  = unserialize( $recur_data->meta_value ); //phpcs:ignore
		$recur_start = array_keys( $recur_meta );
		$recur_end   = $recur_meta[ $recur_start[0] ]['endDate'];
	endif;

	$date_time = new \DateTime();
	$date_time->setTimezone( new DateTimeZone( $meta['_kcal_timezone'][0] ) );
	$today_dst = $date_time->format( 'I' );


	$event_start = ( isset( $recur_start[0] ) ) ? $recur_start[0] : $meta['_kcal_eventStartDate'][0];
	$event_end   = ( isset( $recur_end ) ) ? $recur_end : $meta['_kcal_eventEndDate'][0];
	$event_id    = $the_post_id;

	if ( isset( $_GET['r'] ) && 0 < (int) $_GET['r'] ) : //phpcs:ignore
		$event_id .= '-' . $_GET['r']; //phpcs:ignore
	endif;

	$event_link = $meta['_kcal_eventURL'][0];

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
		$event_time = ( ! isset( $meta['_kcal_allDay'][0] ) || 0 === $meta['_kcal_allDay'][0] ) ? $event_start_time . '-' . $event_end_time : esc_attr( 'All Day Event' );
	endif;

	// Expired events.
	if ( current_time( 'timestamp' ) > $date_time->getTimestamp() ) : //phpcs:ignore
		$event_time = '<i>This event has ended</i>';
	endif;

	$link_href = '';

	if ( ! empty( $meta['_kcal_location'][0] ) ) :
		$link_href = 'https://maps.google.com?q=' . str_replace( ' ', '+', str_replace( ',', '', $meta['_kcal_location'][0] ) );
	endif;

	$calendar   = wp_get_post_terms( $the_post_id, array( 'calendar' ) );
	$cal_colour = get_option( 'calendar_' . $calendar[0]->term_id );
	$cal_text   = get_option( 'calendar_text_' . $calendar[0]->term_id );

	if ( isset( $header ) && 'yes' === $header ) :
		?>
	<header><h1><?php echo wp_kses( get_the_title(), 'post' ); ?></h1></header>
		<?php
	endif;
	?>
	<p id="eventMeta" class="post-meta event-meta">
		<span class='highlight uppercase' style='background-color:#<?php echo esc_attr( $cal_colour ); ?>;color:<?php echo esc_attr( $cal_text ); ?>'><?php echo esc_attr( $event_start_date ); ?></span>
		<?php if ( ! empty( $event_time ) ) : ?>
			<span class='pipe'>&#8226;</span>
			<span class="screen-reader-text"><?php esc_attr_e( 'Event Date: ', 'kcal' ); ?></span><?php echo esc_attr( $event_time ); ?>
		<?php endif; ?>
	</p>
	<div class="event-content" aria-label="<?php esc_attr_e( 'Event details', 'kcal' ); ?>">
		<?php echo wp_kses( apply_filters( 'the_content', $the_post->post_content ), 'post' ); ?>
	</div>
	<div id='event-interact' class='post-meta'>
		<?php if ( ! empty( $link_href ) ) : ?>
		<div>
			<a href="<?php echo $link_href; //phpcs:ignore ?>" aria-label="<?php esc_attr_e( 'View in Google Maps. Opens in a new window', 'kcal' ); ?>">
			<span class='k-icon-mapmarker'></span>
			<span class='text'><?php echo esc_attr( $meta['_kcal_location'][0] ); ?></span>
			</a>
		</div>
		<?php endif; ?>

		<?php if ( ! empty( $calendar[0] ) ) : ?>
		<div>
			<a target='_blank' class='alt kcal-ics' href='<?php echo esc_url( trailingslashit( home_url() ) ); ?>?act=ics&calID=<?php echo (int) $calendar[0]->term_id; ?>&eID=<?php echo (int) $event_id; ?>' aria-label="<?php esc_attr_e( 'Download event to calendar. Opens in a new window', 'kcal' ); ?>">
			<span class='k-icon-calendar' role="decoration"></span>
			<span class='text'><?php esc_attr_e( 'Add to Calendar', 'kcal' ); ?></span>
			</a>
		</div>
		<?php endif; ?>

		<?php if ( ! empty( $event_link ) ) : ?>
		<div>
			<a target="_blank" href="<?php echo esc_url( $event_link ); ?>" aria-label="<?php esc_attr_e( 'Opens in a new window', 'kcal' ); ?>">
			<span class='k-icon-info' role="decoration"></span>
			<span class='text'><?php esc_attr_e( 'More Info/Register', 'kcal' ); ?></span>
			</a>
		</div>
		<?php endif; ?>

		<?php if ( ! empty( $meta['_kcal_locationMap'][0] ) ) : ?>
		<div class='event-location-image'>
			<?php if ( ! empty( $link_href ) ) : ?>
			<a href="<?php echo $link_href; //phpcs:ignore ?>" target="_blank" aria-label="<?php esc_attr_e( 'Open in a Google Maps window', 'kcal' ); ?>">
				<span class='k-icon-mapmarker' role='decoration'></span>
				<?php endif; ?>
			<?php // Translators: %s is the map URL. ?>
			<img src="<?php echo $meta['_kcal_locationMap'][0]; //phpcs:ignore ?>" alt="<?php printf( esc_attr__( 'Image of map to %s' , 'kcal' ), $meta['_kcal_location'][0] ); ?>" width="640" />
			<?php if ( ! empty( $link_href ) ) : ?>
				</a>
			<?php endif; ?>
			</a>
		</div>
		<?php endif; ?>

	</div><!--end interact -->
	<?php
endif;
