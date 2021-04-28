<?php

/**
* Calendar class retrieves data for public viewing of events and passes it back to jQuery Full Calendar
* Date created: Nov 10 2010 by Karen Laansoo
* @package apps/calendar
*/
class Calendar {
    protected $db;
    public $url = "/events";
    protected $blogID;
    protected $timezone;

    /**
    * constructor explicitly called to use this keyword and set db object
    */
    public function __construct(){
        global $wpdb, $blog_id;
        $this->db = $wpdb;
        $this->blogID = $blog_id;
        $this->timezone = get_option("gmt_offset");
    }

    /**
    * Common function for getting calenadar data for view and ajax calls
    * basic check to see if and how many calendars exist
    * @access protected
    * @see DB::result_please()
    * @return resource|false
    */
    protected function get_calendars_common($id = false){
        $terms = get_terms(array("calendar"), array("hide_empty" => false));
        $calendars = array();
        if (!empty($terms)){
            foreach($terms as $term){
                if ($id === false || $term->term_id == $id){
                    $calendars[] = array(
                        "itemID"                => $term->term_id,
                        "calendarName"          => $term->name,
                        "slug"                  => $term->slug,
                        "eventCount"            => $term->count,
                        "eventBackgroundColor"  => "#" . str_replace("#", "", get_option("calendar_".$term->term_id, "#cccccc")),
                        'eventTextColor'        => "#" . str_replace("#", "", get_option("calendar_text_".$term->term_id, "#000"))
                    );
                }
            }
        }
        return json_decode(json_encode($calendars));
    }
    /**
    * Display a checkbox list of calendars on event/calendar page
    * these inputs are tied to jquery fullCalendar events feeds
    * if a specific calendar is selected, only check the one selected
    * @access public
    * @param string|false $id
    * @see get_calendars_common()
    */
    public function get_calendars_view($id = false, $rss = false, $ics = false){
        $res = $this->get_calendars_common();
        $pageAction = get_permalink(get_the_ID());
        if ($res !== false){
            echo('<form name="frm_calendar_list" id="frm_calendar_list" action="'.$pageAction.'" method="post" onsubmit="return false">');
            foreach ($res as $row){
                $checked = ($id == false || $id == trim($row->itemID))?'checked="checked"':'';
                $rssLink = ($rss !== false && !empty($rss)) ? '<a href="/'.$rss.'?calendar='.trim($row->itemID).'"><i class="k-icon-feed" title="Subscribe"></i></a>' : "";
                $icsLink = ($ics !== false && !empty($ics)) ? '<a href="/'.$ics.'?calendar='.trim($row->itemID).'"><i class="k-icon-calendar" title="Add to Calendar"></i></a>' : "";

                echo('<div class="calendarsListItem"><input type="checkbox" name="calendar['.trim($row->itemID).']" id="calendar'.trim($row->itemID).'" value="'.trim($row->itemID).'" '.$checked.' />
                &nbsp;<label class="calendarName" style="color:'.trim($row->eventBackgroundColor).'" for="calendar'.trim($row->itemID).'">'.trim($row->calendarName).'</label>
                '. $rssLink . $icsLink . '</div>');
            }
            echo('</form>');
        }
        //else do nothing b/c no calendars
    }
    /**
    * This method retrieves calendars available for the fullCalendar events feed. URL will have itemID appended to the params list
    * @access public
    * @see get_calendars_common()
    * @return object|false
    */
    public function get_calendars_ajax(){
        $res = $this->get_calendars_common();
        if ($res !== false){
            foreach($res as $row){
                $calendars['cal'.trim($row->itemID)] = trim($row->itemID);
            }
        }
        if (isset($calendars)){
            return json_encode($calendars);
        }
        return "false";
    }
    /**
    * Display event details source for dialog boxes
    * For both admin and public views
    * @access public
    */
    public function display_dlg_events_details($public=false,$allDay="",$start="",$end="",$location="",$description="",$recurrence="",$eventID=""){
        $recurStyle = ($recurrence != "")?"":'style="display:none"';
echo <<<DLGTBL
<table id="tblEventDetails">
<tr id="tr_allDay" style="display:none"><td colspan="2">$allDay</td></tr>
<tr><td><strong>From:</strong></td><td id="tdDateStart">$start</td></tr>
<tr><td><strong>To:</strong></td><td id="tdDateEnd">$end</td></tr>
<tr><td><strong>Location:</strong></td><td id="tdLocation">$location</td></tr>
<tr><td><strong>Description:</strong></td><td id="tdDescription">$description</td></tr>
<tr id="tr_recurring" $recurStyle><td><strong>Event Occurs:</strong></td><td>$recurrence</td></tr>
</table>
DLGTBL;
        if ($public == true){
            $addEventParam = (preg_match("/^([0-9]{1,6})(_)?([0-9]{1,6})?$/",$eventID,$matches))?$matches[1]:"";
            echo("<p id=\"pAddEvent\"><a href=\"/rss/ics?event=$addEventParam\">Add to My Calendar</a></p>");
        }
    }
    /**
    * Build CSS to be displayed in body.
    * This is also called via ajax when calendar main is updated in admin so event colours get updated automatically on calendar
    * @access public
    * @see get_calendars_common()
    */
    public function buildCalendarCSS(){
        $res = $this->get_calendars_common();
        $css = "";
        if ($res !== false){
            foreach($res as $row){
                $css .= "
                .cal_".trim($row->itemID).",
                .fc-event .cal_".trim($row->itemID)." .fc-event-time,
                .cal_".trim($row->itemID)." a{
                        color: ".trim($row->eventBackgroundColor).";
                        background-color: transparent;
                        border:0px;
                }
                .allDay_".trim($row->itemID).",
                .fc-event .allDay_".trim($row->itemID)." .fc-event-time,
                .allDay_".trim($row->itemID)." a{
                        color: ".trim($row->eventTextColor).";
                        background-color: ".trim($row->eventBackgroundColor).";
                        border-color: ".trim($row->eventBackgroundColor).";
                }
                .recur_".trim($row->itemID).",
                .fc-event .recur_".trim($row->itemID)." .fc-event-time,
                .recur_".trim($row->itemID)." a{
                        color: ".trim($row->eventBackgroundColor).";
                        border: 0px;
                        background-color: transparent;
                        background-image: none;
                }
                .recur_allDay_".trim($row->itemID).",
                .fc-event .recur_allDay_".trim($row->itemID)." .fc-event-time,
                .recur_allDay_".trim($row->itemID)." a{
                        color: ".trim($row->eventTextColor).";
                        border-color: ".trim($row->eventBackgroundColor).";
                        background-color: ".trim($row->eventBackgroundColor).";
                        background-image: none;
                        padding: 0px 5px;
                }
                ";
            }
        }
        echo $css;
    }

    /**
    * Create an array of events based on results retrieved from database to be returned to calendar as json encoded
    * This is used for main (parent) events and recurring (child) events
    * @access public
    * @param resource $res
    * @param int $calID
    * @param true|false $recurring
    * @return array
    */
    public function set_event_data($res, $calID, $recurring = false){
        $events = array();
        if ($res !== false){

            foreach($res as $row){
                $itemID = explode("-", $row->ID);
                $meta = get_post_meta($itemID[0]);
                $this->timezone = (isset($meta['_kcal_timezone'])) ? $meta['_kcal_timezone'][0] : get_option('gmt_offset');

                $location = trim($meta["_kcal_location"][0]);
                foreach($meta as $key => $data){
                  if (stristr($key, "location") !== false && $key != "_kcal_location" && $key != "_kcal_locationMap"){
                    $altLocations = unserialize($data[0]);
                    if (is_array($altLocations) && is_numeric($altLocations[0])){
                      $location = get_the_title($altLocations[0]);
                    }
                    else{
                      $location = $altLocations;
                    }
                    break;
                  }
                }

                $eventID = $itemID[0];
                $permalink = get_permalink($eventID);
                if ($recurring === true && count($itemID) == 2){
                    $eventID = $row->ID;
                    $permalink .= "?r=" .$itemID[1];
                }

                try {
                    $timezoneObj = new DateTimeZone($this->timezone);
                } catch (exception $e) {
                    $timezoneObj = new DateTimeZone(get_option('gmt_offset'));
                }

                $dateS = new DateTime('', new DateTimeZone($timezoneObj));
                $dateE = new DateTime('', new DateTimeZone($timezoneObj));



                if ($recurring === true) {
                    $dateS->setTimestamp($row->eventStartDate);
                    $dateE->setTimestamp($row->eventEndDate);
                } else {
                    $dateS->setTimestamp($meta["_kcal_eventStartDate"][0]);
                    if ($meta["_kcal_eventEndDate"][0] <= $meta["_kcal_eventStartDate"][0]) {
                        $dateE->setTimestamp($dateS->getTimestamp() + 3600);
                    } else {
                        $dateE->setTimestamp($meta["_kcal_eventEndDate"][0]);
                    }
                }

                $eventsArray['id'] = $eventID;
                $eventsArray['title'] = preg_replace('%[^A-Za-z0-9\s\_\'\"\?\-\:\&\(\)]*%',"", trim($row->post_title));
                $eventsArray['allDay'] = (isset($meta["_kcal_allDay"][0])) ? (bool)$meta["_kcal_allDay"][0] : false;

                $eventsArray['start'] = $dateS->format('Y-m-d H:i:s');
                $eventsArray['end'] = $dateE->format('Y-m-d H:i:s');

                $eventsArray['className'] = ($eventsArray['allDay'] === false) ? "cal_$calID" : "allDay_$calID";
                $eventsArray['description'] = strip_tags($row->post_content);
                $eventsArray['location'] = $location;
                $eventsArray['altUrl'] = (!empty($meta["_kcal_eventURL"][0])) ? $meta["_kcal_eventURL"][0] : $permalink;
                $eventsArray['recurrence'] = 'None';
                if (isset($meta["_kcal_recurrenceType"][0]) && $meta["_kcal_recurrenceType"][0] != 'None'){
                  $eventsArray['recurrence'] = $meta["_kcal_recurrenceType"][0];
                  $eventsArray['className'] = (false === $eventsArray['allDay']) ? "recur_$calID" : "recur_allDay_$calID";
                  $recurrenceDescription = (isset($meta["_kcal_recurrenceInterval"][0]) && $meta["_kcal_recurrenceInterval"][0] < 2)? $meta["_kcal_recurrenceType"][0] : "Every ".(int) $meta["_kcal_recurrenceInterval"][0]." ".str_replace("ly","s",$meta["_kcal_recurrenceType"][0]);
                  $eventsArray['recurrenceDescription'] = $recurrenceDescription;
                  $eventsArray['recurrenceEnd'] = $meta["_kcal_recurrenceEnd"][0];
                  if ($recurring !== false){$eventsArray['recurrenceID'] = $row->metaID;}
                }
                $events[] = $eventsArray;
            }
        }
        return $events;
    }
    /**
    * Retrieve repeating events based on parent record ID (eventID).
    * Each event returns all of the same information as parent event
    * @access protected
    * @param array $get
    * @see DB::result_please()
    * @see set_event_data()
    * @return array
    */
    protected function get_repeating_events($get, $posts){
        if (preg_match("/^[0-9]{1,6}$/",intVal($get["calendar"],10),$matches)){
            $res = array();
            foreach($posts as $event){
                $recur = get_post_meta($event->ID, "_kcal_recurrenceDate");
                if ($recur !== false){

                    foreach($recur as $rEvent){
                        $startTime = array_keys($rEvent);
                        list($endTime, $metaID) = array_values($rEvent[$startTime[0]]);
                        $recurData = (array) $event;
                        $recurData["eventStartDate"] = $startTime[0];
                        $recurData["eventEndDate"] = $endTime;
                        $recurData["metaID"] = $metaID;
                        $res[] = (object) $recurData;

                    }

                }
            }
            $events = $this->set_event_data($res,intVal($get["calendar"],10),true);
        }
        if (isset($events)){
            return $events;
        }
        return array();
    }
    /**
    * Return the calendar name and background colour for each active calendar
    * @access public
    * @see get_calendars_common()
    * @return void|array
    */
    public function get_calendar_details(){
        $res = $this->get_calendars_common();
        $calendar = array();
        if ($res !== false){
            foreach($res as $row){
                $calendar[trim($row->itemID)]["name"] = trim($row->calendarName);
                $calendar[trim($row->itemID)]["colour"] = trim($row->eventBackgroundColor);
                $calendar[trim($row->itemID)]["text"] = trim($row->eventTextColor);
            }
        }
        return $calendar;
    }
    /**
    * Retrieve event details based on event ID sent from a widget on a page different from the main calendar
    * @access public
    * @param array $get
    * @see DB::result_please()
    * @see set_event_data()
    * @return void|array
    */
    public function get_event_details_byID($get){
        $event = array();
        if (isset($get["event"]) && preg_match("/^([0-9]{1,6})(\-)?([0-9]{1,})?$/",$get["event"],$matches)){
            if (isset($matches[1])){
                $mainPost = get_post($matches[1]);
                if (isset($matches[3])){
                    $event = $this->get_repeating_events($get, $mainPost);
                }
                else{
                    $event = $this->set_event_data($mainPost, $get["calendar"], false);
                }
            }

        }
        return $event;
    }
}
