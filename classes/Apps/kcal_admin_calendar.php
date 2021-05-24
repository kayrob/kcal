<?php

/**
* Admin methods for the events calendar. Not to be used without /includes/apps/calendar/Calendar.php
* created: November 11, 2010 by Karen Laansoo
* @package apps/calendar
*/
if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!class_exists('AdminCalendar') && class_exists('Calendar')) {
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

			if (is_admin()){
				$screenType = (isset($_GET["post_type"]) && $_GET["post_type"] == "event") ? "event" : "";
				if (empty($screenType) && isset($_GET["post"])){
					$screenType = get_post_type($_GET["post"]);
				}

				add_action("add_meta_boxes", array($this, "kCal_add_meta_boxes"));
				add_action("save_post", array($this, "kCal_save_meta_box_data"), 99);
				if ($screenType == "event"){
					add_filter("manage_posts_columns", array($this, "set_eventsList_columns"));
					add_action("manage_posts_custom_column", array($this, "set_eventsList_content"), 10, 2);
				}
				add_action("admin_enqueue_scripts", array($this, "kCal_admin_scripts"));
				add_filter("upload_mimes", array($this, "kCal_custom_mime_types"));

				add_action ("calendar_edit_form_fields", array($this, "kCal_extra_calendar_field"));
				add_action ("edited_calendar", array($this, "save_extra_calendar_field"));
			}
		}

		/**
		 * Enqueue admin scripts/styles
		 */
        public function kCal_admin_scripts(){

            wp_register_script("jquery-ui", KCAL_HOST_URL . "js/jquery-ui/js/jquery-ui-1.12.1.min.js", array("jquery"), "1.12.1", true);

			wp_register_script("fullCalendar", KCAL_HOST_URL ."vendors/fullcalendar-5.6.0/lib/main.js", "5.6.0", true);
			wp_enqueue_script("fullCalendar");
            wp_register_script("kcalendar", KCAL_HOST_URL ."js/calendar.js", array("jquery", "jquery-ui", 'fullCalendar'), "1.0", true);
            wp_enqueue_script("kcalendar");
            wp_register_style("calCSS", KCAL_HOST_URL ."vendors/fullcalendar-5.6.0/lib/main.min.css", "1.5.3");
            wp_register_style("jquery-ui", KCAL_HOST_URL ."js/jquery-ui/css/smoothness/jquery-ui-1.10.3.custom.min.css", "1.10.3");
            wp_enqueue_style("calCSS");
            wp_enqueue_style("jquery-ui");

            wp_register_script("adminCalendar", KCAL_HOST_URL ."js/adminCalendar.js", array("kcalendar", "jquery-ui-core", "jquery-ui-datepicker"), "2.0", true);
            wp_register_script("jscolor", KCAL_HOST_URL ."vendors/jscolor/jscolor.js", array(), true);
            wp_enqueue_script("adminCalendar");
            wp_enqueue_script("jscolor");
            wp_localize_script("jscolor", "url_object", array("plugin_url" => KCAL_HOST_URL ."vendors/jscolor/"));
            wp_register_style("kcal-admin-css", KCAL_HOST_URL ."dist/css/admin.css");
            wp_enqueue_style("kcal-admin-css");
            wp_enqueue_style('thickbox');
            wp_enqueue_script('thickbox');
            wp_localize_script("adminCalendar", "kcal_object", array("edit_url" => admin_url("post.php?action=edit")));

			wp_localize_script("kcalendar", "ajax_object", array("ajax_url" => admin_url("admin-ajax.php")));

        }

		/**
		 * Show the calendar term link in the admin column
		 */
		protected function get_eventColumn_calendar($post_id){
			$calendars = wp_get_post_terms($post_id, "calendar");
			$postcals = "";
			if (!empty($calendars)){
				foreach($calendars as $term){
					$calURL = "term.php?taxonomy=calendar&tag_ID=".$term->term_id."&post_type=event";
					if (!empty($postcals)){
						$postcals .= ", ";
					}
					$postcals .= "<a href=\"".  admin_url($calURL)."\">".$term->name."</a>";
				}
			}
			return $postcals;
		}
		/**
		 * Add a custom mime type for importing calendar files
		 */
		public function kCal_custom_mime_types($mimes){
		$mimes["ics"] = "text/calendar";
		return $mimes;
		}
		/**
		 * Create a new admin column for calendar
		 */
		public function set_eventsList_columns($defaults){
			$newList = $defaults;
			unset($newList["comments"]);
			unset($newList["author"]);
			unset($newList["categories"]);
			unset($newList["tags"]);
			$resetList = array_pop($newList);
			$newList["taxcalendar"] = "Calendar(s)";

			$returnList = $newList;
			$returnList["date"] = "Date";
			return $returnList;
		}
		/**
		 * Show the calendar term link in the admin column
		 */
		public function set_eventsList_content($columnName, $postID){
			switch($columnName){
				case "taxcalendar":
					echo $this->get_eventColumn_calendar($postID);
					break;
				default:
					break;
			}
		}

		/**
		 * Add an extra field to the calendar taxonomy editor
		 * @param integer $tag
		 */
		public function kCal_extra_calendar_field($tag){
			$term_id = $tag->term_id;
			$cat_meta_colour = get_option( "calendar_".$term_id, "#454545");
			$cat_meta_textcolour = get_option( "calendar_text_".$term_id, "#fff");
			$wp_editor_settings = array(
		"wpautop" => true, // Default
		"textarea_rows" => 5,
		"tinymce" => array( "plugins" => "wordpress" ),
				"media_buttons" => false,
				"textarea_name" => "_kcal_calendarDescription",
			);
			$richText = stripslashes(get_option("calendar_description_".$term_id, ""));
		?>
			<tr class="form-field">
			<th scope="row" valign="top"><label for="cal_colour"><?php _e("Select Calendar Colour", 'kcal'); ?></label></th>
			<td>
				<input type="text" id="cal_colour" name="cal_colour" size="10" value="<?php echo $cat_meta_colour;?>" class="color" style="width: 100px"/>
			<div id="colorPickerNew"></div>
			</td>
			</tr>
			<tr class="form-field">
			<th scope="row" valign="top"><label for="_kcal_text_colour"><?php _e("Text Colour for Calendar.", 'kcal'); ?></label></th>
			<td>
				<select name='_kcal_text_colour' id='_kcal_text_colour'>
					<option value='#000'<?php if ('#fff' != $cat_meta_textcolour ){ echo ' selected="selected"';}?>><?php _e('Black', 'kcal');?><?php if ('#fff' != $cat_meta_textcolour ){ echo '*';}?></option>
					<option value='#fff'<?php if ('#fff' == $cat_meta_textcolour ){ echo ' selected="selected"';}?>><?php _e('White', 'kcal');?><?php if ('#fff' == $cat_meta_textcolour ){ echo '*';}?></option>
				</select>
				<p><i><?php _e('The correct colour to pick is the text shown in the colour selected above.', 'kcal');?></i></p>
			</td>
			</tr>
			<tr class="form-field">
			<th scope="row" valign="top"><label for="_kcal_calendarDescription"><?php _e("Rich Text Description", 'kcal'); ?></label></th>
			<td>
				<?php wp_editor($richText, "_kcal_calendarDescription", $wp_editor_settings); ?>
			</td>
			</tr>

		<?php
		}
		/**
		 * Save the extra description field
		 * @param type $term_id
		 */
		public function save_extra_calendar_field($term_id){
			if ( isset( $_POST["cal_colour"] ) ) {
				$cal_meta = get_option( "calendar_" . $term_id);
				preg_match("/^\#?[A-Fa-f0-9]{6}$/", $_POST["cal_colour"], $matches);
				//save the option array
				if (isset($matches[0])){
					update_option( "calendar_".$term_id,  $_POST["cal_colour"], $cal_meta );
				}
			}
			if (isset($_POST["_kcal_text_colour"])){
				$cal_text_meta = get_option( "calendar_text_" . $term_id);
				if ($_POST['_kcal_text_colour'] == '#fff' || $_POST['_kcal_text_colour'] == '#000') {
					update_option( "calendar_text_".$term_id,  $_POST["_kcal_text_colour"], $cal_text_meta );
				}
			}
			if (isset($_POST["_kcal_calendarDescription"])){
				$cal_dxn_meta = get_option( "calendar_description_" . $term_id);
				update_option( "calendar_description_".$term_id,  $_POST["_kcal_calendarDescription"], $cal_dxn_meta );
			}
		}
		/**
		 * Custom meta data - nonce
		 */
		public function kCal_mb_nonce(){}

		/**
		 * Custom meta data - create the boxes for the meta input
		 */

		public function kCal_add_meta_boxes(){
			add_meta_box("kcal_eventDate", __("Event Date", 'kcal'), array($this, "kcal_mb_eventDate"), "event", "advanced");
			add_meta_box("kcal_eventLocation", __("Event Location", 'kcal'), array($this, "kcal_mb_eventLocation"), "event", "advanced");
			add_meta_box("kcal_eventRepeat", __("Event Repeat", 'kcal'), array($this, "kcal_mb_eventRepeat"), "event", "advanced");
			add_meta_box("kcal_eventURL", __("Registration URL", 'kcal'), array($this, "kcal_mb_eventURL"), "event", "advanced");
		}
		public function kcal_mb_eventDate($post){
			$meta = get_post_meta($post->ID);

			$allDay = get_post_meta($post->ID, "_kcal_allDay", true);
			$allDayChecked = (!empty($allDay) && (bool)$allDay == true) ? " checked=\"checked\"" : "";

			$timezone = get_post_meta($post->ID, "_kcal_timezone", true);

			if (empty($timezone) || false == $timezone) {
				$timezone = get_option('gmt_offset');
			}
			$startDate = get_post_meta($post->ID, "_kcal_eventStartDate", true);
			$endDate = get_post_meta($post->ID, "_kcal_eventEndDate", true);

			try {
				$dateTimezone = new DateTimeZone($timezone);
			} catch (exception $e) {
				$dateTimezone = new DateTimeZone(get_option('gmt_offset'));
			}

			$date = new DateTime('', $dateTimezone);
			if (!empty($startDate)) {
				$date->setTimestamp($startDate);
			}
			$date2 = new DateTime('', $dateTimezone);
			if (!empty($endDate)) {
				$date2->setTimestamp($endDate);
			}

			$startDisplay = (!empty($startDate) && (bool) $startDate !== false) ? $date->format("Y-m-d") : "";
			$startTime = (!empty($startDate) && (bool) $startDate !== false) ? $date->format("g:i A") : "";
			$endDisplay = (!empty($endDate) && (bool) $endDate !== false) ? $date2->format("Y-m-d") : "";
			$endTime = (!empty($endDate) && (bool) $endDate !== false) ? $date2->format("g:i A") : "";

			wp_nonce_field("kcal_meta_box", "kCal_mb_nonce");
			echo "<p><label for=\"_kcal_allDay\">".__('All Day Event', 'kcal')."</label>";
			echo "&nbsp;&nbsp;<input type=\"checkbox\" name=\"_kcal_allDay\" id=\"_kcal_allDay\" value=\"1\" {$allDayChecked}/></p>";
			echo "<p><label for=\"_kcal_eventStartDate\">".__('Start Date', 'kcal')."</label><br />";
			echo "<input type=\"text\" name=\"_kcal_eventStartDate\" id=\"_kcal_eventStartDate\" class=\"datepicker\" value=\"".$startDisplay."\" style=\"width: 100%;max-width: 400px\"/></p>";
			echo "<p><label for=\"_kcal_eventStartTime\">".__('Start Time', 'kcal')."</label><br />";
			echo "<input type=\"text\" name=\"_kcal_eventStartTime\" id=\"_kcal_eventStartTime\" class=\"timepicker\" value=\"".$startTime."\" style=\"width: 100%;max-width: 400px\"/></p>";
			echo "<p><label for=\"_kcal_eventEndDate\">".__('End Date', 'kcal')."</label><br />";
			echo "<input type=\"text\" name=\"_kcal_eventEndDate\" id=\"_kcal_eventEndDate\" class=\"datepicker\" value=\"".$endDisplay."\" style=\"width: 100%;max-width: 400px\"/></p>";
			echo "<p><label for=\"_kcal_eventEndTime\">".__('End Time', 'kcal')."</label><br />";
			echo "<input type=\"text\" name=\"_kcal_eventEndTime\" id=\"_kcal_eventEndTime\" class=\"timepicker\" value=\"".$endTime."\" style=\"width: 100%;max-width: 400px\"/></p>";
			echo "<p><label for=\"_kcal_timezone\">".__('Timezone', 'kcal')."</label><br />";
			echo "<select name=\"_kcal_timezone\" id=\"_kcal_timezone\">". wp_timezone_choice( $timezone ) ."</select></p>";

		}
		public function kcal_mb_eventLocation($post){
			$location = get_post_meta($post->ID, "_kcal_location", true);
			$map = get_post_meta($post->ID, "_kcal_locationMap", true);
			echo "<p><label for=\"_kcal_location\">".__('Location Details', 'kcal')."</label><br />";
			echo "<input name=\"_kcal_location\" id=\"_kcal_location\" value=\"".$location."\" style=\"width: 100%;max-width: 400px\"></p>";
			/*echo "<p><label for=\"_kcal_locationMap\">".__('Map Image', 'kcal')."</label><br />";
			echo "<input type=\"text\" name=\"_kcal_locationMap\" id=\"_kcal_locationMap\" value=\"".$map."\" style=\"width: 80%\"/>";
			echo "<input type=\"button\" class=\"button-primary\" value=\"Upload Image\" id=\"uploadimage_kcal_locationMap\" /><br />";
			if (!empty($map)){
				echo "<img src=\"".  $map."\" alt=\"\" style=\"height:auto;width: 100px\" id=\"img_kcal_locationMap\"/><br />";
			}
			echo "</p>";*/
		}
		public function kcal_mb_eventRepeat($post){
			$recurrenceType = get_post_meta($post->ID, "_kcal_recurrenceType", true);
			$recurrenceEnd = get_post_meta($post->ID, "_kcal_recurrenceEnd", true);
			if (is_null($recurrenceEnd) || strtolower($recurrenceEnd) == "null"){
				$recurrenceEnd = "";
			}
			$recurrenceInterval = (int) get_post_meta($post->ID, "_kcal_recurrenceInterval", true);
			$recurrenceDates = get_post_meta($post->ID, "_kcal_recurrenceDate");

			$recurrenceOpts = array("None","Daily","Weekly","Monthly","Yearly");
			echo "<p><label for=\"_kcal_recurrenceType\">Recurrence</label><br />";
			echo "<select name=\"_kcal_recurrenceType\" id=\"_kcal_recurrenceType\">";
			foreach($recurrenceOpts as $rType){
				echo "<option value=\"{$rType}\"".($recurrenceType == $rType? " selected=\"selected\"" : "").">{$rType}</option>";
			}
			echo "</select></p>";
			echo "<p><label for=\"_kcal_recurrenceInterval\">Recurs Every:</label><br />";
			echo "<select name=\"_kcal_recurrenceInterval\" id=\"_kcal_recurrenceInterval\">";
			for ($i = 0; $i < 366; $i++){
				$recurSel = ($i == $recurrenceInterval) ? " selected=\"selected\"" : "";
				echo "<option value=\"$i\"{$recurSel}>$i</option>";
			}
			echo "</select></p>";
			echo "<p><label for=\"_kcal_recurrenceEnd\">Recurrence End Date</label><br />";
			echo "<input type=\"text\" name=\"_kcal_recurrenceEnd\" id=\"_kcal_recurrenceEnd\" class=\"datepicker\" value=\"".$recurrenceEnd."\" style=\"width: 100%;max-width: 400px\"/></p>";

			if (!empty($recurrenceDates)){


				$timezone = get_post_meta($post->ID, "_kcal_timezone", true);
				if (empty($timezone) || false == $timezone) {
					$timezone = get_option('gmt_offset');
				}

				echo "<p><strong>Recurrence Dates</strong></p>";
				echo "<ol>";
				foreach($recurrenceDates as $index => $rDate){
					$liclass = ($index %2 > 0)? " class=\"alt\"" : "";
					$startTime = array_keys($rDate);
					list($endTime, $metaID) = array_values($rDate[$startTime[0]]);
					$startDate = new \DateTime('now', new \DateTimeZone($timezone));
					$endDate = new \DateTime('now', new \DateTimeZone($timezone));

					$startDate->setTimestamp($startTime[0]);
					$endDate->setTimestamp($endTime);

					$display = $startDate->format('D, M j, Y');
					if ($endDate->format('Y-m-d') != $startDate->format('Y-m-d') ){
						$display .= ' ' . $startDate->format('g:i a') . ' - ' . $endDate->format('D, M j g:i a');
					}
					else{
						$display .= ' ' . $startDate->format('g:i a') . ' - ' . $endDate->format('g:i a');
					}

					$dataStart = $startDate->format('Y-m-d h:i:s A');
					$dataEnd = $endDate->format('Y-m-d h:i:s A');
					echo "<li{$liclass}>";
					echo $display;
					echo "<span class=\"recurrence-controls\"><label id=\"edit-recur-{$metaID}\" data-post=\"{$post->ID}\" data-start=\"{$dataStart}\" data-end=\"{$dataEnd}\" title=\"Edit Date\" class=\"recur-edit\"><i class=\"ki kicon-pencil2\"></i></label>
							<label id=\"del-recur-{$metaID}\" data-post=\"{$post->ID}\" title=\"Delete Date\" class=\"del-recur\"><i class=\"ki kicon-bin\"></i></label></span>";

					echo "</li>";

				}
				echo "</ol>";
				include_once(KCAL_HOST_DIR . "/views/Apps/delete_recur_single.php");
				include_once(KCAL_HOST_DIR . "/views/Apps/edit_recurring_single.php");
			}
		}

		public function kcal_mb_eventURL($post){
			$registerURL = get_post_meta($post->ID, "_kcal_eventURL", true);
			echo "<label for=\"_kcal_eventURL\">URL for the Event Details Page</label><br />";
			echo "<input type=\"text\" name=\"_kcal_eventURL\" id=\"_kcal_eventURL\" value=\"".$registerURL."\" style=\"width: 100%;max-width: 400px\"/>";
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
					"_kcal_locationMap"         => (isset($_POST["_kcal_locationMap"]) ? $_POST["_kcal_locationMap"] : '' ),
					"_kcal_timezone"            => (isset($_POST['_kcal_timezone']) ? $_POST['_kcal_timezone'] : get_option('gmt_offset') ),
					"_kcal_allDay"              => (isset($_POST['_kcal_allDay']) ? '1' : '0')

				);

				if (!isset($_POST['_kcal_allDay'])) {
					unset($meta['_kcal_allDay']);
					delete_post_meta($post_id,'_kcal_allDay');
				}
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


			if (isset($meta['_kcal_timezone'])) {
				try {
					$timezone = new \DateTimeZone($meta['_kcal_timezone']);
				} catch(Exception $e) {
					$timezone = new \DateTimeZone(get_option('gmt_offset'));
				}
			} else {
				$timezone = new \DateTimeZone(get_option('gmt_offset'));
			}

			$date = new \DateTime(trim($meta["_kcal_eventStartDate"])." ".$eventStartTime, $timezone);
			$date2 = new \DateTime(trim($meta["_kcal_eventEndDate"])." ".$eventEndTime, $timezone);

			$startDate = $date->format('U');
			$endDate = $date2->format('U');
			if ($startDate == $endDate){
				$endDate += (60 * 15);
			}

			return array($startDate, $endDate);
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

			$values["_kcal_allDay"] = (isset($meta["_kcal_allDay"]) )?"1":"0";
			$values["_kcal_eventURL"] = esc_url($meta["_kcal_eventURL"]);
			$values["_kcal_locationMap"] = esc_url($meta["_kcal_locationMap"]);
			$values['_kcal_timezone'] = $meta['_kcal_timezone'];
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
		* @return array
		*/
		protected function create_recurring_monthly_dates($post_id, $recurrence, $interval, $recurEnd, $firstEventStart, $firstEventEnd, $timezone){
			$events = array();

			if (preg_match("/^[0-9]{1,6}$/",$post_id,$matches)){
				$newDates = new \DateTime('now', new DateTimeZone($timezone) );

				(!empty($recurEnd)) ? $newDates->setTimestamp(strtotime($recurEnd->format('Y-m-d') . ' ' . $firstEventStart->format('g:i a') )) : $newDates->setTimestamp(mktime(0, 0, 0, 12, 31, date("Y")) );

				$firstEventStartTime = $firstEventStart;
				$firstEventEndTime = $firstEventEnd;

				$recurrenceEndDate = $newDates->getTimestamp();

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
		* @param object $recurEnd
		* @param object $firstEventStart
		* @param object $firstEventEnd
		* @return array
		*/
		protected function create_recurring_daily_weekly_dates($post_id, $recurrence, $interval, $recurEnd, $firstEventStart, $firstEventEnd, $timezone) {
			$events = array();

			if (preg_match("/^[0-9]{1,6}$/",$post_id,$matches)){
				$newDates = new \DateTime('now', new DateTimeZone($timezone) );
				//$recurrenceEndDate = (!empty($recurEnd)) ? strtotime(trim($recurEnd)." ".date("g:i a",$firstEventEnd)) : mktime(0,0,0,12,31,date("Y"));
				(!empty($recurEnd)) ? $newDates->setTimestamp(strtotime($recurEnd->format('Y-m-d') . ' ' . $firstEventStart->format('g:i a') )) : $newDates->setTimestamp(mktime(0, 0, 0, 12, 31, date("Y")) );
				$firstEventStartTime = $firstEventStart->getTimestamp();
				$firstEventEndTime = $firstEventEnd->getTimestamp();

				$recurrenceEndDate = $newDates->getTimestamp();

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
		* @param string $timezone
		* @see create_recurring_monthly_dates()
		* @see create_recurring_yearly_dates()
		* @see create_recurring_daily_weekly_dates()
		* @see add_recurring_dates()
		* @return string
		*/
		protected function create_recurring_dates($post_id, $recurrence, $interval, $recurEnd, $firstEventStart, $firstEventEnd, $timezone){
			$recurAdded = false;
			if (preg_match("/^[0-9]{1,6}$/",$post_id,$matches)){
				$events = array();
				$res = 0;

				$tz = new \DateTimeZone($timezone);
				$recurDate = new \DateTime($recurEnd, $tz );
				$firstDateSt = new \DateTime('now', $tz );
				$firstDateEnd = new \DateTime('now', $tz );
				$firstDateSt->setTimestamp($firstEventStart);
				$firstDateEnd->setTimestamp($firstEventEnd);


				switch($recurrence) {
					case "Monthly":
						$events = $this->create_recurring_monthly_dates($post_id, $recurrence, $interval, $recurDate, $firstDateSt, $firstDateEnd, $timezone);
						break;
					case "Yearly":
						$events = $this->create_recurring_yearly_dates($post_id, $recurrence, $interval, $recurDate, $firstDateSt, $firstDateEnd, $timezone);
						break;
					default:
						$events = $this->create_recurring_daily_weekly_dates($post_id, $recurrence, $interval, $recurDate, $firstDateSt, $firstDateEnd, $timezone);
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
							$this->create_recurring_dates($post_id,trim($dataSet["_kcal_recurrenceType"]),$dataSet["_kcal_recurrenceInterval"],$dataSet["_kcal_recurrenceEnd"],$dataSet["_kcal_eventStartDate"],$dataSet["_kcal_eventEndDate"], $dataSet['_kcal_timezone']);
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
			$validData = __("Events could not be updated.", 'kcal');
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
							$validData .= __("Date and/or Time is not the correct format.", 'kcal');
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
							$validData .= __("Date and/or Time is not the correct format.", 'kcal');
						}
					}
				}
			}
			else {
				$validData = __('No event selected', 'kcal');
			}
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
			$calsList = '<p id="calsList">'.__('There are no calendars', 'kcal').'</p>';
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
			$validData = __("Event could not be deleted", 'kcal');
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

					$timezone = get_post_meta($post['eventID'], "_kcal_timezone", true);
					if (empty($timezone) || false == $timezone) {
						$timezone = get_option('gmt_offset');
					}

					$tz = new \DateTimeZone($timezone);

					$newStartDate = new \DateTime('now', $tz);
					list($startDate, $startTime) = explode(' ', $post["_kcal_dropStartDate"]);
					$newStartDate->setDate(substr($startDate, 0, 4), substr($startDate, 5, 2), substr($startDate, 8, 2));
					$newStartDate->setTime(substr($startTime, 0, 2), substr($startTime, 3, 2), substr($startTime, 6, 2));

					//$newStartDate->setTimestamp(strtotime(substr($post["_kcal_dropStartDate"],0 , 21)));

					$newEndDate = new \DateTime('now', $tz);
					list($endDate, $endTime) = explode(' ', $post["_kcal_dropEndDate"]);
					$newEndDate->setDate(substr($endDate, 0, 4), substr($endDate, 5, 2), substr($endDate, 8, 2));
					$newEndDate->setTime(substr($endTime, 0, 2), substr($endTime, 3, 2), substr($endTime, 6, 2));
					//$newEndDate->setTimestamp(strtotime(substr($post["_kcal_dropEndDate"], 0, 21)) );

					echo $newEndDate->format('Y-m-d h:i:s');
					echo $newStartDate->format('Y-m-d h:i:s');

					if ($newStartDate !== false && $newEndDate !== false && $newEndDate->getTimestamp() > $newStartDate->getTimestamp()){
						if (isset($post["recurrenceID"]) && preg_match("/^(\d{1,})$/",trim($post["recurrenceID"]),$rMatch)){
							global $wpdb;
							$metaRes = $wpdb->get_var($wpdb->prepare("SELECT `meta_value` FROM `{$wpdb->prefix}postmeta` WHERE `meta_id` = %d", $post["recurrenceID"] ));
							if (!is_null($metaRes) || !empty($metaRes) || false !== $metaRes){
								$oldMeta = unserialize($metaRes);

								$newMeta = array($newStartDate->getTimestamp() => array("endDate" => $newEndDate->getTimestamp(), "metaID" => $post["recurrenceID"]));
								$saved = update_post_meta($post["eventID"], "_kcal_recurrenceDate", $newMeta, $oldMeta);
								if ((bool) $saved !== false){
									$validData = "true";
								}
							}
						}
						else{
							update_post_meta($post["eventID"], "_kcal_eventStartDate", $newStartDate->getTimestamp());
							update_post_meta($post["eventID"], "_kcal_eventEndDate", $newEndDate->getTimestamp());
						}
					}
				}
			}
			return $validData;
		}

		public function import_parse_RSS($url, $calendar, $timezone){
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$xmlresponse = curl_exec($ch);
			$source = simplexml_load_string($xmlresponse);

			$uploaded = array();
			if (isset($source->channel)){
				foreach ($source->channel->item as $event){

					try {
						$dateTimezone = new DateTimeZone($timezone);
					} catch (exception $e) {
						$dateTimezone = new DateTimeZone(get_option('gmt_offset'));
					}

					$eventDateObj = new Datetime('now' , $dateTimezone);
					//Tue, 19 Oct 2004 13:38:55 -0400
					$eventDateObj->createFromFormat('D, d M Y G:i:s T', $event->{"pubDate"});
					$eventDate = $eventDateObj->getTimestamp();
					$description = (string)$event->{"description"};

					if ($eventDate >= current_time("timestamp")){
						$exists = get_page_by_title((string)$event->{"title"}, OBJECT, "event");
						$eventTime = $eventDateObj->formatDate('h:i A');
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
							"_kcal_locationMap"         => "",
							'_kcal_timezone'            => $timezone
						);

						$created = wp_insert_post($newEvent);
						if (!is_wp_error($created)){
						if ($calendar > 0){
							wp_set_post_terms($created, array($calendar), "calendar", false);
						}
						$uploaded["success"][] = __("Event:", 'kcal') . "<a href=\"".admin_url("post.php?post=".$created."&action=edit")."\">" . (string)$event->{"title"} . __("</a> was imported.", 'kcal');
						}
						else{
						$uploaded["error"][] = __("Event:", 'kcal') . (string)$event->{"title"} . __(" was not imported", 'kcal');
						}
					}
				}
			}
			else{
				$uploaded["error"][] = __("RSS events could not be imported. Check the URL and retry", 'kcal');
			}
			if (isset($uploaded["success"])){
				$uploaded["success"][] = __("RSS Content can't be guaranteed. Events may need to be updated for content and dates.", 'kcal');
			}
			return $uploaded;
		}
		public function import_parse_ICS($icsFile, $calendar, $timezone){
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
						"_kcal_locationMap"         => "",
						"_kcal_timezone"            => $timezone
					);
					try {
						$dateTimezone = new DateTimeZone($timezone);
					} catch (exception $e) {
						$dateTimezone = new DateTimeZone(get_option('gmt_offset'));
					}

					foreach($lines as $info){
						if (!empty($info)){
							$icsData = explode(":", $info);

							if (count($icsData) > 1) {

								$icsParam = $icsData[0];
								unset($icsData[0]);
								$icsContent = array_values($icsData);

								if (strstr($icsParam, "DTSTART") !== false){
									$dateStart = new DateTime($icsContent[0], $dateTimezone);

									$newEvent["meta_input"]["_kcal_eventStartDate"] = $dateStart->getTimestamp();
									$newEvent["meta_input"]["_kcal_eventStartTime"] = $dateStart->format("g:i A");
								}
								if (strstr($icsParam, "DTEND") !== false){
									$dateEnd = new DateTime($icsContent[0], $dateTimezone);
									$newEvent["meta_input"]["_kcal_eventEndDate"] = $dateEnd->getTimestamp();
									$newEvent["meta_input"]["_kcal_eventEndTime"] = $dateEnd->format("g:i A");
								}
								if (strstr($icsParam, "SUMMARY") !== false){
									$newEvent["post_title"] = implode(' ', $icsData);
								}
								if (strstr($icsParam, "DESC") !== false && empty($newEvent["meta_input"]["post_content"])){
									$newEvent["post_content"] .= "<p>".str_replace('\n', '<br />', implode(': ', $icsContent) )."</p>";
									$newEvent["post_content_filtered"] .= implode(' ', $icsContent);
								}
								if (strstr($icsParam, "LOCATION") !== false && empty($newEvent["meta_input"]["_kcal_location"])){
									$newEvent["meta_input"]["_kcal_location"] = implode(': ', $icsContent);
								}
								if (strstr($icsParam, "ORGANIZER") !== false){
									$newEvent["post_content"] .= "<p>".$icsContent[0]."</p>";
									$newEvent["post_content_filtered"] .= implode(': ', $icsContent);
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
						$uploaded["success"][] = __("Event", 'kcal') . ": <a href=\"".admin_url("post.php?post=".$created."&action=edit")."\">" . $newEvent["post_title"] . "</a> " . __("was imported.", 'kcal');
					}
					else{
						$uploaded["error"][] = __("Event", 'kcal') . ": " . $newEvent["post_title"] . __(" was not imported", 'kcal');
					}
					unlink($icsFile);
				}
			}
			return $uploaded;
		}
	}
	$ca = new AdminCalendar();
}