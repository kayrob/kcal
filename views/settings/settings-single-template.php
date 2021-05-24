<?php if (isset($loadTemplate)) : ?>
<label class="switch" for="kcal_single_template">
	<input class="switch-input" type="checkbox" id="kcal_single_template" name="kcal_settings[kcal_single_template]" value="1" <?php echo $loadTemplate;?> />
	<span class="switch-label" data-on="On" data-off="Off"></span>
	<span class="switch-handle"></span>
</label>
<small><?php _e('Overrides the shortcode setting', 'kcal'); ?></small>
<?php endif;