<?php
if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/*
 * Widget to display a mini calendar in a sidebar
 */
if (!class_exists('kCalQuickView')) {
	class kCalQuickView extends WP_Widget
	{
		protected $scripts = array();
		public function __construct()
		{
			$widget_ops = array("classname" => "cal-mini-widget", "description" => __("A widget that displays a mini calendar in a sidebar", "kCalQuickView"));
			$control_ops = array();
			parent::__construct("kcal-mini-widget", __("K-Cal Mini View Widget", "kCalQuickView"), $widget_ops, $control_ops);

			$pURL = trailingslashit(plugins_url()."/k-cal");
			wp_enqueue_script("kcal-widgets-js", $pURL ."js/mini-calendar.js", array("jquery"), "1.0", true);
			wp_enqueue_style("kcal-widgets-css", $pURL ."css/kcal-widgets.css", array(), '2.2.6');
			wp_localize_script("kcal-widgets-js", "ajax_object", array("ajax_url" => admin_url("admin-ajax.php")));
			$this->scripts['kcal-widgets-js'] = false;
			$this->scripts['kcal-widgets-css'] = false;

			add_action('wp_print_footer_scripts', array($this, 'kcal_remove_scripts'));
		}

		public function widget($args, $instance)
		{
			extract($args);

			$calsSelected = (isset($instance["quickView_calendars"])) ? $instance["quickView_calendars"] : array();

			$date = new \DateTime('now', new \DateTimeZone(get_option('gmt_offset')));

			$this->scripts['kcal-widgets-js'] = true;
			$this->scripts['kcal-widgets-css'] = true;

			$cal = new CalendarWidgets();

			$output = "<div class=\"kcal-mini-widget widget\" id=\"cal-mini-widget\">";
			$output .= "<div id=\"dlgQuickView\"></div>";
			$output .= "<h4 id=\"h4QVHeader\"><button aria-label='view previous month' class='no-style'>&#8249;</button><span>" .$date->format('F Y')."</span><button aria-label='View next month' class='no-style'>&#8250;</button></h4>";
			$output .= $cal->quick_view_calendar(false, false, $calsSelected);
			$output .= "<p id=\"pQVdateTime\">" . $date->format('Y-m-d') ."</p>";
			$output .= "</div>";
			echo $output;
		}

		public function kcal_remove_scripts() {
			foreach ( $this->scripts as $script => $keep ) {
				if ( false === $keep ) {
					// It seems dequeue is not "powerful" enough, you really need to deregister it
					wp_deregister_script( $script );
				}
			}

		}

		public function update($new_instance, $old_instance)
		{
			$instance = $old_instance;

			$instance["quickView_calendars"] = $new_instance["quickView_calendars"];

			return $instance;
		}
		public function form($instance)
		{
			$cal = new Calendar();
			$calendars = $cal->get_calendar_details();

			$calDefault = array_keys($calendars);

			$defaults = array("quickView_calendars" => $calDefault);
			$instance = wp_parse_args((array) $instance, $defaults);

			$calsSelected = $instance["quickView_calendars"];
	?>
			<p>Select Calendars:</p>
	<?php
			if (!empty($calendars)){
				foreach($calendars as $calID => $nfo)
				{
					$checked = ($calsSelected == null || in_array($calID, $calsSelected)) ? " checked=\"checked\"" : "";
		?>
				<p>
				<input class="checkbox" type="checkbox" <?php echo $checked; ?> id="<?php echo $this->get_field_name( "quickView_calendars" )."_".$calID; ?>" name="<?php echo $this->get_field_name( "quickView_calendars" ); ?>[]" value="<?php echo $calID;?>"/>
				<label for="<?php echo $this->get_field_name( "quickView_calendars" )."_".$calID; ?>"><?php _e($nfo["name"], "kCalQuickView"); ?></label>
				</p>
		<?php
				}
			}
			else{
				echo "<p>No Calendars</p>";
			}
		}
	}
}