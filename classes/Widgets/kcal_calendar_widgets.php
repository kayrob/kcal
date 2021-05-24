<?php
/**
 * calendarWidgets class displays widgets for public viewing of events
 * Date created: Nov 29 2010 by Karen Laansoo
 *
 * @package apps/calendar
 */
if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!class_exists('CalendarWidgets') && class_exists('Calendar')) {
	class CalendarWidgets extends Calendar {
		/**
		* Retrieves all events from events and recurring events table by UNION. Start and End date are pre-set if not provided
		* @access public
		* @param string $startDate
		* @param string $endDate
		* @return resource
		*/
		public function retrieve_all_events($startDate = false, $endDate = false, $calendarID = 0, $showPrivate = false, $customMetaQuery = array())
		{

			try {
				$timezoneObj = new \DateTimeZone($this->timezone);
			} catch (exception $e) {
				$timezoneObj = new \DateTimeZone(get_option('gmt_offset'));
			}

			$date = new DateTime('', $timezoneObj);
			$current = $date->getTimestamp();
			$today = mktime(0,0,0, $date->format('n'), $date->format('j'), $date->format('Y'));


			$whereDate = ($startDate == false) ? $today : $startDate;


			$whereEndOp = ($endDate == false) ? ">=" : "<=";
			$whereEndStr = ($endDate == false) ? $whereDate : $endDate;

			$metaQuery = array(
				"relation"  => "AND",
				array(
					"key"       => "_kcal_eventStartDate",
					"value"     => $whereDate,
					"compare"   => ">="

				),
				array(
					"key"       => "_kcal_eventEndDate",
					"value"     => $whereEndStr,
					"compare"   => $whereEndOp
				)
			);

			$recurQuery = array(
				"relation"  => "AND",
				array(
					"key"       => "_kcal_recurrenceEnd",
					"value"     => $whereDate, //$whereEndStr,
					"compare"   => ">" //$whereEndOp
					),
				array(
					"key"       => "_kcal_recurrenceType",
					"value"     => "None",
					"compare"   => "Not In"
					)
			);

			if (is_array($customMetaQuery) && !empty($customMetaQuery)){
			foreach($customMetaQuery as $cmq){
				if (isset($cmq["key"])){
				array_push($metaQuery, $cmq);
				array_push($recurQuery, $cmq);
				}
			}
			}

			$postStatus = array("publish");
			if ($showPrivate === true){
				$postStatus[] = "private";
			}
			$args = array(
				"post_type"     => "event",
				"post_status"   => $postStatus,
				"numberposts"  => -1,
				"order_by"      => "_kcal_eventStartDate",
				"order"         => "ASC",
				"meta_query"    => $metaQuery,
				"posts_per_page"	=> -1
			);

			if (is_numeric($calendarID) && (int)$calendarID > 0){
				$taxQuery = array(
				array(
					"taxonomy"  => "calendar",
					"field"     => "term_id",
					"terms"     => (int) $calendarID
					)
				);
				$args["tax_query"] = $taxQuery;
			}

			$recurArgs = $args;
			$recurArgs["meta_query"] = $recurQuery;

			$events = query_posts($args);
			wp_reset_query();
			$recurEvents = query_posts($recurArgs);
			wp_reset_query();

			$res = array();
			$allEvents = array();
			if (false !== $events){
				$allEvents = $events;
			}
			if (false !== $recurEvents){
				foreach($recurEvents as $re){
					$allEvents[] = $re;
				}
			}
			if (!empty($allEvents)){
				foreach($allEvents as $event){
					$eventID = $event->ID;
					$recurStart = 0;
					$recurEnd = 0;
					$eventMeta = get_post_meta($event->ID);
					$eventCalendar = wp_get_post_terms($event->ID, "calendar");

					if (isset($eventCalendar[0]->term_id)){
						$event->calendarID = $eventCalendar[0]->term_id;

						if ($calendarID !== false) {
							foreach($eventCalendar as $calTerm) {
								if ($calTerm->term_id == $calendarID) {
									$event->calendarID = $calendarID;
								}
							}
						}
						$event->eventStartDate = $eventMeta["_kcal_eventStartDate"][0];

						$event->eventEndDate = $eventMeta["_kcal_eventEndDate"][0];
						$event->detailsAlternateURL = $eventMeta["_kcal_eventURL"][0];
						$event->timezone = (isset($eventMeta["_kcal_timezone"])) ? $eventMeta["_kcal_timezone"][0] : get_option('gmt_offset');
						$res[$event->ID] = $event;

						$recurrence = (isset($eventMeta["_kcal_recurrenceType"][0])) ? $eventMeta["_kcal_recurrenceType"][0] : "None";
						$event->recurrence = $recurrence;

						if (isset($eventMeta["_kcal_recurrenceEnd"][0]) && $eventMeta["_kcal_recurrenceEnd"][0] != "Null" && !empty($eventMeta["_kcal_recurrenceEnd"][0])){
							foreach($eventMeta["_kcal_recurrenceDate"] as $rDate){
								$recurDate = unserialize($rDate);
								$startTime = array_keys($recurDate);
								list($endTime, $metaID) = array_values($recurDate[$startTime[0]]);

								$recur = array();
								foreach($event as $key => $value){
									$recur[$key] = $value;
								}
								$recurID = "";
								$recurID = $eventID."-".$metaID;
								$recur["calendarID"] = $event->calendarID;
								$recur["eventStartDate"] = $eventMeta["_kcal_eventStartDate"][0];
								$recur["eventEndDate"] = $eventMeta["_kcal_eventEndDate"][0];
								$recur["detailsAlternateURL"] = $eventMeta["_kcal_eventURL"][0];
								$recur["eventStartDate"] = $startTime[0];
								$recur["eventEndDate"] = $endTime;
								$recur["ID"] = $recurID;
								$recur["metaID"] = $metaID;
								$recur["timezone"] = (isset($eventMeta["_kcal_timezone"])) ? $eventMeta["_kcal_timezone"][0] : get_option('gmt_offset');
								$res[$recurID] = json_decode(json_encode($recur));
							}
						}
					}
				}
			}
			$eList = array();
			$filtered = array_values($res);
			foreach($filtered as $index => $eItem){
			if ($eItem->eventStartDate >= $whereDate){
					if (!isset($eList[$eItem->eventStartDate])){
						$eList[$eItem->eventStartDate] = $eItem;
					}
					else{
						$eList[($index) + $eItem->eventStartDate] = $eItem;
					}
			}
			}
			ksort($eList);
			return $eList;
		}
		/**
		 * Get single event data for a main event or a repeating event
		 * @param integer $postID
		 * @param integer $metaID
		 * @param integer $calendarID
		 */
		public function retrieve_one_event($postID, $metaID = 0){
			global $wpdb;
			$join = "";
			$joinWhere = "";
			$joinSelect = "";
			$event = false;
			$postStatus = "'publish'";
			if (is_user_logged_in()){
				$postStatus = "'publish','private'";
			}
			if ($metaID > 0){
			$join = sprintf(" INNER JOIN `%spostmeta` AS pm ON p.`ID` = pm.`post_id`", $wpdb->prefix) ;
			$joinWhere = sprintf(" AND pm.`meta_id` = %d AND pm.`meta_key` = '%s'",
					$metaID, "_kcal_recurrenceDate"
				);
			$joinSelect = ",pm.`meta_value`";
			}
			$result = $wpdb->get_row(sprintf(
					"SELECT p.* %s FROM `%sposts` as p%s
						WHERE p.`ID` = %d AND p.`post_status` IN (%s)%s",
					$joinSelect,
					$wpdb->prefix,
					$join,
					$postID,
					$postStatus,
					$joinWhere
					));
			if ($result != false){
				if (isset($result->meta_value)){
					$rData = unserialize($result->meta_value);
					$startTime = array_values(array_keys($rData));
					$result->eventStartDate = $startTime[0];
					$result->eventEndDate = $rData[$startTime[0]]["endDate"];
				}
				else{
					$result->eventStartDate = get_post_meta($result->ID, "_kcal_eventStartDate", true);
					$result->eventEndDate = get_post_meta($result->ID, "_kcal_eventEndDate", true);
				}
				$event = $result;
			}
			return $event;
		}
		/**
		* Retrieves events based on start time and end time parameters
		* Return json encoded string
		* @access public
		* @param array $get
		* @see get_calendars_common()
		* @see get_calendar_events_details()
		* @return false|string
		*/
		public function get_calendar_events_ajax($get){
			$eventData = array();

			if (isset($get["calendar"]) && isset($get["start"]) && isset($get["end"])){
				$res = $this->get_calendars_common($get["calendar"]);

				if ($res !== false){

					$startDate = new \DateTime($get['start']);
					$endDate = new \DateTime($get['end']);

					$rows = $this->retrieve_all_events($startDate->getTimestamp(), $endDate->getTimestamp(), $get["calendar"]);
					if (false !== $rows){
						$eList = array();
						foreach($rows as $index => $eItem){

							if (!isset($eList[$eItem->eventStartDate])){
								$eList[$eItem->eventStartDate] = $eItem;
							}
							else{
								$eList[($index) + $eItem->eventStartDate] = $eItem;
							}
						}
						ksort($eList);
						foreach($eList as $sorted){
							$isRecurring = (bool) strstr($sorted->ID, "-");
							$eventDetails = $this->set_event_data(array($sorted), $sorted->calendarID, $isRecurring);
							if (!empty($eventDetails)){
								$eventData[] = $eventDetails[0];
							}
							//echo $sorted->ID."\n";
						}
					}

				}
			}
			return json_encode($eventData);
		}
		/**
		 * Display a list of upcoming events based on the number of events requested
		 * @access public
		 * @param integer $limit
		 * @param string  $calendarURL
		 * @see get_calendar_details()
		 * @see DB_MySQL::valid()
		 */
		public function upcoming_events_widget($limit, $calendarID = false, $start = false, $end = false, $showPrivate = false, $customMetaQuery = array()){
			if (intval($limit, 10) > 0) {
				$res = $this->retrieve_all_events($start, $end, intval($calendarID), $showPrivate, $customMetaQuery);
				$calendars = $this->get_calendar_details();
				$date = new DateTime();
				$timeDiff = ($date->getOffset() + (int)$this->timezone) * (60*60);//may be negative value
				$current = $date->getTimestamp() + $timeDiff;
				$today = mktime(0,0,0, date("n", $current), date("j", $current), date("Y", $current));

				if ($start !== false){
					$today = $start;
				}

				if ($res !== false && !empty($calendars))  {
					$j = 0;
					foreach($res as $row) {
						if ($calendarID === false || (int) $row->calendarID == (int) $calendarID){

							if (array_key_exists($row->calendarID, $calendars) && $row->eventStartDate >= $today) {
								if ($j < $limit) {
									$link = (!empty($row->detailsAlternateURL))? trim($row->detailsAlternateURL): get_permalink($row->ID);
									$events[$row->ID] = array(
										"id"            => trim($row->ID),
										"start"         => $row->eventStartDate,
										"end"           => $row->eventEndDate,
										"link"          => $link,
										"title"         => trim($row->post_title),
										"description"   => trim($row->post_content),
										"calendar"      => $row->calendarID
									);
									$j++;
								}
							}
						}
					}
					if (isset($events)){
						return $events;
					}
				}
			}
			return false;
		}
		/**
		 * Get a filtered list of events inside the taxonomy page
		 */
		public function upcoming_events_archive_filter($paged, $customQuery = array()){
			$term = get_term_by("slug", get_query_var("calendar"), "calendar");

			$start = false;
			$end = false;

			$pageNum = ((int)$paged > 0) ? (int)$paged : 1;

			$limit = $pageNum * 100;

			$today = strtotime(date("Y-m-d"));

			if (isset($_GET["fy"]) && isset($_GET["fm"])){
				$start = strtotime($_GET["fm"]." 1, ".$_GET["fy"]);
				$end = strtotime($_GET["fm"]." ".date("t", $start).", ".$_GET["fy"]." +7days");
			}
			$data = (isset($term->term_id)) ? $this->upcoming_events_widget($limit, $term->term_id, $start, $end, false, $customQuery) : $this->upcoming_events_widget($limit, false, $start, $end, false, $customQuery);


			$filtered = array();
			if (!empty($data)){
				foreach($data as $eventID => $event){
					if ($start === false || ($event["start"] >= $today && date("n", $event["start"]) == date("n", $start))){
						$filtered[$eventID] = $event;
					}
				}
			}
			return $filtered;
		}
		/**
		 * Output RSS feed based on parameters sent in query string. Default is to display all
		 * Only events that are occuring greater than 30 days in advance are currently displayed
		 *
		 * @access public
		 * @param false|string $calendarID
		 * @param integer $limit
		 * @see get_calendar_details()
		 * @see retrieve_all_events()
		 */
		public function buildRSS($calendarID, $limit){

			$title = get_bloginfo("name", "raw" ) ." ";

			$rss = "<?xml version=\"1.0\" encoding=\"UTF-8\">" ."\n";
			$rss .= "<rss version=\"2.0\">"."\n";
			$rss .= "<channel>"."\n";
			$rss .= "<title><![CDATA[" . $title ."]]></title>"."\n";
			$rss .= "<description>" . __('Upcoming Events', 'kcal') ."</description>"."\n";
			$rss .= "<lastBuildDate>" .date("D d M Y G:i:s") ."</lastBuildDate>"."\n";

			$calendars = $this->get_calendar_details();
			$title .= ((int) $calendarID > 0 && isset($calendars[$calendarID])) ? $calendars[$calendarID]["name"] : "All Events";
			$i = 0;
			if ((int) $calendarID > 0 || $calendarID === false){
			$res = $this->retrieve_all_events();
			if (count($res) > 0) {

				foreach($res as $row) {
					try {
						$timezoneObj = new \DateTimeZone($row->timezone);
					} catch (exception $e) {
						$timezoneObj = new \DateTimeZone(get_option('gmt_offset'));
					}

					$startDateObj = new \DateTime('', $timezoneObj);
					$pubDateObj = new \DateTime('', $timezoneObj);

					$startDateObj->setTimestamp($row->eventStartDate);
					$pubDateObj->setTimestamp(get_post_timestamp($row->ID));


					if (array_key_exists(trim($row->calendarID), $calendars) && $startDateObj->getTimestamp() >= (date("U"))) {

						$itemID = explode("-", $row->ID);
						$permalink = get_permalink($itemID [0]);
						if ($recurring === true && count($itemID) == 2){
							$permalink .= "?r=" .$itemID[1];
						}

						$source = false;

						if ((int) $calendarID > 0){
							$source = get_term_link($row->calendarID, "calendar");
						}
						$rss .= "<item>"."\n";
						$rss .= "<title>" .trim($row->post_title). "</title>"."\n";
						$rss .= "<link>" .$permalink. "</link>"."\n";
						$rss .= "<description><![CDATA[" .  get_the_excerpt($row->ID) . "]]></description>"."\n";
						$rss .= "<pubDate>" .$pubDateObj->format('D, j M Y'). "</pubDate>"."\n";
						$rss .= "<source>" .$source. "</source>"."\n";
						$rss .= "</item>"."\n";
					}
					$i++;
					if ($i == $limit){
						break;
					}
				}
			}
			}
			$rss .= "</channel>"."\n";
			$rss .= "</rss>";
			return $rss;
		}
		/**
		 * Output calendar events in iCalendar (.ics) format
		 * Calendar, and start/stop dates can be submitted as parameters via a query string (like ajax)
		 *
		 * @access public
		 * @param array   $get
		 * @get_calendar_details()
		 */
		public function output_ics($get)
		{
			$output = "";
			$calendars = $this->get_calendar_details();
			$endDate = false;
			if (isset($get["calendar"]) || array_key_exists(trim($get["calendar"]), $calendars)) {
				$startDate = (isset($get["start"]) && preg_match("/^[0-9]{8,}$/", trim($get['start']), $matches)) ? $get["start"] : false;
				$endDate = (isset($get["end"]) && preg_match("/^[0-9]{8,}$/", trim($get["end"]), $matches)) ? $get["end"] : false;

				$res = false;
				if (isset($get["event"])){
					$eventID = explode("-", $get["event"]);
					$metaID = (isset($eventID[1])) ? $eventID[1] : 0;
					$res[0] = $this->retrieve_one_event($eventID[0], $metaID);
					if ($res[0] !== false){
						$res[0]->calendarID = $get["calendar"];
					}
				}
				else if ($startDate !== false){
					$res = $this->retrieve_all_events($startDate, $endDate, $get["calendar"]);
				}

				if ($res !== false) {
					$timezone = "UTC".get_option("gmt_offset");
					$offset = abs(get_option("gmt_offset"));
					$output = "BEGIN:VCALENDAR\nVERSION:2.0\n";
					$output .= "BEGIN:VTIMEZONE\n";
					$output .= "TZID:".$timezone."\n";
					$output .= "END:VTIMEZONE\n";
					$blog_title = get_bloginfo("name", "raw" );
					foreach($res as $row) {
						if (isset($calendars[$row->calendarID])) {
							$postID = explode("-",$row->ID);
							if (isset($postID[0])){

								try {
									$timezoneObj = new \DateTimeZone($row->timezone);
								} catch (exception $e) {
									$timezoneObj = new \DateTimeZone(get_option('gmt_offset'));
								}

								$startDateObj = new \DateTime('', $timezoneObj);
								$endDateObj = new \DateTime('', $timezoneObj);

								$startDateObj->setTimestamp($row->eventStartDate);
								$endDateObj->setTimestamp($row->eventEndDate);

								$post = get_post($postID[0]);
								if (!isset($get["event"])){
									$blog_title .= $calendars[$row->calendarID]["name"];
								}
								$startTimeStamp = $row->eventStartDate;
								$endTimeStamp = $row->eventEndDate;
								$consecDays = ceil(($endDateObj->getTimestamp() - $startDateObj->getTimestamp())/(60*60*24));
								$endTimeStamp += ($consecDays > 1) ? 60*60*24 : 0;
								$eventStart = "TZID=".$row->timezone .":" . $startDateObj->format("Ymd\THis");
								$eventEnd = "TZID=".$row->timezone . ":" . $endDateObj->format("Ymd\THis");
								$eventStart = ($consecDays > 1) ? "VALUE=DATE:" . $startDateObj->format("Ymd\THis") : $eventStart;
								$eventEnd = ($consecDays > 1) ? "VALUE=DATE:" . $endDateObj->format("Ymd\THis") : $eventEnd;
								$description = ($consecDays > 1) ? "(" . $startDateObj->format("g:i a") . "-" . $endDateObj->format("g:i a").")":"";

								$output .= "BEGIN:VEVENT\n";
								$output .= "UID:uid".trim($row->ID)."@".site_url()."\n";
								$output .= "DTSTAMP;TZID=".$row->timezone.":".str_replace(" ", "T", preg_replace("/[:-]/", "", trim($post->post_date)))."\n";
								$output .= "DTSTART;$eventStart\n";
								$output .= "DTEND;$eventEnd\n";
								$output .= "SUMMARY: ".$blog_title.": ".trim($post->post_title)."\n";
								$output .= "LOCATION:".get_post_meta($postID[0], "_kcal_location", true)."\n";
								$output .= "DESCRIPTION:".strip_tags($post->post_title)." $description\n";
								$output .= "PRIORITY:3\n";
								$output .= "END:VEVENT\n";
							}
						}
					}
					$output .= "END:VCALENDAR";
				}
			}
			return $output;
		}
		/**
		 * Displays the div container for quick view calendar dialog which is populated via ajax request data
		 *
		 * @access public
		 */
		public function quick_view_dialog()
		{
			echo '<div id="dlgQuickView" title="'.__('Events', 'kcal').'"></div>';
		}


		/**
		 * Returns a list of events formatted in a dl element for quick view calendar ajax request
		 *
		 * @access public
		 * @param string  $timestamp
		 * @see get_calendar_details()
		 * @see retrieve_all_events()
		 * @return string
		 */
		public function get_quick_view_events_ajax($timestamp, $calSettings){
			$output = "false";
			if (preg_match("/^[0-9]{8,}$/", $timestamp, $matches)) {

				try {
					$timezoneObj = new \DateTimeZone($this->timezone);
				} catch (exception $e) {
					$timezoneObj = new \DateTimeZone(get_option('gmt_offset'));
				}

				$dateS = new \DateTime('', $timezoneObj);
				$dateE = new \DateTime('', $timezoneObj);

				$dateSel = mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp), date('Y', $timestamp) );
				$endDateStamp = $dateSel + (60*60*30);
				$dateSelEnd = date('Y-m-d', $endDateStamp);
				$calendars = $this->get_calendar_details();

				$res = $this->retrieve_all_events($dateSel, $endDateStamp);

				if (empty($calSettings)) {
					$calSettings = array_keys($calendars);
				}

				if ($res != false) {
					$output = "<dl>";
					foreach($res as $row) {
						if (isset($calendars[$row->calendarID]) && in_array($row->calendarID, $calSettings)) {
							$dateS->setTimestamp($row->eventStartDate);
							$dateE->setTimestamp($row->eventEndDate);
							$eventDate = ($dateS->format('Y-m-d') == $dateS->format('Y-m-d') ) ? $dateS->format('M d') : $dateS->format('M d') . "-" . $dateE->format('M d');
							$link = get_permalink($row->ID);

							$eventTitle = (!empty($link)) ? "<a href=$link>".trim($row->post_title)."</a>" : trim($row->post_title) ;

							$output .= "<dt style=\"background-color:".$calendars[trim($row->calendarID)]["colour"].";color:".$calendars[trim($row->calendarID)]["text"]."\">".$eventTitle."</dt>";
							$output .= "<dd style=\"background-color:".$calendars[trim($row->calendarID)]["colour"].";color:".$calendars[trim($row->calendarID)]["text"]."\">$eventDate &#8226; ".$dateS->format("g:i a") . "-" . $dateE->format("g:i a")."<br />".trim($row->description)."</dd>";
						}
					}
					$output .= "</dl>";
				}
			}
			return $output;
		}
		/**
		 * Returns day of week for first day of specified month
		 *
		 * @access protected
		 * @param string  $Year
		 * @param string  $Month
		 * @return string
		 */
		protected function get_first_day($Year, $Month)
		{
			$dayOneMonth = mktime(12, 00, 00, $Month, 1, $Year);
			$firstDay = date('w', $dayOneMonth) + 1;
			return $firstDay;
		}
		/**
		 * Return the number of days in a specified month
		 *
		 * @access protected
		 * @param string  $Year
		 * @param string  $Month
		 * @return string
		 */
		protected function month_length($Year, $Month)
		{
			$lastDayMonth=$Month+1;
			$numDays = mktime(0, 0, 0, $lastDayMonth, 0, $Year);
			$length = date('t', $numDays);
			return $length;
		}
		protected function format_month($monthNum){
			return (str_pad($monthNum,2,"0",STR_PAD_LEFT));
		}
		/**
		 * Returns an array of dates that have events occurring within the current calendar month
		 * event has to be in an existing calendar, and that calendar has to be selected in the settings
		 *
		 * @access protected
		 * @param string  $Month
		 * @param string  $Year
		 * @see get_calendar_details()
		 * @see retrieve_all_events()
		 * @return array
		 */
		protected function get_quick_view_dates($Month, $Year, $calSettings)
		{
			$dates = array();
			$eventStart = mktime(0, 0, 0, str_pad($Month,2,"0",STR_PAD_LEFT), "01", $Year);
			$monthNext = (($Month + 1) > 12) ? "01" : (($Month + 1));
			$yearNext = (($Month + 1) > 12) ? ($Year + 1) : $Year;
			$eventEnd = mktime(0, 0, 0, str_pad($monthNext,2,"0",STR_PAD_LEFT), "01", $yearNext );
			$calendars = $this->get_calendar_details();
			$res = $this->retrieve_all_events($eventStart , $eventEnd);

			if (empty($calSettings)) {
				$calSettings = array_keys($calendars);
			}

			try {
				$timezoneObj = new \DateTimeZone($this->timezone);
			} catch (exception $e) {
				$timezoneObj = new \DateTimeZone(get_option('gmt_offset'));
			}
			$date = new \DateTime('', $timezoneObj);
			if ($res != false) {
				foreach($res as $row) {
					$date->setTimestamp($row->eventStartDate);
					if ($date->format('n') == $Month && $date->format('Y') == $Year && isset($calendars[$row->calendarID]) && in_array($row->calendarID, $calSettings) ) {
						$datesAvailable[$date->format('j')] = mktime(0, 0, 0, $date->format('m'), $date->format('d'), $date->format('Y'));
					}
				}
				if (isset($datesAvailable)){
					$dates = array_unique($datesAvailable);
				}
			}
			return $dates;
		}
		/**
		 * Creates a small calendar for viewing dates with events listed in db
		 * CSS is completely customizable
		 * returned as string so it can be accessed statically and dynamically (ajax)
		 * @access public
		 * @see get_quick_view_dates()
		 * @see get_first_day()
		 * @see month_length()
		 */
		public function quick_view_calendar($month = false, $year = false, $calSettings = array())
		{
			/*
			get the current and next month as numerical values 1 through 12
			get the year for each of the current and next month as YYYY for display
			*/

			$Month = ($month != false && $month > 0 && $month < 13) ? $month : date('n', current_time('timestamp'));
			$Year = ($year != false && preg_match("/^20[0-9]{2}$/",$year,$matches)) ? $year : date('Y', current_time('timestamp'));
			$datesAvailable = $this->get_quick_view_dates($Month, $Year, $calSettings);
			/*
			call functions to create arrays for each month for:
			1) the day of the week of the first day of the month as numerical values 0-6
			2) the total number of days in the month
			3) the total number of days to display (includes white space to display the proper
			numerical date values under the correct day of the week.
			*/
			$firstDayOfWeek = $this->get_first_day($Year, $Month);
			$daysOfMonth = $this->month_length($Year, $Month);
			$displayDays = $daysOfMonth + $firstDayOfWeek;
			$daysEndOfMonth = 6 - date('w', mktime(0, 0, 0, $Month, $daysOfMonth, $Year));
			//determine number of days for each month to display highlight
			$today = date('j');
			$MonthNames = array("January", "February", "March", "April", "May", "June", "July",
					"August", "September", "October", "November", "December");
			$cal =  "<table id=\"calendarWidgetTable\">";
			$cal .= "<tr class='calHeader2'><th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th></tr>";
			//populate table
			$cal .= "<tr>";
			for ($i=1; $i < $displayDays; $i++) {
				if ($i < $firstDayOfWeek) {
					//$cal .= "<td class=\"tdCalSpacer\">&nbsp;</td>";
					$cal .= "<td class=\"tdCalSpacer\">".date("j",(mktime(0,0,0,$Month,1,$Year)-60*60*24*($firstDayOfWeek-$i)))."</td>";
				}
				else {
					$style = (array_key_exists(($i-$firstDayOfWeek+1), $datesAvailable))?"class=\"tdQvEvents\"":"";
					$onclick = (array_key_exists(($i-$firstDayOfWeek+1), $datesAvailable))?"onclick=show_quick_widget_events(this,'".$datesAvailable[($i-$firstDayOfWeek+1)]."')":"";
					if (($i-$firstDayOfWeek+1) == $today && $Month == date('n') && $Year == date('Y')) {
						$cal .= "<td class='calToday' $onclick>".($i-$firstDayOfWeek+1)."</td>";
					}
					else {
						$cal .= "<td $style $onclick>".($i-$firstDayOfWeek+1)."</td>";
					}
				}
				if ($i == ($displayDays - 1) && $daysEndOfMonth > 0) {
					for ($j = 0; $j < $daysEndOfMonth; $j++) {
						$cal .= "<td class=\"tdCalSpacer\">".(1+$j)."</td>";
					}
				}
				if ($i % 7==0 && $i != ($displayDays-1)) {
					$cal .= "</tr><tr>";
				}
			}
			$cal .= "</tr></table>";
			return $cal;
		}
		/**
		* Returns the calendar body via ajax so users can scroll through months
		* @access public
		* @param int $adv
		* @param string $currentDate
		* @see quick_view_calendar();
		*/
		public function quick_view_calendar_ajax($adv, $currentDate, $calSettings = array()){
			$qvCalendar = "false";

			try {
				$timezoneObj = new \DateTimeZone($this->timezone);
			} catch (exception $e) {
				$timezoneObj = new \DateTimeZone(get_option('gmt_offset'));
			}

			$date = new \DateTime($currentDate, $timezoneObj);
			if ($adv == 1 || $adv == -1){
				$currentMonth = $date->format('n');
				$currentYear = $date->format('Y');
				$newMonth = $currentMonth + ($adv);
				$newYear = $currentYear;
				if ($newMonth == 13){
						$newMonth = 1;
						$newYear++;
				}
				else if ($newMonth == 0){
						$newMonth = 12;
						$newYear--;
				}
				$newTimeStamp = mktime(0, 0, 0, $newMonth, 1, $newYear);
				$date->setTimestamp($newTimeStamp);
				$calTitle = date('F Y', $newTimeStamp);
				$qvCalendar = $this->quick_view_calendar($newMonth, $newYear, $calSettings)."~".date('Y-m-d', $newTimeStamp)."~".$calTitle;
			}
			return $qvCalendar;
		}
		/**
		* This method displays upcoming events in a list widget within full Calendar page.
		* Similar to listWidget but shows all details rather than linking to fullCalendar
		* Also accessed by ajax request when calendars are selected or deselected from the list
		* @access public
		* @param integer $limit
		* @param false|array $get
		* @see retrieve_all_events()
		* @see get_calendar_details()
		*/
		public function fullCalendar_upcoming_events($limit,$get = false)
		{
			$output = "";

			$o = get_option("kcal-settings");
			$icsPage = (isset($o["icsFeed_page"]) && !empty($o["icsFeed_page"])) ? $o["icsFeed_page"] : "";

			//if (intval($limit,10) > 0){
			try {
				$timezoneObj = new \DateTimeZone($this->timezone);
			} catch (exception $e) {
				$timezoneObj = new \DateTimeZone(get_option('gmt_offset'));
			}

			$date = new \DateTime('', $timezoneObj);
			$startDate = $date->format('Y-m')."-01";
			$endDate = ($date->format('n') + 1 > 12) ? ($date->format('Y') + 1)."-".$this->format_month(($date->format('n')+1)-12)."-01":$date->format('Y')."-".$this->format_month($date->format("n")+1)."-01";
			if (isset($get['timestamp']) && preg_match("/^[0-9]{8,}$/",trim($get['timestamp']),$match)){
				$date->setTimestamp($get['timestamp']);
				if (isset($get['cmmd']) && preg_match("/^(prev|next)$/",trim($get['cmmd']),$matches)){
				if (trim($get["cmmd"]) == "prev"){
					//$endDate = date("Y-m",trim($get['timestamp']))."-01";
					$endDate = $date->format('Y-m') . -'01';
					//$month = $this->format_month(date("n",trim($get['timestamp']))-1);
					$month = $this->format_month($date->format('n')-1);
					//$startDate = (date("n",trim($get['timestamp']))-1 == 0)?(date("Y",trim($get['timestamp']))-1)."-12-01":date("Y",trim($get['timestamp']))."-$month-01";
					$startDate = ($date->format('n')-1 == 0)?($date->format('Y') -1 )."-12-01":$date->format('Y')."-$month-01";
				}
				else if (trim($get["cmmd"]) == "next"){
					$stMonth = $this->format_month($date->format('n') + 1);
					$endMonth = $this->format_month($date->format('n') + 2);
					$startDate = ($date->format('n') + 1 == 13) ? ($date->format('Y') + 1)."-01-01" : $date->format('Y')."-$stMonth-01";
					$endDate = ($date->format('n') + 1 == 13) ? ($date->format('Y') + 1)."-02-01" : $date->format('Y')."-$endMonth-01";
				}
				}
				else{
				$stMonth = $this->format_month(($date->format('n') + 1)-12);
				$endMonth = $this->format_month($date->format('n') + 1);
				$startDate = $date->format('Y-m')."-01";
				$endDate = ($date->format('n') + 1 > 12)?($date->format('Y') + 1)."-$stMonth-01" : $date->format('Y')."-$endMonth-01";
				}
			}

			$date->setTimestamp($startDate);
			$startTimeStamp = $date->getTimestamp();
			$date->setTimestamp($endDate);
			$endTimeStamp = $date->getTimestamp();
			$res = $this->retrieve_all_events($startTimeStamp,$endTimeStamp);
			$calendars = $this->get_calendar_details();

			$j = 0;
			if ($res !== false && !empty($calendars)) {
				$calList = (!is_array($get['view']))?array_keys($calendars):$get['view'];

				$output = "<ul>";
				foreach ($res as $row) {
				$date->setTimestamp($row->eventStartDate);

				try {
					$timezoneObj = new \DateTimeZone($this->timezone);
				} catch (exception $e) {
					$timezoneObj = new \DateTimeZone(get_option('gmt_offset'));
				}

				$dateE = new \DateTime('', $timezoneObj);
				$dateE->setTimestamp($row->eventEndDate);
				$startDate = $date->format('Y-m-d');
				$endDate = $dateE->format('Y-m-d');
				if (isset($calendars[substr(trim($row->calendarID), 0, 2)]) && in_array(trim($row->calendarID),$calList) /*&& $startTimeStamp > date('U')*/) {
					if ($j < $limit) {
					$eventDate = ($startDate == $endDate) ? $date->format('D M j, Y') : $date->format('D M j, Y')." - ".$dateE->format('D M j, Y');
					$link = get_permalink($row->ID);
					if (isset($row->metaID)){
						$link .= "?r=" . $row->metaID;
					}

					$output .= "<li>";
					$output .= "<h3>";
					$output .= "<a href=\"".$link."\" style=\"font-weight:bold; color:".$calendars[trim($row->calendarID)]["colour"]."\">". trim($row->post_title)."</a>";
					if (!empty($icsPage)){
						$output .= "&nbsp;<a href=\"{$icsPage}?event=".trim($row->itemID)."\"><i class=\"k-icon-calendar\" title=\"Add to Calendar\"></i></a>";
					}
					$output .= "</h3>";
					$output .= "<span class=\"kc-event-date\">" . $eventDate." &#8226; ".$date->format("g:i a")."-".$dateE->format("g:i a")."</span><br />";
					//$output .= "<span class=\"kc-event-loc\">" . trim($row->location)."</span><br />".trim($row->description);
					$output .= "</li>";
					$j++;
					}
				}
				}
				$output .= "</ul>";
			}
			if ($j == 0) {
				$output .= "<h3>No Events</h3>";
			}
			$output .= "<p id=\"plistTimeStamp\" style=\"display:none\">$startTimeStamp</p>";

			echo $output;
		//}
		}
	}
}
