<?php
if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/*
 * Archive Widgets for the calendar.
 */
if (!class_exists('kCalfilterEventsDate')) {
	class kCalfilterEventsDate extends WP_Widget {
		protected $scripts = array();
		function __construct() {
			$widget_ops = array( 'classname' => 'kcal-archive-widget kcal-filter-events', 'description' => __('A widget that displays a "filter by month/year" side menu for events archive pages', 'kCalfilterEventsDate') );
			$control_ops = array();
			parent::__construct( 'filter-events-date', __('K-Cal Events Year Month/Year Widget', 'kcal'), $widget_ops, $control_ops );

		}

		public function widget($args, $instance)
		{

			extract($args);

			$catID = get_query_var("calendar");
			$term = get_term_by("slug", $catID, "calendar");

			if (false !== $term && class_exists("CalendarWidgets")){
				$cw = new CalendarWidgets();
				$dTZ = new DateTimeZone(get_option('gmt_offset'));
				$date = new DateTime('now', $dTZ);
				$start = $date->getTimestamp();
				$date->setTimestamp(strtotime($date->format('Y').'-'.$date->format('n'). '-01 + 1 year'));
				$events = $cw->retrieve_all_events($start, $date->getTimestamp(), $term->term_id);

				$res = array();
				if (!empty($events)){
					foreach($events as $event) {
						try {
							$eTZ = new DateTimeZone($event->timezone);
						} catch (\Exception $e) {
							$eTZ = $dTZ;
						}
						$date->setTimezone($eTZ);
						$date->setTimestamp($event->eventStartDate);
						$eventMonth = $date->format('F');
						$eventYear = $date->format('Y');
						$filterKey = strtotime($eventMonth." 1, ".$eventYear);
						if (!isset($res[$filterKey])){
							$res[$filterKey] = array("fm" => $eventMonth, "fy" => $eventYear);
						}
					}
				}
				$navOutput = "";


				if (!empty($res)){

					$this->scripts['kcal-widgets-js'] = true;
					$this->scripts['kcal-widgets-css'] = true;

					ksort($res);
					$navOutput = '<div class="filter-events-wrapper">';
					$navOutput .= $args["before_title"];
					$navOutput .= __("Filter Events", 'kcal');
					$navOutput .= $args["after_title"];
					$navOutput .= "<ul>";
					$catLink = get_category_link($term);
					foreach($res as $row){
						$navOutput .= "<li><a href=\"{$catLink}?fm=".$row["fm"]."&fy=".$row["fy"]."\"><span class=\"link-text\">" .$row["fm"]." ".$row["fy"]. "</span><span class=\"kcal-chevron-right\"></span></a>";
						$navOutput .= "</li>";
					}

					$navOutput .= "<li class=\"clear-filter\"><a href=\"{$catLink}\"><span class=\"link-text\">" . __('Clear Filter', 'kcal') . "</span><span class=\"kcal-x\">&times;</span></a>";
					$navOutput .= "</ul>";
					$navOutput .= '</div>';
				}
			}
			if (!empty($navOutput)){
				echo $args["before_widget"];
				echo $navOutput;
				echo $args["after_widget"];
			}
		}
	}
	class kCalCalendarSidebar extends WP_Widget{

		protected $scripts = array();
		public function __construct() {
			$widget_ops = array( 'classname' => 'kcal-archive-widget kcal-calendars-list-view', 'description' => __('A widget that displays links to other calendars in a sidebar', 'kCalCalendarSidebar') );
			$control_ops = array();
			parent::__construct( 'kcal-calendar-sidebar', __('K-Cal Sidebar List', 'kCalCalendarSidebar'), $widget_ops, $control_ops );
		}

		public function widget($args, $instance)
		{
			extract($args);

			$term = false;

			$catID = get_query_var("calendar");
			if (false !== $catID && !empty($catID)){
				$term = get_term_by("slug", $catID, "calendar");
			}
			$allTerms = get_terms(array("calendar"));

			$navOutput = "";

			if (!empty($allTerms) && count($allTerms) > 1){

				$this->scripts['kcal-widgets-js'] = true;
				$this->scripts['kcal-widgets-css'] = true;

				$navOutput = '<div class="list-calendars-wrapper">';
				$navOutput .= $args["before_title"];
				$navOutput .= (isset($instance["title"]) ? $instance["title"] : __('Calendars', 'kcal') );
				$navOutput .= $args["after_title"];
				$navOutput .= "<ul>";
				foreach($allTerms as $calTerm){
					if (is_single() || !isset($term->term_id) || $calTerm->term_id != $term->term_id){
						$catLink = get_category_link($calTerm);
						$navOutput .= "<li><a href=\"{$catLink}\"><span class=\"link-text\">" .$calTerm->name. "</span><span class=\"kcal-chevron-right\"></span></a>";
						$navOutput .= "</li>";
					}
				}
				$navOutput .= "</ul>";
				$navOutput .= '</div>';
			}
			if (!empty($navOutput)){
				echo $args["before_widget"];
				echo $navOutput;
				echo $args["after_widget"];
			}
		}
		public function update($new_instance, $old_instance)
		{
			$instance = $old_instance;

			$instance["title"] = strip_tags($new_instance["title"]);
			return $instance;
		}
		public function form($instance)
		{

			$defaults = array("title" => "Calendars");
			$instance = wp_parse_args((array) $instance, $defaults);

	?>
			<p>
			<label for="<?php echo $this->get_field_id( "title" ); ?>"><?php _e("Title:", "kCalArchiveView"); ?></label>
			<input type="text" id="<?php echo $this->get_field_id( "title" ); ?>" name="<?php echo $this->get_field_name( "title" ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
			</p>
	<?php
		}

	}
}