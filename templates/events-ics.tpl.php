<?php
/*
 * Template Name: Events ICS
 * Description: Used for downloading events into a calendar application
 *
 */
if (isset($_GET["act"]) && $_GET["act"] == "ics") :
    $cc = new CalendarController();
    $cc->addToCalendar();
endif;