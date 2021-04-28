<?php

/*
 * Events archive content.
 */
$postIter = get_query_var("post_iter");
$numPosts = get_query_var("num_results");
$postID = get_the_ID();
$meta = get_post_meta($postID);

$eventStart = $meta["_kcal_eventStartDate"][0] ;
$eventEnd = $meta["_kcal_eventEndDate"][0];
$eventID = $postID;

$eventLink = $meta["_kcal_eventURL"][0];
$eventDate = date("D M j, Y", $eventStart);
if (date("D M j, Y", $eventStart) != date("D M j, Y", $eventEnd)){
    $eventDate .= " - ". date("D M j, Y", $eventEnd);
}

$eventTime = date("g:i A", $eventStart)."-".date("g:i A", $eventEnd);

$location = $meta["_kcal_location"][0];

//$calendar = get_term_by("slug", get_query_var("calendar"), "calendar");

?>
<article id="post-<?php echo $eventID; ?>" <?php post_class("news-events-wrapper"); ?>>
    <header class="entry-header">
            <h2 class="entry-title">
                <span><?php the_title(); ?></span>
                <div class="entry-title-border"></div>
            </h2>
    </header>
    <div class="event-content">
    <strong>Date:</strong><span><?php echo $eventDate;?></span><br />
    <strong>Time:</strong><span><?php echo $eventTime;?></span><br/>
    <?php
    if (!empty($location)){
        $linkHref = "https://maps.google.com?q=".str_replace(" ", "+", str_replace(",","", $meta["_kcal_location"][0]));
        echo "<strong>Place:</strong><span><a href=\"{$linkHref}\">".$meta["_kcal_location"][0]."</a></span><br />";
    }
    the_content();
    ?>
    </div>

    <div id="event-interact">
    <?php
    if (!empty($eventLink) && strtolower($eventLink) != "null" && false !== $eventLink){
        echo "<a class=\"button pin blue\" href=\"{$eventLink}\" target=\"_blank\"><span>View Details</span><i class=\"fa icon-link\"></i></a>";
    }
    ?>
    <a target="_blank" class="button alt kcal-ics" href="<?php //echo get_permalink($postID);?>?act=ics&calID=<?php //echo //$calendar->term_id;?>&eID=<?php //echo $eventID;?>">
        <span>Add to Calendar</span><i class="fa icon-add"></i></a>
    </div>
    <div class="clearfix"></div>
</article>
<div class="clearfix"></div>
