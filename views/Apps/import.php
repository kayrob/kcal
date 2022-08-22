<?php
/**
 * Admin import events screen
 *
 * @package kcal
 */

if ( is_admin() ) :
	$kcal_options = get_option( 'kcal_settings' );
	$import_rss   = ( isset( $kcal_options['kcal_rss_events'] ) ) ? $kcal_options['kcal_rss_events'] : '';
	$calendars    = get_terms( 'calendar', array( 'hide_empty' => false ) );

	if ( isset( $imported['success'] ) ) :
		?>
		<div class="notice notice-success is-dismissible rl-notice">
		<p><?php echo wp_kses( implode( '<br />', $imported['success'] ), 'post' ); ?></p>
		</div>
		<?php
	endif;
	if ( isset( $imported['error'] ) ) :
		?>
		<div class="notice notice-error is-dismissible rl-notice">
		<p><?php echo wp_kses( implode( '<br />', $imported['error'] ), 'post' ); ?></p>
		</div>
		<?php
	endif;
	?>

	<div id="klcalImportRSS" class="kcal-import-container">
		<form method="post" action="<?php echo esc_url( admin_url( 'edit.php?post_type=event&page=edit.php%3Fview%3Dimport' ) ); ?>">
			<h2><?php esc_attr_e( 'Import RSS Feed', 'kcal' ); ?></h2>
			<div class="kcal-import-actions">
				<p><?php esc_attr_e( 'You can save a default RSS feed at', 'kcal' ); ?> <a href="<?php echo esc_url( admin_url( 'options-general.php?page=manage-calendar-settings' ) ); ?>"><?php esc_attr_e( 'Settings > Events Manager', 'kcal' ); ?></a></p>
				<div class="form-fields">
					<label for ="kcal_importRSS_url"><?php esc_attr_e( 'RSS URL', 'kcal' ); ?></label>
					<input type="url" value="<?php echo $import_rss; //phpcs:ignore ?>" name="kcal_importRSS_url" id="kcal_importRSS_url" />
				</div>
				<div class="form-fields">
					<label for="kcal_importRSS_calendar"><?php esc_attr_e( 'Choose Calendar', 'kcal' ); ?></label>
					<select name="kcal_importRSS_calendar" id="kcal_importRSS_calendar">
						<option value="">--</option>
						<?php
						if ( ! empty( $calendars ) ) :
							foreach ( $calendars as $calendar ) :
								echo '<option value="' . (int) $calendar->term_id . '">' . esc_attr( $calendar->name ) . '</option>';
							endforeach;
						endif;
						?>
					</select>
				</div>
				<div class="form-fields">
					<label for="rssImportTz"><?php esc_attr_e( 'Set the timezone', 'kcal' ); ?></label>
					<select name="kcal_import_events_timezone" id="rssImportTZ"><?php echo wp_timezone_choice( get_option('gmt_offset') ); //phpcs:ignore ?></select>
				</div>
				<div class="form-buttons">
					<input type="submit" name="kcal_submit_rss_import" value="Import Events" class="button-primary"/>
				</div>
			</div>
		</form>
	</div>
	<div id="klcalImportICS" class="kcal-import-container">
		<form enctype="multipart/form-data" method="post" action="<?php echo esc_url( admin_url( 'edit.php?post_type=event&page=edit.php%3Fview%3Dimport' ) ); ?>">
			<h2><?php esc_attr_e( 'Import Single Event', 'kcal' ); ?></h2>
			<div class="kcal-import-actions">
				<p><?php esc_attr_e( 'Only .ics files can be uploaded', 'kcal' ); ?></p>
				<div class="form-fields">
					<label for ="kcal_importICS_file"><?php esc_attr_e( 'Upload .ics File', 'kcal' ); ?></label>
					<input type="file" value="" name="kcal_importICS_file" id="kcal_importICS_file" />
				</div>
				<div class="form-fields">
					<label for="kcal_importICS_calendar"><?php esc_attr_e( 'Choose Calendar', 'kcal' ); ?></label>
					<select name="kcal_importICS_calendar" id="kcal_importICS_calendar">
						<option value="">--</option>
						<?php
						if ( ! empty( $calendars ) ) :
							foreach ( $calendars as $calendar ) :
								echo '<option value="' . (int) $calendar->term_id . '">' . esc_attr( $calendar->name ) . '</option>';
							endforeach;
						endif;
						?>
					</select>
				</div>
				<div class="form-fields">
					<label for="icalImportTz"><?php esc_attr_e( 'Set the timezone', 'kcal' ); ?></label>
					<select name="kcal_import_events_timezone" id="icalImportTZ"><?php echo wp_timezone_choice( get_option('gmt_offset') ); //phpcs:ignore ?></select>
				</div>
				<div class="form-buttons">
					<input type="submit" name="kcal_submit_rss_import" value="<?php esc_attr_e( 'Import Event', 'kcal' ); ?>" class="button-primary"/>
				</div>
			</div>
		</form>
	</div>
	<?php
endif;
