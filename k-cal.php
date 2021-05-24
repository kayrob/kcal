<?php
/*
Plugin Name: K-Cal
Plugin URI: https://github.com/kayrob/kcal/archive/refs/heads/master.zip
Description: Full service calendar using fullCalendar as base
Version: 3.0
Author: Karen Laansoo
Author URI: https://karenlaansoo.me
*/


if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'kCalPlugin' ) ) {

	final class kCalPlugin {

		public static function init() {
			define( 'KCAL_HOST_VERSION', '3.0' );
			define( 'KCAL_HOST_FILE', __FILE__ );
			define( 'KCAL_HOST_DIR', plugin_dir_path( __FILE__ ) );
			define( 'KCAL_HOST_URL', plugins_url( '/', __FILE__ ) );

			/* Classes */
			require_once KCAL_HOST_DIR . 'classes/kcal_activate.php';
			require_once KCAL_HOST_DIR . 'classes/kcal_calendar.php';
			require_once KCAL_HOST_DIR . 'classes/Widgets/kcal_calendar_widgets.php';
			require_once KCAL_HOST_DIR . 'classes/kcal_controller.php';
			require_once KCAL_HOST_DIR . 'classes/kcal_routes.php';
			require_once KCAL_HOST_DIR . 'classes/Widgets/WordPress/kcal_list_widget.php';
			require_once KCAL_HOST_DIR . 'classes/Widgets/WordPress/kcal_mini_widget.php';
			require_once KCAL_HOST_DIR . 'classes/Widgets/WordPress/kcal_archive_widgets.php';
			require_once KCAL_HOST_DIR . 'classes/kcal_shortcodes.php';
			require_once KCAL_HOST_DIR . 'classes/kcal_templates.php';

			if (is_admin()){
				require_once KCAL_HOST_DIR . 'classes/Apps/kcal_admin_calendar.php';
				require_once KCAL_HOST_DIR . 'classes/Apps/kcal_settings.php';
			}

		}

	}
	kCalPlugin::init();
}
