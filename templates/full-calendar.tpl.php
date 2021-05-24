<?php
/**
 * Template Name: Full Calendar
 * Description: Displays a javascript calendar for all calendars on a blog
 */
if (isset($_GET["act"]) && $_GET["act"] == "ics") :
    $cc = new CalendarController();
    $cc->addToCalendar();
else:

$cal =  new CalendarWidgets();

$eventDetails["imagePath"] = KCAL_HOST_URL ."/img/spacer.jpg";
if (isset($_GET["event"]) && preg_match("/^([0-9]{1,6})(_)?([0-9]{1,6})?$/",$_GET["event"],$matches)) :
    $eventDetails = $cal->get_event_details_byID($_GET);
    $eventDetails["date"] = date("F, j Y g:i a", strtotime($eventDetails["start"])) ." - ";
    $eventDetails["date"] .= (date("Y-m-d", strtotime($eventDetails["start"])) == date("Y-m-d", strtotime($eventDetails["end"]))) ? date("g:i a", strtotime($eventDetails["end"])) : date("F, j Y g:i a", strtotime($eventDetails["end"]));
    if (empty($eventDetails["imagePath"])) :
        $eventDetails["imagePath"] = KCAL_HOST_URL ."/img/spacer.jpg";
	endif;
endif;
?>
<section id="calendarWrap" class="kcal-fullcalendar" aria-label="<?php _e('Events calendar with month, week and list view', 'kcal'); ?>">
	<div id="centreCol">
		<!--calendar div should remain empty for fullCalendar.js to fill-->
		<div class="kcal-loading init" role="status" data-loading="<?php _e('Loading events', 'kcal'); ?>" data-loaded="<?php _e('Events are loaded', 'kcal'); ?>">
			<p class="loading-text"><?php _e('Loading events', 'kcal'); ?></p>
			<span class="kcal-ellipsis" role="decoration"><span></span><span></span><span></span><span></span></span>
		</div>
		<div id="calendar"></div>
	</div>
	<div id="leftCol">
        <div class="current-calendars">
            <h3><?php _e('Current Calendars:' , 'kcal'); ?></h3>
			<div class="screen-reader-text visuallyhidden"><?php _e('Check/Uncheck calendars to dynamically filter events', 'kcal'); ?></div>
<?php
            (isset($_GET["view"])) ? $cal->get_calendars_view($_GET["view"], '', '') : $cal->get_calendars_view(false, '', '');
?>
        </div>
	</div> <!-- end leftCol -->
	<div class="clearfix"></div>
</section> <!-- end calendarwrap -->
<div class="quickview-popup" id="dlgEventDetails" aria-hidden="true" tabindex="-1" role="dialog" >
	<div class="animated fadeInUp"><button class="close-btn" aria-label="<?php _e('Close dialog', 'kcal'); ?>">&#215;</button>
		<h2>
			<span <?php if (isset($eventDetails["className"]) && !empty($eventDetails["className"])){echo ' class="'.$eventDetails["className"].'"';};?>><?php if (isset($eventDetails["title"])){ echo $eventDetails["title"];}?></span>
		</h2>
		<div class="quickview-wrap">
			<div class="qv-recurring" aria-hidden="true" style="display:none"><?php _e('This event recurs', 'kcal');?><i></i></div>
			<div class="qv-allday" aria-hidden="true" style="display:none"><?php _e('All Day Event', 'kcal'); ?></div>
			<div class="qv-ics" aria-hidden="true" style="display:none" id="event-ics"></div>
			<table>
			<tr <?php if (isset($eventDetails["imagePath"]) && strstr($eventDetails["imagePath"], "spacer.jpg")) { echo 'style="display:none"';}?>><td colspan="2"><img src="<?php echo $eventDetails["imagePath"];?>" alt="" /></td></tr>
			<tr><td class="strong"><?php _e('Date:', 'kcal'); ?></td><td><?php if (isset($eventDetails["date"])) { echo $eventDetails["date"]; } ?></td></tr>
			<tr><td class="strong"><?php _e('Location:', 'kcal'); ?></td><td><?php if (isset($eventDetails["location"])) { echo $eventDetails["location"]; } ?></td></tr>
			<tr <?php if (isset($eventDetails["description"]) && empty($eventDetails["description"])) { echo 'style="display:none"';}?>>
				<td class="strong"><?php _e('Details:', 'kcal'); ?></td><td><?php if (isset($eventDetails["description"])) { echo $eventDetails["description"]; } ?></td>
			</tr>
			<tr <?php if (isset($eventDetails["altUrl"]) && empty($eventDetails["altUrl"])) { echo 'style="display:none"';}?>>
				<td colspan="2"><a href="<?php echo $eventDetails["altUrl"];?>" id="eventMoreInfo"><span class="k-icon-web" role="icon"></span><?php _e('Learn More', 'kcal'); ?></a></td>
			</tr>
			</table>
		</div> <!-- end qv wrap-->
	</div> <!-- end animated -->
</div> <!-- end qv popup -->
<?php
endif;