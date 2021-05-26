<?php

/*
 * Events archive content.
 * @package kCal
 */
if (!is_singular('event') ) :
	$event = get_posts(array('post_type' => 'event', 'posts_per_page' => 1, 'number_posts' => 1, 'post_status' => 'publish'));
	if (isset($event[0]->ID)) :
		$postID = $event[0]->ID;
	endif;
elseif (is_singular('event')) :
	$postID = get_the_ID();
endif;


if (isset($postID)) :
	$meta = get_post_meta($postID);
	$post = $eventID = get_post($postID);

	if (isset($_GET["r"]) && (int)$_GET["r"] > 0) :
		$cw = new CalendarWidgets();
		$recurData = $cw->retrieve_one_event(get_the_ID(), (int)$_GET["r"]);
		$recurMeta = unserialize($recurData->meta_value);
		$recurStart = array_keys($recurMeta);
		$recurEnd = $recurMeta[$recurStart[0]]["endDate"];
	endif;

	$dateTime = new \DateTime();
	$dateTime->setTimezone(new DateTimeZone($meta["_kcal_timezone"][0]) );


	$eventStart = (isset($recurStart[0])) ? $recurStart[0]: $meta["_kcal_eventStartDate"][0] ;
	$eventEnd = (isset($recurEnd)) ? $recurEnd : $meta["_kcal_eventEndDate"][0];
	$eventID = $postID;

	if (isset($_GET["r"]) && (int)$_GET["r"] > 0) :
		$eventID .= '-' . $_GET['r'];
	endif;

	$eventLink = $meta["_kcal_eventURL"][0];

	$dateFormatOption = get_option('date_format');
	$timeFormatOption = get_option('time_format');

	$dateTime->setTimestamp($eventStart);
	$eventStartDate = $dateTime->format($dateFormatOption);
	$eventStartTime = $dateTime->format($timeFormatOption);

	$dateTime->setTimestamp($eventEnd);
	$eventEndDate = $dateTime->format($dateFormatOption);
	$eventEndTime = $dateTime->format($timeFormatOption);

	$eventTime = '';
	if ($eventStartDate != $eventEndDate) :
		$eventStartDate .= ' - '. $eventEndDate;
	else:
	$eventTime = (!isset($meta['_kcal_allDay'][0]) || $meta['_kcal_allDay'][0] == 0) ? $eventStartTime.'-'.$eventEndTime : 'All Day Event';
	endif;

	//expired
	if (current_time('timestamp') > $dateTime->getTimestamp()) :
		$eventTime = '<i>This event has ended</i>';
	endif;

	$linkHref = '';

	if (!empty($meta["_kcal_location"][0])) :
		$linkHref = "https://maps.google.com?q=".str_replace(" ", "+", str_replace(",","", $meta["_kcal_location"][0]));
	endif;

	$calendar = wp_get_post_terms($postID, array('calendar'));
	$calColour = get_option('calendar_'.$calendar[0]->term_id);
	$calText = get_option('calendar_text_'.$calendar[0]->term_id);

	if (isset($header) && $header == 'yes') :
	?>
	<header><h1><?php echo get_the_title(); ?></h1></header>
	<?php
	endif;
	?>
	<p id="eventMeta" class="post-meta event-meta">
		<span class='highlight uppercase' style='background-color:#<?php echo $calColour;?>;color:<?php echo $calText;?>'><?php echo $eventStartDate;?></span>
		<?php if (!empty($eventTime)) : ?>
			<span class='pipe'>&#8226;</span>
			<span class="screen-reader-text"><?php _e('Event Date: ', 'kcal');?></span><?php echo $eventTime;?>
		<?php endif; ?>
	</p>
	<div class="event-content" aria-label="<?php _e('Event details', 'kcal'); ?>">
		<?php print(apply_filters('the_content', $post->post_content) ); ?>
	</div>
	<div id='event-interact' class='post-meta'>
		<?php if (!empty($linkHref)) : ?>
		<div>
			<a href='<?php echo $linkHref;?>' aria-label="<?php _e('View in Google Maps. Opens in a new window', 'kcal'); ?>">
			<span class='k-icon-mapmarker'></span>
			<span class='text'><?php echo $meta['_kcal_location'][0];?></span>
			</a>
		</div>
		<?php endif; ?>

		<?php if (!empty($calendar[0])): ?>
		<div>
			<a target='_blank' class='alt kcal-ics' href='<?php echo trailingslashit(home_url());?>?act=ics&calID=<?php echo $calendar[0]->term_id;?>&eID=<?php echo $eventID;?>' aria-label="<?php _e('Download event to calendar. Opens in a new window', 'kcal'); ?>">
			<span class='k-icon-calendar' role="decoration"></span>
			<span class='text'><?php _e('Add to Calendar', 'kcal');?></span>
			</a>
		</div>
		<?php endif; ?>

		<?php if (!empty($eventLink )) : ?>
		<div>
			<a target='_blank' href='<?php echo $eventLink;?>' aria-label="<?php _e('Opens in a new window', 'kcal');?>">
			<span class='k-icon-info' role="decoration"></span>
			<span class='text'><?php _e('More Info/Register', 'kcal');?></span>
			</a>
		</div>
		<?php endif; ?>

		<?php if (!empty($meta['_kcal_locationMap'][0])) : ?>
		<div class='event-location-image'>
			<?php if (!empty($linkHref) ) : ?>
			<a href='<?php echo $linkHref;?>' target='_blank' aria-label='<?php _e('Open in a Google Maps window', 'kcal'); ?>'>
				<span class='k-icon-mapmarker' role='decoration'></span>
				<?php endif; ?>
			<img src='<?php echo $meta['_kcal_locationMap'][0];?>' alt='<?php _e('Image of map to' , 'kcal');?> <?php echo $meta["_kcal_location"][0];?>' width='640' />
			<?php if (!empty($linkHref) ) : ?>
				</a>
			<?php endif; ?>
			</a>
		</div>
		<?php endif; ?>

	</div><!--end interact -->
<?php
endif;