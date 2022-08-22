<?php
/**
 * KCal templates
 *
 * @package kcal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'KCalTemplates' ) ) {

	/**
	 * WordPress default template files for single and archive pages
	 */
	class KCalTemplates {
		/**
		 * All the plugin loading/theming and setup. Override in settings pages
		 */
		public function __construct() {

			add_action( 'init', array( $this, 'kcal_template_init' ), 10, 0 );

			// first check to see if this is turned on.
			$kcal_options = get_option( 'kcal_settings' );

			// first check to see if this is turned on.
			if ( ! isset( $kcal_options['kcal_single_template'] ) || 1 === (int) $kcal_options['kcal_single_template'] ) {
				add_filter( 'template_include', array( $this, 'kcal_events_templates' ) );
				add_action( 'wp_enqueue_scripts', array( $this, 'kcal_template_scripts' ) );
			} else {
				if ( ! isset( $kcal_options['kcal_archive_template'] ) || 1 === (int) $kcal_options['kcal_archive_template'] ) {
					add_filter( 'template_include', array( $this, 'kcal_events_templates' ) );
					add_action( 'wp_enqueue_scripts', array( $this, 'kcal_template_scripts' ) );
				}
			}

		}
		/**
		 * Enqueue template scripts and styles
		 *
		 * @return void
		 */
		public function kcal_template_scripts() {
			if ( is_singular( 'event' ) || has_shortcode( get_the_content(), 'kcalSingle' ) ) {
				wp_enqueue_style( 'kcalSingular' );
			}

			if ( is_archive( 'calendar' ) || has_shortcode( get_the_content(), 'kcalArchive' ) ) {
				wp_enqueue_style( 'kcalArchive' );
			}
		}

		/**
		 * Force download ICS or RSS via template files if the action is requested
		 */
		public function kcal_template_init() {
			if ( isset( $_GET['act'] ) && 'ics' === $_GET['act'] )  { //phpcs:ignore
				include KCAL_HOST_DIR . 'templates/events-ics.tpl.php';
				wp_die();
			}
			if ( isset( $_GET['act'] ) && 'rss' === $_GET['act'] ) { //phpcs:ignore
				include KCAL_HOST_DIR . 'templates/events-rss.tpl.php';
				wp_die();
			}
		}

		/**
		 * Apply custom single and archive templates
		 *
		 * @param string $template_name is the template file to get.
		 * @param string $template_path is the dir path to the template file. Optional.
		 * @param string $default_path is the default WP theme dir override. Optional.
		 *
		 * @return string $template is the template file path
		 */
		public function kcal_locate_template( $template_name, $template_path = '', $default_path = '' ) {

			// Set variable to search in woocommerce-plugin-templates folder of theme.
			if ( ! $template_path ) {
				$template_path = 'templates/';
			}

			// Set default plugin templates path.
			if ( ! $default_path ) {
				$default_path = KCAL_HOST_DIR . 'templates/'; // Path to the template folder.
			}

			// Search template file in theme folder.
			$template = locate_template(
				array(
					$template_path . $template_name,
					$template_name,
				)
			);

			// Get plugins template file.
			if ( empty( $template ) ) {
				$template = $default_path . $template_name;
			}
			return $template;

		}

		/**
		 * Filter to set the template from the plugin dir.
		 *
		 * @param string $template is the template file path from WP theme dir.
		 *
		 * @see self::kcal_locate_template
		 *
		 * @return string $template is the final templat file to use
		 */
		public function kcal_events_templates( $template ) {
			$find = array();
			$file = '';

			if ( is_singular( 'event' ) ) {
				$file = 'single-event.php';
			} elseif ( is_archive( 'calendar' ) ) {
				$file = 'taxonomy-calendar.php';
			}

			if ( ! empty( $file ) ) {
				if ( file_exists( $this->kcal_locate_template( $file ) ) ) {
					$template = $this->kcal_locate_template( $file );
				}
			}

			return $template;
		}
	}
	new KCalTemplates();
}
