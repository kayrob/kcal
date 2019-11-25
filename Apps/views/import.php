<?php
if (is_admin())
{
  $kcalOptions = get_option("kcal_settings");
  $importRSS = (isset($kcalOptions["kcal_rss_events"])) ? $kcalOptions["kcal_rss_events"] : "";
  $calendars = get_terms("calendar", array("hide_empty" => false));
  
  if (isset($imported["success"])){
?>
    <div class="notice notice-success is-dismissible rl-notice">
    <p><?php echo implode("<br />", $imported["success"]);?></p>
    </div>
<?php
  }
  if (isset($imported["error"])){
?>
    <div class="notice notice-error is-dismissible rl-notice">
    <p><?php echo implode("<br />", $imported["error"]);?></p>
    </div>
<?php
  }
?>

<div id="klcalImportRSS" class="kcal-import-container">
  <form method="post" action="<?php echo admin_url("edit.php?post_type=event&page=edit.php%3Fview%3Dimport");?>">
  <h2>Import RSS Feed</h2>
  <div class="kcal-import-actions">
    <p>You can save a default RSS feed at <a href="<?php echo admin_url("options-general.php?page=manage-calendar-settings");?>">Settings > Events Manager</a></p>
    <div class="form-fields">
    <label for ="kcal_importRSS_url">RSS URL</label>
    <input type="url" value="<?php echo $importRSS;?>" name="kcal_importRSS_url" id="kcal_importRSS_url" />
    </div>
    <div class="form-fields">
    <label for="kcal_importRSS_calendar">Choose Calendar</label>
    <select name="kcal_importRSS_calendar" id="kcal_importRSS_calendar">
      <option value="">--</option>
      <?php
        if (!empty($calendars)){
          foreach($calendars as $calendar){
            echo "<option value=\"".$calendar->term_id."\">".$calendar->name."</option>";
          }
        }
      ?>
    </select>
    </div>
    <div class="form-buttons">
    <input type="submit" name="kcal_submit_rss_import" value="Import Events" class="button-primary"/>
    </div>
  </div>
  </form>
</div>
<div id="klcalImportICS" class="kcal-import-container">
  <form enctype="multipart/form-data" method="post" action="<?php echo admin_url("edit.php?post_type=event&page=edit.php%3Fview%3Dimport");?>">
  <h2>Import Single Event</h2>
  <div class="kcal-import-actions">
    <p>Only .ics files can be uploaded</p>
    <div class="form-fields">
      <label for ="kcal_importICS_file">Upload .ics File</label>
      <input type="file" value="" name="kcal_importICS_file" id="kcal_importICS_file" />
    </div>
    <div class="form-fields">
    <label for="kcal_importICS_calendar">Choose Calendar</label>
    <select name="kcal_importICS_calendar" id="kcal_importICS_calendar">
      <option value="">--</option>
      <?php
        if (!empty($calendars)){
          foreach($calendars as $calendar){
            echo "<option value=\"".$calendar->term_id."\">".$calendar->name."</option>";
          }
        }
      ?>
    </select>
    </div>
    <div class="form-buttons">
      <input type="submit" name="kcal_submit_rss_import" value="Import Event" class="button-primary"/>
    </div>
  </div>
  </form>
</div>
<?php
}