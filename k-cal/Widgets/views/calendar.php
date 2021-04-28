<?php

$eventDetails = "";

if (isset($_GET['event']) && preg_match("/^([0-9]{1,6})(_)?([0-9]{1,6})?$/",$_GET["event"],$matches)){
    $eventDetails = $cal->get_event_details_byID($_GET);
    $date = new DateTime('', new DateTimeZone(get_option('gmt_offset')));
    $date->setTimestamp($eventDetails["start"]);

    if (date("Y-m-d", strtotime($eventDetails["start"])) == date("Y-m-d", strtotime($eventDetails["end"]))) {
        $eventDetails["date"] = $date->format('F, j Y g:i a') ." - ";
        $date->setTimestamp($eventDetails["end"]);
        $eventDetails["date"] .= $date->format('g:i a');
    } else {
        $eventDetails["date"] = $date->format('F, j Y') ." - ";
        $date->setTimestamp($eventDetails["end"]);
        $eventDetails["date"] .= $date->format('F, j Y');
    }

}

$months = array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");

?>
<div id="calendarWrap">

	<div id="centreCol">
		<div id='calendarTitle'><h2 class="fc-header-title"></h2></div>
		<!--calendar div should remain empty for fullCalendar.js to fill-->
		<div id="calendar"></div>
		<div id="eventsWidget"><?php (isset($_GET['view'])) ? $cal->fullCalendar_upcoming_events(10,array($_GET['view'])) : $cal->fullCalendar_upcoming_events(10); ?></div>
	</div>
	<div id="leftCol">
            <div class="current-calendars">
            <h4>Current Calendars:</h4>
            <?php (isset($_GET['view'])) ? $cal->get_calendars_view($_GET['view']) : $cal->get_calendars_view(); ?>
            <div class="all-calendars"><strong>All Calendars: </strong>
                <span>
                    <a href="/feeds/events"><img src="/src/Modules/Calendar/Assets/img/rss-16.png" alt="RSS" title="RSS" width="16px" height="16px" /></a>
                    <a href="/feeds/ics"><img src="/src/Modules/Calendar/Assets/img/calendar-month.png" alt="ICS" title="Add to my Calendar" width="16px" height="16px" /></a>
                </span>
            </div>

            </div>
	</div>
<div class="clearfix"></div>
</div>
<div class="quickview-popup" id="dlgEventDetails">
<div class="animated fadeInUp"><a class="close-btn" href="#"><i class="ss-icon">&#x2421;</i></a>
<h2<?php if (isset($eventDetails["className"]) && !empty($eventDetails["className"])){echo ' class="'.$eventDetails["className"].'"';};?>>
    <?php if (isset($eventDetails["title"])){ echo $eventDetails["title"];}?>
</h2>
<table>
<tr><td>Date:</td><td><?php if (isset($eventDetails["date"])) { echo $eventDetails["date"]; } ?></td></tr>
<tr><td>Location:</td><td><?php if (isset($eventDetails["location"])) { echo $eventDetails["location"]; } ?></td></tr>
<tr <?php if (isset($eventDetails["description"]) && empty($eventDetails["description"])) { echo 'style="display:none"';}?>>
    <td>Details:</td><td><?php if (isset($eventDetails["description"])) { echo $eventDetails["description"]; } ?></td>
</tr>
<tr <?php if (isset($eventDetails["altUrl"]) && empty($eventDetails["altUrl"])) { echo 'style="display:none"';}?>>
    <td>More Info:</td><td><?php if (isset($eventDetails["altUrl"])) { echo $eventDetails["altUrl"]; } ?></td>
</tr>
</table>
<a href="<?php if (isset($eventDetails["title"])) { echo "/feeds/ics?event=" . $_GET['event'];}?>" class="btn-add-calendar" id="event-ics">Add to My Calendar</a>
</div>
</div>
