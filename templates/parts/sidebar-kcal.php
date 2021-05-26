<?php
/**
 * The Sidebar containing the main widget areas.
 *
 * @package kCal
 */
?>
<div class="grid_4_of_12">
    <div id="secondary" class="widget-area" role="complementary">
            <?php
            do_action( 'before_sidebar' );

            if (is_archive()){
                if (is_active_sidebar("sidebar-kcal-events")){
                    dynamic_sidebar("sidebar-kcal-events");
                }
                else{
                    dynamic_sidebar( "sidebar-blog" );
                }
            }
            else if (is_single()){
                if (is_active_sidebar("sidebar-kcal-single")){
                    dynamic_sidebar("sidebar-kcal-single");
                }
                else{
                    dynamic_sidebar( "sidebar-single" );
                }
            }
            ?>

    </div> <!-- /#secondary.widget-area -->

</div> <!-- /.col.grid_4_of_12 -->
