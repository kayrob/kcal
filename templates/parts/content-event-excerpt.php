<?php

/*
 * Events archive content.
 */

$postIter = get_query_var('post_iter',0);
$numPosts = get_query_var('num_results',0);
$postID = get_the_ID();

$meta = get_post_meta($postID);

$eventStart = get_query_var('eventStart', $meta['_kcal_eventStartDate'][0]);
$eventEnd = get_query_var('eventEnd', $meta['_kcal_eventEndDate'][0]);
$eventID = get_query_var('eventID', $postID);

$eventLink = $meta['_kcal_eventURL'][0];

try {
	$timezone = $meta['_kcal_timezone'][0];
} catch (\Exception $e) {
	$timezone = get_option('gmt_offset');
}

$dateTime = new \DateTime('now', new DateTimeZone($timezone));

$dateFormatOption = get_option('date_format');
$timeFormatOption = get_option('time_format');

$dateTime->setTimestamp($eventStart);
$eventStartDate = $dateTime->format($dateFormatOption);
$eventStartTime = $dateTime->format($timeFormatOption);

$dateTime->setTimestamp($eventEnd);
$eventEndDate = $dateTime->format($dateFormatOption);
$eventEndTime = $dateTime->format($timeFormatOption);

$eventTime = '';
if ($eventStartDate != $eventEndDate){
	$eventStartDate .= ' - '. $eventEndDate;
} else {
	$eventTime = (!isset($meta['_kcal_allDay'][0]) || $meta['_kcal_allDay'][0] == 0) ? $eventStartTime . '-' . $eventEndTime : __('All Day Event', 'kcal');
}

$allIDs = explode('-', $eventID);

$linkHref = '';
$location = '';

if (!empty($meta['_kcal_location'][0])) :
  $linkHref = 'https://maps.google.com?q='.str_replace(' ', '+', str_replace(',','', $meta['_kcal_location'][0]));
endif;

$calendar = get_term_by('slug', get_query_var('calendar'), 'calendar');
$calColour = get_option('calendar_'.$calendar->term_id);
$calText = get_option('calendar_text_'.$calendar->term_id);

if ($calText != '#fff') {
	$calText = '#000';
}
$permalink = get_permalink();

if (isset($allIDs[1])) {
	$permalink .= '?r='.$allIDs[1];
}

$postClass = (0 == $postIter ) ? 'no-border' : '';

?>
<article id='post-<?php echo $eventID; ?>' <?php post_class($postClass); ?>>
<div class='events-wrapper entry-summary'>
<h2><a href='<?php echo $permalink;?>'><?php echo get_the_title();?></a></h2>
<?php  if ($numPosts > 0): //not search ?>
    <p class='post-meta event-meta'>
      <span class='highlight uppercase' style='background-color:#<?php echo $calColour;?>;color:<?php echo $calText;?>'><?php echo $eventStartDate;?></span>
      <?php if (!empty($eventTime)) : ?>
        <span class='pipe'>&#8226;</span>
        <?php echo $eventTime;?>
      <?php endif; ?>
    </p>
<?php endif; ?>
<div id='event-interact' class='post-meta'>
<?php if (!empty($linkHref)) : ?>
<div>
  <a href='<?php echo $linkHref;?>'>
    <span class='k-icon-mapmarker'></span>
    <span class='text'><?php echo $meta['_kcal_location'][0];?></span>
  </a>
</div>
<?php endif; ?>

<?php if (!empty($calendar)): ?>
<div>
    <a target='_blank' class='alt kcal-ics' href='<?php echo get_term_link($calendar);?>?act=ics&calID=<?php echo $calendar->term_id;?>&eID=<?php echo $eventID;?>'>
    <span class='k-icon-calendar'></span>
    <span class='text'><?php _e('Add to Calendar', 'kcal'); ?></span>
  </a>
</div>
<?php endif; ?>

<?php if (!empty($eventLink )) : ?>
<div>
  <a target='_blank' href='<?php echo $eventLink;?>'>
    <span class='k-icon-info'></span>
    <span class='text'><?php _e('More Info/Register', 'kcal'); ?></span>
  </a>
</div>
<?php endif; ?>

</div><!--end interact -->

</div>
</article>
<div class='clearfix'></div>
<?php if (isset($postIter) && $postIter < ($numPosts-1)) : ?>
    <div class='event-spacer'></div>
<?php endif;