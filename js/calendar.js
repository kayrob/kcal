jQuery.curCSS = jQuery.css;

var ajaxURL = (typeof ajaxurl != "undefined")? ajaxurl : ajax_object.ajax_url;
var calendarObj;

//this filter is just for halton
var add_remove_event_sources = function(action, calendarID, calendarObj){

	var sourceURL = ajaxURL + '?action=getCalendarsEventsAjax&calendar=' + calendarID;

	if (action == 'addEventSource') {
		calendarObj.addEventSource(sourceURL);
	} else if (action == 'removeEventSource') {
		var eventSources = calendarObj.getEventSources();
		if (eventSources.length) {
			if (jQuery('.kcal-fullcalendar .kcal-loading .loading-text').length) {
				jQuery('.kcal-fullcalendar .kcal-loading .loading-text').html(jQuery('.kcal-fullcalendar .kcal-loading').attr('data-loading'));
			}
			for (var i = 0; i < eventSources.length; i++) {
				if (eventSources[i].url == sourceURL) {
					eventSources[i].remove();
				}
			}
			if (jQuery('.kcal-fullcalendar .kcal-loading .loading-text').length) {
				jQuery('.kcal-fullcalendar .kcal-loading .loading-text').html(jQuery('.kcal-fullcalendar .kcal-loading').attr('data-loaded'));
			}
		}
	}
};

 /**
 * This method opens a dialog with event details based on event selected
 * is also called from admin page
 * Objects passed are fullCalendar objects - jsEvent can be used to position dialog boxes
 * @param object calEvent
 * @param object jsEvent
 * @param object view
 */
 var show_event_details = function(eventObj) {

	var calEvent = eventObj.event;

	jQuery('#tr_allDay td:eq(0)','#dlgEventDetails').html('&nbsp;');
	jQuery('#tr_allDay','#dlgEventDetails').hide();
	jQuery('#tr_recurring td:eq(1)','#dlgEventDetails').html('&nbsp;');
	jQuery('#tr_recurring','#dlgEventDetails').hide();
	jQuery('#tdDateStart','#dlgEventDetails').html(calEvent.extendedProps.displayStart);
	jQuery('#tdDateEnd','#dlgEventDetails').html(calEvent.extendedProps.displayEnd);

	if (calEvent.allDay == true) {
		jQuery('#tr_allDay td:eq(0)','#dlgEventDetails').html("<em>This is an all day event</em>");
		jQuery('#tr_allDay','#dlgEventDetails').show();
	}

	jQuery('#tdLocation','#dlgEventDetails').html(calEvent.extendedProps.location);
	jQuery('#tdDescription','#dlgEventDetails').html('<div class="event-detail-container">' + calEvent.extendedProps.description + '</div>');
	if (calEvent.extendedProps.recurrence != 'None'){
		jQuery('#tr_recurring td:eq(1)','#dlgEventDetails').html(calEvent.extendedProps.recurrenceDescription);
		jQuery('#tr_recurring','#dlgEventDetails').show();
	}
	if (jQuery('#event-ics','#dlgEventDetails').length >= 1) {
		var icalHref = jQuery("#event-ics").data("ics");
		jQuery('#event-ics','#dlgEventDetails').attr('href',"/" + icalHref + "?event="+calEvent.publicId);

	}

	jQuery('#dlgEventDetails h2').html(calEvent.title);
	jQuery('#dlgEventDetails').fadeIn();
 }

/**
 * Used for the front end calendar
 */
