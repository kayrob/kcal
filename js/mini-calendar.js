/*
 * Mini Calendar scripts to the entire calendar.js file isn't called when it's not needed.
 */
jQuery.curCSS = jQuery.css;

var ajaxURL = (typeof ajaxurl != "undefined")? ajaxurl : ajax_object.ajax_url;

/**
* Used for the quick widget calendar - retrieve a list of events for a specific selected date
* @param string timestamp
*/
var show_quick_widget_events = function(obj,timestamp){

    jQuery('#dlgQuickView').hide();
    var key = Math.round((Math.random() + Math.random()) * 100);
    jQuery.get(ajaxURL,{'action': 'getCalendarsQuickViewEvents', 'ran': key, 'qview':timestamp},function(data) {
        jQuery('#dlgQuickView').empty();
        if (data != 'false'){
            jQuery('#dlgQuickView').html('<a class="close-btn" href="#">&#215;</a>' + data);
            jQuery("#dlgQuickView .close-btn").on("click", function(e){
                e.preventDefault();
                jQuery(this).parent().hide().empty();
            });
        }
        else{
            jQuery('#dlgQuickView').html("An error occurred.<br />Events can't be displayed");
        }
        var mWidth = parseInt(obj.offsetWidth,10);
        var mHeight = parseInt(obj.offsetHeight,10);
        var mLeft = (parseInt(obj.offsetLeft,10)) - (300 - (mWidth / 2));
        var mTop = (parseInt(obj.offsetTop,10)) + (mHeight + (mWidth/2));
        jQuery('#dlgQuickView').css({"margin-left":mLeft+"px","margin-top":mTop+"px"});
        jQuery('#dlgQuickView').show();
    });
}
/**
 * Method to advance forward or backward through the mini calendar
 * @param integer advance
 */
var change_qv_month = function(advance) {
    var key = Math.round((Math.random() + Math.random()) * 100);
    var qvStamp = jQuery("#pQVdateTime").html();
    jQuery.get(ajaxURL,{"action": "getCalendarsQuickViewCalendar" , "ran": key, "isAjax": 'y', "qvAdv":advance,"qvStamp":qvStamp},function(data){
        if (data != false){
            var info = data.split("~");
            jQuery("#calendarWidgetTable").remove();
            jQuery(info[0]).insertBefore("#pQVdateTime");
            jQuery("#pQVdateTime").html(info[1]);
            jQuery("#h4QVHeader .header-text").html(info[2]);
        }
    });
}
jQuery(document).ready(function($)
{
    if ($("#kcal-mini-widget").length == 1){
        $("#kcal-mini-widget h4 button:eq(0)").on("click", function() {
            change_qv_month(-1);
        });
        $("#kcal-mini-widget h4 button:eq(1)").on("click", function() {
            change_qv_month(1);
        });
    }
});

