<?php
require_once('../../../../../bootstrap.php');
require_once(dirname(dirname(__DIR__))."/calendar.php");

use MLHU\RLS\Calendar\Widgets as CAL;

preg_match("/(www.)?(.*)(.com|.ca)/",$_SERVER['SERVER_NAME'],$matches);
$fileName = str_replace(".","",$matches[2])."Events";
header('Content-Type: text/Calendar');
header("Content-Disposition: inline; filename=$fileName.ics");

$cal = new CAL\calendarWidgets($db);
//auto send $_GET so calendar or date set can be selected
$cal->output_ics($_GET);
?>