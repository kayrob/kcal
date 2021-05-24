<?php
if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!class_exists('kcalShortcodes')) {

	class kcalShortcodes {
		public function __construct() {
			add_shortcode('kcal', array($this, 'kcal_fullcalendar') );
			add_shortcode('kcalSingle', array($this, 'kcal_singular') );
			add_shortcode('kcalArchive', array($this, 'kcal_archive') );
		}

		public function kcal_fullcalendar($atts, $content = '') {
			ob_start();
			include_once( KCAL_HOST_DIR . 'templates/full-calendar.tpl.php' );
			$output = ob_get_clean();
			return $output;
		}

		public function kcal_archive($atts, $content = '') {
			ob_start();
			include( KCAL_HOST_DIR . 'templates/parts/content-event-excerpt.php' );
			$output = ob_get_clean();
			return $output;
		}

		public function kcal_singular($atts, $content = '') {
			extract( shortcode_atts( array(
				'header' => 'yes'
			), $atts));

			ob_start();
			include_once( KCAL_HOST_DIR . 'templates/parts/content-event-sc.php' );
			$output = ob_get_clean();
			return $output;
		}

		public static function calendar_pagination($nav_id, $replace="") {
			global $wp_query;
			$big = 999999999; // need an unlikely integer

			$nav_class = 'site-navigation paging-navigation';
			if ( is_singular('event') ) {
				$nav_class = 'site-navigation post-navigation nav-single';
			}
			?>
			<nav role="navigation" id="<?php echo $nav_id; ?>" class="<?php echo $nav_class; ?> tax-calendar-pagination">
				<h2 class="assistive-text"><?php esc_html_e( 'Post navigation', 'kcal' ); ?></h2>

				<?php if ( is_singular() ) { // navigation links for single posts ?>

						<?php previous_post_link( '<div class="nav-previous">%link</div>', '<span class="meta-nav">' . _x( '<span class="fa fa-angle-left"></span>', 'Previous post link', 'ectheme' ) . '</span> %title', true ); ?>
						<?php next_post_link( '<div class="nav-next">%link</div>', '%title <span class="meta-nav">' . _x( '<span class="fa fa-angle-right"></span>', 'Next post link', 'ectheme') . '</span>', true ); ?>

				<?php }
				elseif ( $wp_query->max_num_pages > 1 && ( is_home() || is_archive() || is_search() ) ) { // navigation links for home, archive, and search pages ?>

				<?php $paginateLinks = paginate_links( array(
					'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
					'format' => 'page/%#%',
					'current' => max( 1, get_query_var( 'paged' ) ),
					'total' => $wp_query->max_num_pages,
					'type' => 'list',
					'prev_text' => wp_kses( __( '<span class="fa fa-angle-left"></span> Previous', 'ectheme' ), array( 'span' => array(
					'class' => array() ) ) ),
					'next_text' => wp_kses( __( 'Next <span class="fa fa-angle-right"></span>', 'ectheme' ), array( 'span' => array(
							'class' => array() ) ) ),
					'before_page_number' => '<span class="screen-reader-text">' . __('Go to page', CHAPMANS_TEXTDOMAIN) . '</span> '
				) );

				$paginateLinks = preg_replace( '/<span class=\"screen-reader-text\">([A-Za-z\s]+)<\/span>\s(\d+)<\/span>/', '$2</span>', $paginate_links );
				$paginateLinks = preg_replace( '/\s*page-numbers/', '', $paginate_links );

				echo $paginateLinks;
				?>

				<?php } ?>

			</nav><!-- #<?php echo $nav_id; ?> -->
			<?php
		}
	}
}
new kcalShortcodes();