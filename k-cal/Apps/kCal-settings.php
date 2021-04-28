<?php

/*
 * This file displays the admin settings options page for kCal
 */
class kCalSettings
{
    //holds values used in settings fields
    private $options;

    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    public function add_settings_page()
    {
        add_options_page(
            'Settings Admin',
            'Events Manager Settings',
            'manage_options',
            'manage-calendar-settings',
            array( $this, 'create_admin_page' )
        );
    }
    /**
     * Admin Page Display
     */
    public function create_admin_page()
    {
        $this->options = get_option("kcal_settings");
?>
        <div class="wrap">
            <h2><?php _e('Events Manager Settings', 'kcal');?></h2>
            <form method="post" action="options.php">
<?php
                // This prints out all hidden setting fields
                settings_fields( "kcal-settings-group" );
                do_settings_sections( "kcal_settings" );
                submit_button();
?>
            </form>
        </div>
<?php
    }
    /**
     * Register and add settings
     */
    public function page_init()
    {
        register_setting("kcal-settings-group", "kcal_settings", array($this, "sanitize"));
        add_settings_section("kcal-pages", "Events Manager Pages", array($this, "print_section_info"), "kcal_settings");
        add_settings_section("kcal-rssEvents", "RSS Events Import", array($this, "rss_section_info"), "kcal_settings");
        add_settings_field(
            "fullcalendar_page",
            __("Full Calendar (copy full-calendar.tpl.php)", 'kcal'),
            array($this, "fullcalendar_page_callback"),
            "kcal_settings",
            "kcal-pages"
        );
        add_settings_field(
            "import_rss",
            __("RSS Feed to import events", 'kcal'),
            array($this, "kcal_rss_events"),
            "kcal_settings",
            "kcal-rssEvents"
        );
    }

    /**
    * Sanitize each setting field as needed
    *
    * @param array $input Contains all settings fields as array keys
    */
    public function sanitize($input)
    {
        foreach($input as $field => $value){
            $input[$field] = sanitize_text_field($value);
        }

        return $input;
    }
    /**
     * Print the Section text
     */
    public function print_section_info()
    {
      print(__("Enter the slugs for pages you have created (e.g calendar). DO NOT USE SPACES. Be sure to copy in the templates you need for the pages you want into  your theme.", 'kcal' ));
    }
    public function rss_section_info(){
      print(__("Enter the URL for an RSS you want to import events from.", 'kcal' ));
    }
    /**
     * Get the settings option array and print one of its values
     */
    public function fullcalendar_page_callback()
    {
      printf(
          "<input type=\"text\" id=\"fullcalendar_page\" name=\"kcal_settings[fullcalendar_page]\" value=\"%s\" size=\"50\"/>",
          esc_attr( $this->options["fullcalendar_page"])
      );
    }
    public function kcal_rss_events(){
      $rssEvent = (isset($this->options["kcal_rss_events"])) ? $this->options["kcal_rss_events"] : "";
      printf("<input type=\"text\" id=\"kcal_rss_events\" name=\"kcal_settings[kcal_rss_events]\" value=\"%s\" size=\"50\"/>",
            esc_attr($rssEvent));

    }
}
