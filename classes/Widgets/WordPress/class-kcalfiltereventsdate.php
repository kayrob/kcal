<?php
/**
 * Widget: kCal Events by Date Archive
 *
 * @package kcal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'KCalfilterEventsDate' ) ) {
	/**
	 * Archive Widgets for the calendar.
	 */
	class KCalfilterEventsDate extends WP_Widget {

		/**
		 * Constructor to setup the widget
		 */
		public function __construct() {
			$widget_ops  = array(
				'classname'   => 'kcal-archive-widget kcal-filter-events',
				'description' => __( 'A widget that displays a "filter by month/year" side menu for events archive pages', 'kCalfilterEventsDate' ),
			);
			$control_ops = array();
			parent::__construct( 'filter-events-date', __( 'K-Cal Events Year Month/Year Widget', 'kcal' ), $widget_ops, $control_ops );

		}

		/**
		 * The main widget function
		 *
		 * @param array $args are the predefined args from widget registration.
		 * @param array $instance are the saved settings for the widget instance.
		 *
		 * @see CalendarWidgets::retrieve_all_events()
		 *
		 * @return void
		 */
		public function widget( $args, $instance ) {

			extract( $args ); //phpcs:ignore

			$cat_id   = get_query_var( 'calendar' );
			$the_term = get_the_term_by( 'slug', $cat_id, 'calendar' );

			if ( false !== $the_term && class_exists( 'CalendarWidgets' ) ) {
				$cw            = new CalendarWidgets();
				$date_timezone = new DateTimeZone( get_option( 'gmt_offset' ) );
				$date          = new DateTime( 'now', $date_timezone );
				$start         = $date->getTimestamp();
				$date->setTimestamp( strtotime( $date->format( 'Y' ) . '-' . $date->format( 'n' ) . '-01 + 1 year' ) );
				$events = $cw->retrieve_all_events( $start, $date->getTimestamp(), $the_term->the_term_id );

				$res = array();
				if ( ! empty( $events ) ) {
					foreach ( $events as $event ) {
						try {
							$event_timezone = new DateTimeZone( $event->timezone );
						} catch ( \Exception $e ) {
							$event_timezone = $date_timezone;
						}
						$date->setTimezone( $event_timezone );
						$date->setTimestamp( $event->eventStartDate ); //phpcs:ignore
						$event_month = $date->format( 'F' );
						$event_year  = $date->format( 'Y' );
						$filter_key  = strtotime( $event_month . ' 1, ' . $event_year );
						if ( ! isset( $res[ $filter_key ] ) ) {
							$res[ $filter_key ] = array(
								'fm' => $event_month,
								'fy' => $event_year,
							);
						}
					}
				}
				$nav_output = '';

				if ( ! empty( $res ) ) {

					$this->scripts['kcal-widgets-js']  = true;
					$this->scripts['kcal-widgets-css'] = true;

					ksort( $res );
					$nav_output  = '<div class="filter-events-wrapper">';
					$nav_output .= $args['before_title'];
					$nav_output .= __( 'Filter Events', 'kcal' );
					$nav_output .= $args['after_title'];
					$nav_output .= '<ul>';
					$cat_link    = get_category_link( $the_term );
					foreach ( $res as $row ) {
						$nav_output .= "<li><a href=\"{$cat_link}?fm=" . $row['fm'] . '&fy=' . $row['fy'] . '"><span class="link-text">' . $row['fm'] . ' ' . $row['fy'] . '</span><span class="kcal-chevron-right"></span></a>';
						$nav_output .= '</li>';
					}

					$nav_output .= "<li class=\"clear-filter\"><a href=\"{$cat_link}\"><span class=\"link-text\">" . __( 'Clear Filter', 'kcal' ) . '</span><span class="kcal-x">&times;</span></a>';
					$nav_output .= '</ul>';
					$nav_output .= '</div>';
				}
			}
			if ( ! empty( $nav_output ) ) {
				echo wp_kses( $args['before_widget'], 'post' );
				echo wp_kses( $nav_output, 'post' );
				echo wp_kses( $args['after_widget'], 'post' );
			}
		}
	}
}
