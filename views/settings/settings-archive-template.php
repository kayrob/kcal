<?php
/**
 * Include/tempalte file for the events archive settings field
 *
 * @package kcal
 */

if ( isset( $load_template ) ) :
	?>
<label class="switch" for="kcal_archive_template">
	<input class="switch-input archive" type="checkbox" id="kcal_archive_template" name="kcal_settings[kcal_archive_template]" value="1" <?php echo $load_template; //phpcs:ignore ?> />
	<span class="switch-label archive" data-on="On" data-off="Off"></span>
	<span class="switch-handle"></span>
</label>
<small><?php esc_attr_e( 'Overrides the shortcode setting', 'kcal' ); ?></small>
	<?php
endif;
