<?php
/**
 * Template Name: Full Calendar
 * Description: Displays a javascript calendar for all calendars on a blog
 *
 * @package kcal
 */

if ( isset( $_GET['act'] ) && 'ics' === $_GET['act'] ) : //phpcs:ignore
	$cc = new CalendarController();
	$cc->add_to_calendar();
else :

	$cal = new CalendarWidgets();

	$event_details['imagePath'] = KCAL_HOST_URL . '/img/spacer.jpg';
	if ( isset( $_GET['event'] ) && preg_match( '/^([0-9]{1,6})(_)?([0-9]{1,6})?$/', $_GET['event'], $matches ) ) : //phpcs:ignore
		$event_details          = $cal->get_event_details_byID( $_GET ); //phpcs:ignore
		$event_details['date']  = date( 'F, j Y g:i a', strtotime( $event_details['start'] ) ) . ' - '; //phpcs:ignore
		$event_details['date'] .= ( date( 'Y-m-d', strtotime( $event_details['start'] ) ) === date( 'Y-m-d', strtotime( $event_details['end'] ) ) ) ? date( 'g:i a', strtotime( $event_details['end'] ) ) : date( 'F, j Y g:i a', strtotime( $event_details['end'] ) ); //phpcs:ignore
		if ( empty( $event_details['imagePath'] ) ) :
			$event_details['imagePath'] = KCAL_HOST_URL . '/img/spacer.jpg';
		endif;
	endif;
	?>
	<section id="calendarWrap" class="kcal-fullcalendar" aria-label="<?php esc_attr_e( 'Events calendar with month, week and list view', 'kcal' ); ?>">
		<div id="centreCol">
			<!--calendar div should remain empty for fullCalendar.js to fill-->
			<div class="kcal-loading init" role="status" data-loading="<?php esc_attr_e( 'Loading events', 'kcal' ); ?>" data-loaded="<?php esc_attr_e( 'Events are loaded', 'kcal' ); ?>">
				<p class="loading-text"><?php esc_attr_e( 'Loading events', 'kcal' ); ?></p>
				<span class="kcal-ellipsis" role="decoration">
					<span></span>
					<span></span>
					<span></span>
					<span></span>
				</span>
			</div>
			<div id="calendar"></div>
		</div>
		<div id="leftCol">
			<div class="current-calendars">
				<h3><?php esc_attr_e( 'Current Calendars:', 'kcal' ); ?></h3>
				<div class="screen-reader-text visuallyhidden"><?php esc_attr_e( 'Check/Uncheck calendars to dynamically filter events', 'kcal' ); ?></div>
	<?php
				( isset( $_GET['view'] ) ) ? $cal->get_calendars_view( $_GET['view'], '', '' ) : $cal->get_calendars_view( false, '', '' ); //phpcs:ignore
	?>
			</div>
		</div> <!-- end leftCol -->
		<div class="clearfix"></div>
	</section> <!-- end calendarwrap -->
	<div class="quickview-popup" id="dlgEventDetails" aria-hidden="true" tabindex="-1" role="dialog" >
		<div class="animated fadeInUp"><button class="close-btn" aria-label="<?php esc_attr_e( 'Close dialog', 'kcal' ); ?>">&#215;</button>
			<h2>
				<span <?php echo ( isset( $event_details['className'] ) && ! empty( $event_details['className'] ) ) ? ' class="' . esc_attr( $event_details['className'] ) . '"' : ''; ?>>
				<?php
				if ( isset( $event_details['title'] ) ) :
					echo wp_kses( $event_details['title'], 'post' );
				endif;
				?>
				</span>
			</h2>
			<div class="quickview-wrap">
				<div class="qv-recurring" aria-hidden="true" style="display:none"><?php esc_attr_e( 'This event recurs', 'kcal' ); ?><i></i></div>
				<div class="qv-allday" aria-hidden="true" style="display:none"><?php esc_attr_e( 'All Day Event', 'kcal' ); ?></div>
				<div class="qv-ics" aria-hidden="true" style="display:none" id="event-ics"></div>
				<table>
				<tr <?php echo ( isset( $event_details['imagePath'] ) && strstr( $event_details['imagePath'], 'spacer.jpg' ) ) ? 'style="display:none"' : ''; ?>><td colspan="2"><img src="<?php echo esc_url( $event_details['imagePath'] ); ?>" alt="" /></td></tr>
				<tr><td class="strong"><?php esc_attr_e( 'Date:', 'kcal' ); ?></td><td><?php echo ( isset( $event_details['date'] ) ) ? esc_attr( $event_details['date'] ) : ''; ?></td></tr>
				<tr><td class="strong"><?php esc_attr_e( 'Location:', 'kcal' ); ?></td><td><?php echo ( isset( $event_details['location'] ) ) ? esc_attr( $event_details['location'] ) : ''; ?></td></tr>
				<tr <?php echo ( isset( $event_details['description'] ) && empty( $event_details['description'] ) ) ? 'style="display:none"' : ''; ?>>
					<td class="strong"><?php esc_attr_e( 'Details:', 'kcal' ); ?></td><td><?php echo ( isset( $event_details['description'] ) ) ? esc_attr( $event_details['description'] ) : ''; ?></td>
				</tr>
				<tr <?php echo ( isset( $event_details['altUrl'] ) && empty( $event_details['altUrl'] ) ) ? 'style="display:none"' : ''; ?>>
					<td colspan="2"><a href="<?php echo esc_url( $event_details['altUrl'] ); ?>" id="eventMoreInfo"><span class="k-icon-web" role="icon"></span><?php esc_attr_e( 'Learn More', 'kcal' ); ?></a></td>
				</tr>
				</table>
			</div> <!-- end qv wrap-->
		</div> <!-- end animated -->
	</div> <!-- end qv popup -->
	<?php
endif;
