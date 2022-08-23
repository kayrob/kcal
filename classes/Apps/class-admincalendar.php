<?php
/**
 * Admin methods for the events calendar. Not to be used without /includes/apps/calendar/Calendar.php
 * created: November 11, 2010 by Karen Laansoo
 *
 * @package kcal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'AdminCalendar' ) && class_exists( 'Calendar' ) ) {
	/**
	 * Class AdminCalendar
	 */
	class AdminCalendar extends Calendar {

		/**
		 * Recurrence Options.
		 *
		 * @var $recurrence_opts
		 * @access protected
		 */
		protected $recurrence_opts = array( 'None', 'Daily', 'Weekly', 'Monthly', 'Yearly' );

		/**
		 * Constructor. Call the WP Hooks.
		 */
		public function __construct() {

			parent::__construct();

			if ( is_admin() ) {
				$screen_type = ( isset( $_GET['post_type'] ) && 'event' === $_GET['post_type'] ) ? 'event' : ''; //phpcs:ignore
				if ( empty( $screen_type ) && isset( $_GET['post'] ) ) { //phpcs:ignore
					$screen_type = get_post_type( $_GET['post'] ); //phpcs:ignore
				}

				add_action( 'add_meta_boxes', array( $this, 'kcal_add_meta_boxes' ) );
				add_action( 'save_post', array( $this, 'kcal_save_meta_box_data' ), 99 );
				if ( 'event' === $screen_type ) {
					add_filter( 'manage_posts_columns', array( $this, 'get_events_list_columns' ) );
					add_action( 'manage_posts_custom_column', array( $this, 'set_eventsList_content' ), 10, 2 );
				}
				add_action( 'admin_enqueue_scripts', array( $this, 'kcal_admin_scripts' ) );
				add_filter( 'upload_mimes', array( $this, 'kcal_custom_mime_types' ) );

				add_action( 'calendar_edit_form_fields', array( $this, 'kcal_extra_calendar_field' ) );
				add_action( 'edited_calendar', array( $this, 'save_extra_calendar_field' ) );
			}
		}

		/**
		 * Enqueue admin scripts/styles
		 */
		public function kcal_admin_scripts() {

			wp_register_script( 'jquery-ui', KCAL_HOST_URL . 'js/jquery-ui/js/jquery-ui-1.12.1.min.js', array( 'jquery' ), '1.12.1', true );

			wp_register_script( 'fullCalendar', KCAL_HOST_URL . 'vendors/fullcalendar-5.6.0/lib/main.js', array(), '5.6.0', true );
			wp_enqueue_script( 'fullCalendar' );
			wp_register_script( 'kcalendar', KCAL_HOST_URL . 'js/calendar.js', array( 'jquery', 'jquery-ui', 'fullCalendar' ), '3.0', true );
			wp_enqueue_script( 'kcalendar' );
			wp_register_style( 'calCSS', KCAL_HOST_URL . 'vendors/fullcalendar-5.6.0/lib/main.min.css', array(), '1.5.3' );
			wp_register_style( 'jquery-ui', KCAL_HOST_URL . 'js/jquery-ui/css/smoothness/jquery-ui-1.10.3.custom.min.css', array(), '1.10.3' );
			wp_enqueue_style( 'calCSS' );
			wp_enqueue_style( 'jquery-ui' );

			wp_register_script( 'adminCalendar', KCAL_HOST_URL . 'js/adminCalendar.js', array( 'kcalendar', 'jquery-ui-core', 'jquery-ui-datepicker' ), '2.0', true );
			wp_register_script( 'jscolor', KCAL_HOST_URL . 'vendors/jscolor/jscolor.js', array(), '3.0', true );
			wp_enqueue_script( 'adminCalendar' );
			wp_enqueue_script( 'jscolor' );
			wp_localize_script( 'jscolor', 'url_object', array( 'plugin_url' => KCAL_HOST_URL . 'vendors/jscolor/' ) );
			wp_register_style( 'kcal-admin-css', KCAL_HOST_URL . 'dist/css/admin.css', array(), '3.0' );
			wp_enqueue_style( 'kcal-admin-css' );
			wp_enqueue_style( 'thickbox' );
			wp_enqueue_script( 'thickbox' );
			wp_localize_script( 'adminCalendar', 'kcal_object', array( 'edit_url' => admin_url( 'post.php?action=edit' ) ) );

			wp_localize_script( 'kcalendar', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

		}

		/**
		 * Show the calendar term link in the admin column
		 *
		 * @param string $post_id is the event being edited.
		 *
		 * @return string
		 */
		protected function get_event_column_calendar( $post_id ) {
			$calendars = wp_get_post_terms( $post_id, 'calendar' );
			$postcals  = '';
			if ( ! empty( $calendars ) ) {
				foreach ( $calendars as $the_term ) {
					$cal_url = 'term.php?taxonomy=calendar&tag_ID=' . $the_term->term_id . '&post_type=event';
					if ( ! empty( $postcals ) ) {
						$postcals .= ', ';
					}
					$postcals .= '<a href="' . esc_url( admin_url( $cal_url ) ) . '">' . $the_term->name . '</a>';
				}
			}
			return $postcals;
		}
		/**
		 * Add a custom mime type for importing calendar files
		 *
		 * @param array $mimes are the default mime types.
		 *
		 * @return array
		 */
		public function kcal_custom_mime_types( $mimes ) {
			$mimes['ics'] = 'text/calendar';
			return $mimes;
		}
		/**
		 * Create a new admin column for calendar
		 *
		 * @param array $defaults are the default WP columns.
		 *
		 * @return array
		 */
		public function get_events_list_columns( $defaults ) {
			$new_list = $defaults;
			unset( $new_list['comments'] );
			unset( $new_list['author'] );
			unset( $new_list['categories'] );
			unset( $new_list['tags'] );
			$reset_list              = array_pop( $new_list );
			$new_list['taxcalendar'] = __( 'Calendar(s)', 'kcal' );

			$return_list         = $new_list;
			$return_list['date'] = __( 'Date', 'kcal' );
			return $return_list;
		}
		/**
		 * Show the calendar term link in the admin column.
		 *
		 * @param string $column_name is the admin column name.
		 * @param string $post_id is the event record id.
		 *
		 * @see self::get_event_column_calendar()
		 */
		public function set_eventsList_content( $column_name, $post_id ) {
			switch ( $column_name ) {
				case 'taxcalendar':
					echo $this->get_event_column_calendar( $post_id ); //phpcs:ignore
					break;
				default:
					break;
			}
		}

		/**
		 * Add an extra field to the calendar taxonomy editor
		 *
		 * @param object $tag is the calendar term colours saved in wp admin.
		 */
		public function kcal_extra_calendar_field( $tag ) {
			$term_id             = $tag->term_id;
			$cat_meta_colour     = get_option( 'calendar_' . $term_id, '#454545' );
			$cat_meta_textcolour = get_option( 'calendar_text_' . $term_id, '#fff' );
			$wp_editor_settings  = array(
				'wpautop'       => true,
				'textarea_rows' => 5,
				'tinymce'       => array(
					'plugins' => 'wordpress',
				),
				'media_buttons' => false,
				'textarea_name' => '_kcal_calendarDescription',
			);
			$rich_text           = stripslashes( get_option( 'calendar_description_' . $term_id, '' ) );
			?>
			<tr class="form-field">
			<th scope="row" valign="top"><label for="cal_colour"><?php esc_attr_e( 'Select Calendar Colour', 'kcal' ); ?></label></th>
			<td>
				<input type="text" id="cal_colour" name="cal_colour" size="10" value="<?php echo esc_attr( $cat_meta_colour ); ?>" class="color" style="width: 100px"/>
				<div id="colorPickerNew"></div>
			</td>
			</tr>
			<tr class="form-field">
			<th scope="row" valign="top"><label for="_kcal_text_colour"><?php esc_attr_e( 'Text Colour for Calendar.', 'kcal' ); ?></label></th>
			<td>
				<select name='_kcal_text_colour' id='_kcal_text_colour'>
					<option value='#000'<?php echo ( '#fff' !== $cat_meta_textcolour ? ' selected="selected"' : '' ); ?>><?php esc_attr_e( 'Black', 'kcal' ); ?><?php echo ( '#fff' !== $cat_meta_textcolour ? '*' : '' ); ?></option>
					<option value='#fff'<?php echo ( '#fff' === $cat_meta_textcolour ? ' selected="selected"' : '' ); ?>><?php esc_attr_e( 'White', 'kcal' ); ?><?php echo ( '#fff' === $cat_meta_textcolour ? '*' : '' ); ?></option>
				</select>
				<p><i><?php esc_attr_e( 'The correct colour to pick is the text shown in the colour selected above.', 'kcal' ); ?></i></p>
			</td>
			</tr>
			<tr class="form-field">
			<th scope="row" valign="top"><label for="_kcal_calendarDescription"><?php esc_attr_e( 'Rich Text Description', 'kcal' ); ?></label></th>
			<td>
				<?php wp_editor( $rich_text, '_kcal_calendarDescription', $wp_editor_settings ); ?>
			</td>
			</tr>

			<?php
		}
		/**
		 * Save the extra description field
		 *
		 * @param integer $term_id is the saved calendar term meta.
		 */
		public function save_extra_calendar_field( $term_id ) {
			if ( isset( $_POST['cal_colour'] ) ) { //phpcs:ignore
				$cal_meta = get_option( 'calendar_' . $term_id );
				preg_match( '/^\#?[A-Fa-f0-9]{6}$/', $_POST['cal_colour'], $matches ); //phpcs:ignore
				// save the option array.
				if ( isset( $matches[0] ) ) {
					update_option( 'calendar_' . $term_id,  $_POST['cal_colour'], $cal_meta ); //phpcs:ignore
				}
			}
			if ( isset( $_POST['_kcal_text_colour'] ) ) { //phpcs:ignore
				$cal_text_meta = get_option( 'calendar_text_' . $term_id );
				if ( '#fff' === $_POST['_kcal_text_colour'] || '#000' === $_POST['_kcal_text_colour'] ) { //phpcs:ignore
					update_option( 'calendar_text_' . $term_id,  $_POST['_kcal_text_colour'], $cal_text_meta ); //phpcs:ignore
				}
			}
			if ( isset( $_POST['_kcal_calendarDescription'] ) ) { //phpcs:ignore
				$cal_dxn_meta = get_option( 'calendar_description_' . $term_id );
				update_option( 'calendar_description_' . $term_id,  $_POST['_kcal_calendarDescription'], $cal_dxn_meta ); //phpcs:ignore
			}
		}
		/**
		 * Custom meta data - nonce
		 */
		public function kCal_mb_nonce(){}

		/**
		 * Custom meta data - create the boxes for the meta input
		 *
		 * @see self::kcal_mb_eventDate()
		 * @see self::kcal_mb_eventLocation()
		 * @see self::kcal_mb_event_repeat()
		 * @see self::kcal_mb_event_url()
		 */
		public function kcal_add_meta_boxes() {
			add_meta_box( 'kcal_eventDate', __( 'Event Date', 'kcal' ), array( $this, 'kcal_mb_eventDate' ), 'event', 'advanced' );
			add_meta_box( 'kcal_eventLocation', __( 'Event Location', 'kcal' ), array( $this, 'kcal_mb_eventLocation' ), 'event', 'advanced' );
			add_meta_box( 'kcal_eventRepeat', __( 'Event Repeat', 'kcal' ), array( $this, 'kcal_mb_event_repeat' ), 'event', 'advanced' );
			add_meta_box( 'kcal_eventURL', __( 'Registration URL', 'kcal' ), array( $this, 'kcal_mb_event_url' ), 'event', 'advanced' );
		}
		/**
		 * Meta box for all Day option
		 *
		 * @param object $post is the event data.
		 */
		public function kcal_mb_eventDate( $post ) {
			$meta = get_post_meta( $post->ID );

			$all_day         = get_post_meta( $post->ID, '_kcal_allDay', true );
			$all_day_checked = ( ! empty( $all_day ) && true === (bool) $all_day ) ? ' checked="checked"' : '';

			$timezone = get_post_meta( $post->ID, '_kcal_timezone', true );

			if ( empty( $timezone ) || false === $timezone ) {
				$timezone = get_option( 'gmt_offset' );
			}
			$start_date = get_post_meta( $post->ID, '_kcal_eventStartDate', true );
			$end_date   = get_post_meta( $post->ID, '_kcal_eventEndDate', true );

			try {
				$date_timezone = new DateTimeZone( $timezone );
			} catch ( exception $e ) {
				$date_timezone = new DateTimeZone( get_option( 'gmt_offset' ) );
			}

			$date = new DateTime( '', $date_timezone );
			if ( ! empty( $start_date ) ) {
				$date->setTimestamp( $start_date );
			}
			$date2 = new DateTime( '', $date_timezone );
			if ( ! empty( $end_date ) ) {
				$date2->setTimestamp( $end_date );
			}

			$start_display = ( ! empty( $start_date ) && false !== (bool) $start_date ) ? $date->format( 'Y-m-d' ) : '';
			$start_time    = ( ! empty( $start_date ) && false !== (bool) $start_date ) ? $date->format( 'g:i A' ) : '';
			$end_display   = ( ! empty( $end_date ) && false !== (bool) $end_date ) ? $date2->format( 'Y-m-d' ) : '';
			$end_time      = ( ! empty( $end_date ) && false !== (bool) $end_date ) ? $date2->format( 'g:i A' ) : '';

			wp_nonce_field( 'kcal_meta_box', 'kCal_mb_nonce' );
			echo '<p><label for="_kcal_allDay">' . esc_attr__( 'All Day Event', 'kcal' ) . '</label>';
			echo '&nbsp;&nbsp;<input type="checkbox" name="_kcal_allDay" id="_kcal_allDay" value="1"' . $all_day_checked . '/></p>'; //phpcs:ignore
			echo '<p><label for="_kcal_eventStartDate">' . esc_attr__( 'Start Date', 'kcal' ) . '</label><br />';
			echo '<input type="text" name="_kcal_eventStartDate" id="_kcal_eventStartDate" class="datepicker" value="' . esc_attr( $start_display ) . '" style="width: 100%;max-width: 400px"/></p>';
			echo '<p><label for="_kcal_eventStartTime">' . esc_attr__( 'Start Time', 'kcal' ) . '</label><br />';
			echo '<input type="text" name="_kcal_eventStartTime" id="_kcal_eventStartTime" class="timepicker" value="' . esc_attr( $start_time ) . '" style="width: 100%;max-width: 400px"/></p>';
			echo '<p><label for="_kcal_eventEndDate">' . esc_attr__( 'End Date', 'kcal' ) . '</label><br />';
			echo '<input type="text" name="_kcal_eventEndDate" id="_kcal_eventEndDate" class="datepicker" value="' . esc_attr( $end_display ) . '" style="width: 100%;max-width: 400px"/></p>';
			echo '<p><label for="_kcal_eventEndTime">' . esc_attr__( 'End Time', 'kcal' ) . '</label><br />';
			echo '<input type="text" name="_kcal_eventEndTime" id="_kcal_eventEndTime" class="timepicker" value="' . esc_attr( $end_time ) . '" style="width: 100%;max-width: 400px"/></p>';
			echo '<p><label for="_kcal_timezone">' . esc_attr__( 'Timezone', 'kcal' ) . '</label><br />';
			echo '<select name="_kcal_timezone" id="_kcal_timezone">' . wp_timezone_choice( $timezone ) . '</select></p>'; //phpcs:ignore

		}
		/**
		 * Event location metabox
		 *
		 * @param object $post is the event object.
		 */
		public function kcal_mb_eventLocation( $post ) {
			$location = get_post_meta( $post->ID, '_kcal_location', true );
			$map      = get_post_meta( $post->ID, '_kcal_locationMap', true );
			echo '<p><label for="_kcal_location">' . esc_attr__( 'Location Details', 'kcal' ) . '</label><br />';
			echo '<input name="_kcal_location" id="_kcal_location" value="' . esc_attr( $location ) . '" style="width: 100%;max-width: 400px"></p>';
			/**
			* echo "<p><label for=\"_kcal_locationMap\">".__('Map Image', 'kcal')."</label><br />";
			* echo "<input type=\"text\" name=\"_kcal_locationMap\" id=\"_kcal_locationMap\" value=\"".$map."\" style=\"width: 80%\"/>";
			* echo "<input type=\"button\" class=\"button-primary\" value=\"Upload Image\" id=\"uploadimage_kcal_locationMap\" /><br />";
			* if (!empty($map)){
			*   echo "<img src=\"".  $map."\" alt=\"\" style=\"height:auto;width: 100px\" id=\"img_kcal_locationMap\"/><br />";
			* }
			* echo "</p>";
			*/
		}
		/**
		 * Event repeat metabox
		 *
		 * @param object $post is the event object.
		 */
		public function kcal_mb_event_repeat( $post ) {
			$recurrence_type = get_post_meta( $post->ID, '_kcal_recurrenceType', true );
			$recurrence_end  = get_post_meta( $post->ID, '_kcal_recurrence_end', true );
			if ( is_null( $recurrence_end ) || 'null' === strtolower( $recurrence_end ) ) {
				$recurrence_end = '';
			}
			$recurrence_interval = (int) get_post_meta( $post->ID, '_kcal_recurrenceInterval', true );
			$recurrence_dates    = get_post_meta( $post->ID, '_kcal_recurrenceDate' );

			$recurrence_opts = array( 'None', 'Daily', 'Weekly', 'Monthly', 'Yearly' );
			echo '<p><label for="_kcal_recurrenceType">' . esc_attr__( 'Recurrence', 'kcal' ) . '</label><br />';
			echo '<select name="_kcal_recurrenceType" id="_kcal_recurrenceType">';
			foreach ( $recurrence_opts as $r_type ) {
				echo '<option value="' . esc_attr( $r_type ) . '"' . ( $recurrence_type === $r_type ? ' selected="selected"' : '' ) . '>' . esc_attr( $r_type ) . '</option>';
			}
			echo '</select></p>';
			echo '<p><label for="_kcal_recurrenceInterval">' . esc_attr__( 'Recurs Every:', 'kcal' ) . '</label><br />';
			echo '<select name="_kcal_recurrenceInterval" id="_kcal_recurrenceInterval">';
			for ( $i = 0; $i < 366; $i++ ) {
				$recur_sel = ( $i === (int) $recurrence_interval ) ? ' selected="selected"' : '';
				echo '<option value="' . (int) $i . '"' . $recur_sel . '">' . (int) $i . '</option>'; //phpcs:ignore
			}
			echo '</select></p>';
			echo '<p><label for="_kcal_recurrenceEnd">' . esc_attr__( 'Recurrence End Date', 'kcal' ) . '</label><br />';
			echo '<input type="text" name="_kcal_recurrenceEnd" id="_kcal_recurrenceEnd" class="datepicker" value="' . esc_attr( $recurrence_end ) . '" style="width: 100%;max-width: 400px"/></p>';

			if ( ! empty( $recurrence_dates ) ) {

				$timezone = get_post_meta( $post->ID, '_kcal_timezone', true );
				if ( empty( $timezone ) || false === $timezone ) {
					$timezone = get_option( 'gmt_offset' );
				}

				echo '<p><strong>' . esc_attr__( 'Recurrence Dates', 'kcal' ) . '</strong></p>';
				echo '<ol>';
				foreach ( $recurrence_dates as $index => $r_date ) {
					$liclass                    = ( 0 < ( $index % 2 ) ? ' class="alt"' : '' );
					$start_time                 = array_keys( $r_date );
					list( $end_time, $meta_id ) = array_values( $r_date[ $start_time[0] ] );
					$start_date                 = new \DateTime( 'now', new \DateTimeZone( $timezone ) );
					$end_date                   = new \DateTime( 'now', new \DateTimeZone( $timezone ) );

					$start_date->setTimestamp( $start_time[0] );
					$end_date->setTimestamp( $end_time );

					$display = $start_date->format( 'D, M j, Y' );
					if ( $end_date->format( 'Y-m-d' ) !== $start_date->format( 'Y-m-d' ) ) {
						$display .= ' ' . $start_date->format( 'g:i a' ) . ' - ' . $end_date->format( 'D, M j g:i a' );
					} else {
						$display .= ' ' . $start_date->format( 'g:i a' ) . ' - ' . $end_date->format( 'g:i a' );
					}

					$data_start = $start_date->format( 'Y-m-d h:i:s A' );
					$data_end   = $end_date->format( 'Y-m-d h:i:s A' );
					echo "<li{$liclass}>"; //phpcs:ignore
					echo $display; //phpcs:ignore
					echo '<span class="recurrence-controls"><label id="edit-recur-' . (int) $meta_id . '" data-post="' . (int) $post->ID . '" data-start="' . esc_attr( $data_start ) . '" data-end="' . esc_attr( $data_end ) . '" title="' . esc_attr__( 'Edit Date', 'kcal' ) . '" class="recur-edit"><span class="ki kicon-pencil2"></span></label>
							<label id="del-recur-' . (int) $meta_id . '" data-post="' . (int) $post->ID . '" title="' . esc_attr__( 'Delete Date', 'kcal' ) . '" class="del-recur"><span class="ki kicon-bin"></span></label></span>';

					echo '</li>';

				}
				echo '</ol>';
				include_once KCAL_HOST_DIR . '/views/Apps/delete-recur-single.php';
				include_once KCAL_HOST_DIR . '/views/Apps/edit-recurring-single.php';
			}
		}
		/**
		 * Event url metabox
		 *
		 * @param object $post is the event object.
		 */
		public function kcal_mb_event_url( $post ) {
			$register_url = get_post_meta( $post->ID, '_kcal_eventURL', true );
			echo '<label for="_kcal_eventURL">' . esc_attr__( 'URL for the Event Details Page', 'kcal' ) . '</label><br />';
			echo '<input type="text" name="_kcal_eventURL" id="_kcal_eventURL" value="' . $register_url . '" style="width: 100%;max-width: 400px"/>'; //phpcs:ignore
		}
		/**
		 * Save the metabox data
		 *
		 * @param integer $post_id is the event id.
		 *
		 * @see self::update_event()
		 */
		public function kcal_save_meta_box_data( $post_id ) {
			/*
			* We need to verify this came from our screen and with proper authorization,
			* because the save_post action can be triggered at other times.
			*/
			// Check if our nonce is set.
			if ( ! isset( $_POST['kCal_mb_nonce'] ) ) {
				return;
			}

			// Verify that the nonce is valid.
			if ( ! wp_verify_nonce( $_POST['kCal_mb_nonce'], 'kcal_meta_box' ) ) { //phpcs:ignore
				return;
			}
			// If this is an autosave, our form has not been submitted, so we don't want to do anything.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			// Check the user's permissions.
			$screen_type = ( isset( $_GET['post_type'] ) && 'event' === $_GET['post_type'] ) ? 'event' : '';
			if ( empty( $screen_type ) && isset( $_GET['post'] ) ) {
				$screen_type = get_post_type( $_GET['post'] ); //phpcs:ignore
			}
			if ( 'event' === $screen_type ) {
				if ( ! current_user_can( 'edit_events', $post_id ) ) {
					return;
				}
			} else {
				if ( ! current_user_can( 'edit_events', $post_id ) ) {
					return;
				}
			}

			// Update the meta field in the database.

			$meta = array(
				'_kcal_eventStartDate'     => $_POST['_kcal_eventStartDate'], //phpcs:ignore
				'_kcal_eventEndDate'       => $_POST['_kcal_eventEndDate'], //phpcs:ignore
				'_kcal_eventURL'           => $_POST['_kcal_eventURL'], //phpcs:ignore
				'_kcal_location'           => $_POST['_kcal_location'], //phpcs:ignore
				'_kcal_recurrenceType'     => $_POST['_kcal_recurrenceType'], //phpcs:ignore
				'_kcal_recurrenceInterval' => $_POST['_kcal_recurrenceInterval'], //phpcs:ignore
				'_kcal_recurrenceEnd'      => $_POST['_kcal_recurrenceEnd'], //phpcs:ignore
				'_kcal_eventStartTime'     => $_POST['_kcal_eventStartTime'], //phpcs:ignore
				'_kcal_eventEndTime'       => $_POST['_kcal_eventEndTime'], //phpcs:ignore
				'_kcal_locationMap'        => ( isset( $_POST['_kcal_locationMap'] ) ? $_POST['_kcal_locationMap'] : '' ), //phpcs:ignore
				'_kcal_timezone'           => ( isset( $_POST['_kcal_timezone'] ) ? $_POST['_kcal_timezone'] : get_option( 'gmt_offset' ) ), //phpcs:ignore
				'_kcal_allDay'             => ( isset( $_POST['_kcal_allDay'] ) ? '1' : '0' ),

			);

			if ( ! isset( $_POST['_kcal_allDay'] ) ) {
				unset( $meta['_kcal_allDay'] );
				delete_post_meta( $post_id, '_kcal_allDay' );
			}
			$this->update_event( $meta, $post_id );
		}

		/**
		 * Returns the recurrence interval and recurrence end date. Values are auto set if information is not provided from web form
		 *
		 * @access protected
		 * @param string $recurrence is the event recurring.
		 * @param string $interval is the type of interval.
		 * @param string $end_date is the recurrence end date.
		 * @return array
		 */
		protected function set_recurrence_values( $recurrence, $interval, $end_date ) {
			$recurrence_interval = 0;
			$recurrence_end      = 'Null';
			if ( 'None' !== $recurrence ) {
				$recurrence_interval = ( isset( $interval ) && preg_match( '/^[0-9]{1,3}$/', trim( $interval ), $matches ) ) ? intval( trim( $interval ), 10 ) : 1;
				$recurrence_end      = ( isset( $end_date ) && preg_match( '/^20[0-9]{2}([-])(0[1-9]|1[012])([-])([012][0-9]|3[01])$/', trim( $end_date ), $matches ) ) ? $end_date : date( 'Y' ) . '-12-31'; //phpcs:ignore
			}
			return array( $recurrence_interval, $recurrence_end );
		}
		/**
		 * Set event start and end times based on time of day selected from admin web formså
		 *
		 * @param array $meta is the post meta.
		 * @see DB_MySQL::escape()
		 * @return array
		 */
		public function format_add_edit_dateTime( $meta ) {

			if ( strtotime( $meta['_kcal_eventEndDate'] ) < strtotime( $meta['_kcal_eventStartDate'] ) ) {
				$meta['_kcal_eventEndDate'] = trim( $meta['_kcal_eventStartDate'] );
			}

			$event_start_time = ( isset( $meta['_kcal_eventStartTime'] ) ) ? $meta['_kcal_eventStartTime'] : '12:00 AM';
			$event_end_time   = ( isset( $meta['_kcal_eventEndTime'] ) ) ? $meta['_kcal_eventEndTime'] : '12:15 AM';

			if ( isset( $meta['_kcal_timezone'] ) ) {
				try {
					$timezone = new \DateTimeZone( $meta['_kcal_timezone'] );
				} catch ( Exception $e ) {
					$timezone = new \DateTimeZone( get_option( 'gmt_offset' ) );
				}
			} else {
				$timezone = new \DateTimeZone( get_option( 'gmt_offset' ) );
			}

			$date  = new \DateTime( trim( $meta['_kcal_eventStartDate'] ) . ' ' . $event_start_time, $timezone );
			$date2 = new \DateTime( trim( $meta['_kcal_eventEndDate'] ) . ' ' . $event_end_time, $timezone );

			$start_date = $date->format( 'U' );
			$end_date   = $date2->format( 'U' );
			if ( $start_date === $end_date ) {
				$end_date += ( 60 * 15 );
			}

			return array( $start_date, $end_date );
		}
		/**
		 * Method to create an associative array of event fields/values
		 * Used for both edit event and add new event forms
		 *
		 * @access protected
		 *
		 * @param array   $meta is the saved post meta.
		 * @param integer $post_id is the event record.
		 *
		 * @see format_add_edit_dateTime()
		 * @see set_recurrence_values()
		 * @return array
		 */
		protected function create_edit_event_values( $meta, $post_id ) {

			$values = array();
			list( $values['_kcal_eventStartDate'], $values['_kcal_eventEndDate'] ) = $this->format_add_edit_dateTime( $meta );
			$values['_kcal_location']       = esc_textarea( $meta['_kcal_location'] );
			$values['_kcal_recurrenceType'] = 'None';
			if ( isset( $meta['_kcal_recurrenceType'] ) && false !== in_array( trim( $meta['_kcal_recurrenceType'] ), $this->recurrence_opts, true ) ) {
				$values['_kcal_recurrenceType'] = trim( $meta['_kcal_recurrenceType'] );
			}
			if ( isset( $meta['_kcal_recurrenceInterval'] ) && isset( $meta['_kcal_recurrenceEnd'] ) ) {
				list($values['_kcal_recurrenceInterval'],$values['_kcal_recurrenceEnd']) = $this->set_recurrence_values( $meta['_kcal_recurrenceType'], $meta['_kcal_recurrenceInterval'], $meta['_kcal_recurrenceEnd'], $post_id );
			}

			$values['_kcal_allDay']      = ( isset( $meta['_kcal_allDay'] ) ) ? '1' : '0';
			$values['_kcal_eventURL']    = esc_url( $meta['_kcal_eventURL'] );
			$values['_kcal_locationMap'] = esc_url( $meta['_kcal_locationMap'] );
			$values['_kcal_timezone']    = $meta['_kcal_timezone'];
			return $values;
		}

		/**
		 * Create an array of recurring event start and end dates for yearly recurrences
		 *
		 * @access protected
		 *
		 * @param integer $post_id is the event ID.
		 * @param string  $recurrence is the event recurring.
		 * @param integer $interval is the recurrence interval.
		 * @param string  $recur_end is the recurrence end date.
		 * @param string  $first_event_start is the first recurrence start date.
		 * @param string  $first_event_end is the first recurrence end date.
		 * @return array|void
		 */
		protected function create_recurring_yearly_dates( $post_id, $recurrence, $interval, $recur_end, $first_event_start, $first_event_end ) {
			$events = array();
			if ( preg_match( '/^\d{1,6}$/', $post_id, $matches ) ) {
				$recurrence_end_date    = ( ! empty( $recur_end ) ) ? strtotime( trim( $recur_end ) . ' ' . date( 'g:i a', $first_event_end ) ) : mktime( 0, 0, 0, 12, 31, date( 'Y' ) ); //phpcs:ignore
				$first_event_start_time = $first_event_start;
				$first_event_end_time   = $first_event_end;

				if ( $recurrence_end_date > $first_event_end_time ) {
					$num_years = date( 'Y', $recurrence_end_date ) - date( 'Y',$first_event_end_time ); //phpcs:ignore
					if ( 0 < $num_years ) {
						$day_of_week_event_start = date( 'w', $first_event_start_time ); //phpcs:ignore
						$date_diff_start         = floor( date( 'j', $first_event_start_time ) - 1 ); //phpcs:ignore
						$week_num_start          = ceil( $date_diff_start / 7 );
						for ( $y = 1; $y <= $num_years; $y++ ) {
							$next_year_start_time = mktime( 0, 0, 0, date( 'm', $first_event_start_time ), 1, date( 'Y', $first_event_start_time ) + ( $y * $interval ) ); //phpcs:ignore
							$new_day_of_week      = 1;
							if ( date( 'w', $next_year_start_time ) > $day_of_week_event_start ) { //phpcs:ignore
								$new_day_of_week_start = 1 + ( ( 7 + $day_of_week_event_start ) - date( 'w', $next_year_start_time ) ); //phpcs:ignore
							} else if ( date( 'w', $next_year_start_time ) < $day_of_week_event_start ) { //phpcs:ignore
								$new_day_of_week_start = ( 1 + ( $day_of_week_event_start - date( 'w', $next_year_start_time ) ) ); //phpcs:ignore
							}
							$new_day_of_week_start              += ( $week_num_start * 7 - 7 );
							$events[ -1 + $y ]['eventStartDate'] = mktime( 0, 0, 0, date( 'm', $next_year_start_time ), $new_day_of_week_start,date( 'Y', $next_year_start_time ) ); //phpcs:ignore
							$events[ -1 + $y ]['eventEndDate']   = $events[ -1 + $y ]['eventStartDate'] + ( $first_event_end_time - $first_event_start_time );
						}
					}
				}
			}
			return $events;
		}
		/**
		 * Create an array of recurring event start and end dates for monthly recurrences
		 *
		 * @access protected
		 *
		 * @param integer $post_id is the event ID.
		 * @param string  $recurrence is the event recurring.
		 * @param integer $interval is the recurrence interval.
		 * @param string  $recur_end is the recurrence end date.
		 * @param string  $first_event_start is the first recurrence start date.
		 * @param string  $first_event_end is the first recurrence end date.
		 * @param string  $timezone is the event timezone.
		 * @return array
		 */
		protected function create_recurring_monthly_dates( $post_id, $recurrence, $interval, $recur_end, $first_event_start, $first_event_end, $timezone ) {
			$events = array();

			if ( preg_match( '/^[0-9]{1,6}$/', $post_id, $matches ) ) {
				$new_dates = new \DateTime( 'now', new DateTimeZone( $timezone ) );

				( ! empty( $recur_end ) ) ? $new_dates->setTimestamp( strtotime( $recur_end->format( 'Y-m-d' ) . ' ' . $first_event_start->format( 'g:i a' ) ) ) : $new_dates->setTimestamp( mktime( 0, 0, 0, 12, 31, date( 'Y' ) ) ); //phpcs:ignore

				$first_event_start_time = $first_event_start;
				$first_event_end_time   = $first_event_end;

				$recurrence_end_date = $new_dates->getTimestamp();

				if ( $recurrence_end_date > $first_event_end_time ) {
					$num_months       = ceil( ( $recurrence_end_date - $first_event_end_time ) / ( 60 * 60 * 24 * 7 * 4 ) );
					$num_recur_events = $num_months / $interval;
					if ( 0 < $num_recur_events ) {
						$day_of_week_event_start = date( 'w', $first_event_start_time ); //phpcs:ignore
						$date_diff_start         = floor( date( 'j', $first_event_start_time ) - 1 ); //phpcs:ignore
						$week_num_start          = ceil( $date_diff_start / 7 ); //phpcs:ignore
						for ( $m = 1; $m <= $num_recur_events; $m++ ) {
							$next_month_start      = date( 'n', $first_event_start_time ) + ( $interval * $m ); //phpcs:ignore
							$next_month_start_time = ( $next_month_start > 12 ) ? mktime( 0, 0, 0, ( $next_month_start - 12 ), 1, date( 'Y', $first_event_start_time ) + 1 ) : mktime( 0, 0, 0, $next_month_start, 1, date( 'Y', $first_event_start_time ) ); //phpcs:ignore
							$new_day_of_week_start = 1;
							if ( date( 'w', $next_month_start_time ) > $day_of_week_event_start ) { //phpcs:ignore
								$new_day_of_week_start = 1 + ( ( 7 + $day_of_week_event_start ) - date( 'w', $next_month_start_time ) ); //phpcs:ignore
							} elseif ( date( 'w', $next_month_start_time ) < $day_of_week_event_start ) { //phpcs:ignore
								$new_day_of_week_start = ( 1 + ( $day_of_week_event_start - date( 'w', $next_month_start_time ) ) ); //phpcs:ignore
							}
							$new_day_of_week_start              += ( $week_num_start * 7 - 7 );
							$events[ -1 + $m ]['eventStartDate'] = mktime( 0, 0, 0, date( 'm', $next_month_start_time ), $new_day_of_week_start, date( 'Y', $next_month_start_time ) ); //phpcs:ignore
							$events[ -1 + $m ]['eventEndDate']   = $events[ -1 + $m ]['eventStartDate'] + ( $first_event_end_time - $first_event_start_time ); //phpcs:ignore
						}
					}
				}
			}
			return $events;
		}
		/**
		 * Create an array of recurring event start and end dates for daily and weekly recurrences
		 *
		 * @access protected
		 *
		 * @param integer $post_id is the event ID.
		 * @param string  $recurrence is the event recurring.
		 * @param integer $interval is the recurrence interval.
		 * @param string  $recur_end is the recurrence end date.
		 * @param string  $first_event_start is the first recurrence start date.
		 * @param string  $first_event_end is the first recurrence end date.
		 * @param string  $timezone is the event timezone.
		 * @return array
		 */
		protected function create_recurring_daily_weekly_dates( $post_id, $recurrence, $interval, $recur_end, $first_event_start, $first_event_end, $timezone ) {
			$events = array();

			if ( preg_match( '/^[0-9]{1,6}$/', $post_id, $matches ) ) {
				$new_dates = new \DateTime( 'now', new DateTimeZone( $timezone ) );
				// $recurrence_end_date = (!empty($recur_end)) ? strtotime(trim($recur_end)." ".date( 'g:i a',$first_event_end)) : mktime(0,0,0,12,31,date( 'Y"));
				( ! empty( $recur_end ) ) ? $new_dates->setTimestamp( strtotime( $recur_end->format( 'Y-m-d' ) . ' ' . $first_event_start->format( 'g:i a' ) ) ) : $new_dates->setTimestamp( mktime( 0, 0, 0, 12, 31, date( 'Y' ) ) ); //phpcs:ignore
				$first_event_start_time = $first_event_start->getTimestamp();
				$first_event_end_time   = $first_event_end->getTimestamp();

				$recurrence_end_date = $new_dates->getTimestamp();

				if ( $recurrence_end_date > $first_event_end_time ) {
					$recurrence_factor   = array(
						'Daily'  => 1,
						'Weekly' => 7,
					);
					$one_day             = 60 * 60 * 24;
					$interval_factor     = $one_day * $recurrence_factor[ $recurrence ] * $interval;
					$next_instance_start = $first_event_start_time + $interval_factor;
					$next_instance_end   = $first_event_end_time + $interval_factor;

					$j = 1;
					while ( $next_instance_end <= $recurrence_end_date ) {
						$events[ -1 + $j ]['eventStartDate'] = $next_instance_start;
						$events[ -1 + $j ]['eventEndDate']   = $next_instance_end;
						$j++;
						$next_instance_start += $interval_factor;
						$next_instance_end   += $interval_factor;
					}
				}
			}
			return $events;
		}
		/**
		 * Create (insert) recurring events. Method called is based on recurrence type
		 *
		 * @access protected
		 *
		 * @param integer $post_id is the event ID.
		 * @param string  $recurrence is the event recurring.
		 * @param integer $interval is the recurrence interval.
		 * @param string  $recur_end is the recurrence end date.
		 * @param string  $first_event_start is the first recurrence start date.
		 * @param string  $first_event_end is the first recurrence end date.
		 * @param string  $timezone is the event timezone.
		 *
		 * @see create_recurring_monthly_dates()
		 * @see create_recurring_yearly_dates()
		 * @see create_recurring_daily_weekly_dates()
		 * @see add_recurring_dates()
		 * @return string
		 */
		protected function create_recurring_dates( $post_id, $recurrence, $interval, $recur_end, $first_event_start, $first_event_end, $timezone ) {
			$recur_added = false;
			if ( preg_match( '/^[0-9]{1,6}$/', $post_id, $matches ) ) {
				$events = array();
				$res    = 0;

				$tz             = new \DateTimeZone( $timezone );
				$recur_date     = new \DateTime( $recur_end, $tz );
				$first_date_st  = new \DateTime( 'now', $tz );
				$first_date_end = new \DateTime( 'now', $tz );
				$first_date_st->setTimestamp( $first_event_start );
				$first_date_end->setTimestamp( $first_event_end );

				switch ( $recurrence ) {
					case 'Monthly':
						$events = $this->create_recurring_monthly_dates( $post_id, $recurrence, $interval, $recur_date, $first_date_st, $first_date_end, $timezone );
						break;
					case 'Yearly':
						$events = $this->create_recurring_yearly_dates( $post_id, $recurrence, $interval, $recur_date, $first_date_st, $first_date_end, $timezone );
						break;
					default:
						$events = $this->create_recurring_daily_weekly_dates( $post_id, $recurrence, $interval, $recur_date, $first_date_st, $first_date_end, $timezone );
						break;
				}

				if ( ! empty( $events ) ) {
					$res   = 0;
					$count = count( $events );
					for ( $e = 0; $e < $count; $e++ ) {
						$key        = $events[ $e ]['eventStartDate'];
						$recur_info = array(
							$key => array(
								'endDate' => $events[ $e ]['eventEndDate'],
								'metaID'  => 0,
							),
						);
						$meta_id    = add_post_meta( $post_id, '_kcal_recurrenceDate', $recur_info, false );
						if ( false !== $meta_id ) {
							$old_recur_info               = $recur_info;
							$recur_info[ $key ]['metaID'] = $meta_id;
							update_post_meta( $post_id, '_kcal_recurrenceDate', $recur_info, $old_recur_info );
							$res++;
						} else {
							delete_post_meta( $post_id, '_kcal_recurrenceDate', $recur_info );
						}
					}
				}
				if ( count( $events ) === $res ) {
					$recur_added = true;
				}
			}
			return $recur_added;
		}
		/**
		 * Verify that data submitted via add new event, or edit event is valid before saving changes
		 *
		 * @access protected
		 * @param array $meta is the event meta data.
		 * @return true|string
		 */
		protected function verify_edit_add_event_data( $meta ) {
			$verified = false;

			if ( isset( $meta['_kcal_eventStartDate'] ) && strtotime( $meta['_kcal_eventStartDate'] ) !== false
			&& isset( $meta['_kcal_eventEndDate'] ) && strtotime( $meta['_kcal_eventEndDate'] ) !== false &&
			isset( $meta['_kcal_eventStartTime'] ) && preg_match( '/^(0?[0-9]|1[012])[\:]([012345][0-9])\s([AaPp][mM])$/', trim( $meta['_kcal_eventStartTime'] ) )
			&& isset( $meta['_kcal_eventEndTime'] ) && preg_match( '/^(0?[0-9]|1[012])[\:]([012345][0-9])\s([AaPp][mM])$/', trim( $meta['_kcal_eventEndTime'] ) ) ) {
				$verified = true;
			}

			return $verified;
		}
		/**
		 * If a recurring event is modified and "all instances" selected, then start and end date for each child + parent is retrieved so start date can be updated
		 *
		 * @access protected
		 * @param string $table is the table being selecte from.
		 * @param string $item_id is the event ID.
		 * @param string $where_field is the comparison field.
		 * @see DB::get_results
		 * @return array|void
		 */
		protected function get_event_date( $table, $item_id, $where_field ) {

			$res = $this->db->get_results(
				sprintf(
					'SELECT `eventStartDate`, `eventEndDate` FROM %s WHERE `itemID` = %d',
					$table,
					$item_id,
				)
			);
			if ( false !== $res ) {
				foreach ( $res as $row ) {
					list( $start_date,$start_time ) = explode( ' ', trim( $row->eventStartDate ) ); //phpcs:ignore
					list( $end_date,$end_time )     = explode( ' ', trim( $row->eventEndDate ) ); //phpcs:ignore
				}
				return array( $start_date, $end_date );
			}
		}
		/**
		 * Main method to update an event. Recurring (child) events will be deleted and reset if recurrence is set
		 *
		 * @param array   $meta is the post meta.
		 * @param integer $post_id is the event ID.
		 *
		 * @see self::verify_edit_add_event_data()
		 * @see self::create_edit_event_values()
		 * @see self::update_event_main()
		 * @see delete_events_recurring()
		 * @see self::create_recurring_dates()
		 * @return string
		 */
		public function update_event( $meta, $post_id ) {
			$valid_data = false;

			if ( preg_match( '/^\d{1,}$/', trim( $post_id ), $matches ) && is_admin() && current_user_can( 'edit_events' ) ) {

				$valid_data = $this->verify_edit_add_event_data( $meta );

				if ( true === $valid_data ) {
					$data_set = $this->create_edit_event_values( $meta, $post_id );

					if ( isset( $data_set ) && is_array( $data_set ) ) {
						foreach ( $data_set as $field => $value ) {
							update_post_meta( $post_id, $field, $value );
						}
						// recurrence - delete old values first.
						delete_post_meta( $post_id, '_kcal_recurrenceDate' );
						if ( 'None' !== $data_set['_kcal_recurrenceType'] ) {
							$this->create_recurring_dates( $post_id, trim( $data_set['_kcal_recurrenceType'] ), $data_set['_kcal_recurrenceInterval'], $data_set['_kcal_recurrenceEnd'], $data_set['_kcal_eventStartDate'], $data_set['_kcal_eventEndDate'], $data_set['_kcal_timezone'] );
						}
					}
				}
			}
			return $valid_data;
		}
		/**
		 * Main method accessed by ajax request to update recurring events.
		 * Parent events are updated if "all instances" was selected.
		 *
		 * @param array $post is the event data.
		 * @see self::format_add_edit_dateTime()
		 * @see self::verify_edit_add_event_data()
		 * @return string
		 */
		public function update_recurring_events( $post ) {
			$valid_data = __( 'Events could not be updated . ', 'kcal' );
			global $wpdb;
			if ( isset( $post['recurrenceID'] ) && isset( $post['eventID'] ) && preg_match( "/^(\d+)(\-{$post['recurrenceID']})$/", trim( $post['eventID'] ), $matches ) && preg_match( '/^\d{1, }$/', trim( $post['recurrenceID'] ), $match ) ) {
				if ( is_admin() && current_user_can( 'edit_events', $matches[1] ) ) {
					$post['eventID']              = $matches[1];
					$post['_kcal_eventStartTime'] = $post['_kcal_recurStartTime'];
					$post['_kcal_eventEndTime']   = $post['_kcal_recurEndTime'];
					$post['_kcal_eventStartDate'] = $post['_kcal_recur_eventStartDate'];
					$post['_kcal_eventEndDate']   = $post['_kcal_recur_eventEndDate'];

					if ( ! isset( $post['recurEdit'] ) || 'this' === trim( $post['recurEdit'] ) ) {

						if ( $this->verify_edit_add_event_data( $post ) ) {
							list( $start_date_time, $end_date_time ) = $this->format_add_edit_dateTime( $post );

							$meta_res = $wpdb->get_var( //phpcs:ignore
								$wpdb->prepare(
									"SELECT `meta_value` FROM `{$wpdb->prefix}postmeta` WHERE `meta_id` = %d",
									$post['recurrenceID']
								)
							);
							if ( ! is_null( $meta_res ) || ! empty( $meta_res ) || false !== $meta_res ) {
								$old_meta = unserialize( $meta_res ); //phpcs:ignore
								$new_meta = array(
									$start_date_time => array(
										'endDate' => $end_date_time,
										'metaID'  => $post['recurrenceID'],
									),
								);
								$saved    = update_post_meta( $post['eventID'], '_kcal_recurrenceDate', $new_meta, $old_meta );
								if ( false !== (bool) $saved ) {
									$valid_data = 'true';
								}
							}
						} else {
							$valid_data .= __( 'Date and / or Time is not the correct format . ', 'kcal' );
						}
					} else {
						if ( $this->verify_edit_add_event_data( $post ) ) {
							$recurring_events = get_post_meta( $post['eventID'], '_kcal_recurrenceDate' );
							$updated          = 0;
							foreach ( $recurring_events as $r_data ) {
								$old_meta                              = $r_data;
								$start_time                            = array_keys( $r_data );
								$post['_kcal_eventStartDate']          = date( 'Y-m-d', $start_time[0] ); //phpcs:ignore
								list( $end_time, $meta_id )            = array_values( $r_data[ $start_time[0] ] );
								$post['_kcal_eventEndDate']            = date( 'Y-m-d', $end_time ); //phpcs:ignore
								list( $new_start_time, $new_end_time ) = $this->format_add_edit_dateTime( $post );
								$new_meta                              = array(
									$new_start_time => array(
										'endDate' => $new_end_time,
										'metaID'  => $meta_id,
									),
								);
								if ( false !== update_post_meta( $post['eventID'], '_kcal_recurrenceDate', $new_meta, $old_meta ) ) {
									$updated++;
								}
							}
							if ( count( $recurring_events ) === $updated ) {
								$valid_data = 'true';
							}
						} else {
							$valid_data .= __( 'Date and / or Time is not the correct format . ', 'kcal' );
						}
					}
				}
			} else {
				$valid_data = __( 'No event selected', 'kcal' );
			}
			return $valid_data;
		}

		/**
		 * Method accessed by php and ajax to display list of available calendars in admin view.
		 * List is reloaded when a new calendar is added.
		 *
		 * @see self::get_calendars_common()
		 * @return string
		 */
		public function display_calendar_list_admin() {
			$res       = $this->get_calendars_common();
			$cals_list = '<p id="calsList">' . __( 'There are no calendars', 'kcal' ) . '</p>';
			if ( false !== $res ) {
				$cals_list = '<ul id="calsList">';
				foreach ( $res as $row ) {
					$checked    = ( 0 < (int) $row->eventCount ) ? 'checked="checked"' : ''; //phpcs:ignore
					$cals_list .= '<li style="color:' . esc_attr( $row->eventBackgroundColor ) . ';font-weight:bold">'; //phpcs:ignore
					$cals_list .= '<div class="calendarsListItem">';
					$cals_list .= '<input type="checkbox" style="margin-right:5px;" id="calendarInfo' . (int) $row->itemID .'" name="calendarInfo[]"' . $checked .' value = "' . (int) $row->itemID . '"/>'; //phpcs:ignore
					$cals_list .= '<label for="calendarInfo' . (int) $row->itemID . '">' . esc_attr( $row->calendarName ) . '</label>'; //phpcs:ignore
					$cals_list .= '</div>';
					$cals_list .= '</li>'; // add onclick event.
				}
				$cals_list .= '</ul>';
			}
			return $cals_list;
		}
		/**
		 * Method to display calendars available in add/edit event form.
		 *
		 * @see self::get_calendars_common()
		 * @return string
		 */
		public function display_calendar_list_events_form() {
			$res       = $this->get_calendars_common();
			$cals_list = '';
			if ( false !== $res ) {
				foreach ( $res as $row ) {
					$cals_list .= '<option value="' . (int) $row->itemID .'" style="color:' . esc_attr( $row->eventBackgroundColor ) . '"> ' . esc_attr( $row->calendarName ) .' </option>'; //phpcs:ignore
				}
			}
			return $cals_list;
		}
		/**
		 * Ajax delete an event from the calendar
		 *
		 * @global object $wpdb
		 *
		 * @param array $post is the event to delete.
		 * @return string
		 */
		public function delete_events_main( $post ) {
			$valid_data = __( 'Event could not be deleted', 'kcal' );
			if ( isset( $post['eventID'] ) && preg_match( '/^(\d{1,})(\-)?(\d)*$/', (int) $post['eventID'], $matches ) ) {
				if ( is_admin() && current_user_can( 'edit_events', $matches[1] ) ) {
					if ( isset( $post['recurrenceID'] ) && preg_match( '/^(\d{1,})$/', (int) $post['recurrenceID'], $match_r ) ) {
						if ( ! isset( $post['recurDelete'] ) || 'this' === trim( $post['recurDelete'] ) ) {
							global $wpdb;
							if ( false !== $wpdb->delete( "{$wpdb->prefix}postmeta", array( 'meta_id' => $post['recurrenceID'] ) ) ) { //phpcs:ignore
								$valid_data = 'true';
							}
						} else {
							if ( false !== delete_post_meta( $matches[1], '_kcal_recurrenceDate' ) ) {
								$valid_data = 'true';
							}
						}
					} else {
						delete_post_meta( $matches[1], '_kcal_recurrenceDate' );
						if ( false !== wp_delete_post( $matches[1] ) ) {
							$valid_data = 'true';
						}
					}
				}
			}

			return $valid_data;
		}
		/**
		 * Ajax request to drag and drop from the calendar view
		 *
		 * @global object $wpdb
		 * @param array $post is the event object.
		 *
		 * @return string
		 */
		public function drag_drop_event( $post ) {
			$valid_data = false;
			if ( isset( $post['eventID'] ) && preg_match( '/^(\d{1,})$/', (int) $post['eventID'], $matches ) && isset( $post['_kcal_dropStartDate'] ) ) {
				if ( is_admin() && current_user_can( 'edit_events', $post['eventID'] ) ) {

					$timezone = get_post_meta( $post['eventID'], '_kcal_timezone', true );
					if ( empty( $timezone ) || false === $timezone ) {
						$timezone = get_option( 'gmt_offset' );
					}

					$tz = new \DateTimeZone( $timezone );

					$new_start_date                = new \DateTime( 'now', $tz );
					list($start_date, $start_time) = explode( ' ', $post['_kcal_dropStartDate'] );
					$new_start_date->setDate( substr( $start_date, 0, 4 ), substr( $start_date, 5, 2 ), substr( $start_date, 8, 2 ) );
					$new_start_date->setTime( substr( $start_time, 0, 2 ), substr( $start_time, 3, 2 ), substr( $start_time, 6, 2 ) );

					$new_end_date                = new \DateTime( 'now', $tz );
					list( $end_date, $end_time ) = explode( ' ', $post['_kcal_dropEndDate'] );
					$new_end_date->setDate( substr( $end_date, 0, 4 ), substr( $end_date, 5, 2 ), substr( $end_date, 8, 2 ) );
					$new_end_date->setTime( substr( $end_time, 0, 2 ), substr( $end_time, 3, 2 ), substr( $end_time, 6, 2 ) );

					echo $new_end_date->format( 'Y-m-d h:i:s' ); //phpcs:ignore
					echo $new_start_date->format( 'Y-m-d h:i:s' ); //phpcs:ignore

					if ( false !== $new_start_date && false !== $new_end_date && $new_end_date->getTimestamp() > $new_start_date->getTimestamp() ) {
						if ( isset( $post['recurrenceID'] ) && preg_match( '/^(\d{1,})$/', (int) $post['recurrenceID'], $r_match ) ) {
							global $wpdb;
							$meta_res = $wpdb->get_var( $wpdb->prepare( "SELECT `meta_value` FROM `{$wpdb->prefix}postmeta` WHERE `meta_id` = %d", $post['recurrenceID'] ) ); //phpcs:ignore
							if ( ! is_null( $meta_res ) || ! empty( $meta_res ) || false !== $meta_res ) {
								$old_meta = unserialize( $meta_res ); //phpcs:ignore

								$new_meta = array(
									$new_start_date->getTimestamp() => array(
										'endDate' => $new_end_date->getTimestamp(),
										'metaID'  => $post['recurrenceID'],
									),
								);
								$saved    = update_post_meta( $post['eventID'], '_kcal_recurrenceDate', $new_meta, $old_meta );
								if ( false !== (bool) $saved ) {
									$valid_data = 'true';
								}
							}
						} else {
							update_post_meta( $post['eventID'], '_kcal_eventStartDate', $new_start_date->getTimestamp() );
							update_post_meta( $post['eventID'], '_kcal_eventEndDate', $new_end_date->getTimestamp() );
						}
					}
				}
			}
			return $valid_data;
		}

		/**
		 * Parse the imported RSS feed
		 *
		 * @param string  $url is the RSS url to parse.
		 * @param integer $calendar is the calender to add to the imported events.
		 * @param string  $timezone is the RSS timezone.
		 *
		 * @return string
		 */
		public function import_parse_RSS( $url, $calendar, $timezone ) {
			$ch = curl_init(); //phpcs:ignore
			curl_setopt( $ch, CURLOPT_URL, $url ); //phpcs:ignore
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true ); //phpcs:ignore
			$xmlresponse = curl_exec( $ch ); //phpcs:ignore
			$source      = simplexml_load_string( $xmlresponse );

			$uploaded = array();
			if ( isset( $source->channel ) ) {
				foreach ( $source->channel->item as $event ) {

					try {
						$date_timezone = new DateTimeZone( $timezone );
					} catch ( Exception $e ) {
						$date_timezone = new DateTimeZone( get_option( 'gmt_offset' ) );
					}

					$event_date_obj = new Datetime( 'now', $date_timezone );
					// Format: Tue, 19 Oct 2004 13:38:55 -0400.
					$event_date_obj->createFromFormat( 'D, d M Y G:i:s T', $event->{'pubDate'} );
					$event_date  = $event_date_obj->getTimestamp();
					$description = (string) $event->{'description'};

					if ($event_date >= current_time( 'timestamp' ) ) { //phpcs:ignore
						$exists     = get_page_by_title( (string) $event->{'title'}, OBJECT, 'event' );
						$event_time = $event_date_obj->formatDate( 'h:i A' );
						$content    = strip_tags( $description, '<p><br><strong><a><img>' );
						$filtered   = preg_replace( '/\s(class|style|font)\=(\'\")[^\>](\'\")/', '', $content );

						$new_event['post_content']          = $filtered;
						$new_event['post_content_filtered'] = strip_tags( (string) $event->{'description'} ); //phpcs:ignore
						$new_event['post_status']           = 'publish';
						$new_event['post_type']             = 'event';
						$new_event['post_title']            = (string) $event->{'title'};
						$new_event['comment_status']        = 'closed';
						$new_event['ID']                    = ( isset( $exists->ID ) && 0 < $exists->ID ) ? $exists->ID : 0;
						$new_event['meta_input']            = array(
							'_kcal_eventStartDate'     => $event_date,
							'_kcal_eventEndDate'       => $event_date,
							'_kcal_eventURL'           => (string) $event->{'link'},
							'_kcal_location'           => '',
							'_kcal_recurrenceType'     => 'None',
							'_kcal_recurrenceInterval' => 0,
							'_kcal_recurrenceEnd'      => '',
							'_kcal_eventStartTime'     => $event_time,
							'_kcal_eventEndTime'       => $event_time + ( 60 * 60 * 4 ),
							'_kcal_locationMap'        => '',
							'_kcal_timezone'           => $timezone,
						);

						$created = wp_insert_post( $new_event );
						if ( ! is_wp_error( $created ) ) {
							if ( 0 < $calendar ) {
								wp_set_post_terms( $created, array( $calendar ), 'calendar', false );
							}
							$uploaded['success'][] = __( 'Event:', 'kcal' ) . '<a href="' . admin_url( 'post.php?post=' . $created . '&action=edit' ) . '">' . (string) $event->{'title'} . __( '</> was imported.', 'kcal' );
						} else {
							$uploaded['error'][] = __( 'Event:', 'kcal' ) . (string) $event->{'title'} . __( ' was not imported', 'kcal' );
						}
					}
				}
			} else {
				$uploaded['error'][] = __( 'RSS events could not be imported . Check the URL and retry', 'kcal' );
			}
			if ( isset( $uploaded['success'] ) ) {
				$uploaded['success'][] = __( 'RSS Content can\'t be guaranteed. Events may need to be updated for content and dates.', 'kcal' );
			}
			return $uploaded;
		}
		/**
		 * Import events via ICS file
		 *
		 * @param string  $ics_file is the imported file.
		 * @param integer $calendar is the calendar to assign the event to.
		 * @param string  $timezone is the event timezone.
		 *
		 * @return string
		 */
		public function import_parse_ICS( $ics_file, $calendar, $timezone ) {
			$uploaded = array();

			if ( file_exists( $ics_file ) ) {
				$content = file_get_contents( $ics_file ); //phpcs:ignore
				if ( ! empty( $content ) && false !== strstr( $content, 'BEGIN:' ) ) {
					$lines                              = explode( '\n', $content );
					$new_event['post_content']          = '';
					$new_event['post_content_filtered'] = '';
					$new_event['post_status']           = 'publish';
					$new_event['post_type']             = 'event';
					$new_event['post_title']            = '';
					$new_event['comment_status']        = 'closed';
					$new_event['ID']                    = 0;
					$new_event['meta_input']            = array(
						'_kcal_eventStartDate'     => '',
						'_kcal_eventEndDate'       => '',
						'_kcal_eventURL'           => '',
						'_kcal_location'           => '',
						'_kcal_recurrenceType'     => 'None',
						'_kcal_recurrenceInterval' => 0,
						'_kcal_recurrenceEnd'      => '',
						'_kcal_eventStartTime'     => '',
						'_kcal_eventEndTime'       => '',
						'_kcal_locationMap'        => '',
						'_kcal_timezone'           => $timezone,
					);
					try {
						$date_timezone = new DateTimeZone( $timezone );
					} catch ( exception $e ) {
						$date_timezone = new DateTimeZone( get_option( 'gmt_offset' ) );
					}

					foreach ( $lines as $info ) {
						if ( ! empty( $info ) ) {
							$ics_data = explode( ':', $info );

							if ( 1 < count( $ics_data ) ) {

								$ics_param = $ics_data[0];
								unset( $ics_data[0] );
								$ics_content = array_values( $ics_data );

								if ( false !== strstr( $ics_param, 'DTSTART' ) ) {
									$date_start = new \DateTime( $ics_content[0], $date_timezone );

									$new_event['meta_input']['_kcal_eventStartDate'] = $date_start->getTimestamp();
									$new_event['meta_input']['_kcal_eventStartTime'] = $date_start->format( 'g:i A' );
								}
								if ( false !== strstr( $ics_param, 'DTEND' ) ) {
									$date_end = new \DateTime( $ics_content[0], $date_timezone );

									$new_event['meta_input']['_kcal_eventEndDate'] = $date_end->getTimestamp();
									$new_event['meta_input']['_kcal_eventEndTime'] = $date_end->format( 'g:i A' );
								}
								if ( false !== strstr( $ics_param, 'SUMMARY' ) ) {
									$new_event['post_title'] = implode( ' ', $ics_data );
								}
								if ( false !== strstr( $ics_param, 'DESC' ) && empty( $new_event['meta_input']['post_content'] ) ) {
									$new_event['post_content']          .= '<p>' . str_replace( '\n', '<br />', implode( ': ', $ics_content ) ) . '</p>';
									$new_event['post_content_filtered'] .= implode( ' ', $ics_content );
								}
								if ( false !== strstr( $ics_param, 'LOCATION' ) && empty( $new_event['meta_input']['_kcal_location'] ) ) {
									$new_event['meta_input']['_kcal_location'] = implode( ': ', $ics_content );
								}
								if ( false !== strstr( $ics_param, 'ORGANIZER' ) ) {
									$new_event['post_content']          .= '<p>' . $ics_content[0] . '</p>';
									$new_event['post_content_filtered'] .= implode( ': ', $ics_content );
								}
							}
						}
					}
					if ( $new_event['meta_input']['_kcal_eventStartTime'] === $new_event['meta_input']['_kcal_eventEndTime'] ) {
						$date_end->setTimestamp( $new_event['meta_input']['_kcal_eventEndDate'] + ( 60 * 60 * 4 ) );
						$new_event['meta_input']['_kcal_eventEndTime'] = $date_end->format( 'g:i A' );
					}
					$exists          = get_page_by_title( $new_event['post_title'], OBJECT, 'event' );
					$new_event['ID'] = ( isset( $exists->ID ) && 0 < $exists->ID ) ? $exists->ID : 0;
					$created         = wp_insert_post( $new_event );
					if ( ! is_wp_error( $created ) ) {
						if ( 0 < $calendar ) {
							wp_set_post_terms( $created, array( $calendar ), 'calendar', false );
						}
						// Translators: %s is the link to the admin event edit page.
						$uploaded['success'][] = sprintf( __( 'Event: %s, was imported', 'kcal' ), '<a href="' . admin_url( 'post.php?post=' . $created . '&action=edit' ) . '">' . $new_event['post_title'] . '</a>' );
					} else {
						// Translators: %s is the event title.
						$uploaded['error'][] = sprintf( __( 'Event: %s, was not imported', 'kcal' ), $new_event['post_title'] );
					}
					unlink( $ics_file );
				}
			}
			return $uploaded;
		}
	}
	$ca = new AdminCalendar();
}
