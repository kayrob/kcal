<?php
/**
 * The template for displaying an archive page for Categories.
 *
 * @package kCal
 * @since Quark 1.0
 */
if (isset($_GET["act"]) && $_GET["act"] == "ics"){
    $cc = new CalendarController();
    $cc->addToCalendar();
}
else{
    global $wp_query;
    $cw = new CalendarWidgets();
    $term = get_term_by("slug", get_query_var("calendar"), "calendar");

    $data = $cw->upcoming_events_archive_filter();
    if (!empty($data)){
       $wp_query->post_count = count($data); 
       $wp_query->max_num_pages = ceil(count($data)/10);
    }
    $numPosts = $wp_query->post_count;
    $htmlDescription = stripslashes(get_option( "calendar_description_" . $term->term_id));
    get_header(); 
?>

	<div id="primary" class="site-content row" role="main">

		<div class="col grid_8_of_12 news-events-wrapper">

			<?php if ( have_posts() && !empty($data)) : ?>

				<header class="entry-header">
                <h2 class="entry-title">
                    <span><?php printf( esc_html__( '%s', 'quark' ),   single_cat_title( '', false ) ); ?></span>
                    <div class="entry-title-border"></div>
                </h2>
                <?php if ( category_description() ) { // Show an optional category description ?>
                      <div class="archive-meta"><?php echo category_description(); ?></div>
<?php } 
                      if (!empty($htmlDescription)){
                ?>
                      <div class="entry-description content-section"><?php echo $htmlDescription;?></div>
                <?php
                      }
                ?>
        </header>

				<?php $p = 0; ?>
				<?php  foreach($data as $eventID => $eventData){ 
                $postID = explode("-", $eventID);
                global $post;
                $post = get_post($postID[0]);
                setup_postdata($post);
                set_query_var("eventStart", $eventData["start"]);
                set_query_var("eventEnd", $eventData["end"]);
                set_query_var("eventID", $eventID);
                set_query_var("num_results", $numPosts );
                set_query_var("post_iter", $p);
                get_template_part( "content", "eventSnippet" ); 
                $p++;
                if ($p == 10){
                    break;
                }
               }
               wp_reset_postdata();
        ?>
        <div class="event-spacer"></div>
    <?php 
        
        ectheme_content_nav( "nav-below" ); 
        ?>

			<?php else :?>

				<?php get_template_part( "no-results" ); // Include the template that displays a message that posts cannot be found ?>

			<?php endif; // end have_posts() check ?>

		</div> <!-- /.col.grid_8_of_12 -->
                <?php 
                    get_sidebar("kcal");
               ?>
	</div> <!-- /#primary.site-content.row -->

<?php 
get_footer(); 
}
?>
