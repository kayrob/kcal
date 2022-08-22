<?php
/**
 * Admin view include file to delete recurring event.
 *
 * @package kcal
 */

?>
<div class="quickview-popup" style="display:none" id="dlgEditRecurring">
	<div class="animated fadeInUp">
		<a class="close-btn" href="#">&#215;</a>
		<h2><?php esc_attr_e( 'Edit Events', 'kcal' ); ?></h2>
		<div class="popup-content-wrapper">
			<img src="<?php echo esc_url( plugins_url() ); ?>/k-cal/img/ajax-loader.gif" alt="Please Wait" style="display:none" id="imgEditImgLoad" />
			<p class="message"></p>
			<form method="post" action="" name="frmEditRecurring" id="frmEditRecurring">
				<table>
				<tr><td colspan="2">
				<fieldset><legend><?php esc_attr_e( 'This is a recurring event. Edit', 'kcal' ); ?>:</legend>
				<input type="radio" name="recurEdit" id="recurEdit_this" value="this" /><?php esc_attr_e( 'This instance', 'kcal' ); ?><br />
				<input type="radio" name="recurEdit" id="recurEdit_all" value="all" /><?php esc_attr_e( 'All instances', 'kcal' ); ?>
				</fieldset>
				</td>
				</tr>
				<tr><td><?php esc_attr_e( 'Start Date', 'kcal' ); ?>:</td><td><input type="text" name="_kcal_recur_eventStartDate" id="recur_startDate" value="" class="datepicker" /></td></tr>
				<tr>
				<td><?php esc_attr_e( 'End Date', 'kcal' ); ?>:</td><td><input type="text" name="_kcal_recur_eventEndDate" id="recur_endDate" value="" class="datepicker"/></td>
				</tr>
				<tr>
				<td><?php esc_attr_e( 'Start Time', 'kcal' ); ?>:</td>
				<td><input type="text" name="_kcal_recurStartTime" id="_kcal_recurStartTime" class="timepicker" value="" /></td>
				</tr>
				<tr><td><?php esc_attr_e( 'End Time', 'kcal' ); ?>:</td>
				<td><input type="text" name="_kcal_recurEndTime" id="_kcal_recurEndTime" class="timepicker" value="" /></td>
				</tr>
				</table>
				<input type="hidden" name="eventSaveType" value="r" id="eventSaveType" />
				<input type="hidden" name="eventID" id="edit_event_eventID" value="" />&nbsp;<input type="hidden" name="recurrenceID" id="edit_event_recurrenceID" value="" />
				<input type="submit" name="saveEvent" id="saveRecurEvent" value="<?php esc_attr_e( 'Save', 'kcal' ); ?>" class="button"/>&nbsp;
				<input type="button" name="cancelEditRecurEvent" id="cancelEditRecurEvent" value="<?php esc_attr_e( 'Cancel', 'kcal' ); ?>" class="button"/>
			</form>
		</div>
	</div>
</div>
