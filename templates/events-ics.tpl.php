<?php
/**
 * Template Name: Events ICS
 * Description: Used for downloading events into a calendar application
 *
 * @package kcal
 */

if ( isset( $_GET['act'] ) && 'ics' === $_GET['act'] ) : //phpcs:ignore
	$cc = new CalendarController();
	$cc->add_to_calendar();
endif;
