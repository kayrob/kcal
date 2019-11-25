<?php

/**
* Admin methods for the events calendar. Not to be used without /includes/apps/calendar/Calendar.php
* created: November 11, 2010 by Karen Laansoo
* @package apps/calendar
*/
class AdminCalendar extends Calendar {

    protected $tagClass;
    protected $permClass;

    /**
    * @var array $recurrenceOpts
    * @access protected
    */
    protected $recurrenceOpts = array("None","Daily","Weekly","Monthly","Yearly");

    public function __construct(){

        //$this->permClass = new CalendarPermissions();
        parent::__construct();
    }

    public function kCal_save_meta_box_data($post_id){
        /*
            * We need to verify this came from our screen and with proper authorization,
            * because the save_post action can be triggered at other times.
            */
            // Check if our nonce is set.
            if (!isset($_POST["kCal_mb_nonce"])) {
                   return;
            }
            // Verify that the nonce is valid.
            if (!wp_verify_nonce($_POST["kCal_mb_nonce"], "kcal_meta_box")) {
                   return;
            }
            // If this is an autosave, our form has not been submitted, so we don't want to do anything.
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                   return;
            }

            // Check the user's permissions.
            $screenType = (isset($_GET["post_type"]) && $_GET["post_type"] == "event") ? "event" : "";
            if (empty($screenType) && isset($_GET["post"])){
                $screenType = get_post_type($_GET["post"]);
            }
            if ($screenType == "event") {
                if (!current_user_can( "edit_events", $post_id)) {
                    return;
                }
            } else {
                if (!current_user_can("edit_events", $post_id)) {
                    return;
                }
            }
            /* OK, it's safe for us to save the data now. */

            // Update the meta field in the database.

            $meta = array(
                "_kcal_eventStartDate"      => $_POST["_kcal_eventStartDate"],
                "_kcal_eventEndDate"        => $_POST["_kcal_eventEndDate"],
                "_kcal_eventURL"            => $_POST["_kcal_eventURL"],
                "_kcal_location"            => $_POST["_kcal_location"],
                "_kcal_recurrenceType"      => $_POST["_kcal_recurrenceType"],
                "_kcal_recurrenceInterval"  => $_POST["_kcal_recurrenceInterval"],
                "_kcal_recurrenceEnd"       => $_POST["_kcal_recurrenceEnd"],
                "_kcal_eventStartTime"      => $_POST["_kcal_eventStartTime"],
                "_kcal_eventEndTime"        => $_POST["_kcal_eventEndTime"],
                "_kcal_locationMap"         => $_POST["_kcal_locationMap"]

            );

