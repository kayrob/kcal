<?php
/**
 * Quickview Widget
 *
 * @package kcal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/*
 * Widget to display a mini calendar in a sidebar
 */
if ( ! class_exists( 'kCalQuickView' ) ) {
	/**
	 * Class: kCalQuickView shows a calendar widget in a sidebar with clickable dates to display events by list.
	 */
	class KCalQuickView extends WP_Widget {

		/**
		 * Scripts to enqueue.
		 *
		 * @var $scripts
		 * @access protected
		 */
		protected $scripts = array();

		/**
		 * Constructor: define the widget
		 */
		public function __construct() {
			$widget_ops  = array(
				'classname'   => 'cal-mini-widget',
				'description' => __( 'A widget that displays a mini calendar in a sidebar', 'kcal' ),
			);
			$control_ops = array();
			parent::__construct( 'kcal-mini-widget', __( 'K-Cal Mini View Widget', 'kcal' ), $widget_ops, $control_ops );

		}

		/**
		 * The main widget function
		 *
		 * @param array $args are the predefined args from widget registration.
		 * @param array $instance are the saved settings for the widget instance.
		 *
		 * @return void
		 */
		public function widget( $args, $instance ) {
			extract( $args ); //phpcs:ignore

			$cals_selected = ( isset( $instance['quickView_calendars'] ) ) ? $instance['quickView_calendars'] : array();

			$date = new \DateTime( 'now', new \DateTimeZone( get_option( 'gmt_offset' ) ) );

			$cal = new CalendarWidgets();

			$output  = '<div class="kcal-mini-widget widget" id="kcal-mini-widget">';
			$output .= '<div id="dlgQuickView"></div>';
			$output .= '<h4 id="h4QVHeader" class="kcal-mini-heade\"><button aria-label="' . __( 'View previous month', 'kcal' ) . '" class="no-style"><span class="kcal-chevron-left"></span></button><span class="header-text">' . $date->format( 'F Y' ) . '</span><button aria-label="' . __( 'View next month', 'kcal' ) . '" class="no-style"><span class="kcal-chevron-right"></span></button></h4>';
			$output .= $cal->quick_view_calendar( false, false, $cals_selected );
			$output .= '<p id="pQVdateTime">' . $date->format( 'Y-m-d' ) . '</p>';
			$output .= '</div>';
			echo $output; //phpcs:ignore
		}

		/**
		 * Update the widget instance
		 *
		 * @param array $new_instance are the new settings to save.
		 * @param array $old_instance is the previously saved settings.
		 *
		 * @return array
		 */
		public function update( $new_instance, $old_instance ) {
			$instance = $old_instance;

			$instance['quickView_calendars'] = $new_instance['quickView_calendars'];

			return $instance;
		}
		/**
		 * Admin widget settings form
		 *
		 * @param array $instance is the saved settings.
		 *
		 * @return void
		 */
		public function form( $instance ) {
			$cal       = new Calendar();
			$calendars = $cal->get_calendar_details();

			$cal_default = array_keys( $calendars );

			$defaults = array( 'quickView_calendars' => $cal_default );
			$instance = wp_parse_args( (array) $instance, $defaults );

			$cals_selected = $instance['quickView_calendars'];
			?>
			<p>Select Calendars:</p>
			<?php
			if ( ! empty( $calendars ) ) {
				foreach ( $calendars as $cal_id => $nfo ) {
					$checked = ( $cals_selected == null || in_array( $cal_id, $cals_selected ) ) ? ' checked="checked"' : ''; //phpcs:ignore
					?>
				<p>
				<input class="checkbox" type="checkbox" <?php echo $checked; //phpcs:ignore ?> id="<?php echo esc_attr( $this->get_field_name( 'quickView_calendars' ) ) . '_' . $cal_id; ?>" name="<?php echo esc_attr( $this->get_field_name( 'quickView_calendars' ) ); ?>[]" value="<?php echo (int) $cal_id; ?>" />
				<label for="<?php echo esc_attr( $this->get_field_name( 'quickView_calendars' ) ) . '_' . (int) $cal_id; ?>"><?php echo esc_attr( $nfo['name'] ); ?></label>
				</p>
					<?php
				}
			} else {
				echo '<p>' . esc_attr__( 'No Calendars', 'kcal' ) . '</p>';
			}
		}
	}
}