var show_front_end_events = function(eventObj) {

	var calEvent = eventObj.event;
	var displayDate;

	if (calEvent.allDay === false) {
		displayDate = calEvent.extendedProps.displayStart + '-' + calEvent.extendedProps.displayEnd;
	} else {
		displayDate = calEvent.extendedProps.displayStart + '-' + calEvent.extendedProps.displayEnd;
	}


	jQuery('.quickview-popup #event-ics').empty().hide().attr('aria-hidden', true);
	jQuery('.quickview-popup .qv-recurring i').empty();
	jQuery('.quickview-popup .qv-recurring').hide().attr('aria-hidden', true);
	jQuery('.quickview-popup .qv-allday').hide().attr('aria-hidden', true);

	jQuery('.quickview-popup h2').css({'color' : calEvent.extendedProps.style.color, 'background' : calEvent.extendedProps.style.background});
	jQuery('.quickview-popup h2 span').html(calEvent.title).attr('class', calEvent.extendedProps.className);
	jQuery('.quickview-popup tr:eq(1) td:eq(1)').html(displayDate);
	jQuery('.quickview-popup tr:eq(2) td:eq(1)').html(calEvent.extendedProps.location);

	if (calEvent.allDay === true) {
		jQuery('.quickview-popup .qv-allday').show().attr('aria-hidden', false);
	}
	if (calEvent.extendedProps.recurrence != 'None') {
		jQuery('.quickview-popup .qv-recurring i').html(calEvent.extendedProps.recurrence);
		jQuery('.quickview-popup .qv-recurring').show().attr('aria-hidden', false);
	}
	if (calEvent.extendedProps.imagePath && calEvent.extendedProps.imagePath != ""){

		jQuery('.quickview-popup tr:eq(0) img').attr("src", calEvent.extendedProps.imagePath);
		jQuery('.quickview-popup tr:eq(0)').show();
	}
	else{
		jQuery('.quickview-popup tr:eq(0) img').attr("src", "");
		jQuery('.quickview-popup tr:eq(0)').hide();
	}
	if (calEvent.description != ''){
		jQuery('.quickview-popup tr:eq(3) td:eq(1)').html('<div class="qv-dxn">' + calEvent.extendedProps.description + '</div>');
		jQuery('.quickview-popup tr:eq(3)').show();
	}
	else{
		jQuery('.quickview-popup tr:eq(3)').hide();
	}
	if (calEvent.altUrl != ''){
		//jQuery('.quickview-popup tr:eq(3) td:eq(1)').html('<a href="http://'+calEvent.altUrl.replace(/https?:\/\//, '') +' ">' + calEvent.altUrl + '</a>');
		jQuery('.quickview-popup tr:eq(4) #eventMoreInfo').attr("href", calEvent.extendedProps.altUrl);
		jQuery('.quickview-popup tr:eq(4)').show();
	}
	else{
		jQuery('.quickview-popup tr:eq(4)').hide();
	}
	if (calEvent.extendedProps.ics != ''){
		jQuery('.quickview-popup #event-ics').html(calEvent.extendedProps.ics).attr('aria-hidden', false).show();
	}
	jQuery('.quickview-popup .close-btn').on('click', function(e){
		jQuery('.quickview-popup').fadeOut('fast', function() {
			jQuery('body').removeClass('qv-open');
		});
	});
	jQuery('.quickview-popup').fadeIn('fast', function() {
		jQuery('.quickview-popup a:eq(0)').focus();
		jQuery('body').addClass('qv-open');
	});
}

 jQuery(document).ready(function($)
 {
	 if ($('#calendar').length == 1 && $("#calendar").is(":visible")){

		var calendarEl = document.getElementById('calendar');
		var today = new Date();
		//set this globally

		var defaults = {
			initialDate: today,
			initialView: 'dayGridMonth',
			editable: false,
			selectable: true,
			businessHours: true,
			dayMaxEvents: true, // allow "more" link when too many events
			events: [],
			headerToolbar: {
				start: 'title',
				center: 'dayGridMonth,timeGridWeek,listMonth',
				end: 'today prev,next'
			},
			views: {
				listMonth: {
					buttonText: 'List'
				},
				timeGridWeek: {
					buttonText: 'Week'
				},
				dayGridMonth: {
					buttonText: 'Month'
				},
			},
			eventClick: function(eventObj){
				show_front_end_events(eventObj);
			},
			eventDidMount: function(el) {
				$(el.el).attr('aria-label', 'Opens a dialog box in page').attr('role', 'button');
			},
			loading: function (isloading) {
				if (isloading) {
					if ($('.kcal-fullcalendar .kcal-loading').length) {
						$('.kcal-fullcalendar .kcal-loading .loading-text').html($('.kcal-fullcalendar .kcal-loading').attr('data-loading'));
						$('.kcal-fullcalendar .kcal-loading .loading-icon').show();
						$('.kcal-fullcalendar .kcal-loading').removeClass('visuallyhidden');
					}
				} else {
					if ($('.kcal-fullcalendar .kcal-loading').length) {
						if (!$('.kcal-fullcalendar .kcal-loading').hasClass('init')) {
							$('.kcal-fullcalendar .kcal-loading').addClass('visuallyhidden');
							$('.kcal-fullcalendar .kcal-loading .loading-text').html($('.kcal-fullcalendar .kcal-loading').attr('data-loaded'));
							$('.kcal-fullcalendar .kcal-loading .loading-icon').hide();
							$('#calendar .fc-view a').each(function() {
								if ($(this).hasClass('fc-col-header-cell-cushion')) {
									$(this).attr('aria-hidden', true).attr('tabindex', '-1');
								} else {
									$(this).attr('role', 'button').attr('aria-label', 'Calendar date: ' + $(this).html());
								}
							})
						}
						else {
							$('.kcal-fullcalendar .kcal-loading').removeClass('init');
						}
					}
				}
			},
		};

		var calOptions = (typeof(adminDefaults) == "undefined") ? defaults : $.extend(defaults,adminDefaults()) ;

		calendarObj = new FullCalendar.Calendar(calendarEl, calOptions);

		//ajax call to get calendars and set events feeds
		//only one feed is shown if a specific calendar is selected
		var key = Math.round((Math.random() + Math.random()) * 100);

		$.get(ajaxURL, {action: "getCalendarsAjax", ran: key, calendar: 's'}, function(calendars){
			if (calendars != 'false'){
				var qstring = window.location.href.split('view=');
				var calSelected = (qstring[1] && parseInt(qstring[1],10) >=0)?parseInt(qstring[1],10):false;
				var j = 0;
				for (var key in calendars){
					if (calendars.hasOwnProperty(key)){
						if ((calSelected == false && j == 0) || (calSelected == calendars[key])){
							j = key;
						}
						if (calSelected == false || calSelected == calendars[key]){
							add_remove_event_sources('addEventSource', calendars[key], calendarObj);
						}
					}
				}
				calendarObj.render();
			}
		});

		//check to see if a date request has been submitted via get from a calendar widget
		if (window.location.href.match(/event=[0-9]{1,6}(_)?([0-9]{1,6})?&start=([0-9]{1,})/) && $('#dlgEventDetails').length == 1){
			var queryString = window.location.href.split("?");
			if (queryString.length == 2){
				var eventSel = queryString[1].split("&");
				var eventSelID = eventSel[0].substring(6);
				var eventTime = new Date((eventSel[1].substring(6))*1000);
				$('#calendar').fullCalendar('gotoDate',eventTime);
				$('#dlgEventDetails').fadeIn();
			}
		}

		$(window).on('resize', function(){
			if ($(this).width() <= 600) {
				calendarObj.changeView('listMonth');
			}
			else {
				calendarObj.changeView("dayGridMonth");
			}
		});

		//calendar list items
		if ($('#frm_calendar_list .calendars-list-item').length) {
			$('#frm_calendar_list .calendars-list-item input').on('change', function() {
				var filterAxn = ($(this).is(':checked') )  ? 'addEventSource' : 'removeEventSource';
				add_remove_event_sources(filterAxn, $(this).val(), calendarObj);
			});
		}
	}

	$(".quickview-popup .close-btn").on("click", function(e){
		e.preventDefault();
		$(this).parents(".quickview-popup").fadeOut('400', function() {
			$('body').removeClass('qv-open');
		});
	});

	$(document).on("keyup", function(e){
		if ($(".quickview-popup").is(":visible")){
			var keyPressed = (e.which) ? e.which : e.keyCode;
			if (keyPressed == 27 ){
				$(".quickview-popup .close-btn").trigger("click");
			}
		}
	});
 });