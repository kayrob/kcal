<?php
//wordpress routes

if (!class_exists("kCalRoutes")){
    
    class kCalRoutes{
        public $ca;
        public function __construct(){
            if (is_admin()){
                add_action("wp_ajax_adminEditCalendar", array($this, "adminEditCalendar"));
                add_action("wp_ajax_adminEditEvents", array($this, "adminEditEvents"));
                add_action("wp_ajax_adminListCalendars", array($this, "adminListCalendars"));
                add_action("wp_ajax_adminCalendarStyle", array($this, "adminCalendarStyle"));
                add_action("wp_ajax_adminAutoTags", array($this, "adminAutoTags"));
                add_action("wp_ajax_getCalendarsAjax", array($this, "getCalendarsAjax"));
                add_action("wp_ajax_getCalendarsEventsAjax", array($this, "getCalendarsEventsAjax"));
                add_action("wp_ajax_getCalendarsQuickViewEvents", array($this, "getCalendarsQuickViewEvents"));
                add_action("wp_ajax_getCalendarsQuickViewCalendar", array($this, "getCalendarsQuickViewCalendar"));
                add_action("wp_ajax_getCalendarsFullCalendar", array($this, "getCalendarsFullCalendar"));
                add_action("wp_ajax_calendarRSS", array($this, "calendarRSS"));
                add_action("wp_ajax_calendarICS", array($this, "calendarICS"));
                add_action("wp_ajax_eventListCSS", array($this, "eventListCSS"));
            }
            add_action("wp_ajax_nopriv_getCalendarsAjax", array($this, "getCalendarsAjax"));
            add_action("wp_ajax_nopriv_getCalendarsEventsAjax", array($this, "getCalendarsEventsAjax"));
            add_action("wp_ajax_nopriv_getCalendarsQuickViewEvents", array($this, "getCalendarsQuickViewEvents"));
            add_action("wp_ajax_nopriv_getCalendarsQuickViewCalendar", array($this, "getCalendarsQuickViewCalendar"));
            add_action("wp_ajax_nopriv_getCalendarsFullCalendar", array($this, "getCalendarsFullCalendar"));
            add_action("wp_ajax_nopriv_calendarRSS", array($this, "calendarRSS"));
            add_action("wp_ajax_nopriv_calendarICS", array($this, "calendarICS"));
            add_action("wp_ajax_nopriv_eventListCSS", array($this, "eventListCSS"));
            
            $this->ca = new CalendarController();
        }
        /**
        * Admin
        */
        public function adminEditCalendar()
        {
            //$ca = new CalendarController();
            $this->ca->adminCalendarsAction($_GET);
            die();
        }
        public function adminEditEvents()
        {
            //$ca = new CalendarController();
            $this->ca->adminEventsAction($_GET);
            die();
        }
        public function adminListCalendars()
        {
            //$ca = new CalendarController();
            $this->ca->adminListCalendarsAction($_GET);
            die();
        }
        public function adminCalendarStyle()
        {
            //$ca = new CalendarController();
            $this->ca->adminListCalendarsAction();
            die();
        }
        public function adminAutoTags()
        {
            //$ca = new CalendarController();
            $this->ca->adminGetTermTags($_GET);
            die();
        }

        /**
         * General and Admin
         */

        public function getCalendarsAjax()
        {
            //$c = new CalendarController();
            $this->ca->getCalendarsAjax();
            die();
        }
        public function getCalendarsEventsAjax()
        {
            //$c = new CalendarController();
            $this->ca->getCalendarsEventsAjax();
            die();
        }
        public function getCalendarsQuickViewEvents()
        {
            //$c = new CalendarController();
            $this->ca->getCalendarsQuickViewEvents();
            die();
        }

        public function getCalendarsQuickViewCalendar()
        {
            //$c = new CalendarController();
            $this->ca->getCalendarsQuickViewCalendar();
            die();
        }
        public function getCalendarsFullCalendar()
        {
            //$c = new CalendarController();
            $this->ca->getCalendarsFullCalendar();
            die();
        }
        public function eventListCSS(){
            //$c = new CalendarController;
            $this->ca->buildCalendarsCSS();
            die();
        }
        public function calendarRSS()
        {
            //$c = new CalendarController;
            $this->ca->buildCalendarsRSS();
            die();
        }
        public function calendarICS()
        {
            //$c = new CalendarController;
            $this->ca->addToCalendar();
            die();
        }
    }
    
}
new kCalRoutes();
