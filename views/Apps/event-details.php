<?php
/**
 * Admin view include file to view event details.
 *
 * @package kcal
 */

?>
<div class="quickview-popup" style="display:none" id="dlgEventDetails">
	<div class="animated fadeInUp">
		<a class="close-btn" href="#" aria-label="<?php esc_attr_e( 'Close', 'kcal' ); ?>">&#215;</a>
		<h2><?php esc_attr_e( 'Edit Events', 'kcal' ); ?></h2>
		<div class="popup-content-wrapper">
			<img src="<?php echo esc_url( plugins_url() ); ?>/k-cal/img/ajax-loader.gif" alt="<?php esc_attr_e( 'Please Wait', 'kcal' ); ?>" style="display:none" id="imgEditImgLoad" />
			<p class="message"></p>
			<?php $cal->display_dlg_events_details(); //phpcs:ignore ?>
			<input type="button" name="editEvent" id="editEvent" value="<?php esc_attr_e( 'Edit', 'kcal' ); ?>" class="button button-primary"/><input type="button" name="deleteEvent" id="deleteEvent" value="<?php esc_attr_e( 'Delete', 'kcal' ); ?>" class="button" />
		</div>
	</div>
</div>
