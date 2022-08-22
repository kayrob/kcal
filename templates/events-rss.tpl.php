<?php
/**
 * Template Name: Events RSS
 * Description: Used for displaying the events RSS feed
 *
 * @package kcal
 */

if ( isset( $_GET['calendar'] ) && is_numeric( $_GET['calendar'] ) ) : //phpcs:ignore
	$cc = new CalendarController();
	$cc->build_calendar_rss();
endif;
