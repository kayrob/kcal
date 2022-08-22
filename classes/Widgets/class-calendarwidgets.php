<?php
/**
 * CalendarWidgets class displays widgets for public viewing of events
 * Date created: Nov 29 2010 by Karen Laansoo
 *
 * @package kcal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'CalendarWidgets' ) && class_exists( 'Calendar' ) ) {
	/**
	 * Class CalendarWidgets
	 */
	class CalendarWidgets extends Calendar {
		/**
		 * Retrieves all events from events and recurring events table by UNION. Start and End date are pre-set if not provided.
		 *
		 * @param string  $start_date is the event start date boundary. Optional.
		 * @param string  $end_date is the event end date boundary. Optional.
		 * @param integer $calendar_id is the calendar to select from. Optional.
		 * @param boolean $show_private events. Optional.
		 * @param array   $custom_meta_query - Customize how events are queried. Optional.
		 * @return object
		 */
		public function retrieve_all_events( $start_date = false, $end_date = false, $calendar_id = 0, $show_private = false, $custom_meta_query = array() ) {

			try {
				$timezone_obj = new \DateTimeZone( $this->timezone );
			} catch ( Exception $e ) {
				$timezone_obj = new \DateTimeZone( get_option( 'gmt_offset' ) );
			}

			$date    = new \DateTime( '', $timezone_obj );
			$current = $date->getTimestamp();
			$today   = mktime( 0, 0, 0, $date->format( 'n' ), $date->format( 'j' ), $date->format( 'Y' ) );

			$where_date = ( false === $start_date ) ? $today : $start_date;

			$where_end_op  = ( false === $end_date ) ? '>=' : '<=';
			$where_end_str = ( false === $end_date ) ? $where_date : $end_date;

			$meta_query = array(
				'relation' => 'AND',
				array(
					'key'     => '_kcal_eventStartDate',
					'value'   => $where_date,
					'compare' => '>=',

				),
				array(
					'key'     => '_kcal_eventEndDate',
					'value'   => $where_end_str,
					'compare' => $where_end_op,
				),
			);

			$recur_query = array(
				'relation' => 'AND',
				array(
					'key'     => '_kcal_recurrenceEnd',
					'value'   => $where_date,
					'compare' => '>',
				),
				array(
					'key'     => '_kcal_recurrenceType',
					'value'   => 'None',
					'compare' => 'Not In',
				),
			);

			if ( is_array( $custom_meta_query ) && ! empty( $custom_meta_query ) ) {
				foreach ( $custom_meta_query as $cmq ) {
					if ( isset( $cmq['key'] ) ) {
						array_push( $meta_query, $cmq );
						array_push( $recur_query, $cmq );
					}
				}
			}

			$post_status = array( 'publish' );
			if ( true === $show_private ) {
				$post_status[] = 'private';
			}
			$args = array(
				'post_type'      => 'event',
				'post_status'    => $post_status,
				'numberposts'    => -1,
				'order_by'       => '_kcal_eventStartDate',
				'order'          => 'ASC',
				'meta_query'     => $meta_query, //phpcs:ignore
				'posts_per_page' => -1,
			);

			if ( is_numeric( $calendar_id ) && 0 < (int) $calendar_id ) {
				$tax_query         = array(
					array(
						'taxonomy' => 'calendar',
						'field'    => 'term_id',
						'terms'    => (int) $calendar_id,
					),
				);
				$args['tax_query'] = $tax_query; //phpcs:ignore
			}

			$recur_args               = $args;
			$recur_args['meta_query'] = $recur_query; //phpcs:ignore

			$events = query_posts( $args ); //phpcs:ignore
			wp_reset_query(); //phpcs:ignore
			$recur_events = query_posts( $recur_args ); //phpcs:ignore
			wp_reset_query(); //phpcs:ignore

			$res        = array();
			$all_events = array();
			if ( false !== $events ) {
				$all_events = $events;
			}
			if ( false !== $recur_events ) {
				foreach ( $recur_events as $re ) {
					$all_events[] = $re;
				}
			}
			if ( ! empty( $all_events ) ) {
				foreach ( $all_events as $event ) {
					$event_id       = $event->ID;
					$recur_start    = 0;
					$recur_end      = 0;
					$event_meta     = get_post_meta( $event->ID );
					$event_calendar = wp_get_post_terms( $event->ID, 'calendar' );

					if ( isset( $event_calendar[0]->term_id ) ) {
						$event->calendarID = $event_calendar[0]->term_id; //phpcs:ignore

						if ( false !== $calendar_id ) {
							foreach ( $event_calendar as $cal_term ) {
								if ( (int) $cal_term->term_id === (int) $calendar_id ) {
									$event->calendarID = (int) $calendar_id; //phpcs:ignore
								}
							}
						}
						$event->eventStartDate = $event_meta['_kcal_eventStartDate'][0]; //phpcs:ignore

						$event->eventEndDate        = $event_meta['_kcal_eventEndDate'][0]; //phpcs:ignore
						$event->detailsAlternateURL = $event_meta['_kcal_eventURL'][0]; //phpcs:ignore
						$event->timezone            = ( isset( $event_meta['_kcal_timezone'] ) ) ? $event_meta['_kcal_timezone'][0] : get_option( 'gmt_offset' );
						$res[ $event->ID ]          = $event;

						$recurrence        = ( isset( $event_meta['_kcal_recurrenceType'][0] ) ) ? $event_meta['_kcal_recurrenceType'][0] : 'None';
						$event->recurrence = $recurrence;

						if ( isset( $event_meta['_kcal_recurrenceEnd'][0] ) && 'Null' !== $event_meta['_kcal_recurrenceEnd'][0] && ! empty( $event_meta['_kcal_recurrenceEnd'][0] ) ) {
							foreach ( $event_meta['_kcal_recurrenceDate'] as $r_date ) {
								$recur_date                 = unserialize( $r_date ); //phpcs:ignore
								$start_time                 = array_keys( $recur_date );
								list( $end_time, $meta_id ) = array_values( $recur_date[ $start_time[0] ] );

								$recur = array();
								foreach ( $event as $key => $value ) {
									$recur[ $key ] = $value;
								}
								$recur_id                     = '';
								$recur_id                     = $event_id . '-' . $meta_id;
								$recur['calendarID']          = $event->calendarID; //phpcs:ignore
								$recur['eventStartDate']      = $event_meta['_kcal_eventStartDate'][0];
								$recur['eventEndDate']        = $event_meta['_kcal_eventEndDate'][0];
								$recur['detailsAlternateURL'] = $event_meta['_kcal_eventURL'][0];
								$recur['eventStartDate']      = $start_time[0];
								$recur['eventEndDate']        = $end_time;
								$recur['ID']                  = $recur_id;
								$recur['metaID']              = $meta_id;
								$recur['timezone']            = ( isset( $event_meta['_kcal_timezone'] ) ) ? $event_meta['_kcal_timezone'][0] : get_option( 'gmt_offset' );
								$res[ $recur_id ]             = json_decode( json_encode( $recur ) ); //phpcs:ignore
							}
						}
					}
				}
			}
			$e_list   = array();
			$filtered = array_values( $res );
			foreach ( $filtered as $index => $e_item ) {
				if ( $e_item->eventStartDate >= $where_date ) { //phpcs:ignore
					if ( ! isset( $e_list[ $e_item->eventStartDate ] ) ) { //phpcs:ignore
						$e_list[ $e_item->eventStartDate ] = $e_item; //phpcs:ignore
					} else {
						$e_list[ ($index) + $e_item->eventStartDate ] = $e_item; //phpcs:ignore
					}
				}
			}
			ksort( $e_list );
			return $e_list;
		}
		/**
		 * Get single event data for a main event or a repeating event.
		 *
		 * @param integer $post_id is the main event ID.
		 * @param integer $meta_id recurrence ID. Optional.
		 */
		public function retrieve_one_event( $post_id, $meta_id = 0 ) {
			global $wpdb;
			$join        = '';
			$join_where  = '';
			$join_select = '';
			$event       = false;
			$post_status = "'publish'";
			if ( is_user_logged_in() ) {
				$post_status = "'publish','private'";
			}
			if ( 0 < $meta_id ) {
				$join        = sprintf( ' INNER JOIN `%spostmeta` AS pm ON p.`ID` = pm.`post_id`', $wpdb->prefix );
				$join_where  = sprintf(
					" AND pm.`meta_id` = %d AND pm.`meta_key` = '%s'",
					$meta_id,
					'_kcal_recurrenceDate',
				);
				$join_select = ',pm.`meta_value`';
			}
			$result = $wpdb->get_row( //phpcs:ignore
				$wpdb->prepare(
					'SELECT p.* %s FROM `%sposts` as p%s WHERE p.`ID` = %d AND p.`post_status` IN (%s)%s',
					$join_select,
					$wpdb->prefix,
					$join,
					$post_id,
					$post_status,
					$join_where,
				)
			);
			if ( false !== $result ) {
				if ( isset( $result->meta_value ) ) {
					$r_data                 = unserialize( $result->meta_value ); //phpcs:ignore
					$start_time             = array_values( array_keys( $r_data ) );
					$result->eventStartDate = $start_time[0]; //phpcs:ignore
					$result->eventEndDate   = $r_data[ $start_time[0] ]['endDate']; //phpcs:ignore
				} else {
					$result->eventStartDate = get_post_meta( $result->ID, '_kcal_eventStartDate', true ); //phpcs:ignore
					$result->eventEndDate   = get_post_meta( $result->ID, '_kcal_eventEndDate', true ); //phpcs:ignore
				}
				$event = $result;
			}
			return $event;
		}
		/**
		 * Retrieves events based on start time and end time parameters
		 * Return json encoded string
		 *
		 * @param array $get is the ajax request data.
		 *
		 * @see Calendar::get_calendars_common()
		 * @see get_calendar_events_details()
		 * @return object
		 */
		public function get_calendar_events_ajax( $get ) {
			$event_data = array();

			if ( isset( $get['calendar'] ) && isset( $get['start'] ) && isset( $get['end'] ) ) {
				$res = $this->get_calendars_common( $get['calendar'] );

				if ( false !== $res ) {

					$start_date = new \DateTime( $get['start'] );
					$end_date   = new \DateTime( $get['end'] );

					$rows = $this->retrieve_all_events( $start_date->getTimestamp(), $end_date->getTimestamp(), $get['calendar'] );
					if ( false !== $rows ) {
						$e_list = array();
						foreach ( $rows as $index => $e_item ) {

							if ( ! isset( $e_list[ $e_item->eventStartDate ] ) ) { //phpcs:ignore
								$e_list[ $e_item->eventStartDate ] = $e_item; //phpcs:ignore
							} else {
								$e_list[ ($index) + $e_item->eventStartDate ] = $e_item; //phpcs:ignore
							}
						}
						ksort( $e_list );
						foreach ( $e_list as $sorted ) {
							$is_recurring  = (bool) strstr( $sorted->ID, '-' );
							$event_details = $this->set_event_data( array( $sorted ), $sorted->calendarID, $is_recurring ); //phpcs:ignore
							if ( ! empty( $event_details ) ) {
								$event_data[] = $event_details[0];
							}
						}
					}
				}
			}
			return json_encode( $event_data ); //phpcs:ignore
		}
		/**
		 * Display a list of upcoming events based on the number of events requested
		 *
		 * @param integer $limit is the number of items to return.
		 * @param integer $calendar_id is the calendar to query. Optional.
		 * @param string  $start is the start date boundary. Optional.
		 * @param string  $end is the end date boundary. Optional.
		 * @param boolean $show_private is the public/private display flag. Optional.
		 * @param array   $custom_meta_query is an optional array for custom query parameters.
		 *
		 * @see get_calendar_details()
		 * @see DB_MySQL::valid()
		 */
		public function upcoming_events_widget( $limit, $calendar_id = false, $start = false, $end = false, $show_private = false, $custom_meta_query = array() ) {
			if ( 0 < intval( $limit, 10 ) ) {
				$res       = $this->retrieve_all_events( $start, $end, intval( $calendar_id ), $show_private, $custom_meta_query );
				$calendars = $this->get_calendar_details();
				$date      = new \DateTime();
				$time_diff = ( $date->getOffset() + (int) $this->timezone ) * ( 60 * 60 ); // May be negative value.
				$current   = $date->getTimestamp() + $time_diff;
				$today     = mktime( 0, 0, 0, date( 'n', $current ), date( 'j', $current ), date( 'Y', $current ) ); //phpcs:ignore

				if ( false !== $start ) {
					$today = $start;
				}

				if ( false !== $res && ! empty( $calendars ) ) {
					$j = 0;
					foreach ( $res as $row ) {
						if ( false === $calendar_id || (int) $row->calendarID === (int) $calendar_id ) { //phpcs:ignore

							if ( array_key_exists( $row->calendarID, $calendars ) && $row->eventStartDate >= $today ) { //phpcs:ignore
								if ( $j < $limit ) {
									$link = ( ! empty( $row->detailsAlternateURL ) ) ? trim( $row->detailsAlternateURL ) : get_permalink( $row->ID ); //phpcs:ignore

									$events[ $row->ID ] = array(
										'id'          => (int) $row->ID,
										'start'       => $row->eventStartDate, //phpcs:ignore
										'end'         => $row->eventEndDate, //phpcs:ignore
										'link'        => $link,
										'title'       => trim( $row->post_title ),
										'description' => trim( $row->post_content ),
										'calendar'    => $row->calendarID, //phpcs:ignore
									);
									$j++;
								}
							}
						}
					}
					if ( isset( $events ) ) {
						return $events;
					}
				}
			}
			return false;
		}
		/**
		 * Get a filtered list of events inside the taxonomy page.
		 *
		 * @param integer $paged is the page the user is on.
		 * @param array   $custom_query is an optional custom query for the events.
		 *
		 * @return array
		 */
		public function upcoming_events_archive_filter( $paged, $custom_query = array() ) {
			$the_term = get_term_by( 'slug', get_query_var( 'calendar' ), 'calendar' );

			$start = false;
			$end   = false;

			$page_num = ( 0 < (int) $paged ) ? (int) $paged : 1;

			$limit = $page_num * 100;

			$today = strtotime( date( 'Y-m-d' ) ); //phpcs:ignore

			if ( isset( $_GET['fy'] ) && isset( $_GET['fm'] ) ) { //phpcs:ignore
				$start = strtotime( $_GET['fm'] . ' 1, ' . $_GET['fy'] ); //phpcs:ignore
				$end   = strtotime( $_GET['fm'] . ' '. date( 't', $start ) . ', ' . $_GET['fy'] . ' +7days' ); //phpcs:ignore
			}
			$data = ( isset( $the_term->term_id ) ) ? $this->upcoming_events_widget( $limit, $the_term->term_id, $start, $end, false, $custom_query ) : $this->upcoming_events_widget( $limit, false, $start, $end, false, $custom_query );

			$filtered = array();
			if ( ! empty( $data ) ) {
				foreach ( $data as $event_id => $event ) {
					if ( false === $start || ( $event['start'] >= $today && date( 'n', $event['start'] ) == date( 'n', $start ) ) ) { //phpcs:ignore
						$filtered[ $event_id ] = $event;
					}
				}
			}
			return $filtered;
		}
		/**
		 * Output RSS feed based on parameters sent in query string. Default is to display all
		 * Only events that are occuring greater than 30 days in advance are currently displayed
		 *
		 * @access public
		 * @param false|string $calendar_id is the record ID of the calendar. Optional.
		 * @param integer      $limit is the number of events to show.
		 * @see get_calendar_details()
		 * @see retrieve_all_events()
		 */
		public function build_rss( $calendar_id, $limit ) {

			$title = get_bloginfo( 'name', 'raw' ) . ' ';

			$rss  = '<?xml version="1.0" encoding="UTF-8">' . "\n";
			$rss .= '<rss version="2.0">' . "\n";
			$rss .= '<channel>' . "\n";
			$rss .= "<title><![CDATA[" . $title . "]]></title>" . "\n"; //phpcs:ignore
			$rss .= '<description>' . __( 'Upcoming Events', 'kcal' ) . '</description>' . "\n";
			$rss .= '<lastBuildDate>' . date( 'D d M Y G:i:s' ) . '</lastBuildDate>' . "\n"; //phpcs:ignore

			$calendars = $this->get_calendar_details();
			$title    .= ( 0 < (int) $calendar_id && isset( $calendars[ $calendar_id ] ) ) ? $calendars[ $calendar_id ]['name'] : esc_attr__( 'All Events', 'kcal' );
			$i         = 0;
			if ( 0 < (int) $calendar_id || false === $calendar_id ) {
				$res = $this->retrieve_all_events();
				if ( 0 < count( $res ) ) {

					foreach ( $res as $row ) {
						try {
							$timezone_obj = new \DateTimeZone( $row->timezone );
						} catch ( Exception $e ) {
							$timezone_obj = new \DateTimeZone( get_option( 'gmt_offset' ) );
						}

						$start_date_obj = new \DateTime( '', $timezone_obj );
						$pub_date_obj   = new \DateTime( '', $timezone_obj );

						$start_date_obj->setTimestamp( $row->eventStartDate ); //phpcs:ignore
						$pub_date_obj->setTimestamp( get_post_timestamp( $row->ID ) );

						if ( array_key_exists( trim( $row->calendarID ), $calendars ) && $start_date_obj->getTimestamp() >= ( date( 'U' ) ) ) { //phpcs:ignore

							$item_id   = explode( '-', $row->ID );
							$permalink = get_permalink( $item_id[0] );
							if ( true === $recurring && 2 === count( $item_id ) ) {
								$permalink .= '?r=' . $item_id[1];
							}

							$source = false;

							if ( 0 < (int) $calendar_id ) {
								$source = get_term_link( $row->calendarID, 'calendar' ); //phpcs:ignore
							}
							$rss .= '<item>' . "\n";
							$rss .= '<title>' . esc_attr( $row->post_title ) . '</title>' . "\n";
							$rss .= '<link>' . $permalink . '</link>' . "\n";
							$rss .= "<description><![CDATA[" .  get_the_excerpt( $row->ID ) . "]]></description>" . "\n"; //phpcs:ignore
							$rss .= '<pubDate>' . $pub_date_obj->format( 'D, j M Y' ) . '</pubDate>' . "\n"; //phpcs:ignore
							$rss .= '<source>' . $source . '</source>' . "\n";
							$rss .= '</item>' . "\n";
						}
						$i++;
						if ( $i === $limit ) {
							break;
						}
					}
				}
			}
			$rss .= '</channel>' . "\n";
			$rss .= '</rss>';
			return $rss;
		}
		/**
		 * Output calendar events in iCalendar (.ics) format
		 * Calendar, and start/stop date_s can be submitted as parameters via a query string (like ajax)
		 *
		 * @access public
		 *
		 * @param array $get is the AJAX request data.
		 *
		 * @see get_calendar_details()
		 */
		public function output_ics( $get ) {
			$output    = '';
			$calendars = $this->get_calendar_details();
			$end_date  = false;
			if ( isset( $get['calendar'] ) || array_key_exists( trim( $get['calendar'] ), $calendars ) ) {
				$start_date = ( isset( $get['start'] ) && preg_match( '/^[0-9]{8,}$/', trim( $get['start'] ), $matches ) ) ? $get['start'] : false;
				$end_date   = ( isset( $get['end'] ) && preg_match( '/^[0-9]{8,}$/', trim( $get['end'] ), $matches ) ) ? $get['end'] : false;

				$res = false;
				if ( isset( $get['event'] ) ) {
					$event_id = explode( '-', $get['event'] );
					$meta_id  = ( isset( $event_id[1] ) ) ? $event_id[1] : 0;
					$res[0]   = $this->retrieve_one_event( $event_id[0], $meta_id );
					if ( false !== $res[0] ) {
						$res[0]->calendarID = $get['calendar'];
					}
				} elseif ( false !== $start_date ) {
					$res = $this->retrieve_all_events( $start_date, $end_date, $get['calendar'] );
				}

				if ( false !== $res ) {

					$timezone   = 'UTC' . get_option( 'gmt_offset' );
					$offset     = abs( get_option( 'gmt_offset' ) );
					$output     = "BEGIN:VCALENDAR\nVERSION:2.0\n";
					$output    .= "BEGIN:VTIMEZONE\n";
					$output    .= "TZID:{$timezone}\n";
					$output    .= "END:VTIMEZONE\n";
					$blog_title = get_bloginfo( 'name', 'raw' );
					foreach ( $res as $row ) {
						if ( isset( $calendars[ $row->calendarID ] ) ) { //phpcs:ignore
							$post_id = explode( '-', $row->ID );
							if ( isset( $post_id[0] ) ) {
								$event_tz = get_post_meta( $post_id[0], '_kcal_timezone', true );

								try {
									$timezone_obj = new \DateTimeZone( $event_tz );
								} catch ( Exception $e ) {
									$timezone_obj = new \DateTimeZone( get_option( 'gmt_offset' ) );
								}

								$start_date_obj = new \DateTime( '', $timezone_obj );
								$end_date_obj   = new \DateTime( '', $timezone_obj );

								$start_date_obj->setTimestamp( $row->eventStartDate ); //phpcs:ignore
								$end_date_obj->setTimestamp( $row->eventEndDate ); //phpcs:ignore

								$post = get_post( $post_id[0] );
								if ( ! isset( $get['event'] ) ) {
									$blog_title .= $calendars[ $row->calendarID ]['name']; //phpcs:ignore
								}
								$start_timestamp = $row->eventStartDate; //phpcs:ignore
								$end_timestamp   = $row->eventEndDate; //phpcs:ignore
								$consec_days     = ceil( ( $end_date_obj->getTimestamp() - $start_date_obj->getTimestamp() ) / ( 60 * 60 * 24 ) );
								$end_timestamp  += ( 1 < $consec_days ) ? 60 * 60 * 24 : 0;
								$event_start     = "TZID={$row->timezone}:" . $start_date_obj->format( 'Ymd\THis' );
								$event_end       = "TZID={$row->timezone}:" . $end_date_obj->format( 'Ymd\THis' );
								$event_start     = ( 1 < $consec_days ) ? 'VALUE=DATE:' . $start_date_obj->format( 'Ymd\THis' ) : $event_start;
								$event_end       = ( 1 < $consec_days ) ? 'VALUE=DATE:' . $end_date_obj->format( 'Ymd\THis' ) : $event_end;
								$description     = ( 1 < $consec_days ) ? '(' . $start_date_obj->format( 'g:i a' ) . '-' . $end_date_obj->format( 'g:i a' ) . ')' : '';

								$output .= "BEGIN:VEVENT\n";
								$output .= 'UID:uid' . trim( $row->ID ) . '@' . site_url() . "\n";
								$output .= 'DTSTAMP;TZID=' . $row->timezone . ':' . str_replace( ' ', 'T', preg_replace( '/[:-]/', '', trim( $post->post_date ) ) ) . "\n";
								$output .= "DTSTART;$event_start\n";
								$output .= "DTEND;$event_end\n";
								$output .= "SUMMARY: {$blog_title}: " . trim( $post->post_title ) . "\n";
								$output .= 'LOCATION:' . get_post_meta( $post_id[0], '_kcal_location', true ) . "\n";
								$output .= 'DESCRIPTION:' . strip_tags( $post->post_title ) . " $description\n"; //phpcs:ignore
								$output .= "PRIORITY:3\n";
								$output .= "END:VEVENT\n";
							}
						}
					}
					$output .= 'END:VCALENDAR';
				}
			}
			return $output;
		}
		/**
		 * Displays the div container for quick view calendar dialog which is populated via ajax request data
		 *
		 * @access public
		 */
		public function quick_view_dialog() {
			echo '<div id="dlgQuickView" title="' . esc_attr__( 'Events', 'kcal' ) . '"></div>';
		}

		/**
		 * Returns a list of events formatted in a dl element for quick view calendar ajax request
		 *
		 * @access public
		 * @param string $timestamp is the time to start the query at.
		 * @param string $cal_settings is the selected calendar.
		 * @see get_calendar_details()
		 * @see retrieve_all_events()
		 * @return string
		 */
		public function get_quick_view_events_ajax( $timestamp, $cal_settings ) {
			$output = 'false';
			if ( preg_match( '/^[0-9]{8,}$/', $timestamp, $matches ) ) {

				try {
					$timezone_obj = new \DateTimeZone( $this->timezone );
				} catch ( Exception $e ) {
					$timezone_obj = new \DateTimeZone( get_option( 'gmt_offset' ) );
				}

				$date_s = new \DateTime( 'now', $timezone_obj );
				$date_e = new \DateTime( 'now', $timezone_obj );

				$date_sel       = mktime( 0, 0, 0, date( 'n', $timestamp ), date( 'j', $timestamp ), date( 'Y', $timestamp ) ); //phpcs:ignore
				$end_date_stamp = $date_sel + ( 60 * 60 * 30 );
				$date_sel_end   = date( 'Y-m-d', $end_date_stamp ); //phpcs:ignore
				$calendars      = $this->get_calendar_details();

				$res = $this->retrieve_all_events( $date_sel, $end_date_stamp );

				if ( empty( $cal_settings ) ) {
					$cal_settings = array_keys( $calendars );
				}

				$date_format = get_option( 'date_format' );
				$time_format = get_option( 'time_format' );

				if ( false !== $res ) {
					$output = '';
					foreach ( $res as $row ) {

						if ( isset( $calendars[ $row->calendarID ] ) && in_array( $row->calendarID, $cal_settings ) ) { //phpcs:ignore

							$event_id = explode( '-', $row->ID );

							try {
								$timezone_obj = new \DateTimeZone( $row->timezone );
							} catch ( Exception $e ) {
								$timezone_obj = new \DateTimeZone( get_option( 'gmt_offset' ) );
							}
							$date_s->setTimezone( $timezone_obj );
							$date_e->setTimezone( $timezone_obj );

							$date_s->setTimestamp( $row->eventStartDate ); //phpcs:ignore
							$date_e->setTimestamp( $row->eventEndDate ); //phpcs:ignore

							if (date( 'Y-m-d', $date_sel ) === $date_s->format( 'Y-m-d' ) ) { //phpcs:ignore
								$event_date = ( $date_s->format( 'Y-m-d' ) === $date_s->format( 'Y-m-d' ) ) ? $date_s->format( 'M d' ) : $date_s->format( 'M d' ) . '-' . $date_e->format( 'M d' );

								$link = get_permalink( $event_id[0] );

								if ( isset( $event_id[1] ) ) {
									$link .= '?r=' . $event_id[1];
								}

								$event_title = ( ! empty( $link ) ) ? '<a href=" ' . esc_url( $link ) . '" style="background-color:' . $calendars[ trim( $row->calendarID ) ]['colour'] . ';color:' . $calendars[ trim( $row->calendarID ) ]['text'] . '">' . esc_attr( $row->post_title ) . '</a>' : esc_attr( $row->post_title ); //phpcs:ignore

								$output .= '<dt><span>' . $event_title . '</span></dt>';
								$output .= "<dd>{$event_date} &#8226; " . $date_s->format( $time_format ) . '-' . $date_e->format( $time_format ) . '</dd>';
							}
						}
					}
					if ( ! empty( $output ) ) {
						$output = '<dl>' . $output . '</dl>';
					}
				}
			}
			if ( empty( $output ) ) {
				$output = 'false';
			}
			return $output;
		}
		/**
		 * Returns day of week for first day of specified month.
		 *
		 * @access protected
		 *
		 * @param string $year is the calendar year.
		 * @param string $month is the calendar month.
		 *
		 * @return string
		 */
		protected function get_first_day( $year, $month ) {
			$day_one_month = mktime( 12, 00, 00, $month, 1, $year );
			$first_day     = date( 'w', $day_one_month ) + 1; //phpcs:ignore
			return $first_day;
		}
		/**
		 * Return the number of days in a specified month
		 *
		 * @access protected
		 *
		 * @param string $year is the calendar year.
		 * @param string $month is the calendar month.
		 *
		 * @return string
		 */
		protected function month_length( $year, $month ) {
			$last_day_month = $month + 1;
			$num_days       = mktime( 0, 0, 0, $last_day_month, 0, $year );
			$length         = date( 't', $num_days ); //phpcs:ignore
			return $length;
		}
		/**
		 * Format the month to two digits.
		 *
		 * @access protected
		 *
		 * @param string $month_num is the numerical version of the month.
		 *
		 * @return string
		 */
		protected function format_month( $month_num ) {
			return ( str_pad( $month_num, 2, '0', STR_PAD_LEFT ) );
		}
		/**
		 * Returns an array of date_s that have events occurring within the current calendar month
		 * event has to be in an existing calendar, and that calendar has to be selected in the settings
		 *
		 * @access protected
		 *
		 * @param string $month is the calendar month.
		 * @param string $year is the calendar year.
		 * @param array  $cal_settings is the calendar id.
		 *
		 * @see get_calendar_details()
		 * @see retrieve_all_events()
		 * @return array
		 */
		protected function get_quick_view_dates( $month, $year, $cal_settings ) {
			$date_s      = array();
			$event_start = mktime( 0, 0, 0, str_pad( $month, 2, '0', STR_PAD_LEFT ), '01', $year );
			$month_next  = ( ( $month + 1 ) > 12 ) ? '01' : ( ( $month + 1 ) );
			$year_next   = ( ( $month + 1 ) > 12 ) ? ( $year + 1 ) : $year;
			$event_end   = mktime( 0, 0, 0, str_pad( $month_next, 2, '0', STR_PAD_LEFT ), '01', $year_next );
			$calendars   = $this->get_calendar_details();
			$res         = $this->retrieve_all_events( $event_start, $event_end );

			if ( empty( $cal_settings ) ) {
				$cal_settings = array_keys( $calendars );
			}

			try {
				$timezone_obj = new \DateTimeZone( $this->timezone );
			} catch ( Exception $e ) {
				$timezone_obj = new \DateTimeZone( get_option( 'gmt_offset' ) );
			}
			$date = new \DateTime( '', $timezone_obj );
			if ( false !== $res ) {
				foreach ( $res as $row ) {
					$date->setTimestamp( $row->eventStartDate ); //phpcs:ignore
					if ( $date->format('n') === $month && $date->format( 'Y' ) === $year && isset( $calendars[ $row->calendarID ] ) && in_array( $row->calendarID, $cal_settings ) ) { //phpcs:ignore
						$dates_available[ $date->format( 'j' ) ] = mktime( 0, 0, 0, $date->format( 'm' ), $date->format( 'd' ), $date->format( 'Y' ) );
					}
				}
				if ( isset( $dates_available ) ) {
					$date_s = array_unique( $dates_available );
				}
			}
			return $date_s;
		}
		/**
		 * Creates a small calendar for viewing date_s with events listed in db
		 * CSS is completely customizable
		 * returned as string so it can be accessed statically and dynamically (ajax)
		 *
		 * @param string $month is the calendar month.
		 * @param string $year is the calendar year.
		 * @param array  $cal_settings is the calendar id.
		 *
		 * @see get_quick_view_dates()
		 * @see get_first_day()
		 * @see month_length()
		 */
		public function quick_view_calendar( $month = false, $year = false, $cal_settings = array() ) {
			/**
			 * Get the current and next month as numerical values 1 through 12
			 * Get the year for each of the current and next month as YYYY for display
			*/

			$month = ( false !== $month && 0 < $month && 13 > $month ) ? $month : date( 'n', current_time( 'timestamp' ) ); //phpcs:ignore
			$year = ( false !== $year && preg_match( '/^20[0-9]{2}$/', $year, $matches ) ) ? $year : date( 'Y', current_time( 'timestamp' ) ); //phpcs:ignore
			$dates_available = $this->get_quick_view_dates( $month, $year, $cal_settings );

			/*
			Call functions to create arrays for each month for:
			1) the day of the week of the first day of the month as numerical values 0-6
			2) the total number of days in the month
			3) the total number of days to display (includes white space to display the proper
			numerical date values under the correct day of the week.
			*/
			$first_day_of_week = $this->get_first_day( $year, $month );
			$days_of_month     = $this->month_length( $year, $month );
			$display_days      = $days_of_month + $first_day_of_week;
			$days_end_of_month = 6 - date( 'w', mktime( 0, 0, 0, $month, $days_of_month, $year ) ); //phpcs:ignore
			// determine number of days for each month to display highlight.
			$today = date( 'j' ); //phpcs:ignore
			$month_names = array(
				'January',
				'February',
				'March',
				'April',
				'May',
				'June',
				'July',
				'August',
				'September',
				'October',
				'November',
				'December',
			);

			$cal  = '<table id="calendarWidgetTable">';
			$cal .= '<tr class="calHeader2"><th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th></tr>';
			// populate table.
			$cal .= '<tr>';
			for ( $i = 1; $i < $display_days; $i++ ) {
				if ( $i < $first_day_of_week ) {
					$cal .= '<td class="tdCalSpacer">' . date( 'j', ( mktime( 0, 0, 0, $month, 1, $year ) - ( 60 * 60 * 24 * ( $first_day_of_week - $i ) ) ) ) . '</td>'; //phpcs:ignore
				} else {
					$style   = ( array_key_exists( ( $i - $first_day_of_week + 1 ), $dates_available ) ) ? 'class="tdQvEvents"' : '';
					$onclick = ( array_key_exists( ( $i - $first_day_of_week + 1 ), $dates_available ) ) ? "onclick=show_quick_widget_events(this,'" . $dates_available[ ( $i - $first_day_of_week + 1 ) ] . "')" : '';
					if ( ( $i - $first_day_of_week + 1 ) === (int) $today && $month === date( 'n' ) && $year === date( 'Y' ) ) { //phpcs:ignore
						$cal .= '<td class="calToday"' . $onclick . '>' . ( $i - $first_day_of_week + 1 ) . '</td>';
					} else {
						$cal .= '<td' . $style . ' ' . $onclick . '>' . ( $i - $first_day_of_week + 1 ) . '</td>';
					}
				}
				if ( ( $display_days - 1 ) === $i && 0 < $days_end_of_month ) {
					for ( $j = 0; $j < $days_end_of_month; $j++ ) {
						$cal .= '<td class="tdCalSpacer">' . ( 1 + $j ) . '</td>';
					}
				}
				if ( 0 === ( $i % 7 ) && ( $display_days - 1 ) !== $i ) {
					$cal .= '</tr><tr>';
				}
			}
			$cal .= '</tr></table>';
			return $cal;
		}
		/**
		 * Returns the calendar body via ajax so users can scroll through months
		 *
		 * @param integer $adv is the direction of the month change (forward or back).
		 * @param string  $current_date is the current date.
		 * @param array   $cal_settings is the optional calendar settings.
		 *
		 * @see quick_view_calendar();
		 */
		public function quick_view_calendar_ajax( $adv, $current_date, $cal_settings = array() ) {
			$qv_calendar = 'false';

			try {
				$timezone_obj = new \DateTimeZone( $this->timezone );
			} catch ( Exception $e ) {
				$timezone_obj = new \DateTimeZone( get_option( 'gmt_offset' ) );
			}

			$date = new \DateTime( $current_date, $timezone_obj );
			if ( 1 === (int) $adv || -1 === (int) $adv ) {
				$current_month = $date->format( 'n' );
				$current_year  = $date->format( 'Y' );
				$new_month     = $current_month + ( $adv );
				$new_year      = $current_year;
				if ( 13 === $new_month ) {
						$new_month = 1;
						$new_year++;
				} elseif ( 0 === $new_month ) {
						$new_month = 12;
						$new_year--;
				}
				$new_timestamp = mktime( 0, 0, 0, $new_month, 1, $new_year );
				$date->setTimestamp( $new_timestamp );
				$cal_title   = date( 'F Y', $new_timestamp ); //phpcs:ignore
				$qv_calendar = $this->quick_view_calendar( $new_month, $new_year, $cal_settings ) . '~' . date( 'Y-m-d', $new_timestamp ) . '~' . $cal_title; //phpcs:ignore
			}
			return $qv_calendar;
		}
		/**
		 * This method displays upcoming events in a list widget within full Calendar page.
		 * Similar to listWidget but shows all details rather than linking to fullCalendar
		 * Also accessed by ajax request when calendars are selected or deselected from the list
		 *
		 * @param integer     $limit is the max number of events to retrieve.
		 * @param false|array $get is the ajax request data if this is an ajax call.
		 *
		 * @see retrieve_all_events()
		 * @see get_calendar_details()
		 */
		public function fullcalendar_upcoming_events( $limit, $get = false ) {
			$output = '';

			$o        = get_option( 'kcal-settings' );
			$ics_page = ( isset( $o['icsFeed_page'] ) && ! empty( $o['icsFeed_page'] ) ) ? $o['icsFeed_page'] : '';

			try {
				$timezone_obj = new \DateTimeZone( $this->timezone );
			} catch ( Exception $e ) {
				$timezone_obj = new \DateTimeZone( get_option( 'gmt_offset' ) );
			}

			$date       = new \DateTime( '', $timezone_obj );
			$start_date = $date->format( 'Y-m' ) . '-01';
			$end_date   = ( $date->format( 'n' ) + 1 > 12 ) ? ( $date->format( 'Y' ) + 1 ) . '-' . $this->format_month( ( $date->format( 'n' ) + 1 ) - 12 ) . '-01' : $date->format( 'Y' ) . '-' . $this->format_month( $date->format( 'n' ) + 1 ) . '-01';
			if ( isset( $get['timestamp'] ) && preg_match( '/^[0-9]{8,}$/', trim( $get['timestamp'] ), $match ) ) {
				$date->setTimestamp( $get['timestamp'] );
				if ( isset( $get['cmmd'] ) && preg_match( '/^(prev|next)$/', trim( $get['cmmd'] ), $matches ) ) {
					if ( 'prev' === trim( $get['cmmd'] ) ) {
						$end_date   = $date->format( 'Y-m' ) . -'01';
						$month      = $this->format_month( $date->format( 'n' ) - 1 );
						$start_date = ( 0 === ( $date->format( 'n' ) - 1 ) ) ? ( $date->format( 'Y' ) - 1 ) . '-12-01' : $date->format( 'Y' ) . "-$month-01";
					} elseif ( 'next' === trim( $get['cmmd'] ) ) {
						$st_month   = $this->format_month( $date->format( 'n' ) + 1 );
						$end_month  = $this->format_month( $date->format( 'n' ) + 2 );
						$start_date = ( 13 === ( $date->format( 'n' ) + 1 ) ) ? ( $date->format( 'Y' ) + 1 ) . '-01-01' : $date->format( 'Y' ) . "-$st_month-01";
						$end_date   = ( 13 === ( $date->format( 'n' ) + 1 ) ) ? ( $date->format( 'Y' ) + 1 ) . '-02-01' : $date->format( 'Y' ) . "-$end_month-01";
					}
				} else {
					$st_month   = $this->format_month( ( $date->format( 'n' ) + 1 ) - 12 );
					$end_month  = $this->format_month( $date->format( 'n' ) + 1 );
					$start_date = $date->format( 'Y-m' ) . '-01';
					$end_date   = ( 12 < ( $date->format( 'n' ) + 1 ) ) ? ( $date->format( 'Y' ) + 1 ) . "-$st_month-01" : $date->format( 'Y' ) . "-$end_month-01";
				}
			}

			$date->setTimestamp( $start_date );
			$start_timestamp = $date->getTimestamp();
			$date->setTimestamp( $end_date );
			$end_timestamp = $date->getTimestamp();
			$res           = $this->retrieve_all_events( $start_timestamp, $end_timestamp );
			$calendars     = $this->get_calendar_details();

			$j = 0;
			if ( false !== $res && ! empty( $calendars ) ) {
				$cal_list = ( ! is_array( $get['view'] ) ) ? array_keys( $calendars ) : $get['view'];

				$output = '<ul>';
				foreach ( $res as $row ) {
					$date->setTimestamp( $row->eventStartDate ); //phpcs:ignore

					try {
						$timezone_obj = new \DateTimeZone( $this->timezone );
					} catch ( Exception $e ) {
						$timezone_obj = new \DateTimeZone( get_option( 'gmt_offset' ) );
					}

					$date_e = new \DateTime( '', $timezone_obj );
					$date_e->setTimestamp( $row->eventEndDate ); //phpcs:ignore
					$start_date = $date->format( 'Y-m-d' );
					$end_date   = $date_e->format( 'Y-m-d' );
					if ( isset( $calendars[ substr( trim( $row->calendarID ), 0, 2 ) ] ) && in_array( trim( $row->calendarID ), $cal_list ) ) { //phpcs:ignore
						if ( $j < $limit ) {
							$event_date = ( $start_date === $end_date ) ? $date->format( 'D M j, Y' ) : $date->format( 'D M j, Y' ) . ' - ' . $date_e->format( 'D M j, Y' );
							$link       = get_permalink( $row->ID );
							if ( isset( $row->metaID ) ) { //phpcs:ignore
								$link .= '?r=' . $row->metaID; //phpcs:ignore
							}

							$output .= '<li>';
							$output .= '<h3>';
							$output .= '<a href="' . esc_url( $link ) . '" style=\"font-weight:bold; color:' . $calendars[ trim( $row->calendarID ) ]['colour'] . '">' . esc_attr( $row->post_title ) . '</a>'; //phpcs:ignore
							if ( ! empty( $ics_page ) ) {
								$output .= '&nbsp;<a href="' . esc_url( $ics_page ) . '?event=' . (int) $row->itemID . '"><span class="k-icon-calendar" title="' . esc_attr__( 'Add to Calendar', 'kcal' ) . '"></span></a>'; //phpcs:ignore
							}
							$output .= '</h3>';
							$output .= '<span class="kc-event-date">' . $event_date . ' &#8226; ' . $date->format( 'g:i a' ) . '-' . $date_e->format( 'g:i a' ) . '</span><br />';
							$output .= '</li>';
							$j++;
						}
					}
				}
				$output .= '</ul>';
			}
			if ( 0 === $j ) {
				$output .= '<h3>' . esc_attr__( 'No Events', 'kcal' ) . '</h3>';
			}
			$output .= '<p id="plistTimeStamp" style="display:none">' . $start_timestamp . '</p>';

			echo wp_kses( $output, 'post' );
		}
	}
}
