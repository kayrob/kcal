<?php
/**
 * Calendar class retrieves data for public viewing of events and passes it back to jQuery Full Calendar
 * Date created: Nov 10 2010 by Karen Laansoo
 *
 * @package kcal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Calendar' ) ) {
	/**
	 * Main calendar class
	 */
	class Calendar {
		/**
		 * Holds a reference to the wpdb object
		 *
		 * @var $db
		 * @access protected
		 */
		protected $db;
		/**
		 * Holds the url for the events page. Default is /events.
		 *
		 * @var $url
		 * @access public
		 */
		public $url = '/events';

		/**
		 * Holds the blog ID if this is a multisite.
		 *
		 * @var $blog_id
		 * @access protected
		 */
		protected $blog_id;
		/**
		 * Holds the event/calendar timezone.
		 *
		 * @var $timezone
		 * @access protected
		 */
		protected $timezone;

		/**
		 * Constructor explicitly called to use this keyword and set db object
		 */
		public function __construct() {
			global $wpdb, $blog_id;
			$this->db       = $wpdb;
			$this->blog_id  = $blog_id;
			$this->timezone = get_option( 'gmt_offset' );
		}

		/**
		 * Common function for getting calenadar data for view and ajax calls
		 * basic check to see if and how many calendars exist.
		 *
		 * @access protected
		 *
		 * @param int $id is calendar ID (wp term id). Optional. Default is get all terms.
		 * @see DB::result_please()
		 * @return resource|false
		 */
		protected function get_calendars_common( $id = false ) {
			$terms     = get_terms(
				array( 'calendar' ),
				array(
					'hide_empty' => false,
				)
			);
			$calendars = array();
			if ( ! empty( $terms ) ) {
				foreach ( $terms as $term ) {
					if ( false === $id || (int) $id === (int) $term->term_id ) {
						$calendars[] = array(
							'itemID'               => $term->term_id,
							'calendarName'         => $term->name,
							'slug'                 => $term->slug,
							'eventCount'           => $term->count,
							'eventBackgroundColor' => '#' . str_replace( '#', '', get_option( 'calendar_' . $term->term_id, '#cccccc' ) ),
							'eventTextColor'       => '#' . str_replace( '#', '', get_option( 'calendar_text_' . $term->term_id, '#000' ) ),
						);
					}
				}
			}
			return json_decode( json_encode( $calendars ) ); //phpcs:ignore
		}
		/**
		 * Display a checkbox list of calendars on event/calendar page
		 * these inputs are tied to jquery fullCalendar events feeds
		 * if a specific calendar is selected, only check the one selected
		 *
		 * @param int  $id is the optional calendar id.
		 * @param bool $rss is to show or not show the RSS link.
		 * @param bool $ics is to show or not show the ICS link.
		 * @see get_calendars_common()
		 */
		public function get_calendars_view( $id = false, $rss = true, $ics = true ) {
			$res               = $this->get_calendars_common();
			$page_action       = get_permalink( get_the_ID() );
			$background_colour = $row->eventBackgroundColor; //phpcs:ignore
			$calendar_name     = $row->calendarName; //phpcs:ignore
			if ( false !== $res ) {
				echo '<form name="frm_calendar_list" id="frm_calendar_list" action="' . esc_url( $page_action ) . '" method="post" onsubmit="return false">';
				echo '<div class="calendar-select-wrap">';
				foreach ( $res as $row ) {
					$item_id = trim( $row->itemID ); //phpcs:ignore
					$checked = ( false === $id || (int) $item_id === (int) $id ) ? 'checked="checked"' : ''; //phpcs:ignore

					if ( $rss ) {
						// Translators: %s is the calendar name.
						$rss_link = '<a href="' . trailingslashit( home_url() ) . '?act=rss&calendar=' . $item_id . '" target="_blank" aria-label="' . __( 'Opens in a new window', 'kcal' ) . '"><span class="k-icon-feed" title="Subscribe"></span><span class="visuallyhidden">' . sprintf( __( 'RSS: Subscribe to Events for %s', 'kcal' ), trim( $row->calendarName ) ) . '</span></span></a>'; //phpcs:ignore
					}
					if ( $ics ) {
						$ics_link = '<a href="' . trailingslashit( home_url() ) . '?act=ics&cal_id=' . $item_id . '" target="_blank" aria-label="' . __( 'Opens in a new window', 'kcal' ) . '"><span class="k-icon-calendar" title="' . esc_attr__( 'Add to Calendar', 'kcal' ) . '"></span><span class="visuallyhidden">' . esc_attr__( 'Add to Calendar', 'kcal' ) . '</span></a>';
					}

					echo '<div class="calendars-list-item">';
					echo '<input type="checkbox" name="calendar[' . (int) $item_id . ']" id="calendar' . (int) $item_id . '" value="' . (int) $item_id . '" ' . $checked . ' />'; //phpcs:ignore
					echo '&nbsp;<label class="calendarName" style="color:' . esc_attr( $background_colour ) . '" for="calendar' . (int) $item_id . '">' . esc_attr( $calendar_name ) . '</label>';
					echo $rss_link . $ics_link . '</div>'; //phpcs:ignore
				}
				echo '<div class="calendar-select-wrap">';
				echo '</form>';
			}
		}
		/**
		 * This method retrieves calendars available for the fullCalendar events feed. URL will have itemID appended to the params list
		 *
		 * @access public
		 * @see get_calendars_common()
		 * @return object|false
		 */
		public function get_calendars_ajax() {
			$res = $this->get_calendars_common();
			if ( false !== $res ) {
				foreach ( $res as $row ) {
					$calendars[ 'cal' . trim( $row->itemID ) ] = (int) $row->itemID; //phpcs:ignore
				}
			}
			if ( isset( $calendars ) ) {
				return json_encode( $calendars ); //phpcs:ignore
			}
			return 'false';
		}
		/**
		 * Display event details source for dialog boxes
		 * For both admin and public views
		 *
		 * @param bool   $public is the event publc. Optional. Default false.
		 * @param string $all_day is this an all day event. Optional.
		 * @param string $start is the event start date. Optional.
		 * @param string $end is the end date. Optional.
		 * @param string $location is the event location. Optional.
		 * @param string $description is the event description. Optional.
		 * @param string $recurrence - Is this a recurring event. Optional.
		 * @param string $event_id - The specific event ID to create an ICS download for one item. Optional.
		 */
		public function display_dlg_events_details( $public = false, $all_day = '', $start = '', $end = '', $location = '', $description = '', $recurrence = '', $event_id = '' ) {
			$recur_style = ( ! empty( $recurrence ) ) ? '' : 'style="display:none"';
			// Content for the dialog box.
			ob_start();
			$output = '
		<table id="tblEventDetails">
		<tr id="tr_allDay" style="display:none"><td colspan="2">' . $all_day . '</td></tr>
		<tr><td><strong>From:</strong></td><td id="tddate_start">' . $start . '</td></tr>
		<tr><td><strong>To:</strong></td><td id="tddate_end">' . $end . '</td></tr>
		<tr><td><strong>Location:</strong></td><td id="tdLocation">' . $location . '</td></tr>
		<tr><td><strong>Description:</strong></td><td id="tdDescription">' . $description . '</td></tr>
		<tr id="tr_recurring" $recur_style><td><strong>Event Occurs:</strong></td><td>' . $recurrence . '</td></tr>
		</table>';
			if ( true === $public ) {
				preg_match( '/^([0-9]{1,6})(_)?([0-9]{1,6})?$/', $event_id, $matches );
				$add_event_param = ( isset( $matches[1] ) ) ? $matches[1] : '';

				$output .= '<p id="pAddEvent"><a href="' . esc_url( trailingslashit( home_url() ) ) . '?act=ics&eID' . (int) $add_event_param . '&cal_id" aria-label="' . esc_attr__( 'Opens in a new window', 'kcal' ) . ' target="_blank">' . esc_attr__( 'Add to My Calendar', 'kcal' ) . '</a></p>';
			}
			echo wp_kses( $output, 'post' );
			ob_get_contents();
			ob_end_clean();
		}
		/**
		 * Build CSS to be displayed in body.
		 * This is also called via ajax when calendar main is updated in admin so event colours get updated automatically on calendar
		 *
		 * @access public
		 * @see get_calendars_common()
		 */
		public function build_calendar_css() {
			$res = $this->get_calendars_common();
			$css = '';
			if ( false !== $res ) {
				foreach ( $res as $row ) {
					$item_id          = $row->itemID; //phpcs:ignore
					$event_background = trim( $row->eventBackgroundColor ); //phpcs:ignore
					$text_colour      = trim( $row->eventTextColor ); //phpcs:ignore

					$css .= '
					a.cal_' . (int) $item_id . ' .fc-event-title,
					a.cal_' . (int) $item_id . ' .fc-list-event-title,
					a.recur_' . (int) $item_id . ' .fc-event-title,
					a.recur_' . (int) $item_id . ' .fc-list-event-title,
					a.cal_' . (int) $item_id . ':hover .fc-event-title,
					a.cal_' . (int) $item_id . ':hover .fc-list-event-title,
					a.recur_' . (int) $item_id . ':hover .fc-event-title,
					a.recur_' . (int) $item_id . ':hover .fc-list-event-title {
						color: ' . $event_background . ';
						border: 0px;
						background-color: transparent;
						background-image: none;
					}
					a.fc-event.cal_' . (int) $item_id . ' .fc-event-time,
					a.fc-event.recur_' . (int) $item_id . ' .fc-event-time,
					a.fc-event.cal_' . (int) $item_id . ' .fc-list-event-time,
					a.fc-event.recur_' . (int) $item_id . ' .fc-list-event-time {
						color: #404040;
					}
					a.fc-event.cal_' . (int) $item_id . ' .fc-daygrid-event-dot,
					a.fc-event.recur_' . (int) $item_id . ' .fc-daygrid-event-dot,
					.fc .cal_' . (int) $item_id . ' .fc-list-event-dot,
					.fc .recur_' . (int) $item_id . ' .fc-list-event-dot {
						border-color: ' . $event_background . ';
					}
					.allDay_' . (int) $item_id . ',
					a.fc-event .allDay_' . (int) $item_id . ' .fc-event-time,
					a.fc-event .allDay_' . (int) $item_id . ' .fc-list-event-time,
					.allDay_' . (int) $item_id . ' a {
							color: ' . $text_colour . ';
							background-color: ' . $event_background . ';
							border-color: ' . $event_background . ';
					}

					.allDay_' . (int) $item_id . ' .fc-event-title {
						color: ' . $text_colour . ';
					}

					a.fc-timegrid-event.fc-v-event.recur_' . (int) $item_id . ' ,
					a.fc-timegrid-event.fc-v-event.cal_' . (int) $item_id . ' {
						color: ' . $text_colour . ';
						background-color: ' . $event_background . ';
						border-color: ' . $event_background . ';
					}
					a.fc-timegrid-event.fc-v-event.recur_' . (int) $item_id . ' .fc-event-time,
					a.fc-timegrid-event.fc-v-event.cal_' . (int) $item_id . ' .fc-event-time,
					a.fc-timegrid-event.fc-v-event.recur_' . (int) $item_id . ' .fc-event-title,
					a.fc-timegrid-event.fc-v-event.cal_' . (int) $item_id . ' .fc-event-title {
						color: ' . $text_colour . ';
					}


					a.recur_allDay_' . (int) $item_id . ' {
							color: ' . $text_colour . ';
							border-color: ' . $event_background . ';
							background-color: ' . $event_background . ';
							background-image: none;
							padding: 0px 5px;
					}
					a.recur_allDay_' . (int) $item_id . ':hover {
						color: ' . $event_background . ';
						border-color: ' . $event_background . ';
						background-color: ' . $text_colour . ';
				}
					';
				}
			}
			echo wp_kses( $css, 'post' );
		}

		/**
		 * Create an array of events based on results retrieved from database to be returned to calendar as json encoded
		 * This is used for main (parent) events and recurring (child) events
		 *
		 * @param resource $res is the event data from the database.
		 * @param int      $cal_id is the calendar ID.
		 * @param bool     $recurring is this a recurring event. Optional. Default false.
		 * @return array
		 */
		public function set_event_data( $res, $cal_id, $recurring = false ) {
			$events = array();
			if ( false !== $res ) {
				foreach ( $res as $row ) {
					$item_id = explode( '-', $row->ID );

					$meta = get_post_meta( $item_id[0] );

					$timezone = ( isset( $meta['_kcal_timezone'][0] ) ) ? $meta['_kcal_timezone'][0] : get_option( 'gmt_offset' );

					$location = trim( $meta['_kcal_location'][0] );

					foreach ( $meta as $key => $data ) {

						if ( false !== stristr( $key, 'location' ) && '_kcal_location' !== $key && '_kcal_locationMap' !== $key ) {
							$alt_locations = unserialize( $data[0] ); //phpcs:ignore
							if ( is_array( $alt_locations ) && is_numeric( $alt_locations[0] ) ) {
								$location = get_the_title( $alt_locations[0] );
							} elseif ( ! empty( $alt_locations ) ) {
								$location = $alt_locations;
							}
							break;
						}
					}

					$event_id      = $item_id[0];
					$permalink     = get_permalink( $event_id );
					$calendar_data = $this->get_calendars_common( $cal_id );

					if ( true === $recurring && 2 === count( $item_id ) ) {
						$event_id   = $row->ID;
						$permalink .= '?r=' . $item_id[1];
					}

					try {
						$timezone_obj = new \DateTimeZone( $timezone );
					} catch ( exception $e ) {
						$timezone_obj = new \DateTimeZone( get_option( 'gmt_offset' ) );
					}

					$date_s = new DateTime( '', $timezone_obj );
					$date_e = new DateTime( '', $timezone_obj );

					if ( true === $recurring ) {
						$date_s->setTimestamp( $row->eventStartDate ); //phpcs:ignore
						$date_e->setTimestamp( $row->eventEndDate ); //phpcs:ignore
					} else {
						$date_s->setTimestamp( $meta['_kcal_eventStartDate'][0] );
						if ( $meta['_kcal_eventEndDate'][0] <= $meta['_kcal_eventStartDate'][0] ) {
							$date_e->setTimestamp( $date_s->getTimestamp() + 3600 );
						} else {
							$date_e->setTimestamp( $meta['_kcal_eventEndDate'][0] );
						}
					}

					$date_format_options = get_option( 'date_format' );
					$time_format_option  = get_option( 'time_format' );

					$full_date_format = $date_format_options . ' ' . $time_format_option;

					$all_day = ( isset( $meta['_kcal_allDay'][0] ) ) ? (bool) $meta['_kcal_allDay'][0] : false;
					$ics_id  = $event_id;

					$events_array['id']     = $event_id;
					$events_array['title']  = preg_replace( '%[^A-Za-z0-9\s\_\'\"\?\-\:\&\(\)]*%', '', trim( $row->post_title ) );
					$events_array['allDay'] = $all_day;

					$events_array['start'] = $date_s->format( 'Y-m-d H:i:s' );
					$events_array['end']   = $date_e->format( 'Y-m-d H:i:s' );

					$events_array['displayStart'] = ( $all_day ) ? $date_s->format( $time_format_option ) : $date_s->format( $full_date_format );
					$events_array['displayEnd']   = ( $all_day ) ? $date_e->format( $time_format_option ) : $date_e->format( $full_date_format );

					if ( $date_s->format( 'Y-m-d' ) === $date_e->format( 'Y-m-d' ) && ! ( $all_day ) ) {
						$events_array['displayEnd'] = $date_e->format( $time_format_option );
					}

					$events_array['className']   = ( false === $events_array['allDay'] ) ? "cal_$cal_id" : "allDay_$cal_id";
					$events_array['description'] = strip_tags( $row->post_content ); //phpcs:ignore
					$events_array['location']    = $location;
					$events_array['altUrl']      = ( ! empty( $meta['_kcal_eventURL'][0] ) ) ? $meta['_kcal_eventURL'][0] : $permalink;
					$events_array['recurrence']  = 'None';
					if ( isset( $meta['_kcal_recurrenceType'][0] ) && 'None' !== $meta['_kcal_recurrenceType'][0] ) {
						$events_array['recurrence']            = $meta['_kcal_recurrenceType'][0];
						$events_array['className']             = ( false === $events_array['allDay'] ) ? "recur_$cal_id" : "recur_allDay_$cal_id";
						$recurrence_description                = ( isset( $meta['_kcal_recurrenceInterval'][0] ) && $meta['_kcal_recurrenceInterval'][0] < 2 ) ? $meta['_kcal_recurrenceType'][0] : __( 'Every', 'kcal' ) . ' ' . (int) $meta['_kcal_recurrenceInterval'][0] . ' ' . str_replace( 'ly', 's', $meta['_kcal_recurrenceType'][0] );
						$events_array['recurrenceDescription'] = $recurrence_description;
						$events_array['recurrenceEnd']         = $meta['_kcal_recurrenceEnd'][0];
						if ( false !== $recurring ) {
							$events_array['recurrenceID'] = $row->metaID; //phpcs:ignore
							$ics_id                      .= '-' . $row->metaID; //phpcs:ignore
						}
					}
					$events_array['style'] = array(
						'color'      => $calendar_data[0]->eventTextColor,
						'background' => $calendar_data[0]->eventBackgroundColor,
					);
					$events_array['ics']   = '<a href="' . trailingslashit( home_url() ) . '?act=ics&eID=' . $event_id . '&cal_id=' . $cal_id . '" aria-label="' . __( 'Opens in a new window' ) . '" target="_blank" title="' . __( 'Add to my calenar', 'kcal' ) . '"><span class="k-icon-calendar" role="decoration"></span><span class="visuallyhidden">' . __( 'Add to Calendar', 'kcal' ) . '</span></a>';
					$events[]              = $events_array;
				}
			}
			return $events;
		}
		/**
		 * Retrieve repeating events based on parent record ID (eventID).
		 * Each event returns all of the same information as parent event
		 *
		 * @access protected
		 * @param array $get is the server _get array.
		 * @param array $posts is the server $posts are the events to check for repeatinge events.
		 * @see DB::result_please()
		 * @see set_event_data()
		 * @return array
		 */
		protected function get_repeating_events( $get, $posts ) {
			if ( preg_match( '/^[0-9]{1,6}$/', intVal( $get['calendar'], 10 ), $matches ) ) {
				$res = array();
				foreach ( $posts as $event ) {
					$recur = get_post_meta( $event->ID, '_kcal_recurrenceDate' );
					if ( false !== $recur ) {

						foreach ( $recur as $r_event ) {
							$start_time                   = array_keys( $r_event );
							list( $end_time, $meta_id )   = array_values( $r_event[ $start_time[0] ] );
							$recur_data                   = (array) $event;
							$recur_data['eventStartDate'] = $start_time[0];
							$recur_data['eventEndDate']   = $end_time;
							$recur_data['metaID']         = $meta_id;
							$res[]                        = (object) $recur_data;

						}
					}
				}
				$events = $this->set_event_data( $res, intVal( $get['calendar'], 10 ), true );
			}
			if ( isset( $events ) ) {
				return $events;
			}
			return array();
		}
		/**
		 * Return the calendar name and background colour for each active calendar
		 *
		 * @access public
		 * @see get_calendars_common()
		 * @return void|array
		 */
		public function get_calendar_details() {
			$res      = $this->get_calendars_common();
			$calendar = array();
			if ( false !== $res ) {
				foreach ( $res as $row ) {
					$calendar[ trim( $row->itemID ) ]['name']   = trim( $row->calendarName ); //phpcs:ignore
					$calendar[ trim( $row->itemID ) ]['colour'] = trim( $row->eventBackgroundColor ); //phpcs:ignore
					$calendar[ trim( $row->itemID ) ]['text']   = trim( $row->eventTextColor ); //phpcs:ignore
				}
			}
			return $calendar;
		}
		/**
		 * Retrieve event details based on event ID sent from a widget on a page different from the main calendar
		 *
		 * @access public
		 * @param array $get is the GET server var.
		 * @see DB::result_please()
		 * @see set_event_data()
		 * @return void|array
		 */
		public function get_event_details_by_id( $get ) {
			$event = array();
			if ( isset( $get['event'] ) && preg_match( '/^([0-9]{1,6})(\-)?([0-9]{1,})?$/', $get['event'], $matches ) ) {
				if ( isset( $matches[1] ) ) {
					$main_post = get_post( $matches[1] );
					if ( isset( $matches[3] ) ) {
						$event = $this->get_repeating_events( $get, $main_post );
					} else {
						$event = $this->set_event_data( $main_post, $get['calendar'], false );
					}
				}
			}
			return $event;
		}
	}
}
