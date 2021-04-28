<div class="quickview-popup" style="display:none" id="dlgEditRecurring">
    <div class="animated fadeInUp">
        <a class="close-btn" href="#">&#215;</a>
        <h2><?php _e('Edit Events', 'kcal');?></h2>
        <div class="popup-content-wrapper">
            <img src="<?php echo plugins_url();?>/k-cal/img/ajax-loader.gif" alt="<?php _e('Please Wait', 'kcal');?>" style="display:none" id="imgEditImgLoad" />
            <p class="message"></p>
            <div id="frmEditRecurring">
                <table>
                <tr><td><?php _e('Start Date', 'kcal');?>:</td><td><input type="text" name="_kcal_recur_eventStartDate" id="recur_startDate" value="" class="datepicker" /></td></tr>
                <tr>
                <td><?php _e('End Date', 'kcal');?>:</td><td><input type="text" name="_kcal_recur_eventEndDate" id="recur_endDate" value="" class="datepicker" /></td>
                </tr>
                <tr>
                <td><?php _e('Start Time', 'kcal');?>:</td>
                <td><input type="text" name="_kcal_recurStartTime" id="_kcal_recurStartTime" class="timepicker" value="" /></td>
                </tr>
                <tr><td><?php _e('End Time', 'kcal');?>:</td>
                <td><input type="text" name="_kcal_recurEndTime" id="_kcal_recurEndTime" class="timepicker" value="" /></td>
                </tr>
                </table>
                <input type="hidden" name="recurEdit" value="this" id="recurEdit" />
                <input type="hidden" name="eventSaveType" value="r" id="eventSaveType" />
                <input type="hidden" name="eventID" id="edit_event_eventID" value="" />&nbsp;<input type="hidden" name="recurrenceID" id="edit_event_recurrenceID" value="" />
                <input type="submit" name="saveEvent" id="saveRecurEvent" value="<?php _e('Save', 'kcal');?>" class="button"/>&nbsp;
                <input type="button" name="cancelEditRecurEvent" id="cancelEditRecurEvent" value="<?php _e('Cancel', 'kcal');?>" class="button"/>
            </div>
        </div>
    </div>
</div>
