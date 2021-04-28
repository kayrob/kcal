<?php

/*
 * Events archive content.
 */
$postIter = get_query_var("post_iter");
$numPosts = get_query_var("num_results");
$postID = get_the_ID();
$meta = get_post_meta($postID);

$eventStart = get_query_var("eventStart");
$eventEnd = get_query_var("eventEnd");
$eventID = get_query_var("eventID");

$eventLink = $meta["_kcal_eventURL"][0];
$eventDate = date("D M j, Y", $eventStart);
if (date("D M j, Y", $eventStart) != date("D M j, Y", $eventEnd)){
    $eventDate .= " - ". date("D M j, Y", $eventEnd);
}

$eventTime = date("g:i A", $eventStart)."-".date("g:i A", $eventEnd);

$location = $meta["_kcal_location"][0];

$calendar = get_term_by("slug", get_query_var("calendar"), "calendar");

?>
<article id="post-<?php echo $eventID; ?>" <?php post_class(); ?>>
<div class="events-wrapper">
<div class="news-events-title"><?php echo get_the_title();?></div>
<div class="event-content">
<strong>Date:</strong><span><?php echo $eventDate;?></span><br />
<strong>Time:</strong><span><?php echo $eventTime;?></span><br/>
<?php
if (!empty($location)){
    $linkHref = "https://maps.google.com?q=".str_replace(" ", "+", str_replace(",","", $meta["_kcal_location"][0]));
    echo "<strong>Place:</strong><span><a href=\"{$linkHref}\">".$meta["_kcal_location"][0]."</a></span><br />";
}
?>
</div>
<div id="event-interact">
<?php
if (!empty($eventLink) && strtolower($eventLink) != "null" && false !== $eventLink){
    echo "<a class=\"button pin blue\" href=\"{$eventLink}\" target=\"_blank\"><span>View Details</span><i class=\"fa icon-link\"></i></a>";
}
?>
<a target="_blank" class="button alt kcal-ics" href="<?php echo get_term_link($calendar);?>?act=ics&calID=<?php echo $calendar->term_id;?>&eID=<?php echo $eventID;?>">
    <span>Add to Calendar</span><i class="fa icon-add"></i></a>
</div>

</div>
</article>
<div class="clearfix"></div>
