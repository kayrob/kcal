<?php

/*
 * Widget to display a mini calendar in a sidebar
 */
class kCalQuickView extends WP_Widget
{
    public function __construct()
    {
        $widget_ops = array("classname" => "cal-mini-widget", "description" => __("A widget that displays a mini calendar in a sidebar", "kCalQuickView"));
        $control_ops = array();
        parent::__construct("kcal-mini-widget", __("Calendar Mini View Widget", "kCalQuickView"), $widget_ops, $control_ops);
    }
    
    public function widget($args, $instance)
    {
        
        extract($args);
        $calsSelected = $instance["quickView_calendars"];
        
        $cal = new CalendarWidgets();
        //$props = $instance["quickView-calendars"];
        $output = "<div id=\"dlgQuickView\"></div>";
        $output .= "<div class=\"event-mini-widget widget\" id=\"cal-mini-widget\">";
        $output .= "<h4 id=\"h4QVHeader\"><span>&#8249;</span><span>" .date("F Y")."</span><span>&#8250;</span></h4>";
        $output .= $cal->quick_view_calendar(false, false, $calsSelected);
        $output .= "<p id=\"pQVdateTime\">" . time() ."</p>";
        $output .= "</div>";
        echo $output;
    }
    
    public function update($new_instance, $old_instance)
    {
        $instance = $old_instance;

        $instance["quickView_calendars"] = $new_instance["quickView_calendars"];
        
        return $instance;
    }
    public function form($instance)
    {
        $cal = new Calendar();
        $calendars = $cal->get_calendar_details();
        
        $calDefault = array_keys($calendars);
        
        $defaults = array("quickView_calendars" => $calDefault);
        $instance = wp_parse_args((array) $instance, $defaults);

        $calsSelected = $instance["quickView_calendars"];
?>   
        <p>Select Calendars:</p>
<?php
        if (!empty($calendars)){
            foreach($calendars as $calID => $nfo)
            { 
                $checked = ($calsSelected == null || in_array($calID, $calsSelected)) ? " checked=\"checked\"" : "";
    ?>
            <p>  
            <input class="checkbox" type="checkbox" <?php echo $checked; ?> id="<?php echo $this->get_field_name( "quickView_calendars" )."_".$calID; ?>" name="<?php echo $this->get_field_name( "quickView_calendars" ); ?>[]" value="<?php echo $calID;?>"/>
            <label for="<?php echo $this->get_field_name( "quickView_calendars" )."_".$calID; ?>"><?php _e($nfo["name"], "kCalQuickView"); ?></label>
            </p>
    <?php
            }
        }
        else{
            echo "<p>No Calendars</p>";
        }
    }
}


function kCal_mini_widget()
{
    register_widget("kCalQuickView");
}