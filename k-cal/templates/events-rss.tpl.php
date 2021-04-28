<?php
/*
 * Template Name: Events RSS
 * Description: Used for displaying the events RSS feed
 * 
 */
header("Content-Type: application/rss+xml; charset=UTF-8");
$cc = new CalendarController();
$rss = $cc->buildCalendarsRSS();
echo $rss;
