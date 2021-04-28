<?php
/**
 * The Template for displaying all single events.
 *
 * @package kCal
 * @since Quark 1.0
 */
if (isset($_GET["act"]) && $_GET["act"] == "ics"){
    $cc = new CalendarController();
    $cc->addToCalendar();
}
else{
get_header(); ?>
    <div id="primary" class="site-content row" role="main">

                    <div class="col grid_8_of_12">

                            <?php while ( have_posts() ) : the_post(); ?>

                                    <?php get_template_part( "content", "event" ); ?>

                            <?php endwhile; // end of the loop. ?>

                    </div> <!-- /.col.grid_8_of_12 -->
                    <?php get_sidebar("kcal"); ?>
    </div> <!-- /#primary.site-content.row -->

<?php get_footer();
}
