<?php

class CalendarController{
    /**
    * Admin functions to add/edit/delete calendars
    * Users have to be authenticated
    * Ajax route
    * @access public
    * @return void
    */
    public function adminCalendarsAction($post){
        global $blog_id;
        $response = "false";
        if (is_admin()){

            $action = $post["input"];
            unset($post["input"]);
            unset($post["action"]);
            unset($post["request"]);
            unset($post["nonce"]);
            unset($post["p"]);
            unset($post["attr"]);

            $cal = new AdminCalendar();
            switch ($action){
                case "u":
                    $response = $cal->update_calendar($post);
                    break;
                case "d":
                    $response = $cal->disable_calendar($post);
                    break;
                default:
                    $response = $cal->add_new_calendar($post, $blog_id);
                    break;
            }
        }
        header("Content-type: text/html");
        echo $response;
        die();
    }
    /**
    * Admin functions to add/edit/delete events
    * Users have to be authenticated
    * Ajax route
    * @access public
    * @return void
    */
    public function adminEventsAction($post){

        $response = "false";

        //if is loggged in and has permission to edit
        if (is_admin()){

            $action = $post["input"];
            unset($post["input"]);
            unset($post["action"]);
            unset($post["request"]);
            unset($post["nonce"]);
            unset($post["p"]);
            unset($post["attr"]);

            $cal = new AdminCalendar();
            switch ($action){
                case("r"):
                    $response = $cal->update_recurring_events($post);
                break;
                case("u"):
                    $response = $cal->drag_drop_event($post);
                break;
                case("d"):
                    $response = $cal->delete_events_main($post);
                break;
                default:
                    $response = $cal->add_new_event($post);
                break;
            }
        }
        header("Content-type: text/html");
        echo $response;
        die();
    }
    /**
    * Admin function to get a list of calendars to display in the left hand column
    * Users have to be authenticated
    * Ajax route
    * @access public
    * @return void
    */
    public function adminListCalendarsAction(){
        //if is loggged in and has permission to edit
        $response = "false";
        if (is_admin()){
            $cal = new AdminCalendar();
            $response = $cal->display_calendar_list_admin();
        }
        header("Content-type: text/html");
        echo $response;
        die();
    }
    /**
    * Admin function to get a list of style options for the admin calendar view
    * Used on update/add
    * Ajax route
    * @access public
    * @return void
    */
    public function adminStyleCalendarsAction(){
        header("Content-type: text/css");
        $ca = new AdminCalendar();
        return $ca->buildCalendarCSS();
        die();
    }

    /**
    * Autocomplete for event tags
    * @access public
    */
    public function adminGetTermTags(){
        $term = (isset($_GET["term"])) ? $_GET["term"] : "";
        $response = "";
        if (!empty($term)){
            $ca = new CalendarTags();
            $response = $ca->getSearchTagsList($term);
        }
        header("Content-type: text/html");
        echo $response;
        die();
    }

