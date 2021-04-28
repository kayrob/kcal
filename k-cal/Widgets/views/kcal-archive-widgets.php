<?php

/*
 * Archive Widgets for the calendar.
 */
class kCalfilterEventsDate extends WP_Widget {
    protected $scripts = array();
    function __construct() {
        $widget_ops = array( 'classname' => 'kcal-archive-widget kcal-filter-events', 'description' => __('A widget that displays a "filter by month/year" side menu for events archive pages', 'kCalfilterEventsDate') );
        $control_ops = array();
        parent::__construct( 'filter-events-date', __('K-Cal Events Year Month/Year Widget', 'kCalfilterEventsDate'), $widget_ops, $control_ops );

        $pURL = trailingslashit(plugins_url()."/k-cal");
        wp_enqueue_script("kcal-widgets-js", $pURL ."js/mini-calendar.js", array("jquery"), "1.0", true);
        wp_enqueue_style("kcal-widgets-css", $pURL ."css/kcal-widgets.css", array(), '2.2.6');
        wp_localize_script("kcal-widgets-js", "ajax_object", array("ajax_url" => admin_url("admin-ajax.php")));
        $this->scripts['kcal-widgets-js'] = false;
        $this->scripts['kcal-widgets-css'] = false;

        add_action('wp_print_footer_scripts', array($this, 'kcal_remove_scripts'));
    }

    public function kcal_remove_scripts() {
        foreach ( $this->scripts as $script => $keep ) {
            if ( false === $keep ) {
                // It seems dequeue is not "powerful" enough, you really need to deregister it
                wp_deregister_script( $script );
            }
        }

    }
    public function widget($args, $instance)
    {

        extract($args);

        $catID = get_query_var("calendar");
        $term = get_term_by("slug", $catID, "calendar");
        if (false !== $term && class_exists("CalendarWidgets")){
            $cw = new CalendarWidgets();
            $date = new DateTime('', new DateTimeZone(get_option('gmt_offset')));
            $start = mktime(0,0,0, $date->format('Y'), $date->format('n'), '01');
            $date->setTimestamp(strtotime($date->format('Y').'-'.$date->format('n'). '-01 + 1 year'));
            $end = $date->getTimestamp();
            $events = $cw->retrieve_all_events($start, $end, $term->term_id);

            $res = array();
            if (!empty($events)){
                foreach($events as $event){
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
                $navOutput = $args["before_title"];
                $navOutput .= "Filter Events";
                $navOutput .= $args["after_title"];
                $navOutput .= "<ul>";
                $catLink = get_category_link($term);
                foreach($res as $row){
                    $navOutput .= "<li><a href=\"{$catLink}?fm=".$row["fm"]."&fy=".$row["fy"]."\">" .$row["fm"]." ".$row["fy"]. "<span class=\"fa fa-chevron-right\"></span></a>";
                    $navOutput .= "</li>";
                }

                $navOutput .= "<li><a href=\"{$catLink}\">Clear Filter<span class=\"fa fa-chevron-right\"></span></a>";
                $navOutput .= "</ul>";
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

        $pURL = trailingslashit(plugins_url()."/k-cal");
        wp_enqueue_style("kcal-widgets-css", $pURL ."css/kcal-widgets.css", array(), '2.2.6');
        $this->scripts['kcal-widgets-css'] = false;

        add_action('wp_print_footer_scripts', array($this, 'kcal_remove_scripts'));
    }

    public function kcal_remove_scripts() {
        foreach ( $this->scripts as $script => $keep ) {
            if ( false === $keep ) {
                wp_deregister_script( $script );
            }
        }

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

            $navOutput = $args["before_title"];
            $navOutput .= (isset($instance["title"]) ? $instance["title"] : __('Calendars', 'kcal') );
            $navOutput .= $args["after_title"];
            $navOutput .= "<ul>";
            foreach($allTerms as $calTerm){
                if (is_single() || !isset($term->term_id) || $calTerm->term_id != $term->term_id){
                    $catLink = get_category_link($calTerm);
                    $navOutput .= "<li><a href=\"{$catLink}\">" .$calTerm->name. "<span class=\"fa fa-chevron-right\"></span></a>";
                    $navOutput .= "</li>";
                }
            }
            $navOutput .= "</ul>";
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
