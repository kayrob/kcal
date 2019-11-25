<div class="quickview-popup" style="display:none" id="dlgDeleteEvent">
    <div class="animated fadeInUp">
        <a class="close-btn" href="#">&#215;</a>
        <h2>Delete Recurring Event:</h2>
        <div class="popup-content-wrapper">
        <p class="message"></p>
        <div id="frm_delete_event">
        <p>To re-create this event, update the main event, OR create an individual event</p>
        <input type="hidden" name="eventSaveType" value="d" id="eventSaveType" />
        <input type="hidden" name="eventID" id="delete_event_eventID" value="" /><input type="hidden" name="recurrenceID" id="delete_event_recurrenceID" value="" />
        <input type="submit" name="btnDeleteEvent" id="btnDeleteEvent" value="Continue" class="button"/>
        <input type="button" name="btnCancelDeleteEvent" id="btnCancelDeleteEvent" value="Cancel" class="button"/>
        </div>
        </div>
    </div>
</div>