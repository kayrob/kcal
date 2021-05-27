<?php
if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!class_exists("kCalTemplates")){

    class kCalTemplates {
		/**
		 * All the plugin loading/theming and setup
		 */
        public function __construct(){

            add_action( "init", array($this,"kCal_templates_init"), 10, 0);

			//first check to see if this is turned on
			$kcalOptions = get_option("kcal_settings");

			//first check to see if this is turned on
			if (!isset($kcalOptions['kcal_single_template']) || (int) $kcalOptions['kcal_single_template'] == 1) {
				add_filter("template_include", array($this, "kCal_events_templates" ) );
				add_action("wp_enqueue_scripts", array($this, "kCal_template_scripts"));
			} else {
				if (!isset($kcalOptions['kcal_archive_template']) || (int) $kcalOptions['kcal_archive_template'] == 1) {
					add_filter("template_include", array($this, "kCal_events_templates" ) );
					add_action("wp_enqueue_scripts", array($this, "kCal_template_scripts"));
				}
			}

		}

		public function kCal_template_scripts() {
			if (is_singular('event') || has_shortcode(get_the_content(), 'kcalSingle') ) {
				wp_enqueue_style("kcalSingular");
			}

			if (is_archive('calendar') || has_shortcode(get_the_content(), 'kcalArchive') ) {
				wp_enqueue_style("kcalArchive");
			}
		}

		public function kCal_templates_init() {
			if ( isset( $_GET['act'] ) && $_GET['act'] == 'ics')  {
				include KCAL_HOST_DIR . 'templates/events-ics.tpl.php';
				wp_die();
			}
			if ( isset( $_GET['act'] ) && $_GET['act'] == 'rss')  {
				include KCAL_HOST_DIR . 'templates/events-rss.tpl.php';
				wp_die();
			}
		}

		/**
		 * Apply custom single and archive templates
		 */
		public function kcal_locate_template( $template_name, $template_path = '', $default_path = '' ) {

			// Set variable to search in woocommerce-plugin-templates folder of theme.
			if ( ! $template_path ) :
				$template_path = 'templates/';
			endif;

			// Set default plugin templates path.
			if ( ! $default_path ) :
				$default_path = KCAL_HOST_DIR . 'templates/'; // Path to the template folder
			endif;

			// Search template file in theme folder.
			$template = locate_template( array(
				$template_path . $template_name,
				$template_name
			) );

			// Get plugins template file.
			if ( empty($template) ) :
				$template = $default_path . $template_name;
			endif;
			return $template;

		}

		/**
		 * filter to set the template from the plugin dir
		 */
		public function kCal_events_templates($template) {
			$find = array();
			$file = '';

			if ( is_singular( 'event' ) ) :
				$file = 'single-event.php';

			elseif ( is_archive( 'calendar' ) ) :
				$file = 'taxonomy-calendar.php';
			endif;

			if (!empty($file)) :
				if ( file_exists( $this->kcal_locate_template( $file ) ) ) :
					$template = $this->kcal_locate_template( $file );
				endif;
			endif;

			return $template;
		}
	}
	new kCalTemplates();
}