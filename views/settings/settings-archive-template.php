<?php if (isset($loadTemplate)) : ?>
<label class="switch" for="kcal_archive_template">
	<input class="switch-input archive" type="checkbox" id="kcal_archive_template" name="kcal_settings[kcal_archive_template]" value="1" <?php echo $loadTemplate;?> />
	<span class="switch-label archive" data-on="On" data-off="Off"></span>
	<span class="switch-handle"></span>
</label>
<small><?php _e('Overrides the shortcode setting', 'kcal'); ?></small>
<?php endif;