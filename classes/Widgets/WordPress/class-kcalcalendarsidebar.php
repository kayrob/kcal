<?php
/**
 * Widget: kCal Sidebar
 *
 * @package kcal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'KCalCalendarSidebar' ) ) {
	/**
	 * Links to other calendars in a sidebar
	 */
	class KCalCalendarSidebar extends WP_Widget {

		/**
		 * Constructor to setup widget
		 */
		public function __construct() {
			$widget_ops  = array(
				'classname'   => 'kcal-archive-widget kcal-calendars-list-view',
				'description' => __( 'A widget that displays links to other calendars in a sidebar', 'kCalCalendarSidebar' ),
			);
			$control_ops = array();
			parent::__construct( 'kcal-calendar-sidebar', __( 'K-Cal Sidebar List', 'kCalCalendarSidebar' ), $widget_ops, $control_ops );
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

			$the_term = false;

			$cat_id = get_query_var( 'calendar' );
			if ( false !== $cat_id && ! empty( $cat_id ) ) {
				$the_term = get_term_by( 'slug', $cat_id, 'calendar' );
			}
			$allthe_terms = get_terms( 'calendar' );

			$nav_output = '';

			if ( ! empty( $allthe_terms ) && count( $allthe_terms ) > 1 ) {

				$this->scripts['kcal-widgets-js']  = true;
				$this->scripts['kcal-widgets-css'] = true;

				$title = ( isset( $instance['title'] ) ? $instance['title'] : __( 'Calendars', 'kcal' ) );

				$nav_output  = '<div class="list-calendars-wrapper">';
				$nav_output .= $args['before_title'];
				$nav_output .= $title;
				$nav_output .= $args['after_title'];
				$nav_output .= '<ul>';
				foreach ( $allthe_terms as $calthe_term ) {
					if ( is_single() || ! isset( $the_term->the_term_id ) || (int) $calthe_term->the_term_id !== (int) $the_term->the_term_id ) {
						$cat_link    = get_category_link( $calthe_term );
						$nav_output .= "<li><a href=\"{$cat_link}\"><span class=\"link-text\">" . $calthe_term->name . '</span><span class="kcal-chevron-right"></span></a>';
						$nav_output .= '</li>';
					}
				}
				$nav_output .= '</ul>';
				$nav_output .= '</div>';

				$nav_output = apply_filters( 'kcal_sidebar_html', $nav_output, 'calendarsidebar', $title );
			}
			if ( ! empty( $nav_output ) ) {
				echo wp_kses( $args['before_widget'], 'post' );
				echo wp_kses( $nav_output, 'post' );
				echo wp_kses( $args['after_widget'], 'post' );
			}
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

			$instance['title'] = strip_tags( $new_instance['title'] ); //phpcs:ignore
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

			$defaults = array( 'title' => 'Calendars' );
			$instance = wp_parse_args( (array) $instance, $defaults );

			?>
			<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'kcal' ); ?></label>
			<input type="text" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>" style="width:100%;" />
			</p>
			<?php
		}
	}
}
