<?php
if (is_admin())
{
    $months = array("01"=>"Jan","02"=>"Feb","03"=>"Mar","04"=>"Apr","05"=>"May","06"=>"Jun","07"=>"Jul","08"=>"Aug","09"=>"Sep","10"=>"Oct","11"=>"Nov","12"=>"Dec");
    $year = date("Y");
    $hours = array("01"=>"1","02"=>"2","03"=>"3","04"=>"4","05"=>"5","06"=>"6","07"=>"7","08"=>"8","09"=>"9","10"=>"10","11"=>"11","00"=>"12");

    $cal = new AdminCalendar();

    echo '<style type="text/css">';
    $cal->buildCalendarCSS();
    echo '</style>';
    
?>


<div id="adminCalendarWrap">
<!--start section of dialog boxes -->
<!--create a new calendar-->

<?php
//include_once(__DIR__ ."/new_calendar.php");
//include_once(__DIR__ ."/edit_calendar.php");
include_once(__DIR__ ."/event_details.php");
include_once(__DIR__ ."/delete_event.php");
include_once(__DIR__ ."/edit_recurring_event.php");
?>
<!--end section of dialog boxes -->
<div id="leftColAdmin">
	<p>Current Calendars:</p>
	<?php echo($cal->display_calendar_list_admin()); ?>
        <a id="calendar-new" value="Create New Calendar" href="<?php echo admin_url();?>edit-tags.php?taxonomy=calendar&post_type=event">Create New Calendar</a>
</div>
<div id="centreCol">
	<div id="calendarTitle"><h2 class="fc-header-title"></h2></div>

	<!--calendar div must remain empty for fullCalendar.js to fill-->
	<div id="calendar"></div>
</div>
</div>
<?php
}