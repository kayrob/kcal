<?php
/**
 * Kcal List View Widget
 *
 * @package kcal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'KCalListView' ) ) {
	/**
	 * Widget to display coming events in a list view.
	 */
	class KCalListView extends WP_Widget {

		/**
		 * Constructor: define the widget
		 */
		public function __construct() {
			$widget_ops  = array(
				'classname'   => 'kcal-event-list-widget',
				'description' => __( 'A widget that displays list of events by calendar', 'kcal' ),
			);
			$control_ops = array();
			parent::__construct( 'kcal-list-widget', __( 'K-Cal List View Widget', 'kcal' ), $widget_ops, $control_ops );
		}

		/**
		 * The main widget function
		 *
		 * @param array $args are the predefined args from widget registration.
		 * @param array $instance are the saved settings for the widget instance.
		 *
		 * @see CalendarWidgets::get_calendar_details()
		 *
		 * @return void
		 */
		public function widget( $args, $instance ) {
			extract( $args ); //phpcs:ignore

			$cw        = new CalendarWidgets();
			$calendars = $cw->get_calendar_details();
			$props[]   = ( isset( $instance['listView_calendars'] ) ) ? $instance['listView_calendars'] : 'auto';

			if ( 'auto' === $props[0] && is_singular( 'event' ) ) {

				$event_cals = wp_get_post_terms( get_the_ID(), 'calendar' );

				if ( isset( $event_cals[0]->term_id ) ) {
					foreach ( $event_cals as $index => $cal ) {
						$props[ $index ] = $cal->term_id;
					}
				}
			}

			$limit       = ( isset( $instance['listView_calendars'] ) ) ? (int) $instance['number_of_events'] : 3;
			$list_events = array();

			if ( ! empty( $props ) ) {
				foreach ( $props as $cal_id ) {

					$event_list = $cw->upcoming_events_widget( $limit, (int) $cal_id );
					if ( ! empty( $event_list ) ) {
						foreach ( $event_list as $event_data ) {
							$event_data['calendar']                = $calendars[ $cal_id ]['name'];
							$list_events[ $event_data['start'] ][] = $event_data;
						}
					}
				}
			}
			if ( ! empty( $list_events ) ) {
				ksort( $list_events );
			}
			echo wp_kses( $args['before_widget'], 'post' );
			?>
			<div class="events-list-wrapper">
			<?php echo wp_kses( $args['before_title'], 'post' ); ?>
			<?php ( isset( $instance['widget_title'] ) ) ? print esc_attr( $instance['widget_title'] ) : esc_attr_e( 'Upcoming Events', 'kcal' ); ?>
			<?php echo wp_kses( $args['after_title'] ); ?>
			<ol>
			<?php
			if ( ! empty( $list_events ) ) {
				$e           = 0;
				$option_tz   = new \DateTimeZone( get_option( 'gmt_offset' ) );
				$date        = new \DateTime( 'now', $option_tz );
				$today       = $date->format( 'Y-m-d ' );
				$date_format = get_option( 'date_format' );
				$time_format = get_option( 'time_format' );

				$events = array();

				foreach ( $list_events as $start_date => $items ) {
					foreach ( $items as $index => $nfo ) {

						if ( ! isset( $events[ $nfo['id'] ] ) ) {

							$events[ $nfo['id'] ] = $nfo['id'];
							$event_id             = explode( '-', $nfo['id'] );

							$event_tz = get_post_meta( $event_id[0], '_kcal_timezone', true );

							try {
								$timezone_obj = new \DateTimeZone( $event_tz );
							} catch ( \Exception $e ) {
								$timezone_obj = $option_tz;
							}
							$date = new \DateTime( 'now', $timezone_obj );
							$date->setTimestamp( $nfo['end'] );

							if ( $date->getTimestamp() > current_time( 'timestamp' ) ) { //phpcs:ignore
								$date->setTimestamp( $nfo['start'] );

								$event_day = $date->format( $date_format );

								$date_diff = ( strtotime( $date->format( 'Y-m-d' ) ) - current_time( 'timestamp' ) ) / ( 60 * 60 * 24 ); //phpcs:ignore
								if ( 0 === $date_diff ) {
									$event_day = __( 'Today', 'kcal' );
								} elseif ( 1 === $date_diff ) {
									$event_day = __( 'Tomorrow', 'kcal' );
								} elseif ( 7 < $date_diff ) {
									$event_day = $date->format( 'l' );
								}
								$event_url  = '';
								$event_link = get_permalink( $event_id[0] );
								if ( ! empty( $nfo['link'] ) ) {
									$event_url = $nfo['link'];
								} else {
									$event_url = $event_link;
								}
								if ( $event_link === $event_url && isset( $event_id[1] ) ) {
									$event_url .= '?r=' . $event_id[1];
								}
								$dxn     = strip_tags( $nfo['description'] ); //phpcs:ignore
								$dxn_end = ( 140 < strlen( $dxn ) ) ? 130 + strpos( substr( $dxn, 130 ), ' ' ) : 140;
								$dxn     = substr( $dxn, 0, $dxn_end );

								$time_start = $date->format( $time_format );
								$date->setTimestamp( $nfo['end'] );
								$time_end = $date->format( $time_format );
								?>
							<li class="kcal-feed-item<?php echo ( 0 === $e ) ? ' first' : ''; ?>">
								<h4><a href="<?php echo esc_url( $event_url ); ?>" class="event-main"><?php echo esc_attr( $nfo['title'] ); ?></a></h4>
								<p><?php echo esc_attr( $event_day . ' ' . $time_start . ' - ' . $time_end ); ?></p>
							</li>
								<?php
								$e++;
								if ( 3 === $e ) {
									break;
								}
							}
						}
					}
					if ( 3 === $e ) {
						break;
					}
				}
			} else {
				echo '<p>' . esc_attr__( 'No Upcoming Events', 'kcal' ) . '</p>';
			}
			if ( ! empty( $cal_link ) ) {
				?>
			<li class="calendar-links"><a href="<?php echo esc_url( $cal_link ); ?>" class="more-news-events"><?php esc_attr_e( 'more events', 'kcal' ); ?></a></li>
				<?php
			}
			?>
			</ol>
			</div>
			<?php
			echo wp_kses( $args['after_widget'], 'post' );

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

			$instance['widget_title']       = strip_tags( $new_instance['widget_title'] ); //phpcs:ignore
			$instance['listView_calendars'] = $new_instance['listView_calendars'];
			$instance['number_of_events']   = ( 0 < intval( $new_instance['number_of_events'], 10 ) ) ? intval( $new_instance['number_of_events'], 10 ) : 3;

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

			$defaults = array(
				'number_of_events'   => 3,
				'listView_calendars' => $cal_default,
				'widget_title'       => __( 'Events', 'kcal' ),
			);
			$instance = wp_parse_args( (array) $instance, $defaults );

			$cals_selected = $instance['listView_calendars'];
			?>
			<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'widget_title' ) ); ?>"><?php esc_attr_e( 'Title:', 'kcal' ); ?></label>
			<input type="text" id="<?php echo esc_attr( $this->get_field_id( 'widget_title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'widget_title' ) ); ?>" value="<?php echo esc_attr( $instance['widget_title'] ); ?>" style="width:100%;" />
			</p>
			<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'number_of_events' ) ); ?>"><?php esc_attr_e( 'Number of Events:', 'kcal' ); ?></label>
			<input min="1" type="number" id="<?php echo esc_attr( $this->get_field_id( 'number_of_events' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'number_of_events' ) ); ?>" value="<?php echo (int) $instance['number_of_events']; ?>" style="width:100%;" />
			</p>
			<p><?php esc_attr_e( 'Select Calendar:', 'kcal' ); ?></p>
			<p>
				<input class="checkbox" type="radio"<?php print( 'auto' === $cals_selected ? ' checked="checked"' : '' ); ?> id="<?php echo esc_attr( $this->get_field_name( 'listView_calendars' ) . '_default' ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'listView_calendars' ) ); ?>" value="auto" />
				<label for="<?php echo esc_attr( $this->get_field_name( 'listView_calendars' ) . '_default' ); ?>"><?php esc_attr_e( 'Auto (Single Events)', 'kcal' ); ?></label>
			</p>

			<?php
			if ( ! empty( $calendars ) ) {
				foreach ( $calendars as $cal_id => $nfo ) {
					$checked = ( $cals_selected == null || in_array( $cal_id, $cals_selected ) ) ? ' checked="checked"' : ''; //phpcs:ignore
					?>
				<p>
					<input class="checkbox" type="radio" <?php echo $checked; //phpcs:ignore ?> id="<?php echo esc_attr( $this->get_field_name( 'listView_calendars' ) . '_' . $cal_id ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'listView_calendars' ) ); ?>" value="<?php echo (int) $cal_id; ?>"/>
					<label for="<?php echo esc_attr( $this->get_field_name( 'listView_calendars' ) . '_' . $cal_id ); ?>"><?php echo esc_attr( $nfo['name'] ); ?></label>
				</p>
					<?php
				}
			} else {
				echo '<p>' . esc_attr__( 'No Calendars', 'kcal' ) . '</p>';
			}
		}
	}
}
