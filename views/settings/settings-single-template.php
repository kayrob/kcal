<?php
/**
 * Include/tempalte file for the single events template settings field
 *
 * @package kcal
 */

if ( isset( $load_template ) ) : ?>
<label class="switch" for="kcal_single_template">
	<input class="switch-input" type="checkbox" id="kcal_single_template" name="kcal_settings[kcal_single_template]" value="1" <?php echo $load_template; //phpcs:ignore ?> />
	<span class="switch-label" data-on="On" data-off="Off"></span>
	<span class="switch-handle"></span>
</label>
<small><?php esc_attr_e( 'Overrides the shortcode setting', 'kcal' ); ?></small>
	<?php
endif;