    /**
    * Health Unit function to retrieve the details of a selected event in HTML format
    * Ajax route
    * @access public
    * @return void
    */
    public function userGetAjaxEvent(Request $request){
        $response = 'false';

        $get = Quipp()->getRequest()->query->all();
        if (isset($get["assetView"]) && (int) $get["assetView"] > 0){
        	$response = Quipp()->getModule('CalendarWidgets')->get_ajax_user_event($get['assetView']);
    	}
    	header("Content-type: text/html");
        echo $response;
    }
    /**
    * Health Unit method to retrieve the list of site calendars
    * Ajax route
    * @access public
    * @return void
    */
    public function getCalendarsAjax(){
        $response = "false";

        $get = $_GET;
        if (isset($get["calendar"]) && trim($get["calendar"]) === "s"){
            $c = new Calendar();
            $response = $c->get_calendars_ajax();
        }
        header("Content-type: text/json");
        echo $response;
        die();
    }
    /**
    * Calendar method to retrieve the list of events for a specific site calendar
    * Ajax route
    * @access public
    * @return void
    */
    public function getCalendarsEventsAjax(){
        $response = "false";

        $get = $_GET;
        if (isset($get["calendar"]) && preg_match("/^[0-9]{1,6}$/",$get["calendar"],$matches)){
            $cw = new CalendarWidgets();
            $response = $cw->get_calendar_events_ajax($get);
	}
        header("Content-type: text/json");
        echo $response;
        die();
    }
    /**
    * General method to retrieve the events occurring on a specific date
    * Ajax route
    * @access public
    * @return void
    */
    public function getCalendarsQuickViewEvents(){
        $response = "false";

        $w = array_values(get_option("widget_kcal-mini-widget", array()));
        $calSettings = (isset($w[0]["quickView_calendars"])) ? $w[0]["quickView_calendars"]: array();
        
        $o = get_option("kcal_settings");
        
        $calPage = (isset($o["fullcalendar_page"]) && !empty($o["fullcalendar_page"])) ? $o["fullcalendar_page"] : "";
        $eventPage = (isset($o["eventDetails_page"]) && !empty($o["eventDetails_page"])) ? $o["eventDetails_page"] : "";
        
        $get = $_GET;
        if (isset($get["qview"]) && preg_match("/^[0-9]{8,}$/",$get['qview'],$matches)){
            $cw = new CalendarWidgets();
            $response = $cw->get_quick_view_events_ajax(trim($get["qview"]), $calSettings, $calPage, $eventPage);
        }
        header("Content-type: text/html");
        echo $response;
        die();
    }
    /**
    * General method to retrieve the HTML representation of a calendar when cycling through months
    * Ajax route
    * @access public
    * @return void
    */
    public function getCalendarsQuickViewCalendar(){

        $response = "false";

        $w = array_values(get_option("widget_kcal-mini-widget", array()));
        $calSettings = (isset($w[0]["quickView_calendars"])) ? $w[0]["quickView_calendars"]: array();

        $get = $_GET;
        if (isset($get["qvAdv"]) && preg_match("/(-)?[1]{1}/",trim($get["qvAdv"]),$matches) && isset($get["qvStamp"]) && preg_match("/^[0-9]{4}(\-\d{2}){2}$/",trim($get["qvStamp"]),$match)){
            $cw = new CalendarWidgets();
            $response = $cw->quick_view_calendar_ajax(trim($get["qvAdv"]),trim($get["qvStamp"]), $calSettings);
        }
        header("Content-type: text/html");
        echo $response;
        die();
    }

    /**
    * General method to retrieve the upcoming events in HTML for the full calendar list view when cycling through dates
    * Ajax route
    * @access public
    * @return void
    */
    public function getCalendarsFullCalendar(){
        $response = "false";
        $get = $_GET;
        if (isset($get["qview"]) && trim($get["qview"]) == "list" && isset($get["view"]) && is_array($get["view"])){
          unset($get["qview"]);
          $cw = new CalendarWidgets();
          $response = $cw->fullCalendar_upcoming_events(10,$get);
    	}
      echo $response;
      die();
    }

    public function buildCalendarsRSS(){

        $get = $_GET;

        $rss = "";
        $cw = new CalendarWidgets();
        if (isset($get["calendar"])){
            $rss = $cw->buildRSS((int) $get["calendar"], 30);
        }
        else{
            $rss = $cw->buildRSS(false, 100);
        }
        //header("Content-Type: application/rss+xml; charset=UTF-8");
        //echo $rss;
        return $rss;
    }

    public function addToCalendar(){

        $get = $_GET;
        $output = "";
        $fileName = site_url()." Events";

        if (isset($get["calID"]) || isset($get["eID"])){

            $cw = new CalendarWidgets();
            $get["calendar"] = $get["calID"];
            if (isset($get["eID"])){
                $get["event"] = $get["eID"];
                unset($get["eID"]);
            }
            unset($get["calID"]);
            $output = $cw->output_ics($get);
            if (isset($get["calendar"]) && isset($get["start"])){

                $calendars = $cw->get_calendar_details();
                if (isset($calendars[$get["calendar"]])){
                    $fileName = preg_replace("/[^A-Za-z0-9]/","",$calendars[$get["calendar"]]["name"]) . "Events";

                }
            }
            else if (isset($get["event"]) && isset($get["calendar"])){
                $eventID = explode("-", $get["event"]);
                $eventTitle = preg_replace("/[^A-Za-z0-9]/","",get_the_title($eventID[0]));
                if (!empty($eventTitle)){
                    $fileName = str_replace(" ","",$eventTitle);
                }
            }
        }

        header("Content-Type: text/Calendar");
        header("Content-Disposition: inline; filename=$fileName.ics");
        die($output);
    }

    public function buildCalendarsCSS()
    {
        $c = new Calendar();
        header("Content-Type: text/css");
        die($c->buildCalendarCSS());
    }
}
