<?php //phpcs:ignore
/**
 * Plugin Name: K-Cal
 * Plugin URI: https://github.com/kayrob/kcal/archive/refs/heads/master.zip
 * Description: Full service calendar using fullCalendar as base
 * Version: 3.0.5
 * Author: Karen Laansoo
 * Author URI: https://karenlaansoo.me
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'kCalPlugin' ) ) {

	/**
	 * Plugin init class
	 */
	final class kCalPlugin { //phpcs:ignore

		/**
		 * Class initialization
		 */
		public static function init() {
			define( 'KCAL_HOST_VERSION', '3.0.5' );
			define( 'KCAL_HOST_FILE', __FILE__ );
			define( 'KCAL_HOST_DIR', plugin_dir_path( __FILE__ ) );
			define( 'KCAL_HOST_URL', plugins_url( '/', __FILE__ ) );

			/* Classes */
			require_once KCAL_HOST_DIR . 'classes/class-kcal-activate.php';
			require_once KCAL_HOST_DIR . 'classes/class-calendar.php';
			require_once KCAL_HOST_DIR . 'classes/Widgets/class-calendarwidgets.php';
			require_once KCAL_HOST_DIR . 'classes/class-calendarcontroller.php';
			require_once KCAL_HOST_DIR . 'classes/class-kcalroutes.php';
			require_once KCAL_HOST_DIR . 'classes/Widgets/WordPress/class-kcallistview.php';
			require_once KCAL_HOST_DIR . 'classes/Widgets/WordPress/class-kcalquickview.php';
			require_once KCAL_HOST_DIR . 'classes/Widgets/WordPress/class-kcalfiltereventsdate.php';
			require_once KCAL_HOST_DIR . 'classes/Widgets/WordPress/class-kcalcalendarsidebar.php';
			require_once KCAL_HOST_DIR . 'classes/class-kcal-shortcodes.php';
			require_once KCAL_HOST_DIR . 'classes/class-kcaltemplates.php';

			if ( is_admin() ) {
				require_once KCAL_HOST_DIR . 'classes/Apps/class-admincalendar.php';
				require_once KCAL_HOST_DIR . 'classes/Apps/class-kcalsettings.php';
			}

		}

	}
	kCalPlugin::init();
}
