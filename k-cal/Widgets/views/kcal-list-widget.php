<?php

/*
 * Widget to display coming events in a list view.
 */
class kCalListView extends WP_Widget
{
    public function __construct()
    {
        $widget_ops = array("classname" => "event-list-widget", "description" => __("A widget that displays list of events by calendar", "kCalListView"));
        $control_ops = array();
        parent::__construct("kcal-list-widget", __("K-Cal List View Widget", "kCalListView"), $widget_ops, $control_ops);
    }

    public function widget($args, $instance)
    {
        extract($args);
        $o = get_option("kcal_settings");

        $eventLink = "";
        $calLink = "";
        if (isset($o["fullcalendar_page"]) || isset($o["eventDetails_page"]))
        {
            $eventLink = (isset($o["eventDetails_page"])) ? trim($o["eventDetails_page"], "/")."?event=!e" : trim($o["fullcalendar_page"], "/")."?start=!s&amp;event=!e";
            $calLink = (isset($o["fullcalendar_page"])) ? $o["fullcalendar_page"] : "";
        }

        $cal = new CalendarWidgets();
        $calendars = $cal->get_calendar_details();
        $props = (isset($instance['listView_calendars'])) ? $instance["listView_calendars"] : array();
        $limit = (isset($instance['listView_calendars'])) ? (int) $instance["number_of_events"] : 3;
        $listEvents = array();
        if (!empty($props)){
            foreach ($props as $calID){
                $eventList = $cal->upcoming_events_widget($limit, (int) $calID);
                if (!empty($eventList)){
                    foreach($eventList as $eventData){
                        $eventData["calendar"] = $calendars[$calID]["name"];
                        $listEvents[$eventData["start"]][] = $eventData;
                    }
                }
            }
        }
        if (!empty($listEvents))
        {
            ksort($listEvents);
        }
?>
        <div class="kcal-feed">
        <?php echo $args["before_title"];?>
        <?php echo (isset($instance["widget_title"]) ) ? $instance["widget_title"] : __('Upcoming Events', 'kcal');?>
        <?php echo $args["after_title"];?>
 <?php
        if (!empty($listEvents)){
            $e = 0;
            $date = new \DateTime('now', new \DateTimeZone(get_option('gmt_offset')));
            $today = $date->format('Y-m-d');
            foreach ($listEvents as $startDate => $items){
                foreach($items as $index => $nfo){
                    echo $index;
                    $date->setTimestamp($nfo['end']);
                    if ($date->getTimestamp() > current_time('timestamp')){
                        $date->setTimestamp($nfo['start']);
                        $eventDay = $date->format('l F j, Y');
                        $dateDiff = (strtotime($date->format('Y-m-d')) - strtotime($today)) / (60*60*24);
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
                        if (!empty($nfo["link"])){
                            $eventURL = urlencode($nfo["link"]);
                        }
                        $dxn = strip_tags($nfo["description"]);
                        $dxnEnd = (strlen($dxn) > 140) ? 130 + strpos(substr($dxn, 130), " ") : 140;
                        $dxn = substr($dxn, 0, $dxnEnd);

                        $timeStart = $date->format('g:i a');
                        $date->setTimestamp($nfo['end']);
                        $timeEnd = $date->format('g:i a');
 ?>
                    <div class="kcal-feed-item<?php echo ($index == 0) ? ' first' : '';?>">
                        <h4><a href="<?php echo $eventURL;?>" class="event-main"><?php echo $nfo["title"];?></a></h4>
                        <h5>Date:
                    <?php echo $eventDay . ' '. $timeStart. ' - '. $timeEnd; ?>
                        </h5>
                    </div>
<?php
                        $e++;
                        if ($e == 3){
                            break;
                        }
                    }
                }
                if ($e == 3){
                    break;
                }
            }
        }
        else{
            echo "<p>No Upcoming Events</p>";
        }
        if (!empty($calLink)){
 ?>
        <p><a href="<?php echo $calLink;?>" class="more-news-events">more events</a></p>
<?php
        }
?>
        </div>
<?php

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

        $defaults = array("number_of_events" => 3, "listView_calendars" => $calDefault, "widget_title" => "Events");
        $instance = wp_parse_args((array) $instance, $defaults);

        $calsSelected = $instance["listView_calendars"];
?>
        <p>
        <label for="<?php echo $this->get_field_id( "widget_title" ); ?>"><?php _e("Title:", "kCalListView"); ?></label>
        <input type="text" id="<?php echo $this->get_field_id( "widget_title" ); ?>" name="<?php echo $this->get_field_name( "widget_title" ); ?>" value="<?php echo $instance['widget_title']; ?>" style="width:100%;" />
        </p>
        <p>
        <label for="<?php echo $this->get_field_id( "number_of_events" ); ?>"><?php _e("Number of Events:", "kCalListView"); ?></label>
        <input min="1" type="number" id="<?php echo $this->get_field_id( "number_of_events" ); ?>" name="<?php echo $this->get_field_name( "number_of_events" ); ?>" value="<?php echo $instance['number_of_events']; ?>" style="width:100%;" />
        </p>
        <p>Select Calendars:</p>
<?php
        if (!empty($calendars)){
            foreach($calendars as $calID => $nfo)
            {
                $checked = ($calsSelected == null || in_array($calID, $calsSelected)) ? " checked=\"checked\"" : "";
    ?>
            <p>
            <input class="checkbox" type="checkbox" <?php echo $checked; ?> id="<?php echo $this->get_field_name( "listView_calendars" )."_".$calID; ?>" name="<?php echo $this->get_field_name( "listView_calendars" ); ?>[]" value="<?php echo $calID;?>"/>
            <label for="<?php echo $this->get_field_name( "listView_calendars" )."_".$calID; ?>"><?php _e($nfo["name"], "kCalListView"); ?></label>
            </p>
    <?php
            }
        }
        else{
            echo "<p>No Calendars</p>";
        }
    }
}
