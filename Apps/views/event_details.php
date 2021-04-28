<div class="quickview-popup" style="display:none" id="dlgEventDetails">
    <div class="animated fadeInUp">
        <a class="close-btn" href="#">&#215;</a>
        <h2><?php _e('Edit Events', 'kcal');?></h2>
        <div class="popup-content-wrapper">
    	<img src="<?php echo plugins_url();?>/k-cal/img/ajax-loader.gif" alt="<?php _e('Please Wait', 'kcal');?>" style="display:none" id="imgEditImgLoad" />
        <p class="message"></p>
        <?php $cal->display_dlg_events_details();?>
        <input type="button" name="editEvent" id="editEvent" value="<?php _e('Edit', 'kcal');?>" class="button"/>&nbsp;|&nbsp;<input type="button" name="deleteEvent" id="deleteEvent" value="<?php _e('Delete','kcal');?>" class="button"/>
        </div>
    </div>
</div>
