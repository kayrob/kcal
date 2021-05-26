<?php
if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/*
 * Widget to display coming events in a list view.
 */
if (!class_exists('kCalListView')) {
	class kCalListView extends WP_Widget
	{
		public function __construct()
		{
			$widget_ops = array("classname" => "kcal-event-list-widget", "description" => __("A widget that displays list of events by calendar", "kcal"));
			$control_ops = array();
			parent::__construct("kcal-list-widget", __("K-Cal List View Widget", "kcal"), $widget_ops, $control_ops);
		}

		public function widget($args, $instance)
		{
			extract($args);

			$cw = new CalendarWidgets();
			$calendars = $cw->get_calendar_details();
			$props[] = (isset($instance['listView_calendars'])) ? $instance["listView_calendars"] : 'auto';

			if ($props[0] == 'auto' && is_singular('event')) {

				$eventCals = wp_get_post_terms(get_the_ID(), 'calendar');

				if (isset($eventCals[0]->term_id)) {
					foreach($eventCals as $index => $cal) {
						$props[$index] = $cal->term_id;
					}
				}
			}

			$limit = (isset($instance['listView_calendars'])) ? (int) $instance["number_of_events"] : 3;
			$listEvents = array();

			if (!empty($props)){
				foreach ($props as $calID){

					$eventList = $cw->upcoming_events_widget($limit, (int) $calID);
					if (!empty($eventList)){
						foreach($eventList as $eventData){
							$eventData["calendar"] = $calendars[$calID]["name"];
							$listEvents[$eventData["start"]][] = $eventData;
						}
					}
				}
			}
			if (!empty($listEvents)) {
				ksort($listEvents);
			}
			echo $args["before_widget"];
	?>
			<div class="events-list-wrapper">
			<?php echo $args["before_title"];?>
			<?php echo (isset($instance["widget_title"]) ) ? $instance["widget_title"] : __('Upcoming Events', 'kcal');?>
			<?php echo $args["after_title"];?>
			<ol>
	<?php
			if (!empty($listEvents)) {
				$e = 0;
				$optionTZ =  new \DateTimeZone(get_option('gmt_offset'));
				$date = new \DateTime('now', $optionTZ);
				$today = $date->format('Y-m-d');
				$dateFormat = get_option('date_format');
				$timeFormat = get_option('time_format');

				$events = [];

				foreach ($listEvents as $startDate => $items){
					foreach($items as $index => $nfo){

						if (!isset($events[$nfo['id']])) {

							$events[$nfo['id']] = $nfo['id'];
							$eventID = explode('-', $nfo['id']);


							$eventTZ = get_post_meta($eventID[0], '_kcal_timezone', true);

							try {
								$timezoneObj = new \DateTimeZone($eventTZ);
							} catch (\Exception $e) {
								$timezoneObj = $optionTZ;
							}
							$date = new \DateTime('now', $timezoneObj);
							$date->setTimestamp($nfo['end']);

							if ($date->getTimestamp() > current_time('timestamp')){
								$date->setTimestamp($nfo['start']);

								$eventDay = $date->format($dateFormat);

								$dateDiff = (strtotime($date->format('Y-m-d')) - current_time('timestamp')) / (60*60*24);
								if ($dateDiff == 0){
									$eventDay = "Today";
								}
								else if ($dateDiff == 1){
									$eventDay = "Tomorrow";
								}
								else if ($dateDiff < 7){
									$eventDay = $date->format('l');
								}
								$eventURL = "";
								$eventLink = get_permalink($eventID[0]);
								if (!empty($nfo["link"])){
									$eventURL = ($nfo["link"]);
								} else {
									$eventURL = $eventLink;
								}
								if ($eventLink == $eventURL && isset($eventID[1])) {
									$eventURL .= '?r=' . $eventID[1];
								}
								$dxn = strip_tags($nfo["description"]);
								$dxnEnd = (strlen($dxn) > 140) ? 130 + strpos(substr($dxn, 130), " ") : 140;
								$dxn = substr($dxn, 0, $dxnEnd);

								$timeStart = $date->format($timeFormat);
								$date->setTimestamp($nfo['end']);
								$timeEnd = $date->format($timeFormat);
		?>
							<li class="kcal-feed-item<?php echo ($e == 0) ? ' first' : '';?>">
								<h4><a href="<?php echo $eventURL;?>" class="event-main"><?php echo $nfo["title"];?></a></h4>
								<p><?php echo $eventDay . ' '. $timeStart. ' - '. $timeEnd; ?></p>
							</li>
		<?php
								$e++;
								if ($e == 3) {
									break;
								}
							}
						}
					}
					if ($e == 3){
						break;
					}
				}
			}
			else{
				echo "<p>" . __('No Upcoming Events', 'kcal') . "</p>";
			}
			if (!empty($calLink)){
	?>
			<li class="calendar-links"><a href="<?php echo $calLink;?>" class="more-news-events"><?php _e('more events', 'kcal'); ?></a></li>
	<?php
			}
	?>
			</ol>
			</div>
	<?php
		echo $args["after_widget"];

		}

		public function update($new_instance, $old_instance)
		{
			$instance = $old_instance;

			$instance["widget_title"] = strip_tags($new_instance["widget_title"]);
			$instance["listView_calendars"] = $new_instance["listView_calendars"];
			$instance["number_of_events"] = (intval($new_instance["number_of_events"], 10) > 0) ? intval($new_instance["number_of_events"], 10) : 3;

			return $instance;
		}
		public function form($instance)
		{
			$cal = new Calendar();
			$calendars = $cal->get_calendar_details();

			$calDefault = array_keys($calendars);

			$defaults = array("number_of_events" => 3, "listView_calendars" => $calDefault, "widget_title" => __("Events", 'kcal') );
			$instance = wp_parse_args((array) $instance, $defaults);

			$calsSelected = $instance["listView_calendars"];
	?>
			<p>
			<label for="<?php echo $this->get_field_id( "widget_title" ); ?>"><?php _e("Title:", "kcal"); ?></label>
			<input type="text" id="<?php echo $this->get_field_id( "widget_title" ); ?>" name="<?php echo $this->get_field_name( "widget_title" ); ?>" value="<?php echo $instance['widget_title']; ?>" style="width:100%;" />
			</p>
			<p>
			<label for="<?php echo $this->get_field_id( "number_of_events" ); ?>"><?php _e("Number of Events:", "kcal"); ?></label>
			<input min="1" type="number" id="<?php echo $this->get_field_id( "number_of_events" ); ?>" name="<?php echo $this->get_field_name( "number_of_events" ); ?>" value="<?php echo $instance['number_of_events']; ?>" style="width:100%;" />
			</p>
			<p><?php _e('Select Calendar:', 'kcal'); ?></p>
			<p>
				<input class="checkbox" type="radio"<?php print($calsSelected == 'auto' ?  ' checked="checked"' : ''); ?> id="<?php echo $this->get_field_name( "listView_calendars" )."_default"; ?>" name="<?php echo $this->get_field_name( "listView_calendars" ); ?>" value="auto" />
				<label for="<?php echo $this->get_field_name( "listView_calendars" )."_default"; ?>"><?php _e('Auto (Single Events)', 'kcal'); ?></label>
			</p>

	<?php
			if (!empty($calendars)){
				foreach($calendars as $calID => $nfo) {
					$checked = ($calsSelected == null || in_array($calID, $calsSelected)) ? " checked=\"checked\"" : "";
		?>
				<p>
					<input class="checkbox" type="radio" <?php echo $checked; ?> id="<?php echo $this->get_field_name( "listView_calendars" )."_".$calID; ?>" name="<?php echo $this->get_field_name( "listView_calendars" ); ?>" value="<?php echo $calID;?>"/>
					<label for="<?php echo $this->get_field_name( "listView_calendars" )."_".$calID; ?>"><?php _e($nfo["name"], "kcal"); ?></label>
				</p>
		<?php
				}
			}
			else{
				echo "<p>No Calendars</p>";
			}
		}
	}
}