            $this->update_event($meta, $post_id);
    }

    /**
    * Returns the recurrence interval and recurrence end date. Values are auto set if information is not provided from web form
    * @access protected
    * @param string $recurrence
    * @param string $interval
    * @param string $endDate
    * @return array
    */
    protected function set_recurrence_values($recurrence,$interval,$endDate){
            $recurrenceInterval = 0;
            $recurrenceEnd = "Null";
            if ($recurrence != "None"){
                    $recurrenceInterval = (isset($interval) && preg_match("/^[0-9]{1,3}$/",trim($interval),$matches))?intval(trim($interval),10):1;
                    $recurrenceEnd = (isset($endDate) && preg_match("/^20[0-9]{2}([-])(0[1-9]|1[012])([-])([012][0-9]|3[01])$/",trim($endDate),$matches))? $endDate :date("Y")."-12-31";
            }
            return array($recurrenceInterval,$recurrenceEnd);
    }
    /**
    * Set event start and end times based on time of day selected from admin web forms
    * @access public
    * @param array $post
    * @see DB_MySQL::escape()
    * @return array
    */
    public function format_add_edit_dateTime($meta){

        if (strtotime($meta["_kcal_eventEndDate"]) < strtotime($meta["_kcal_eventStartDate"])) {
            $meta["_kcal_eventEndDate"] = trim($meta["_kcal_eventStartDate"]);
        }

        $eventStartTime = (isset($meta["_kcal_eventStartTime"]))? $meta["_kcal_eventStartTime"] : "12:00 AM";
        $eventEndTime = (isset($meta["_kcal_eventEndTime"])) ? $meta["_kcal_eventEndTime"] : "12:15 AM";

        $timezone = get_option('gmt_offset');
        $date = new DateTime(trim($meta["_kcal_eventStartDate"])." ".$eventStartTime, new DateTimeZone($timezone));
        $date2 = new DateTime(trim($meta["_kcal_eventEndDate"])." ".$eventEndTime, new DateTimeZone($timezone));

        $startDate = $date->format('U');
        $endDate = $date2->format('U');
        if ($startDate == $endDate){
            $endDate += (60 * 15);
        }

        return array($startDate,$endDate);
    }
    /**
    * Method to create an associative array of event fields/values
    * Used for both edit event and add new event forms
    * @access protected
    * @param array $post
    * @see format_add_edit_dateTime()
    * @see set_recurrence_values()
    * @return array
    */
    protected function create_edit_event_values($meta, $post_id){

        $values = array();
        list($values["_kcal_eventStartDate"],$values["_kcal_eventEndDate"]) = $this->format_add_edit_dateTime($meta);
        $values["_kcal_location"] = esc_textarea($meta["_kcal_location"]);
        $values["_kcal_recurrenceType"] = "None";
        if (isset($meta["_kcal_recurrenceType"]) && in_array(trim($meta["_kcal_recurrenceType"]),$this->recurrenceOpts) != false){
            $values["_kcal_recurrenceType"] = trim($meta["_kcal_recurrenceType"]);
        }
        if (isset($meta["_kcal_recurrenceInterval"]) && isset($meta["_kcal_recurrenceEnd"])){
            list($values["_kcal_recurrenceInterval"],$values["_kcal_recurrenceEnd"]) = $this->set_recurrence_values($meta["_kcal_recurrenceType"],$meta["_kcal_recurrenceInterval"],$meta["_kcal_recurrenceEnd"], $post_id);
        }
        $values["_kcal_allDay"] = (isset($meta["_kcal_allDay"]) && trim($meta["_kcal_allDay"]) == "1")?"1":"0";
        $values["_kcal_eventURL"] = esc_url($meta["_kcal_eventURL"]);
        $values["_kcal_locationMap"] = esc_url($meta["_kcal_locationMap"]);
        return $values;
    }

    /**
    * Create an array of recurring event start and end dates for yearly recurrences
    * @access protected
    * @param string $eventID
    * @param string $recurrence
    * @param integer $interval
    * @param string $recurEnd
    * @param string $firstEventStart
    * @param string $firstEventEnd
    * @see create_timestamp()
    * @return array|void
    */
    protected function create_recurring_yearly_dates($post_id,$recurrence,$interval,$recurEnd,$firstEventStart,$firstEventEnd){
        $events = array();
        if (preg_match("/^[0-9]{1,6}$/",$post_id,$matches)){
            $recurrenceEndDate = (!empty($recurEnd)) ? strtotime(trim($recurEnd)." ".date("g:i a",$firstEventEnd)) : mktime(0,0,0,12,31,date("Y"));
            $firstEventStartTime = $firstEventStart;
            $firstEventEndTime = $firstEventEnd;

            if ($recurrenceEndDate > $firstEventEndTime){
                $numYears = date("Y",$recurrenceEndDate) - date('Y',$firstEventEndTime);
                if ($numYears > 0){
                    $dayOfWeekEventStart = date("w",$firstEventStartTime);
                    $dateDiffStart = floor(date("j",$firstEventStartTime) - 1);
                    $weekNumStart = ceil($dateDiffStart/7);
                    for ($y = 1; $y <= $numYears; $y++){
                        $nextYearStartTime = mktime(0,0,0,date("m",$firstEventStartTime),1,date("Y",$firstEventStartTime)+($y*$interval));
                        $newDayOfWeek = 1;
                        if (date("w",$nextYearStartTime) > $dayOfWeekEventStart){
                            $newDayOfWeekStart = 1 + ((7+$dayOfWeekEventStart) - date("w",$nextYearStartTime));
                        }
                        else if (date("w",$nextYearStartTime) < $dayOfWeekEventStart){
                            $newDayOfWeekStart = (1 + ($dayOfWeekEventStart - date("w",$nextYearStartTime)));
                        }
                        $newDayOfWeekStart += ($weekNumStart*7 - 7);
                        $events[-1+$y]["eventStartDate"] = mktime(0,0,0,date("m",$nextYearStartTime),$newDayOfWeekStart,date("Y",$nextYearStartTime));
                        $events[-1+$y]["eventEndDate"] =  $events[-1+$y]["eventStartDate"] + ($firstEventEndTime - $firstEventStartTime);
                    }
                }
            }
        }
        return $events;
    }
    /**
    * Create an array of recurring event start and end dates for monthly recurrences
    * @access protected
    * @param string $eventID
    * @param string $recurrence
    * @param integer $interval
    * @param string $recurEnd
    * @param string $firstEventStart
    * @param string $firstEventEnd
    * @see create_timestamp()
    * @return array
    */
    protected function create_recurring_monthly_dates($post_id,$recurrence,$interval,$recurEnd,$firstEventStart,$firstEventEnd){
        $events = array();
        if (preg_match("/^[0-9]{1,6}$/",$post_id,$matches)){
            $recurrenceEndDate = (!empty($recurEnd)) ? strtotime(trim($recurEnd)." ".date("g:i a",$firstEventEnd)) : mktime(0,0,0,12,31,date("Y"));
            $firstEventStartTime = $firstEventStart;
            $firstEventEndTime = $firstEventEnd;

            if ($recurrenceEndDate > $firstEventEndTime){
                $numMonths = ceil(($recurrenceEndDate - $firstEventEndTime)/(60*60*24*7*4));
                $numRecurEvents = $numMonths/$interval;
                if ($numRecurEvents > 0){
                    $dayOfWeekEventStart = date("w",$firstEventStartTime);
                    $dateDiffStart = floor(date("j",$firstEventStartTime) - 1);
                    $weekNumStart = ceil($dateDiffStart/7);
                    for ($m = 1; $m <= $numRecurEvents; $m++){
                        $nextMonthStart = date("n",$firstEventStartTime) + ($interval*$m);
                        $nextMonthStartTime = ($nextMonthStart > 12)?mktime(0,0,0,($nextMonthStart - 12),1,date("Y",$firstEventStartTime)+1):mktime(0,0,0,$nextMonthStart,1,date("Y",$firstEventStartTime));
                        $newDayOfWeekStart = 1;
                        if (date("w",$nextMonthStartTime) > $dayOfWeekEventStart){
                            $newDayOfWeekStart = 1 + ((7+$dayOfWeekEventStart) - date("w",$nextMonthStartTime));
                        }
                        else if (date("w",$nextMonthStartTime) < $dayOfWeekEventStart){
                            $newDayOfWeekStart = (1 + ($dayOfWeekEventStart - date("w",$nextMonthStartTime)));
                        }
                        $newDayOfWeekStart += ($weekNumStart*7 - 7);
                        $events[-1+$m]["eventStartDate"] = mktime(0,0,0,date("m",$nextMonthStartTime),$newDayOfWeekStart,date("Y",$nextMonthStartTime));
                        $events[-1+$m]["eventEndDate"] =  $events[-1+$m]["eventStartDate"] + ($firstEventEndTime - $firstEventStartTime);
                    }
                }
            }
        }
        return $events;
    }
    /**
    * Create an array of recurring event start and end dates for daily and weekly recurrences
    * @access protected
    * @param string $eventID
    * @param string $recurrence
    * @param integer $interval
    * @param string $recurEnd
    * @param string $firstEventStart
    * @param string $firstEventEnd
    * @see create_timestamp()
    * @return array
    */
    protected function create_recurring_daily_weekly_dates($post_id,$recurrence,$interval,$recurEnd,$firstEventStart,$firstEventEnd){
        $events = array();
        if (preg_match("/^[0-9]{1,6}$/",$post_id,$matches)){
            $recurrenceEndDate = (!empty($recurEnd)) ? strtotime(trim($recurEnd)." ".date("g:i a",$firstEventEnd)) : mktime(0,0,0,12,31,date("Y"));
            $firstEventStartTime = $firstEventStart;
            $firstEventEndTime = $firstEventEnd;

            if ($recurrenceEndDate > $firstEventEndTime){
                $recurrenceFactor = array("Daily" => 1, "Weekly" => 7);
                $oneDay = 60*60*24;
                $intervalFactor = $oneDay * $recurrenceFactor[$recurrence] * $interval;
                $nextInstanceStart = $firstEventStartTime + $intervalFactor;
                $nextInstanceEnd = $firstEventEndTime + $intervalFactor;
                $j = 1;
                while ($nextInstanceEnd <= $recurrenceEndDate){
                    $events[-1+$j]["eventStartDate"] = $nextInstanceStart;
                    $events[-1+$j]["eventEndDate"] = $nextInstanceEnd;
                    $j++;
                    $nextInstanceStart += $intervalFactor;
                    $nextInstanceEnd += $intervalFactor;
                }
            }
        }
        return $events;
    }
    /**
    * Create (insert) recurring events. Method called is based on recurrence type
    * @access protected
    * @param string $eventID
    * @param string $recurrence
    * @param integer $interval
    * @param string $recurEnd
    * @param string $firstEventStart
    * @param string $firstEventEnd
    * @see create_recurring_monthly_dates()
    * @see create_recurring_yearly_dates()
    * @see create_recurring_daily_weekly_dates()
    * @see add_recurring_dates()
    * @return string
    */
    protected function create_recurring_dates($post_id,$recurrence,$interval,$recurEnd,$firstEventStart,$firstEventEnd){
        $recurAdded = false;
        if (preg_match("/^[0-9]{1,6}$/",$post_id,$matches)){
            $events = array();
            $res = 0;
            switch($recurrence){
                case "Monthly":
                    $events = $this->create_recurring_monthly_dates($post_id,$recurrence,$interval,$recurEnd,$firstEventStart,$firstEventEnd);
                    break;
                case "Yearly":
                    $events = $this->create_recurring_yearly_dates($post_id,$recurrence,$interval,$recurEnd,$firstEventStart,$firstEventEnd);
                    break;
                default:
                    $events = $this->create_recurring_daily_weekly_dates($post_id,$recurrence,$interval,$recurEnd,$firstEventStart,$firstEventEnd);
                    break;
            }

            if (!empty($events)){
                $res = 0;
                for ($e = 0; $e < count($events); $e++){
                    $key = $events[$e]["eventStartDate"];
                    $recurInfo = array($key => array("endDate" => $events[$e]["eventEndDate"], "metaID" => 0));
                    $metaID = add_post_meta($post_id, "_kcal_recurrenceDate", $recurInfo, false);
                    if ($metaID !== false){
                        $oldRecurInfo = $recurInfo;
                        $recurInfo[$key]["metaID"] = $metaID;
                        update_post_meta($post_id, "_kcal_recurrenceDate", $recurInfo, $oldRecurInfo);
                        $res += 1;
                    }
                    else{
                        delete_post_meta($post_id, "_kcal_recurrenceDate", $recurInfo);
                    }
                }
            }
            if ($res == count($events)){
                $recurAdded = true;
            }
        }
        return $recurAdded;
    }
    /**
    * Verify that data submitted via add new event, or edit event is valid before saving changes
    * @access protected
    * @param array $meta
    * @return true|string
    */
    protected function verify_edit_add_event_data($meta){
        $verified = false;

        if (isset($meta["_kcal_eventStartDate"]) && strtotime($meta["_kcal_eventStartDate"]) !== false
        && isset($meta["_kcal_eventEndDate"]) && strtotime($meta["_kcal_eventEndDate"]) !== false &&
        isset($meta["_kcal_eventStartTime"]) && preg_match("/^(0?[0-9]|1[012])[\:]([012345][0-9])\s([AaPp][mM])$/",trim($meta["_kcal_eventStartTime"]))
        && isset($meta["_kcal_eventEndTime"]) && preg_match("/^(0?[0-9]|1[012])[\:]([012345][0-9])\s([AaPp][mM])$/",trim($meta["_kcal_eventEndTime"]))){
            $verified = true;
        }

        return $verified;
    }
    /**
    * If a recurring event is modified and "all instances" selected, then start and end date for each child + parent is retrieved so start date can be updated
    * @access protected
    * @param string $table
    * @param string $itemID
    * @param string $whereField
    * @see DB::result_please
    * @return array|void
    */
    protected function get_event_date($table,$itemID,$whereField){
        //$res = $this->db->result_please($itemID,$table,"$table.eventStartDate,$table.eventEndDate");
        $res = $this->db->get_results(
                sprintf("SELECT `eventStartDate`, `eventEndDate` FROM %s WHERE `itemID` = %d",
                    $table, $itemID)
            );
        if ($res != false){
            foreach($res as $row){
                list($startDate,$startTime) = explode(" ",trim($row->eventStartDate));
                list($endDate,$endTime) = explode(" ",trim($row->eventEndDate));
            }
            return array($startDate,$endDate);
        }
    }
    /**
    * Main method to update an event. Recurring (child) events will be deleted and reset if recurrence is set
    * @access public
    * @param array $post
    * @see verify_edit_add_event_data()
    * @see create_edit_event_values()
    * @see update_event_main()
    * @see delete_events_recurring()
    * @see create_recurring_dates()
    * @return string
    */
    public function update_event($meta, $post_id){
        $validData = false;

        if (preg_match("/^\d{1,}$/",trim($post_id),$matches) && is_admin() && current_user_can("edit_events")){

            $validData = $this->verify_edit_add_event_data($meta);

            if ($validData == true){
                $dataSet = $this->create_edit_event_values($meta, $post_id);
                if (isset($dataSet) && is_array($dataSet)){
                    foreach($dataSet as $field => $value){
                        update_post_meta($post_id, $field, $value);
                    }
                    //recurrence - delete old values first
                    delete_post_meta($post_id, "_kcal_recurrenceDate");
                    if ($dataSet["_kcal_recurrenceType"] != "None"){
                        $this->create_recurring_dates($post_id,trim($dataSet["_kcal_recurrenceType"]),$dataSet["_kcal_recurrenceInterval"],$dataSet["_kcal_recurrenceEnd"],$dataSet["_kcal_eventStartDate"],$dataSet["_kcal_eventEndDate"]);
                    }
                }
            }
        }
        return $validData;
    }
    /**
    * Main method accessed by ajax request to update recurring events
    * Parent events are updated if "all instances" was selected
    * @access public
    * @param array $post
    * @see format_add_edit_dateTime()
    * @see verify_edit_add_event_data()
    * @return string
    */
    public function update_recurring_events($post){
        $validData = "Events could not be updated.";
        global $wpdb;
        if (isset($post["recurrenceID"]) && isset($post["eventID"]) && preg_match("/^(\d{1,})(\-".$post["recurrenceID"].")$/",trim($post["eventID"]),$matches) && preg_match("/^[0-9]{1,}$/",trim($post["recurrenceID"]),$match)){
            if (is_admin() && current_user_can("edit_events", $matches[1])){
                $post["eventID"] = $matches[1];
                $post["_kcal_eventStartTime"] = $post["_kcal_recurStartTime"];
                $post["_kcal_eventEndTime"] = $post["_kcal_recurEndTime"];
                $post["_kcal_eventStartDate"] = $post["_kcal_recur_eventStartDate"];
                $post["_kcal_eventEndDate"] = $post["_kcal_recur_eventEndDate"];

                if (!isset($post["recurEdit"]) || trim($post["recurEdit"]) == "this"){

                    if ($this->verify_edit_add_event_data($post)){
                        list($startDateTime, $endDateTime) = $this->format_add_edit_dateTime($post);
                        $metaRes = $wpdb->get_var($wpdb->prepare("SELECT `meta_value` FROM `{$wpdb->prefix}postmeta` WHERE `meta_id` = %d", $post["recurrenceID"] ));
                        if (!is_null($metaRes) || !empty($metaRes) || false !== $metaRes){
                            $oldMeta = unserialize($metaRes);
                            $newMeta = array($startDateTime => array("endDate" => $endDateTime, "metaID" => $post["recurrenceID"]));
                            $saved = update_post_meta($post["eventID"], "_kcal_recurrenceDate", $newMeta, $oldMeta);
                            if ((bool) $saved !== false){
                                $validData = "true";
                            }
                        }
                    }
                    else{
                        $validData .= "Date and/or Time is not the correct format.";
                    }
                }
                else{
                    if ($this->verify_edit_add_event_data($post)){
                        $recurringEvents = get_post_meta($post["eventID"], "_kcal_recurrenceDate");
                        $updated = 0;
                        foreach($recurringEvents as $rData){
                            $oldMeta = $rData;
                            $startTime = array_keys($rData);
                            $post["_kcal_eventStartDate"] = date("Y-m-d", $startTime[0]);
                            list($endTime, $metaID) = array_values($rData[$startTime[0]]);
                            $post["_kcal_eventEndDate"] = date("Y-m-d", $endTime);
                            list($newStartTime,$newEndTime) = $this->format_add_edit_dateTime($post);
                            $newMeta = array($newStartTime => array("endDate" => $newEndTime, "metaID" => $metaID));
                            if (false !== update_post_meta($post["eventID"], "_kcal_recurrenceDate",$newMeta, $oldMeta)){
                                $updated++;
                            }
                        }
                        if ($updated == count($recurringEvents)){
                            $validData = "true";
                        }
                    }
                    else{
                        $validData .= "Date and/or Time is not the correct format.";
                    }
                }
            }
        }
        else{ $validData = 'no event or calendar selected';}
        return $validData;
    }

    /**
    *Method accessed by php and ajax to display list of available calendars in admin view
    *List is reloaded when a new calendar is added
    *@access public
    *@see get_calendars_common()
    *@return string
    */
    public function display_calendar_list_admin(){
        $res = $this->get_calendars_common();
        $calsList = '<p id="calsList">There are no calendars</p>';
        if ($res != false){
            $calsList = '<ul id="calsList">';
            foreach ($res as $row){
                $checked = ((int)$row->eventCount > 0)?'checked="checked"':'';
                $calsList .= '<li style="color:'.trim($row->eventBackgroundColor).';font-weight:bold">
                <div class="calendarsListItem"><input type="checkbox" style="margin-right:5px;" id="calendarInfo'.trim($row->itemID).'" name="calendarInfo[]" '.$checked.' value="'.trim($row->itemID).'" /><label for="calendarInfo'.trim($row->itemID).'">'.trim($row->calendarName).
                '</label></div></li>'; //add onclick event
            }
            $calsList .= '</ul>';
        }
        return $calsList;
    }
    /**
    * Method to display calendars available in add/edit event form
    * @access public
    * @see get_calendars_common()
    * @return string
    */
    public function display_calendar_list_events_form(){
        $res = $this->get_calendars_common();
        $calsList = "";
        if ($res != false){
            foreach($res as $row){
                $calsList .= '<option value="'.trim($row->itemID).'" style="color:'.trim($row->eventBackgroundColor).'">'.trim($row->calendarName).'</option>';
            }
        }
        return $calsList;
    }
    /**
     * Ajax delete an event from the calendar
     * @global object $wpdb
     * @param array $post
     * @return string
     */
    public function delete_events_main($post){
        $validData = "Event could not be deleted";
        if (isset($post["eventID"]) && preg_match("/^(\d{1,})(\-)?(\d)*$/",trim($post["eventID"]),$matches)){
            if (is_admin() && current_user_can("edit_events", $matches[1])){
                if (isset($post["recurrenceID"]) && preg_match("/^(\d{1,})$/",trim($post["recurrenceID"]),$matchR)){
                    if (!isset($post["recurDelete"]) || trim($post["recurDelete"]) == "this"){
                        global $wpdb;
                        if (false !== $wpdb->delete("{$wpdb->prefix}postmeta", array("meta_id" => $post["recurrenceID"]))){
                            $validData = "true";
                        }
                    }
                    else{
                        if (false !== delete_post_meta($matches[1], "_kcal_recurrenceDate")){
                            $validData = "true";
                        }
                    }
                }
                else{
                    delete_post_meta($matches[1], "_kcal_recurrenceDate");
                    if (false !== wp_delete_post($matches[1])){
                       $validData = "true";
                    }

                }
            }
        }

        return $validData;
    }
    /**
     * Ajax request to drag and drop from the calendar view
     * @global object $wpdb
     * @param array $post
     * @return string
     */
    public function drag_drop_event($post){
        $validData = false;
        if (isset($post["eventID"]) && preg_match("/^(\d{1,})$/",trim($post["eventID"]),$matches) && isset($post["_kcal_dropStartDate"])){
            if (is_admin() && current_user_can("edit_events", $post["eventID"])){
                $newStartDate = strtotime(substr($post["_kcal_dropStartDate"],0,21));
                $newEndDate = strtotime(substr($post["_kcal_dropEndDate"],0,21));
                if ($newStartDate !== false && $newEndDate !== false && $newEndDate > $newStartDate){
                    if (isset($post["recurrenceID"]) && preg_match("/^(\d{1,})$/",trim($post["recurrenceID"]),$rMatch)){
                        global $wpdb;
                        $metaRes = $wpdb->get_var($wpdb->prepare("SELECT `meta_value` FROM `{$wpdb->prefix}postmeta` WHERE `meta_id` = %d", $post["recurrenceID"] ));
                        if (!is_null($metaRes) || !empty($metaRes) || false !== $metaRes){
                            $oldMeta = unserialize($metaRes);

                            $newMeta = array($newStartDate => array("endDate" => $newEndDate, "metaID" => $post["recurrenceID"]));
                            $saved = update_post_meta($post["eventID"], "_kcal_recurrenceDate", $newMeta, $oldMeta);
                            if ((bool) $saved !== false){
                                $validData = "true";
                            }
                        }
                    }
                    else{
                        update_post_meta($post["eventID"], "_kcal_eventStartDate", $newStartDate);
                        update_post_meta($post["eventID"], "_kcal_eventEndDate", $newEndDate);
                    }
                }
            }
        }
        return $validData;
    }

    public function import_parse_RSS($url, $calendar){
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $xmlresponse = curl_exec($ch);
      $source = simplexml_load_string($xmlresponse);

      $uploaded = array();
      if (isset($source->channel)){
        foreach ($source->channel->item as $event){
          $eventDate = strtotime($event->{"pubDate"});
          $description = (string)$event->{"description"};

          if ($eventDate >= current_time("timestamp")){
            $exists = get_page_by_title((string)$event->{"title"}, OBJECT, "event");
            $eventTime = strtotime(date("h:i A", strtotime((string)$event->pubDate)));
            $content =  strip_tags($description, "<p><br><strong><a><img>");
            $filtered = preg_replace("/\s(class|style|font)\=(\'\")[^\>](\'\")/", "", $content);

            $newEvent["post_content"] = $filtered;
            $newEvent["post_content_filtered"] = strip_tags((string)$event->{"description"});
            $newEvent["post_status"] = "publish";
            $newEvent["post_type"] = "event";
            $newEvent["post_title"] = (string)$event->{"title"};
            $newEvent["comment_status"] = "closed";
            $newEvent["ID"] = (isset($exists->ID) && $exists->ID > 0) ? $exists->ID : 0;
            $newEvent["meta_input"] = array(
                "_kcal_eventStartDate"      => $eventDate,
                "_kcal_eventEndDate"        => $eventDate,
                "_kcal_eventURL"            => (string)$event->{"link"},
                "_kcal_location"            => "",
                "_kcal_recurrenceType"      => "None",
                "_kcal_recurrenceInterval"  => 0,
                "_kcal_recurrenceEnd"       => "",
                "_kcal_eventStartTime"      => $eventTime,
                "_kcal_eventEndTime"        => $eventTime + (60*60*4),
                "_kcal_locationMap"         => ""
            );

            $created = wp_insert_post($newEvent);
            if (!is_wp_error($created)){
              if ($calendar > 0){
                wp_set_post_terms($created, array($calendar), "calendar", false);
              }
              $uploaded["success"][] = "Event: <a href=\"".admin_url("post.php?post=".$created."&action=edit")."\">" . (string)$event->{"title"} . "</a> was imported.";
            }
            else{
              $uploaded["error"][] = "Event: " . (string)$event->{"title"} . " was not imported";
            }
          }
        }
      }
      else{
        $uploaded["error"][] = "RSS events could not be imported. Check the URL and retry";
      }
      if (isset($uploaded["success"])){
        $uploaded["success"][] = "RSS Content can't be guaranteed. Events may need to be updated for content and dates.";
      }
      return $uploaded;
    }
    public function import_parse_ICS($icsFile, $calendar){
      $uploaded = array();
      if (file_exists($icsFile)){
        $content = file_get_contents($icsFile);
        if (!empty($content) && strstr($content, "BEGIN:") !== false){
          $lines = explode("\n", $content);
          $newEvent["post_content"] = "";
          $newEvent["post_content_filtered"] = "";
          $newEvent["post_status"] = "publish";
          $newEvent["post_type"] = "event";
          $newEvent["post_title"] = "";
          $newEvent["comment_status"] = "closed";
          $newEvent["ID"] = 0;
          $newEvent["meta_input"] = array(
              "_kcal_eventStartDate"      => "",
              "_kcal_eventEndDate"        => "",
              "_kcal_eventURL"            => "",
              "_kcal_location"            => "",
              "_kcal_recurrenceType"      => "None",
              "_kcal_recurrenceInterval"  => 0,
              "_kcal_recurrenceEnd"       => "",
              "_kcal_eventStartTime"      => "",
              "_kcal_eventEndTime"        => "",
              "_kcal_locationMap"         => ""
          );
          $timezone = new DateTimeZone(get_option('gmt_offset'));
          foreach($lines as $info){
            if (!empty($info)){
              $icsData = explode(":", $info);
              if (count($icsData) > 1){
                if (strstr($icsData[0], "DTSTART") !== false){
                  $dateStart = new DateTime($icsData[1], $timezone);
                  $newEvent["meta_input"]["_kcal_eventStartDate"] = $dateStart->getTimestamp();
                  $newEvent["meta_input"]["_kcal_eventStartTime"] = $dateStart->format("g:i A");
                }
                if (strstr($icsData[0], "DTEND") !== false){
                  $dateEnd = new DateTime($icsData[1], $timezone);
                  $newEvent["meta_input"]["_kcal_eventEndDate"] = $dateEnd->getTimestamp();;
                  $newEvent["meta_input"]["_kcal_eventEndTime"] = $dateEnd->format("g:i A");
                }
                if (strstr($icsData[0], "SUMMARY") !== false){
                  $newEvent["post_title"] = $icsData[1];
                }
                if (strstr($icsData[0], "DESC") !== false && empty($newEvent["meta_input"]["post_content"])){
                  $newEvent["post_content"] .= "<p>".nl2br(stripslashes($icsData[1]))."</p>";
                  $newEvent["post_content_filtered"] .= $icsData[1];
                }
                if (strstr($icsData[0], "LOCATION") !== false && empty($newEvent["meta_input"]["_kcal_location"])){
                  $newEvent["meta_input"]["_kcal_location"] = $icsData[1];
                }
                if (strstr($icsData[0], "ORGANIZER") !== false){
                  $newEvent["post_content"] .= "<p>".$icsData[1]."</p>";
                  $newEvent["post_content_filtered"] .= $icsData[1];
                }
              }
            }
          }
          if ($newEvent["meta_input"]["_kcal_eventStartTime"] == $newEvent["meta_input"]["_kcal_eventEndTime"]){
            $dateEnd->setTimestamp( $newEvent["meta_input"]["_kcal_eventEndDate"] + (60*60*4) );
            $newEvent["meta_input"]["_kcal_eventEndTime"] = $dateEnd->format("g:i A");
          }
          $exists = get_page_by_title($newEvent["post_title"], OBJECT, "event");
          $newEvent["ID"] = (isset($exists->ID) && $exists->ID > 0) ? $exists->ID : 0;
          $created = wp_insert_post($newEvent);
          if (!is_wp_error($created)){
            if ($calendar > 0){
              wp_set_post_terms($created, array($calendar), "calendar", false);
            }
            $uploaded["success"][] = "Event: <a href=\"".admin_url("post.php?post=".$created."&action=edit")."\">" . $newEvent["post_title"] . "</a> was imported.";
          }
          else{
            $uploaded["error"][] = "Event: " . $newEvent["post_title"] . " was not imported";
          }
          unlink($icsFile);
        }
      }
      return $uploaded;
    }
}