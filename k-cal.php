<?php
/*
Plugin Name: K-Cal
Plugin URI: https://example.com/plugins/the-basics/
Description: Full service calendar using fullCalendar as base
Version: 2.3
Author: Karen Laansoo
Author URI: https://karenlaansoo.me
*/

require_once(__DIR__ ."/Calendar.php");
require_once(__DIR__ ."/Widgets/CalendarWidgets.php");
require_once(__DIR__ ."/CalendarController.php");
require_once(__DIR__ ."/routes.php");
require_once(__DIR__ ."/Widgets/views/kcal-list-widget.php");
require_once(__DIR__ ."/Widgets/views/kcal-mini-widget.php");
require_once(__DIR__ ."/Widgets/views/kcal-archive-widgets.php");

if (is_admin()){
    require_once(__DIR__ ."/Apps/AdminCalendar.php");
    require_once(__DIR__ ."/Apps/kCal-settings.php");
}

if (!class_exists("kCal")){

    class kCal{
        public function __construct(){

            add_action( "init", array($this,"kCal_init"));
            register_activation_hook( __FILE__, array( $this, "on_activate" ) );
            register_deactivation_hook( __FILE__, array( $this, "on_deactivate" ) );

            add_action("wp_enqueue_scripts", array($this, "kCal_front_scripts"));

            add_action("admin_menu", array($this, "kCal_add_calendar_menu"));

            add_action("widgets_init", array($this, "kCal_register_widgets"));

            add_action ("calendar_edit_form_fields", array($this, "kCal_extra_calendar_field"));
            add_action ("edited_calendar", array($this, "save_extra_calendar_field"));

            add_action("pre_get_posts", array($this, "kCal_mainquery_filter_archive"));



            if (is_admin()){
                $ca = new AdminCalendar();
                $screenType = (isset($_GET["post_type"]) && $_GET["post_type"] == "event") ? "event" : "";
                if (empty($screenType) && isset($_GET["post"])){
                    $screenType = get_post_type($_GET["post"]);
                }

                add_action("add_meta_boxes", array($this, "kCal_add_meta_boxes"));
                add_action("save_post", array($ca, "kCal_save_meta_box_data"), 99);
                if ($screenType == "event"){
                    add_filter("manage_posts_columns", array($this, "set_eventsList_columns"));
                    add_action("manage_posts_custom_column", array($this, "set_eventsList_content"), 10, 2);
                    add_action("admin_enqueue_scripts", array($this, "kCal_admin_scripts"));
                }
                add_filter("upload_mimes", array($this, "kCal_custom_mime_types"));
            }
        }
        public function kCal_init(){
            //Register Taxonomy
            $taxonomy_labels = array(
                'name'          => __('Calendars'),
                'singular_name' => __('Calendar'),
                'menu_name'     => __('Calendars'),
                'edit_item'     => __('Edit Calendars'),
                'view_item'     => __('View Calendars'),
                'update_item'   => __('Update Calendars'),
                'add_new_item'  => __('Add New Calendars'),
                'new_item_name' => __('New Calendar'),
                'parent_item'   => __('Parent Calendar'),
                'search_items'  => __('Search Calendars')
            );
            $taxonomy = array(
                'labels'        => $taxonomy_labels,
                'public'        => true,
                'show_ui'       => true,
                'show_tagcloud' => false,
                'hierarchical'  => true,
                'rewrite'       => array(
                    'slug'          => 'calendar',
                    'with_front'    => false,
                ),
                'capabilities'  => array(
                    'manage_terms'  => 'edit_events',
                    'edit_terms'    => 'edit_events',
                    'delete_terms'  => 'delete_events',
                    'assign_terms'  => 'edit_events'
                ),
                'show_in_rest' => true

            );
            register_taxonomy("calendar", "event", $taxonomy);
            //Register Post Type
            $labels = array(
                'name'              => __( "Events" ),
                'singular_name'     => __( "Event" ),
                'menu_name'         => __("Events"),
                'name_admin_bar'    => __("Events"),
                'add_new'           => __("Add New"),
                'add_new_item'      => __("Add New Event"),
                'edit_item'         => __("Edit Event"),
                'new_item'          => __("New Event"),
                'view_item'         => __("View Details"),
                'search_items'      => __("Search Events"),
                'not_found'         => __("No Events Found"),
                'not_found_in_trash'=> __("No events found in trash"),

            );

            $supports = array("title", "editor", "thumbnail", "excerpt");

            $rewrite = array(
                "slug"          => "events",
                "with_front"    => false,
                "pages"         => true
            );
            register_post_type( "event",
                array('labels'          => $labels,
                  'description'         => "Calendar Manager",
                  'public'              => true,
                  'has_archive'         => true,
                  'show_ui'             => true,
                  'show_in_menu'        => true,
                  'show_in_admin_bar'   => true,
                  'menu_position'       => 20,
                  'menu_icon'           => 'dashicons-calendar',
                  'capability_type'     => array("post", "posts", "event", "events"),
                  'map_meta_cap'        => true,
                  'hierarchical'        => false,
                  'supports'            => $supports,
                  'taxonomies'          => array("calendar"),
                  'rewrite'             => $rewrite,
                  'show_admin_column'   => true
                )
            );

            $adminRole = get_role("administrator");
            $adminRole->add_cap("edit_events");
            $adminRole->add_cap("delete_events");

            if( is_active_widget( 'kCalQuickView' ) ) { // check if search widget is used
                wp_enqueue_script('kcalendar-mini');
                wp_enqueue_style('kcalendar-mini');
            }

			$editorRole = get_role("editor");
			$editorRole->add_cap("edit_events");
            $editorRole->add_cap("delete_events");

			$authorRole = get_role("author");
			$authorRole->add_cap("edit_events");
            $authorRole->add_cap("delete_events");

        }
        /**
        * Flush re-write rules when plugin is activated.
        * @access public
        */
        public function on_activate(){
           flush_rewrite_rules();
        }
        /**
        * Flush re-write rules when plugin is de-activated.
        * @access public
        */
        public function on_deactivate(){
			flush_rewrite_rules();
			$adminRole = get_role("administrator");
			$adminRole->remove_cap("edit_events");
			$adminRole->remove_cap("delete_events");

			$editorRole = get_role("editor");
			$editorRole->remove_cap("edit_events");
			$editorRole->remove_cap("delete_events");

			$authorRole = get_role("author");
			$authorRole->remove_cap("edit_events");
			$authorRole->remove_cap("delete_events");
        }
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
        public function kCal_custom_mime_types($mimes){
          $mimes["ics"] = "text/calendar";
          return $mimes;
        }
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
        public function set_eventsList_content($columnName, $postID){
            switch($columnName){
                case "taxcalendar":
                    echo $this->get_eventColumn_calendar($postID);
                    break;
                default:
                    break;
            }
        }
        public function kCal_front_scripts(){
            $o = get_option("kcal_settings");
            $calPage = (isset($o["fullcalendar_page"]) && !empty($o["fullcalendar_page"])) ? $o["fullcalendar_page"] : "";
            //$eventPage = (isset($o["eventDetails_page"]) && !empty($o["eventDetails_page"])) ? $o["eventDetails_page"] : "";
            $pURL = trailingslashit(plugins_url()."/k-cal");
            $currentPage = (isset($_SERVER["QUERY_STRING"])) ? basename(str_replace("?".$_SERVER["QUERY_STRING"], "", $_SERVER["REQUEST_URI"])) : str_replace("?", "", basename($_SERVER["REQUEST_URI"]));

            wp_register_script("kcalendar", $pURL ."js/calendar.js", array("jquery", "jquery-ui"), "1.0", true);
            wp_enqueue_script("kcalendar");
            wp_register_style("calCSS", $pURL ."vendors/fullcalendar/fullcalendar.css", array(), "1.5.3");
            wp_register_style("kcalCSS", $pURL ."css/calendar-min.css", array(), "1.5.3");
            wp_register_style("jquery-ui", $pURL ."js/jquery-ui/css/smoothness/jquery-ui-1.10.3.custom.min.css", array(), "1.10.3");
            wp_enqueue_style("calCSS");
            wp_enqueue_style("kcalCSS");
            wp_enqueue_style("jquery-ui");
            wp_localize_script("kcalendar", "ajax_object", array("ajax_url" => admin_url("admin-ajax.php")));

            wp_register_style("eventsCSS", admin_url("admin-ajax.php")."?action=eventListCSS");
            wp_enqueue_style("eventsCSS");
            if( is_active_widget( false, false, 'kcal-mini-widget' ) ){
                wp_enqueue_style("calMiniCSS", $pURL . "/css/calendarMini-min.css");
                wp_enqueue_script("kcalMiniJS", $pURL."/js/mini-calendar-min.js", array("jquery"), false, true);
                wp_localize_script("kcalMiniJS", "ajax_object", array("ajax_url" => admin_url("admin-ajax.php")));
            }

        }
        public function kCal_admin_scripts(){
            $pURL = trailingslashit(plugins_url()."/k-cal");
            wp_register_script("jquery-ui", $pURL . "js/jquery-ui/js/jquery-ui-1.12.1.min.js", array("jquery"), "1.12.1", true);

            wp_register_script("kcalendar", $pURL ."js/calendar.js", array("jquery", "jquery-ui"), "1.0", true);
            wp_enqueue_script("kcalendar");
            wp_register_style("calCSS", $pURL ."vendors/fullcalendar/fullcalendar.css", "1.5.3");
            wp_register_style("jquery-ui", $pURL ."js/jquery-ui/css/smoothness/jquery-ui-1.10.3.custom.min.css", "1.10.3");
            wp_enqueue_style("calCSS");
            wp_enqueue_style("jquery-ui");

            wp_register_script("adminCalendar", $pURL ."js/adminCalendar.js", array("kcalendar", "jquery-ui-core", "jquery-ui-datepicker"), "2.0", true);
            wp_register_script("jscolor", $pURL ."vendors/jscolor/jscolor.js", array(), true);
            wp_enqueue_script("adminCalendar");
            wp_enqueue_script("jscolor");
            wp_localize_script("jscolor", "url_object", array("plugin_url" => $pURL ."vendors/jscolor/"));
            wp_register_style("kcal-admin-css", $pURL ."css/admin.min.css");
            wp_enqueue_style("kcal-admin-css");
            wp_enqueue_style('thickbox');
            wp_enqueue_script('thickbox');
            wp_localize_script("adminCalendar", "kcal_object", array("edit_url" => admin_url("post.php?action=edit")));

        }
        /**
        * Admin subpage to display the fullcalendar view
        */
        public function kCal_display_admin_calendar(){
            include_once(__DIR__ ."/Apps/views/index.php");
        }
        /**
        * Admin subpage to allow events importing
        */
        public function kCal_display_importEvents(){
          $imported = array();
          if (isset($_POST["kcal_importRSS_url"]) && filter_var($_POST["kcal_importRSS_url"], FILTER_VALIDATE_URL)){
            $ca = new AdminCalendar();
            $calendar = (isset($_POST["kcal_importRSS_calendar"]) && (int)$_POST["kcal_importRSS_calendar"] > 0) ? (int)$_POST["kcal_importRSS_calendar"] : 0;
            $imported = $ca->import_parse_RSS($_POST["kcal_importRSS_url"], $calendar);
          }

          if (isset($_FILES["kcal_importICS_file"])){
            $ics = wp_handle_upload($_FILES["kcal_importICS_file"], array("test_form" => false));
            if (isset($ics["file"])){
              $ca = new AdminCalendar();
              $calendar = (isset($_POST["kcal_importICS_calendar"]) && (int)$_POST["kcal_importICS_calendar"] > 0) ? (int)$_POST["kcal_importICS_calendar"] : 0;
              $imported = $ca->import_parse_ICS($ics["file"], $calendar);
            }
            else{
              $imported["error"][] = __("ICS file could not be uploaded.", 'kcal');
            }
          }
          include_once(__DIR__ ."/Apps/views/import.php");
        }
        /**
        * Hook to add fullcalendar and import events sub pages
        */
        public function kCal_add_calendar_menu(){
            add_submenu_page("edit.php?post_type=event", "Manage Events", "Calendar View", "edit_events", "edit.php?view=calendar" ,array($this, "kCal_display_admin_calendar"));
            add_submenu_page("edit.php?post_type=event", "Import Events", "Import Events", "edit_events", "edit.php?view=import" ,array($this, "kCal_display_importEvents"));
        }


        public function kCal_register_widgets(){
            if (class_exists("kCalListView")){
                register_widget("kCalListView");
            }
            if (class_exists("kCalQuickView")){
                register_widget("kCalQuickView");
            }
            if (class_exists("kCalfilterEventsDate")){
                register_widget("kCalfilterEventsDate");
            }
            if (class_exists("kCalCalendarSidebar")){
                register_widget("kCalCalendarSidebar");
            }

            register_sidebar(array(
                    'name'          => esc_html__( 'Events Archive Sidebar', 'kCal' ),
                    'id'            => "sidebar-kcal-events",
                    'description'   => esc_html__( 'Appears on the calendar archive pages', 'kCal' ),
                    'before_widget' => '<aside id="%1$s" class="widget %2$s">',
                    'after_widget'  => '</aside>',
                    'before_title'  => '<h3 class="widget-title">',
                    'after_title'   => '</h3>'
            ));
            register_sidebar(array(
                    'name'          => esc_html__( 'Single Event Sidebar', 'kCal' ),
                    'id'            => "sidebar-kcal-single",
                    'description'   => esc_html__( 'Appears on single event page', 'kCal' ),
                    'before_widget' => '<aside id="%1$s" class="widget %2$s">',
                    'after_widget'  => '</aside>',
                    'before_title'  => '<h3 class="widget-title">',
                    'after_title'   => '</h3>'
            ));
        }
        public function buildCalendarsCSS()
        {
            $c = new Calendar();
            header("Content-Type: text/css");
            die($c->buildCalendarCSS());
        }
        /**
         * Add an extra field to the calendar taxonomy editor
         * @param integer $tag
         */
        public function kCal_extra_calendar_field($tag){
            $term_id = $tag->term_id;
            $cat_meta_colour = get_option( "calendar_".$term_id, "#cccccc");
            $cat_meta_textcolour = get_option( "calendar_text_".$term_id, "#000");
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
        public function kCal_mb_nonce(){}
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
                echo "<p><strong>Recurrence Dates</strong></p>";
                echo "<ol>";
                foreach($recurrenceDates as $index => $rDate){
                    $liclass = ($index %2 > 0)? " class=\"alt\"" : "";
                    $startTime = array_keys($rDate);
                    list($endTime, $metaID) = array_values($rDate[$startTime[0]]);
                    $startDate = date("Y-m-d", $startTime[0]);
                    $endDate = date("Y-m-d", $endTime);
                    $display = date("D, M j, Y", $startTime[0]);
                    if ($endDate != $startDate){
                        $display .= " " . date("g:i a", $startTime[0])." - " .date("D, M j, Y", $endTime)." ".date("g:i a", $endTime);
                    }
                    else{
                       $display .= " " .date("g:i a", $startTime[0]). "-".date("g:i a", $endTime);
                    }
                    $startDate .= date(" h:i:s A", $startTime[0]);
                    $endDate .= date(" h:i:s A", $endTime);
                    echo "<li{$liclass}>";
                    echo $display;
                    echo "<span class=\"recurrence-controls\"><label id=\"edit-recur-{$metaID}\" data-post=\"{$post->ID}\" data-start=\"{$startDate}\" data-end=\"{$endDate}\" title=\"Edit Date\" class=\"recur-edit\"><i class=\"ki kicon-pencil2\"></i></label>
                            <label id=\"del-recur-{$metaID}\" data-post=\"{$post->ID}\" title=\"Delete Date\" class=\"del-recur\"><i class=\"ki kicon-bin\"></i></label></span>";

                    echo "</li>";

                }
                echo "</ol>";
                include_once(__DIR__ ."/Apps/views/delete_recur_single.php");
                include_once(__DIR__ ."/Apps/views/edit_recurring_single.php");
            }
        }

        public function kcal_mb_eventURL($post){
            $registerURL = get_post_meta($post->ID, "_kcal_eventURL", true);
            echo "<label for=\"_kcal_eventURL\">URL for the Event Details Page</label><br />";
            echo "<input type=\"text\" name=\"_kcal_eventURL\" id=\"_kcal_eventURL\" value=\"".$registerURL."\" style=\"width: 100%;max-width: 400px\"/>";
        }
        /**
         * Filter the archive page if a year/month filter is selected
         * Get recurring events as part of the display
         * @param object $query
         */
        public function kCal_mainquery_filter_archive($query){
            if ($query->is_main_query() && $query->is_archive() && isset($query->query_vars["calendar"])){
                $months = array("January", "February","March","April","May","June","July","August","September",
                    "October","November","December");
                $start = strtotime(date("Y")."-".date("n")."-".date("d"));
                $end = strtotime(date("Y")."-12-31");
                if (isset($_GET["fy"]) && preg_match("/^(19|20)\d{2}$/", $_GET["fy"], $matchY) &&
                      isset($_GET["fm"]) && in_array($_GET["fm"], $months) ){

                    $start = strtotime($_GET["fm"]." 1, ".$_GET["fy"]);
                    $end = strtotime($_GET["fm"]." ".date("t", $start).", ".$_GET["fy"]."+7days");
                }
                $metaQuery = array(
                    "relation"  => "AND",
                    array(
                        "key"   => "_kcal_eventStartDate",
                        "value" =>  $start,
                        "compare"   => ">="
                    ),
                    array(
                        "key"   => "_kcal_eventEndDate",
                        "value" =>  $end,
                        "compare"   => "<="
                    )
                );
                $query->set("meta_query", $metaQuery );
                $query->set("orderby", "_kcal_eventStartDate");
                $query->set("order", "asc");
                add_filter("posts_request", array($this, "kCal_mainquery_filter_recur"));
            }
        }
        /**
         * Modify the WHERE clause of the main query to include recurring events
         * @global object $wpdb
         * @param string $input
         * @return string
         */
        public function kCal_mainquery_filter_recur($input){
            preg_match("/(WHERE\s1=1\s)([A-Za-z0-9\s\=\<\>\(\)\%\_\-\'\"\`\.]+)(GROUP)/i", $input, $matches);

            if (isset($matches[2])){
                global $wpdb;
                $relations = explode("AND", $matches[2]);
                if (!empty($relations)){
                    $modified = $matches[2];
                    unset($relations[0]);
                    unset($relations[4]);
                    unset($relations[5]);
                    $where = array_values($relations);
                    $where[1] = str_replace("_kcal_eventStartDate", "_kcal_recurrenceDate", $where[1]);
                    preg_match("/([0-9]+)/", $where[2], $start);
                    if (isset($start[1])){
                        $where[2] = "SUBSTR(CAST(".$wpdb->prefix."postmeta.meta_value AS CHAR), 8, ".strlen($start[1]).") >= " . $start[1] . "))";
                    }
                    $modified .= " OR (" . implode(" AND ", $where).") ";
                }
                $input = str_replace($matches[2], $modified, $input);
            }
            remove_filter("posts_request", array($this, "kCal_mainquery_filter_recur"));
            return $input;
        }
    }
}
$kCal = new kCal();
if (is_admin()){
    $kcalSettings = new kCalSettings();
}


