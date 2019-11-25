<div class="quickview-popup" style="display:none" id="dlgDeleteEvent">
    <div class="animated fadeInUp">
        <a class="close-btn" href="#">&#215;</a>
        <h2>Delete Event</h2>
        <div class="popup-content-wrapper">
        <p class="message"></p>
        <form action="" method="post" name="frm_delete_event" id="frm_delete_event">
        <table>
        <tbody id="tbRecurring" style="display:none">
        	<tr><td>
        	<fieldset><legend>This is a recurring event. Delete:</legend>
        		<input type="radio" name="recurDelete" id="recurDelete_this" value="this" />This instance<br />
        		<input type="radio" name="recurDelete" id="recurDelete_all" value="all" />All instances
        	</fieldset>
        	</td>
        	</tr>
        </tbody>
        </table>
        <input type="hidden" name="eventSaveType" value="d" id="eventSaveType" />
        <input type="hidden" name="eventID" id="delete_event_eventID" value="" /><input type="hidden" name="recurrenceID" id="delete_event_recurrenceID" value="" />
        <input type="submit" name="btnDeleteEvent" id="btnDeleteEvent" value="Continue" class="button"/>&nbsp;
        <input type="button" name="btnCancelDeleteEvent" id="btnCancelDeleteEvent" value="Cancel" class="button"/>
        </form>
        </div>
    </div>
</div>