<?php
/*
 * Template Name: Events RSS
 * Description: Used for displaying the events RSS feed
 *
 */
if (isset($_GET['calendar']) && is_numeric($_GET['calendar']) ) :
	$cc = new CalendarController();
	$cc->buildCalendarsRSS();
endif;
