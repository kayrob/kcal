<?php
/**
 * Ajax Routes
 *
 * @package kcal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'kCalRoutes' ) ) {

	/**
	 * Create basic Ajax routes
	 */
	class KCalRoutes {
		/**
		 * Holds the calendar controller object
		 *
		 * @var $ca
		 * @access public
		 */
		public $ca;
		/**
		 * Constructor to setup the ajax routes
		 *
		 * @see CalendarController::__construct()
		 */
		public function __construct() {
			if ( is_admin() ) {
				add_action( 'wp_ajax_adminEditCalendar', array( $this, 'admin_edit_calendar' ) );
				add_action( 'wp_ajax_adminEditEvents', array( $this, 'admin_edit_events' ) );
				add_action( 'wp_ajax_adminListCalendars', array( $this, 'admin_list_calendars' ) );
				add_action( 'wp_ajax_adminCalendarStyle', array( $this, 'admin_calendar_style' ) );
				add_action( 'wp_ajax_adminAutoTags', array( $this, 'admin_auto_tags' ) );
				add_action( 'wp_ajax_getCalendarsAjax', array( $this, 'getCalendarsAjax' ) );
				add_action( 'wp_ajax_getCalendarsEventsAjax', array( $this, 'get_calendars_events_ajax_route' ) );
				add_action( 'wp_ajax_get_calendars_quick_view_events', array( $this, 'get_calendars_quick_view_events' ) );
				add_action( 'wp_ajax_get_calendars_quick_view_calendar', array( $this, 'get_calendars_quick_view_calendar' ) );
				add_action( 'wp_ajax_get_calendars_fullcalendar', array( $this, 'get_calendars_fullcalendar' ) );
				add_action( 'wp_ajax_calendarRSS', array( $this, 'calendar_rss' ) );
				add_action( 'wp_ajax_calendarICS', array( $this, 'calendar_ics' ) );
				add_action( 'wp_ajax_eventListCSS', array( $this, 'event_list_css' ) );
			}
			add_action( 'wp_ajax_nopriv_getCalendarsAjax', array( $this, 'get_calendars_ajax_route' ) );
			add_action( 'wp_ajax_nopriv_getCalendarsEventsAjax', array( $this, 'get_calendars_events_ajax_route' ) );
			add_action( 'wp_ajax_nopriv_get_calendars_quick_view_events', array( $this, 'get_calendars_quick_view_events' ) );
			add_action( 'wp_ajax_nopriv_get_calendars_quick_view_calendar', array( $this, 'get_calendars_quick_view_calendar' ) );
			add_action( 'wp_ajax_nopriv_get_calendars_fullcalendar', array( $this, 'get_calendars_fullcalendar' ) );
			add_action( 'wp_ajax_nopriv_calendarRSS', array( $this, 'calendar_rss' ) );
			add_action( 'wp_ajax_nopriv_calendarICS', array( $this, 'calendar_ics' ) );
			add_action( 'wp_ajax_nopriv_eventListCSS', array( $this, 'event_list_css' ) );

			$this->ca = new CalendarController();
		}
		/**
		 * Route to edit the calendar via WP Admin
		 *
		 * @see CalendarController::admin_calendars_action()
		 */
		public function admin_edit_calendar() {
			$this->ca->admin_calendars_action( $_GET ); //phpcs:ignore
			die();
		}
		/**
		 * Route to edit an event via WP Admin
		 *
		 * @see CalendarController::admin_events_action()
		 */
		public function admin_edit_events() {
			$this->ca->admin_events_action( $_GET ); //phpcs:ignore
			die();
		}
		/**
		 * Route display events via WP Admin
		 *
		 * @see CalendarController::admin_list_calendars_action()
		 */
		public function admin_list_calendars() {
			$this->ca->admin_list_calendars_action( $_GET ); //phpcs:ignore
			die();
		}
		/**
		 * Route display calendar styling via WP Admin
		 *
		 * @see CalendarController::admin_style_calendars_action()
		 */
		public function admin_calendar_style() {
			$this->ca->admin_style_calendars_action();
			die();
		}
		/**
		 * Route display calendar terms WP Admin
		 *
		 * @see CalendarController::admin_get_term_tags()
		 */
		public function admin_auto_tags() {
			$this->ca->admin_get_term_tags( $_GET ); //phpcs:ignore
			die();
		}

		/**
		 * Route display calendars general
		 *
		 * @see CalendarController::get_calendars_ajax_controller()
		 */
		public function get_calendars_ajax_route() {
			$this->ca->get_calendars_ajax_controller();
			die();
		}
		/**
		 * Route display events general
		 *
		 * @see CalendarController::get_calendars_events_ajax_controller()
		 */
		public function get_calendars_events_ajax_route() {
			$this->ca->get_calendars_events_ajax_controller();
			die();
		}
		/**
		 * Route display quick view events widget general
		 *
		 * @see CalendarController::get_calendars_quick_view_events_controller()
		 */
		public function get_calendars_quick_view_events() {
			$this->ca->get_calendars_quick_view_events_controller();
			die();
		}
		/**
		 * Route display quick view calendar widget general
		 *
		 * @see CalendarController::get_calendars_quick_view_calendar_controller()
		 */
		public function get_calendars_quick_view_calendar() {
			$this->ca->get_calendars_quick_view_calendar_controller();
			die();
		}
		/**
		 * Route display fullcalendar widget general
		 *
		 * @see CalendarController::get_calendars_fullcalendar_controller()
		 */
		public function get_calendars_fullcalendar() {
			$this->ca->get_calendars_fullcalendar_controller();
			die();
		}
		/**
		 * Route display event list view css style block general
		 *
		 * @see CalendarController::build_calendars_css_controller()
		 */
		public function event_list_css() {
			$this->ca->build_calendars_css_controller();
			die();
		}
		/**
		 * Route display rss feed general
		 *
		 * @see CalendarController::build_calendar_rss_controller()
		 */
		public function calendar_rss() {
			$this->ca->build_calendar_rss_controller();
			die();
		}
		/**
		 * Route download ICS file general
		 *
		 * @see CalendarController::add_to_calendar()
		 */
		public function calendar_ics() {
			$this->ca->add_to_calendar();
			die();
		}
	}
	new KCalRoutes();
}
