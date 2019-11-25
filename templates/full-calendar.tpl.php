<?php
/**
 * Template Name: Full Calendar
 * Description: Displays a javascript calendar for all calendars on a blog
 */
$cal =  new CalendarWidgets();
$o = get_option("kcal_settings");
$rssPage = (isset($o["rssFeed_page"]) && !empty($o["rssFeed_page"])) ? $o["rssFeed_page"] : "";
$icsPage = (isset($o["icsFeed_page"]) && !empty($o["icsFeed_page"])) ? $o["icsFeed_page"] : "";
$eventDetails = array();

$eventDetails["imagePath"] = plugins_url() ."/k-cal/img/spacer.jpg";
if (isset($_GET["event"]) && preg_match("/^([0-9]{1,6})(_)?([0-9]{1,6})?$/",$_GET["event"],$matches)){
    $eventDetails = $cal->get_event_details_byID($_GET);
    $eventDetails["date"] = date("F, j Y g:i a", strtotime($eventDetails["start"])) ." - ";
    $eventDetails["date"] .= (date("Y-m-d", strtotime($eventDetails["start"])) == date("Y-m-d", strtotime($eventDetails["end"]))) ? date("g:i a", strtotime($eventDetails["end"])) : date("F, j Y g:i a", strtotime($eventDetails["end"]));
    if (empty($eventDetails["imagePath"])){
        $eventDetails["imagePath"] = dirname(dirname(get_stylesheet_directory())) ."/plugins/k-cal/img/spacer.jpg";
    }
}

$months = array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");

get_header();
?>
<div id="primary" class="content-area">
<div id="content" class="site-content" role="main">
    <article class="hentry">
<div id="calendarWrap">
	
	<div id="centreCol">
		<div id="calendarTitle"><h2 class="fc-header-title"></h2></div>
		<!--calendar div should remain empty for fullCalendar.js to fill-->
		<div id="calendar"></div>
		<div id="eventsWidget"><?php (isset($_GET['view'])) ? $cal->fullCalendar_upcoming_events(10,array($_GET['view'])) : $cal->fullCalendar_upcoming_events(10); ?></div>
	</div>
	<div id="leftCol">
            <div class="all-calendars">
<?php
          if (!empty($rssPage) && !empty($icsPage)){
?>
                <strong>All Calendars: </strong>
                   <span> 
<?php
                    if (!empty($rssPage)){
?>
                        <a href="/<?php echo $rssPage;?>"><i class="k-icon-feed" title="Subscribe"></i></a>
<?php
                    }
                    if (!empty($icsPage)){
?>
                        <a href="/<?php echo $icsPage;?>"><i class="k-icon-calendar" title="Add to Calendar"></i></a>
<?php
                    }
?>
                   </span>
<?php
            }
?>
            </div><!--end all calendars-->
            <div class="current-calendars">
            <h4>Current Calendars:</h4>
<?php       
            (isset($_GET["view"])) ? $cal->get_calendars_view($_GET["view"], $rssPage, $icsPage) : $cal->get_calendars_view(false, $rssPage, $icsPage); 
?>
            </div>
	</div>
<div class="clearfix"></div> 
</div>
<div class="quickview-popup" id="dlgEventDetails">
<div class="animated fadeInUp"><a class="close-btn" href="#">&#215;</a>
<h2>
    <span <?php if (isset($eventDetails["className"]) && !empty($eventDetails["className"])){echo ' class="'.$eventDetails["className"].'"';};?>><?php if (isset($eventDetails["title"])){ echo $eventDetails["title"];}?></span>
    <a href="<?php if (isset($eventDetails["title"]) && !empty($icsPage)) { echo "/{$icsPage}?event=" . $_GET['event'];}?>" data-ics="<?php echo $icsPage;?>" class="btn-add-calendar" id="event-ics"><small class="k-icon-calendar"></small><small class="assistive-text">Add to Calendar</small></a>
</h2>
<div class="quickview-wrap">
<table>
    <tr <?php if (isset($eventDetails["imagePath"]) && strstr($eventDetails["imagePath"], "spacer.jpg")) { echo 'style="display:none"';}?>><td colspan="2"><img src="<?php echo $eventDetails["imagePath"];?>" alt="" /></td></tr>
<tr><td class="strong">Date:</td><td><?php if (isset($eventDetails["date"])) { echo $eventDetails["date"]; } ?></td></tr>
<tr><td class="strong">Location:</td><td><?php if (isset($eventDetails["location"])) { echo $eventDetails["location"]; } ?></td></tr>
<tr <?php if (isset($eventDetails["description"]) && empty($eventDetails["description"])) { echo 'style="display:none"';}?>>
    <td class="strong">Details:</td><td><?php if (isset($eventDetails["description"])) { echo $eventDetails["description"]; } ?></td>
</tr>
<tr <?php if (isset($eventDetails["altUrl"]) && empty($eventDetails["altUrl"])) { echo 'style="display:none"';}?>>
    <td colspan="2"><a href="<?php echo $eventDetails["altUrl"];?>" id="eventMoreInfo"><span class="k-icon-web"></span>&nbsp;More Info</a></td>
</tr>
</table>
</div>
</div>
</div>
    </article>
</div><!-- #content -->
</div><!-- #primary -->
<?php
get_footer();