<?php
/**
 * KCal controller.
 *
 * @package kcal.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'CalendarController' ) ) {
	/**
	 * The class which all ajax requests go through.
	 */
	class CalendarController {
		/**
		 * Admin functions to add/edit/delete calendars
		 * Users have to be authenticated
		 * Ajax route
		 *
		 * @param array $post is the ajax request data.
		 * @return void
		 */
		public function admin_calendars_action( $post ) {
			global $blog_id;
			$response = 'false';
			if ( is_admin() ) {

				$action = $post['input'];
				unset( $post['input'] );
				unset( $post['action'] );
				unset( $post['request'] );
				unset( $post['nonce'] );
				unset( $post['p'] );
				unset( $post['attr'] );

				$cal = new AdminCalendar();
				switch ( $action ) {
					case 'u':
						$response = $cal->update_calendar( $post );
						break;
					case 'd':
						$response = $cal->disable_calendar( $post );
						break;
					default:
						$response = $cal->add_new_calendar( $post, $blog_id );
						break;
				}
			}
			header( 'Content-type: text/html' );
			echo $response; //phpcs:ignore
			die();
		}
		/**
		 * Admin functions to add/edit/delete events
		 * Users have to be authenticated
		 * Ajax route
		 *
		 * @param array $post is the ajax request data.
		 *
		 * @return void
		 */
		public function admin_events_action( $post ) {

			$response = 'false';

			// if is logged in and has permission to edit.
			if ( is_admin() ) {

				$action = $post['input'];
				unset( $post['input'] );
				unset( $post['action'] );
				unset( $post['request'] );
				unset( $post['nonce'] );
				unset( $post['p'] );
				unset( $post['attr'] );

				$cal = new AdminCalendar();
				switch ( $action ) {
					case 'r':
						$response = $cal->update_recurring_events( $post );
						break;
					case 'u':
						$response = $cal->drag_drop_event( $post );
						break;
					case 'd':
						$response = $cal->delete_events_main( $post );
						break;
					default:
						$response = $cal->add_new_event( $post );
						break;
				}
			}
			header( 'Content-type: text/html' );
			echo $response; //phpcs:ignore
			die();
		}
		/**
		 * Admin function to get a list of calendars to display in the left hand column
		 * Users have to be authenticated
		 * Ajax route
		 *
		 * @see AdminCalendar::display_calendar_list_admin()
		 * @return void
		 */
		public function admin_list_calendars_action() {
			// If is logged in and has permission to edit.
			$response = 'false';
			if ( is_admin() ) {
				$cal      = new AdminCalendar();
				$response = $cal->display_calendar_list_admin();
			}
			header( 'Content-type: text/html' );
			echo $response; //phpcs:ignore
			die();
		}
		/**
		 * Admin function to get a list of style options for the admin calendar view
		 * Used on update/add
		 * Ajax route
		 *
		 * @see AdminCalendar::build_calendar_css()
		 * @return string
		 */
		public function admin_style_calendars_action() {
			header( 'Content-type: text/css' );
			$ca = new AdminCalendar();
			return $ca->build_calendar_css();
			die(); //phpcs:ignore
		}

		/**
		 * Autocomplete for event tags
		 *
		 * @see CalendarTags::get_search_tags_list()
		 *
		 * @return void
		 */
		public function admin_get_term_tags() {
			$the_term = ( isset( $_GET['term'] ) ) ? $_GET['term'] : ''; //phpcs:ignore
			$response = '';
			if ( ! empty( $the_term ) ) {
				$ca       = new CalendarTags();
				$response = $ca->get_search_tags_list( $the_term );
			}
			header( 'Content-type: text/html' );
			echo $response; //phpcs:ignore
			die();
		}

		/**
		 * Method to retrieve the list of site calendars
		 * Ajax route
		 *
		 * @see Calendar::get_calendars_ajax()
		 * @return void
		 */
		public function get_calendars_ajax_controller() {
			$response = 'false';

			$get = $_GET; //phpcs:ignore
			if ( isset( $get['calendar'] ) && 's' === trim( $get['calendar'] ) ) {
				$c        = new Calendar();
				$response = $c->get_calendars_ajax();
			}
			header( 'Content-type: text/json' );
			echo $response; //phpcs:ignore
			die();
		}
		/**
		 * Calendar method to retrieve the list of events for a specific site calendar
		 * Ajax route
		 *
		 * @see CalendarWidgets::get_calendar_events_ajax()
		 * @return void
		 */
		public function get_calendars_events_ajax_controller() {
			$response = 'false';

			$get = $_GET; //phpcs:ignore
			if ( isset( $get['calendar'] ) && preg_match( '/^[0-9]{1,6}$/', $get['calendar'], $matches ) ) {
				$cw       = new CalendarWidgets();
				$response = $cw->get_calendar_events_ajax( $get );
			}
			header( 'Content-type: text/json' );
			echo $response; //phpcs:ignore
			die();
		}
		/**
		 * General method to retrieve the events occurring on a specific date
		 * Ajax route
		 *
		 * @see CalendarWidgets::get_quick_view_events_ajax()
		 * @return void
		 */
		public function get_calendars_quick_view_events_controller() {
			$response = 'false';

			$w            = array_values( get_option( 'widget_kcal-mini-widget', array() ) );
			$cal_settings = ( isset( $w[0]['quickView_calendars'] ) ) ? $w[0]['quickView_calendars'] : array();

			$o = get_option( 'kcal_settings' );

			$cal_page   = ( isset( $o['fullcalendar_page'] ) && ! empty( $o['fullcalendar_page'] ) ) ? $o['fullcalendar_page'] : '';
			$event_page = ( isset( $o['eventDetails_page'] ) && ! empty( $o['eventDetails_page'] ) ) ? $o['eventDetails_page'] : '';

			$get = $_GET; //phpcs:ignore
			if ( isset( $get['qview'] ) && preg_match( '/^[0-9]{8,}$/', $get['qview'], $matches ) ) {
				$cw       = new CalendarWidgets();
				$response = $cw->get_quick_view_events_ajax( trim( $get['qview'] ), $cal_settings, $cal_page, $event_page );
			}
			header( 'Content-type: text/html' );
			echo $response; //phpcs:ignore
			die();
		}
		/**
		 * General method to retrieve the HTML representation of a calendar when cycling through months
		 * Ajax route
		 *
		 * @see CalendarWidgets::quick_view_calendar_ajax()
		 * @return void
		 */
		public function get_calendars_quick_view_calendar_controller() {

			$response = 'false';

			$w            = array_values( get_option( 'widget_kcal-mini-widget', array() ) );
			$cal_settings = ( isset( $w[0]['quickView_calendars'] ) ) ? $w[0]['quickView_calendars'] : array();
			$get          = $_GET; //phpcs:ignore

			if ( isset( $get['qvAdv'] ) && preg_match( '/(-)?[1]{1}/', trim( $get['qvAdv'] ), $matches ) && isset( $get['qvStamp'] ) && preg_match( '/^[0-9]{4}(\-\d{2}){2}$/', trim( $get['qvStamp'] ), $match ) ) {
				$cw       = new CalendarWidgets();
				$response = $cw->quick_view_calendar_ajax( trim( $get['qvAdv'] ), trim( $get['qvStamp'] ), $cal_settings );
			}
			header( 'Content-type: text/html' );
			echo $response; //phpcs:ignore
			die();
		}

		/**
		 * General method to retrieve the upcoming events in HTML for the full calendar list view when cycling through dates
		 * Ajax route
		 *
		 * @return void
		 */
		public function get_calendars_fullcalendar_controller() {
			$response = 'false';
			$get      = $_GET; //phpcs:ignore
			if ( isset( $get['qview'] ) && 'list' === trim( $get['qview'] ) && isset( $get['view'] ) && is_array( $get['view'] ) ) {
				unset( $get['qview'] );
				$cw       = new CalendarWidgets();
				$response = $cw->fullcalendar_upcoming_events( 10, $get );
			}
			echo $response; //phpcs:ignore
			die();
		}

		/**
		 * Build a a calendar RSS file, by calendar ID passed via ajax or page query string
		 *
		 * @return void
		 */
		public function build_calendar_rss_controller() {

			$get = $_GET; //phpcs:ignore

			$rss = '';
			$cw  = new CalendarWidgets();
			if ( isset( $get['calendar'] ) ) {
				$rss = $cw->build_rss( (int) $get['calendar'], 30 );
			} else {
				$rss = $cw->build_rss( false, 100 );
			}
			header( 'Content-Type: application/rss+xml; charset=UTF-8' );
			echo $rss; //phpcs:ignore
		}

		/**
		 * Function call to force download the ICS/add to calendar file
		 *
		 * @see CalendarWidgets::output_ics()
		 * @see CalendarWidgets::get_calendar_details()
		 * @return void
		 */
		public function add_to_calendar() {

			$get    = $_GET; //phpcs:ignore
			$output = '';
			// Translators: %s is the site URL.
			$file_name = sprintf( __( '%s Events', 'kcal' ), site_url() );

			if ( isset( $get['calID'] ) || isset( $get['eID'] ) ) {

				$cw              = new CalendarWidgets();
				$get['calendar'] = $get['calID'];
				if ( isset( $get['eID'] ) ) {
					$get['event'] = $get['eID'];
					unset( $get['eID'] );
				}
				unset( $get['calID'] );
				$output = $cw->output_ics( $get );
				if ( isset( $get['calendar'] ) && isset( $get['start'] ) ) {

					$calendars = $cw->get_calendar_details();
					if ( isset( $calendars[ $get['calendar'] ] ) ) {
						$file_name = preg_replace( '/[^A-Za-z0-9]/', '', $calendars[ $get['calendar'] ]['name'] ) . 'Events';

					}
				} elseif ( isset( $get['event'] ) && isset( $get['calendar'] ) ) {
					$event_id    = explode( '-', $get['event'] );
					$event_title = preg_replace( '/[^A-Za-z0-9]/', '', get_the_title( $event_id[0] ) );
					if ( ! empty( $event_title ) ) {
						$file_name = str_replace( ' ', '', $event_title );
					}
				}
			}
			header( 'Content-Type: text/Calendar' );
			header( "Content-Disposition: inline; file_name=$file_name.ics" );
			echo $output; //phpcs:ignore;
			die();
		}

		/**
		 * Create CSS output for the calendars in the page header.
		 *
		 * @return void.
		 */
		public function build_calendars_css_controller() {
			$c = new Calendar();
			header( 'Content-Type: text/css' );
			echo $c->build_calendar_css(); //phpcs:ignore
			die();
		}
	}
}
