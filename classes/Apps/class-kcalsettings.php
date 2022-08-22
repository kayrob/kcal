<?php
/**
 * This file displays the admin settings options page for kCal
 *
 * @package kcal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'KCalSettings' ) ) {
	/**
	 * Admin/plugin settings KCalSettings
	 */
	class KCalSettings {
		/**
		 * Holds values used in settings fields
		 *
		 * @var $options
		 * @access private
		 */
		private $options;

		/**
		 * Setup WP actions
		 *
		 * @see self::add_settings_page()
		 * @see self::page_init()
		 */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
			add_action( 'admin_init', array( $this, 'page_init' ) );
		}
		/**
		 * Add an admin settings page
		 *
		 * @see self::create_admin_page()
		 */
		public function add_settings_page() {
			add_options_page(
				'Settings Admin',
				'Events Manager Settings',
				'manage_options',
				'manage-calendar-settings',
				array( $this, 'create_admin_page' )
			);
		}
		/**
		 * Admin Page Display
		 */
		public function create_admin_page() {
			$this->options = get_option( 'kcal_settings' );
			?>
			<div class="kcal-settings-wrap">
				<h1><?php esc_attr_e( 'Events Manager Settings', 'kcal' ); ?></h1>
				<form method="post" action="options.php">
			<?php

					// This prints out all hidden setting fields.
					settings_fields( 'kcal-settings-group' );
					do_settings_sections( 'kcal_settings' );
					submit_button();
			?>
				</form>
			</div>
			<?php
		}
		/**
		 * Register and add settings
		 *
		 * @see self::fullcalendar_page_callback()
		 * @see self::singular_page_callback()
		 * @see self::archive_content_callback()
		 * @see self::load_single_callback()
		 * @see self::load_archive_callback()
		 * @see self::kcal_rss_events()
		 */
		public function page_init() {
			register_setting( 'kcal-settings-group', 'kcal_settings', array( $this, 'sanitize' ) );
			add_settings_section( 'kcal-pages', 'Events Manager Content', array( $this, 'print_section_info' ), 'kcal_settings' );
			add_settings_section( 'kcal-rssEvents', 'RSS Events Import', array( $this, 'rss_section_info' ), 'kcal_settings' );

			add_settings_field(
				'fullcalendar_page',
				__( 'Full Calendar: Shortcode', 'kcal' ),
				array( $this, 'fullcalendar_page_callback' ),
				'kcal_settings',
				'kcal-pages',
			);
			add_settings_field(
				'singular_page',
				__( 'Single Event Content: Shortcode', 'kcal' ),
				array( $this, 'singular_page_callback' ),
				'kcal_settings',
				'kcal-pages',
			);
			add_settings_field(
				'archive_content',
				__( 'Calendar Archive Excerpt: Shortcode', 'kcal' ),
				array( $this, 'archive_content_callback' ),
				'kcal_settings',
				'kcal-pages',
			);
			add_settings_field(
				'kcal_load_template',
				__( 'Single Event: Full Template', 'kcal' ),
				array( $this, 'load_single_callback' ),
				'kcal_settings',
				'kcal-pages',
			);
			add_settings_field(
				'kcal_archive_template',
				__( 'Calendar Archive: Use Archive Template', 'kcal' ),
				array( $this, 'load_archive_callback' ),
				'kcal_settings',
				'kcal-pages',
			);
			add_settings_field(
				'import_rss',
				__( 'RSS Feed to import events', 'kcal' ),
				array( $this, 'kcal_rss_events' ),
				'kcal_settings',
				'kcal-rssEvents',
			);
		}

		/**
		 * Sanitize each setting field as needed
		 *
		 * @param array $input Contains all settings fields as array keys.
		 */
		public function sanitize( $input ) {
			foreach ( $input as $field => $value ) {
				$input[ $field ] = sanitize_text_field( $value );
			}

			if ( ! isset( $input['kcal_single_template'] ) ) {
				$input['kcal_single_template'] = 0;
			}
			if ( ! isset( $input['kcal_archive_template'] ) ) {
				$input['kcal_archive_template'] = 0;
			}

			return $input;
		}
		/**
		 * Print the Section text
		 */
		public function print_section_info() {
			$options_url = '<a href="' . esc_attr( admin_url( 'options-general.php' ) ) . '">general options page</a>';
			printf( '<p>%s %s</p>', __( 'Use the following shortcode(s) to use default templates.<br />Set the date/time format using the ', 'kcal' ), $options_url ); //phpcs:ignore
		}
		/**
		 * RSS Settings
		 */
		public function rss_section_info() {
			printf( '<p>%s</p>', esc_attr__( 'Enter the URL for an RSS you want to import events from.', 'kcal' ) );
		}
		/**
		 * Show the full calendar shortcode
		 */
		public function fullcalendar_page_callback() {
			printf( '[kcal]' );
		}
		/**
		 * Show the single calendar shortcode. Options is Yes or No for showing the header.
		 */
		public function singular_page_callback() {
			printf( '[kcalSingle header="yes|no"]' );
		}
		/**
		 * Show the calendar archive shortcode
		 */
		public function archive_content_callback() {
			printf( '[kcalArchive]' );
		}
		/**
		 * Option to use the default single template, or use a custom template
		 */
		public function load_single_callback() {
			$load_template = ( ! isset( $this->options['kcal_single_template'] ) || 1 === (int) $this->options['kcal_single_template'] ) ? 'checked="checked"' : '';
			include_once KCAL_HOST_DIR . '/views/settings/settings-single-template.php';

		}
		/**
		 * Option to use the default archive template, or use a custom template
		 */
		public function load_archive_callback() {
			$load_template = ( ! isset( $this->options['kcal_archive_template'] ) || 1 === (int) $this->options['kcal_archive_template'] ) ? 'checked="checked"' : '';
			include_once KCAL_HOST_DIR . '/views/settings/settings-archive-template.php';

		}
		/**
		 * Option to set a default RSS feed to import
		 */
		public function kcal_rss_events() {
			$rss_event = ( isset( $this->options['kcal_rss_events'] ) ) ? $this->options['kcal_rss_events'] : '';
			printf(
				'<input type="text" id="kcal_rss_events" name="kcal_settings[kcal_rss_events]" value="%s" size="50"/>',
				esc_attr( $rss_event )
			);
		}
	}
	new KCalSettings();
}
