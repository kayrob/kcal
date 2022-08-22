<?php
/**
 * KCal
 *
 * @package kcal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'KCal_Activate' ) ) {

	/**
	 * Main activation class for KCal
	 */
	class KCal_Activate {
		/**
		 * All the plugin loading/theming and setup
		 */
		public function __construct() {

			add_action( 'init', array( $this, 'kcal_init' ), 10, 0 );
			register_activation_hook( __FILE__, array( $this, 'on_activate' ) );
			register_deactivation_hook( __FILE__, array( $this, 'on_deactivate' ) );

			add_action( 'wp_enqueue_scripts', array( $this, 'kcal_front_scripts' ) );

			add_action( 'admin_menu', array( $this, 'kcal_add_calendar_menu' ) );

			add_action( 'widgets_init', array( $this, 'kcal_register_widgets' ) );
			add_action( 'pre_get_posts', array( $this, 'kcal_mainquery_filter_archive' ) );

		}

		/**
		 * Register taxonomies, custom post types and roles
		 */
		public function kcal_init() {
			// Register Taxonomy.
			$taxonomy_labels = array(
				'name'          => __( 'Calendars' ),
				'singular_name' => __( 'Calendar' ),
				'menu_name'     => __( 'Calendars' ),
				'edit_item'     => __( 'Edit Calendars' ),
				'view_item'     => __( 'View Calendars' ),
				'update_item'   => __( 'Update Calendars' ),
				'add_new_item'  => __( 'Add New Calendars' ),
				'new_item_name' => __( 'New Calendar' ),
				'parent_item'   => __( 'Parent Calendar' ),
				'search_items'  => __( 'Search Calendars' ),
			);
			$taxonomy        = array(
				'labels'        => $taxonomy_labels,
				'public'        => true,
				'show_ui'       => true,
				'show_tagcloud' => false,
				'hierarchical'  => true,
				'rewrite'       => array(
					'slug'       => 'calendar',
					'with_front' => false,
				),
				'capabilities'  => array(
					'manage_terms' => 'edit_events',
					'edit_terms'   => 'edit_events',
					'delete_terms' => 'delete_events',
					'assign_terms' => 'edit_events',
				),
				'show_in_rest'  => true,

			);
			register_taxonomy( 'calendar', 'event', $taxonomy );
			// Register Post Type.
			$labels = array(
				'name'               => __( 'Events' ),
				'singular_name'      => __( 'Event' ),
				'menu_name'          => __( 'Events' ),
				'name_admin_bar'     => __( 'Events' ),
				'add_new'            => __( 'Add New' ),
				'add_new_item'       => __( 'Add New Event' ),
				'edit_item'          => __( 'Edit Event' ),
				'new_item'           => __( 'New Event' ),
				'view_item'          => __( 'View Details' ),
				'search_items'       => __( 'Search Events' ),
				'not_found'          => __( 'No Events Found' ),
				'not_found_in_trash' => __( 'No events found in trash' ),

			);

			$supports = array( 'title', 'editor', 'thumbnail', 'excerpt' );

			$rewrite = array(
				'slug'       => 'events',
				'with_front' => false,
				'pages'      => true,
			);
			register_post_type(
				'event',
				array(
					'labels'            => $labels,
					'description'       => 'Calendar Manager',
					'public'            => true,
					'has_archive'       => true,
					'show_ui'           => true,
					'show_in_menu'      => true,
					'show_in_admin_bar' => true,
					'menu_position'     => 20,
					'menu_icon'         => 'dashicons-calendar',
					'capability_type'   => array( 'post', 'posts', 'event', 'events' ),
					'map_meta_cap'      => true,
					'hierarchical'      => false,
					'supports'          => $supports,
					'taxonomies'        => array( 'calendar' ),
					'rewrite'           => $rewrite,
					'show_admin_column' => true,
				)
			);

			$admin_role = get_role( 'administrator' );
			$admin_role->add_cap( 'edit_events' );
			$admin_role->add_cap( 'delete_events' );

			if ( is_active_widget( 'kCalQuickView' ) ) { // check if search widget is used.
				wp_enqueue_script( 'kcalendar-mini' );
				wp_enqueue_style( 'kcalendar-mini' );
			}

			$editor_role = get_role( 'editor' );
			$editor_role->add_cap( 'edit_events' );
			$editor_role->add_cap( 'delete_events' );

			$author_role = get_role( 'author' );
			$author_role->add_cap( 'edit_events' );
			$author_role->add_cap( 'delete_events' );

		}

		/**
		 * Flush re-write rules when plugin is activated.
		 *
		 * @access public
		 */
		public function on_activate() {
			flush_rewrite_rules();
		}
		/**
		 * Flush re-write rules when plugin is de-activated.
		 *
		 * @access public
		 */
		public function on_deactivate() {
			flush_rewrite_rules();
			$admin_role = get_role( 'administrator' );
			$admin_role->remove_cap( 'edit_events' );
			$admin_role->remove_cap( 'delete_events' );

			$editor_role = get_role( 'editor' );
			$editor_role->remove_cap( 'edit_events' );
			$editor_role->remove_cap( 'delete_events' );

			$author_role = get_role( 'author' );
			$author_role->remove_cap( 'edit_events' );
			$author_role->remove_cap( 'delete_events' );
		}

		/**
		 * Enqueue front end scripts/styles
		 */
		public function kcal_front_scripts() {

			wp_register_script( 'jquery-ui', KCAL_HOST_URL . 'js/jquery-ui/js/jquery-ui-1.12.1.min.js', array( 'jquery' ), '1.12.1', true );
			wp_register_script( 'fullCalendar', KCAL_HOST_URL . 'vendors/fullcalendar-5.6.0/lib/main.js', array(), '5.6.0', true );
			wp_register_script( 'kcalendar', KCAL_HOST_URL . 'js/calendar.js', array( 'jquery', 'jquery-ui', 'fullCalendar' ), '3.0', true );
			wp_register_style( 'calCSS', KCAL_HOST_URL . 'vendors/fullcalendar-5.6.0/lib/main.min.css', array(), '5.6.0' );
			wp_register_style( 'k-calCSS', KCAL_HOST_URL . 'dist/css/calendar.css', array(), '3.0' );
			wp_register_style( 'jquery-ui', KCAL_HOST_URL . 'js/jquery-ui/css/smoothness/jquery-ui-1.10.3.custom.min.css', array(), '1.10.3' );
			wp_register_style( 'eventsCSS', admin_url( 'admin-ajax.php' ) . '?action=eventListCSS', array(), '3.0' );
			wp_register_style( 'kcalSingular', KCAL_HOST_URL . 'dist/css/single-event.css', array(), '3.0' );
			wp_register_style( 'kcalArchive', KCAL_HOST_URL . 'dist/css/archive-calendar.css', array(), '3.0' );
			wp_register_style( 'kcalWidgets', KCAL_HOST_URL . 'dist/css/kcal-widgets.css', array(), '3.0' );

			wp_register_script( 'kcalMiniJS', KCAL_HOST_URL . 'js/mini-calendar-min.js', array( 'jquery' ), '3.0', true );
			wp_register_style( 'calMiniCSS', KCAL_HOST_URL . 'dist/css/calendar-mini.css', array(), '3.0' );

			if ( has_shortcode( get_the_content(), 'kcal' ) ) {

				wp_enqueue_script( 'kcalendar' );
				wp_enqueue_style( 'calCSS' );
				wp_enqueue_style( 'k-calCSS' );
				wp_enqueue_style( 'jquery-ui' );
				wp_localize_script( 'kcalendar', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
				wp_enqueue_style( 'eventsCSS' );
			}

			if ( is_active_widget( false, false, 'kcal-mini-widget' ) ) {
				wp_enqueue_script( 'kcalendar' );
				wp_enqueue_style( 'calMiniCSS' );
				wp_enqueue_script( 'kcalMiniJS' );
				wp_localize_script( 'kcalMiniJS', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
			}

			if ( is_active_widget( false, false, 'filter-events-date' ) || is_active_widget( false, false, 'kcal-calendar-sidebar' ) || is_active_widget( false, false, 'kcal-list-widget' ) ) {
				wp_enqueue_style( 'kcalWidgets' );
			}

		}

		/**
		 * Admin subpage to display the fullcalendar view
		 */
		public function kcal_display_admin_calendar() {
			include_once KCAL_HOST_DIR . '/views/Apps/index.php';
		}
		/**
		 * Admin subpage to allow events importing
		 */
		public function kcal_display_import_events() {
			$imported = array();
			if ( isset( $_POST['kcal_importRSS_url'] ) && filter_var( $_POST['kcal_importRSS_url'], FILTER_VALIDATE_URL ) ) { //phpcs:ignore
				$ca       = new AdminCalendar();
				$calendar = ( isset( $_POST['kcal_importRSS_calendar'] ) && 0 < (int) $_POST['kcal_importRSS_calendar'] ) ? (int) $_POST['kcal_importRSS_calendar'] : 0; //phpcs:ignore
				$timezone = ( isset( $_POST['kcal_import_events_timezone'] ) && ! empty( $_POST['kcal_import_events_timezone'] ) ) ? $_POST['kcal_import_events_timezone'] : get_option( 'gmt_offset' ); //phpcs:ignore
				$imported = $ca->import_parse_RSS( $_POST['kcal_importRSS_url'], $calendar, $timezone ); //phpcs:ignore
			}

			if ( isset( $_FILES['kcal_importICS_file'] ) ) {
				$ics = wp_handle_upload( $_FILES['kcal_importICS_file'], array( 'test_form' => false ) ); //phpcs:ignore
				if ( isset( $ics['file'] ) ) {
					$ca       = new AdminCalendar();
					$calendar = ( isset( $_POST['kcal_importICS_calendar'] ) && (int)$_POST['kcal_importICS_calendar'] > 0 ) ? (int) $_POST['kcal_importICS_calendar'] : 0; //phpcs:ignore
					$timezone = ( isset( $_POST['kcal_import_events_timezone'] ) && ! empty( $_POST['kcal_import_events_timezone'] ) ) ? $_POST['kcal_import_events_timezone'] : get_option( 'gmt_offset' ); //phpcs:ignore
					$imported = $ca->import_parse_ICS( $ics['file'], $calendar, $timezone );
				} else {
					$imported['error'][] = __( 'ICS file could not be uploaded.', 'kcal' );
				}
			}
			include_once KCAL_HOST_DIR . '/views/Apps/import.php';
		}

		/**
		 * Hook to add fullcalendar and import events sub pages
		 */
		public function kcal_add_calendar_menu() {
			add_submenu_page( 'edit.php?post_type=event', 'Manage Events', 'Calendar View', 'edit_events', 'edit.php?view=calendar', array( $this, 'kcal_display_admin_calendar' ) );
			add_submenu_page( 'edit.php?post_type=event', 'Import Events', 'Import Events', 'edit_events', 'edit.php?view=import', array( $this, 'kcal_display_import_events' ) );
		}

		/**
		 * Register standard WP Widgets
		 */
		public function kcal_register_widgets() {
			if ( class_exists( 'kCalListView' ) ) {
				register_widget( 'kCalListView' );
			}
			if ( class_exists( 'kCalQuickView' ) ) {
				register_widget( 'kCalQuickView' );
			}
			if ( class_exists( 'kCalfilterEventsDate' ) ) {
				register_widget( 'kCalfilterEventsDate' );
			}
			if ( class_exists( 'kCalCalendarSidebar' ) ) {
				register_widget( 'kCalCalendarSidebar' );
			}

			register_sidebar(
				array(
					'name'          => esc_html__( 'Events Archive Sidebar', 'kCal' ),
					'id'            => 'sidebar-kcal-events',
					'description'   => esc_html__( 'Appears on the calendar archive pages', 'kCal' ),
					'before_widget' => '<aside id="%1$s" class="widget %2$s">',
					'after_widget'  => '</aside>',
					'before_title'  => '<h3 class="widget-title"><span>',
					'after_title'   => '</span></h3>',
				)
			);
			register_sidebar(
				array(
					'name'          => esc_html__( 'Single Event Sidebar', 'kCal' ),
					'id'            => 'sidebar-kcal-single',
					'description'   => esc_html__( 'Appears on single event page', 'kCal' ),
					'before_widget' => '<aside id="%1$s" class="widget %2$s">',
					'after_widget'  => '</aside>',
					'before_title'  => '<h3 class="widget-title"><span>',
					'after_title'   => '</span></h3>',
				)
			);
		}
		/**
		 * Output dynamic css file for calendars.
		 *
		 * @see Calendar::build_calendar_css()
		 */
		public function build_calendars_css() {
			$c = new Calendar();
			header( 'Content-Type: text/css' );
			die( $c->build_calendar_css() ); //phpcs:ignore
		}

		/**
		 * Filter the archive page if a year/month filter is selected
		 * Get recurring events as part of the display
		 *
		 * @param object $query is the main wp query.
		 */
		public function kcal_mainquery_filter_archive( $query ) {
			if ( $query->is_main_query() && $query->is_archive() && isset( $query->query_vars['calendar'] ) ) {
				$months = array(
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
				$start = strtotime( date( 'Y' ) . '-' . date( 'n' ) . '-' . date( 'd' ) ); //phpcs:ignore
				$end   = strtotime( date( 'Y' ) . '-12-31' ); //phpcs:ignore
				if ( isset( $_GET['fy'] ) && preg_match( '/^(19|20)\d{2}$/', $_GET['fy'], $match_y ) && //phpcs:ignore
						isset( $_GET['fm'] ) && in_array( $_GET['fm'], $months)  ) { //phpcs:ignore

					$start = strtotime( $_GET['fm'] . ' 1, ' . wp_unslash( $_GET['fy'] ) ); //phpcs:ignore
					$end   = strtotime( $_GET['fm'] . ' ' . date( 't', $start ) . ', ' . $_GET['fy'] . '+7days' ); //phpcs:ignore
				}
				$meta_query = array(
					'relation' => 'AND',
					array(
						'key'     => '_kcal_eventStartDate',
						'value'   => $start,
						'compare' => '>=',
					),
					array(
						'key'     => '_kcal_eventEndDate',
						'value'   => $end,
						'compare' => '<=',
					),
				);
				$query->set( 'meta_query', $meta_query );
				$query->set( 'orderby', '_kcal_eventStartDate' );
				$query->set( 'order', 'asc' );
				add_filter( 'posts_request', array( $this, 'kcal_mainquery_filter_recur' ) );
			}
		}
		/**
		 * Modify the WHERE clause of the main query to include recurring events.
		 *
		 * @global object $wpdb is the database object.
		 * @param string $input is the default where clause.
		 * @return string
		 */
		public function kcal_mainquery_filter_recur( $input ) {
			preg_match( '/(WHERE\s1=1\s)([A-Za-z0-9\s\=\<\>\(\)\%\_\-\'\"\`\.]+)(GROUP)/i', $input, $matches );

			if ( isset( $matches[2] ) ) {
				global $wpdb;
				$relations = explode( 'AND', $matches[2] );
				if ( ! empty( $relations ) ) {
					$modified = $matches[2];
					unset( $relations[0] );
					unset( $relations[4] );
					unset( $relations[5] );
					$where    = array_values( $relations );
					$where[1] = str_replace( '_kcal_eventStartDate', '_kcal_recurrenceDate', $where[1] );
					preg_match( '/([0-9]+)/', $where[2], $start );
					if ( isset( $start[1] ) ) {
						$where[2] = 'SUBSTR(CAST(' . $wpdb->prefix . 'postmeta.meta_value AS CHAR), 8, ' . strlen( $start[1] ) . ') >= ' . $start[1] . '))';
					}
					$modified .= ' OR (' . implode( ' AND ', $where ) . ') ';
				}
				$input = str_replace( $matches[2], $modified, $input );
			}
			remove_filter( 'posts_request', array( $this, 'kcal_mainquery_filter_recur' ) );
			return $input;
		}
	}
	$kcal = new KCal_Activate();
}
