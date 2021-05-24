<?php

/*
 * This file displays the admin settings options page for kCal
 */

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!class_exists('kCalSettings')) {
	class kCalSettings
	{
		//holds values used in settings fields
		private $options;

		public function __construct()
		{
			add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
			add_action( 'admin_init', array( $this, 'page_init' ) );
		}

		public function add_settings_page()
		{
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
		public function create_admin_page()
		{
			$this->options = get_option("kcal_settings");
	?>
			<div class="kcal-settings-wrap">
				<h1><?php _e('Events Manager Settings', 'kcal');?></h1>
				<form method="post" action="options.php">
	<?php

					// This prints out all hidden setting fields
					settings_fields( "kcal-settings-group" );
					do_settings_sections( "kcal_settings" );
					submit_button();
	?>
				</form>
			</div>
	<?php
		}
		/**
		 * Register and add settings
		 */
		public function page_init()
		{
			register_setting("kcal-settings-group", "kcal_settings", array($this, "sanitize"));
			add_settings_section("kcal-pages", "Events Manager Content", array($this, "print_section_info"), "kcal_settings");
			add_settings_section("kcal-rssEvents", "RSS Events Import", array($this, "rss_section_info"), "kcal_settings");

			add_settings_field(
				"fullcalendar_page",
				__("Full Calendar: Shortcode", 'kcal'),
				array($this, "fullcalendar_page_callback"),
				"kcal_settings",
				"kcal-pages"
			);
			add_settings_field(
				"singular_page",
				__("Single Event Content: Shortcode", 'kcal'),
				array($this, "singular_page_callback"),
				"kcal_settings",
				"kcal-pages"
			);
			add_settings_field(
				"archive_content",
				__("Calendar Archive Excerpt: Shortcode", 'kcal'),
				array($this, "archive_content_callback"),
				"kcal_settings",
				"kcal-pages"
			);
			add_settings_field(
				"kcal_load_template",
				__("Single Event: Full Template", 'kcal'),
				array($this, "load_single_callback"),
				"kcal_settings",
				"kcal-pages"
			);
			add_settings_field(
				"kcal_archive_template",
				__("Calendar Archive: Use Archive Template", 'kcal'),
				array($this, "load_archive_callback"),
				"kcal_settings",
				"kcal-pages"
			);
			add_settings_field(
				"import_rss",
				__("RSS Feed to import events", 'kcal'),
				array($this, "kcal_rss_events"),
				"kcal_settings",
				"kcal-rssEvents"
			);
		}

		/**
		* Sanitize each setting field as needed
		*
		* @param array $input Contains all settings fields as array keys
		*/
		public function sanitize($input)
		{
			foreach($input as $field => $value) {
				$input[$field] = sanitize_text_field($value);
			}

			if (!isset($input['kcal_single_template'])) {
				$input['kcal_single_template'] = 0;
			}
			if (!isset($input['kcal_archive_template'])) {
				$input['kcal_archive_template'] = 0;
			}

			return $input;
		}
		/**
		 * Print the Section text
		 */
		public function print_section_info()
		{
			$optionsURL = '<a href="' . admin_url('options-general.php') . '">general options page</a>';
			printf('<p>%s %s</p>', __("Use the following shortcode(s) to use default templates.<br />Set the date/time format using the ", 'kcal' ), $optionsURL );
		}
		public function rss_section_info(){
			printf('<p>%s</p>' , __("Enter the URL for an RSS you want to import events from.", 'kcal' ) );
		}
		/**
		 * Get the settings option array and print one of its values
		 */
		public function fullcalendar_page_callback()
		{
			printf( "[kcal]" );
		}
		public function singular_page_callback()
		{
			printf( "[kcalSingle header=\"yes|no\"]" );
		}
		public function archive_content_callback()
		{
			printf( "[kcalArchive]" );
		}
		public function load_single_callback(){
			$loadTemplate = (!isset($this->options["kcal_single_template"]) || (int)$this->options["kcal_single_template"] == 1) ? 'checked="checked"' : '';
			include_once(KCAL_HOST_DIR . '/views/settings/settings-single-template.php');

		}
		public function load_archive_callback(){
			$loadTemplate = (!isset($this->options["kcal_archive_template"]) || (int)$this->options["kcal_archive_template"] == 1) ? 'checked="checked"' : '';
			include_once(KCAL_HOST_DIR . '/views/settings/settings-archive-template.php');

		}
		public function kcal_rss_events(){
		$rssEvent = (isset($this->options["kcal_rss_events"])) ? $this->options["kcal_rss_events"] : "";
		printf("<input type=\"text\" id=\"kcal_rss_events\" name=\"kcal_settings[kcal_rss_events]\" value=\"%s\" size=\"50\"/>",
				esc_attr($rssEvent));

		}
	}
	new kCalSettings();
}