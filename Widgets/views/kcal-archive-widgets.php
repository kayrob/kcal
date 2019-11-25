<?php

/*
 * Archive Widgets for the calendar.
 */
class kCalfilterEventsDate extends WP_Widget
{ 
    function __construct() {  
        $widget_ops = array( 'classname' => 'inner-filter-widget', 'description' => __('A widget that displays a "filter by month/year" side menu for events archive pages', 'kCalfilterEventsDate') );  
        $control_ops = array();  
        parent::__construct( 'filter-events-date', __('Events Year Month/Year Widget', 'kCalfilterEventsDate'), $widget_ops, $control_ops );  
    }
    public function widget($args, $instance)
    {
        
        extract($args);
        
        $catID = get_query_var("calendar");
        $term = get_term_by("slug", $catID, "calendar");
        if (false !== $term && class_exists("CalendarWidgets")){
            $cw = new CalendarWidgets();
            $start = strtotime(date("Y")."-".date("n")."-01");
            $end = strtotime(date("Y")."-".date("n")."-01 + 1 year");
            $events = $cw->retrieve_all_events($start, $end, $term->term_id);
            $res = array();
            if (!empty($events)){
                foreach($events as $event){
                    $eventMonth = date("F", $event->eventStartDate);
                    $eventYear = date("Y", $event->eventStartDate);
                    $filterKey = strtotime($eventMonth." 1, ".$eventYear);
                    if (!isset($res[$filterKey])){
                        $res[$filterKey] = array("fm" => $eventMonth, "fy" => $eventYear);
                    }
                }
            }
            $navOutput = "";


            if (!empty($res)){
                ksort($res);
                $navOutput = $args["before_title"];
                $navOutput .= "<span class=\"fa fa-history\"></span><span>Filter Events</span>";
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
class kCalCalendarSidebar extends WP_Widget
{
    public function __construct() {  
        $widget_ops = array( 'classname' => 'inner-filter-widget', 'description' => __('A widget that displays links to other calendars in a sidebar', 'kCalCalendarSidebar') );  
        $control_ops = array();  
        parent::__construct( 'kcal-calendar-sidebar', __('Calendar Sidebar List', 'kCalCalendarSidebar'), $widget_ops, $control_ops );  
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
            $navOutput = $args["before_title"];
            $navOutput .= "<span class=\"fa fa-calendar-o\"></span><span>Calendars</span>";
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
    
}
