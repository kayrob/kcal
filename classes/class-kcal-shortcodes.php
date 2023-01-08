<?php
/**
 * Shortcodes for kcal
 *
 * @package kcal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Kcal_Shortcodes' ) ) {

	/**
	 * Create some shortcodes to display the various calendar options.
	 */
	class Kcal_Shortcodes {
		/**
		 * Constructor. Setup the hooks.
		 *
		 * @see self::kcal_fullcalendar()
		 * @see self::kcal_singular()
		 * @see self::kcal_archive()
		 */
		public function __construct() {
			add_shortcode( 'kcal', array( $this, 'kcal_fullcalendar' ) );
			add_shortcode( 'kcalSingle', array( $this, 'kcal_singular' ) );
			add_shortcode( 'kcalArchive', array( $this, 'kcal_archive' ) );
		}

		/**
		 * Shortcode to display the fullcalendar
		 *
		 * @param array  $atts are the shortcode properties.
		 * @param string $content is the content between shortcode opener/closer. Optional.
		 */
		public function kcal_fullcalendar( $atts, $content = '' ) {
			ob_start();
			include_once KCAL_HOST_DIR . 'templates/full-calendar.tpl.php';
			$output = ob_get_clean();
			return $output;
		}
		/**
		 * Shortcode to display the search results/archive content
		 *
		 * @param array  $atts are the shortcode properties.
		 * @param string $content is the content between shortcode opener/closer. Optional.
		 */
		public function kcal_archive( $atts, $content = '' ) {
			ob_start();
			include_once KCAL_HOST_DIR . 'templates/parts/content-event-excerpt.php';
			$output = ob_get_clean();
			return $output;
		}
		/**
		 * Shortcode to display the search results/archive content
		 * Header attribute - include the header file, or use this as a partical.
		 *
		 * @param array  $atts are the shortcode properties.
		 * @param string $content is the content between shortcode opener/closer. Optional.
		 */
		public function kcal_singular( $atts, $content = '' ) {
			extract( shortcode_atts( //phpcs:ignore
				array(
					'header' => 'yes',
				),
				$atts
			) ); //phpcs:ignore

			ob_start();
			include_once KCAL_HOST_DIR . 'templates/parts/content-event-sc.php';
			$output = ob_get_clean();
			return $output;
		}
		/**
		 * Shortcode to display calendar pagination on search results/archive pages.
		 *
		 * @param string $nav_id is the navigation html id.
		 * @param string $replace is the content between shortcode opener/closer. Optional.
		 */
		public static function calendar_pagination( $nav_id, $replace = '' ) {
			global $wp_query;
			$big = 999999999; // need an unlikely integer.

			$nav_class = 'site-navigation paging-navigation';
			if ( is_singular( 'event' ) ) {
				$nav_class = 'site-navigation post-navigation nav-single';
			}
			?>
			<nav role="navigation" id="<?php echo esc_attr( $nav_id ); ?>" class="<?php echo esc_attr( $nav_class ); ?> tax-calendar-pagination">
				<h2 class="assistive-text"><?php esc_html_e( 'Post navigation', 'kcal' ); ?></h2>

				<?php
				if ( is_singular() ) { // navigation links for single posts.
					?>

						<?php previous_post_link( '<div class="nav-previous">%link</div>', '<span class="meta-nav">' . _x( '<span class="fa fa-angle-left"></span>', 'Previous post link', 'kcal' ) . '</span> %title', true ); ?>
						<?php next_post_link( '<div class="nav-next">%link</div>', '%title <span class="meta-nav">' . _x( '<span class="fa fa-angle-right"></span>', 'Next post link', 'kcal' ) . '</span>', true ); ?>

					<?php
				} elseif ( $wp_query->max_num_pages > 1 && ( is_home() || is_archive() || is_search() ) ) { // navigation links for home, archive, and search pages.

					$paginate_links = paginate_links(
						array(
							'base'               => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
							'format'             => 'page/%#%',
							'current'            => max( 1, get_query_var( 'page' ) ),
							'total'              => $wp_query->max_num_pages,
							'type'               => 'list',
							'prev_text'          => wp_kses(
								__( '<span class="fa fa-angle-left"></span> Previous', 'ectheme' ),
								array(
									'span' => array(
										'class' => array(),
									),
								),
							),
							'next_text'          => wp_kses(
								__( 'Next <span class="fa fa-angle-right"></span>', 'kcal' ),
								array(
									'span' => array(
										'class' => array(),
									),
								),
							),
							'before_page_number' => '<span class="screen-reader-text">' . esc_attr__( 'Go to page', 'kcal' ) . '</span> ',
						)
					);

					$paginate_links = preg_replace( '/<span class=\"screen-reader-text\">([A-Za-z\s]+)<\/span>\s(\d+)<\/span>/', '$2</span>', $paginate_links );
					$paginate_links = preg_replace( '/\s*page-numbers/', '', $paginate_links );

					if ( is_archive() ) {
						$qv = get_queried_object();
						if ( isset( $qv->taxonomy ) && 'calendar' === $qv->taxonomy ) {
							$paginate_links = str_replace( '/page/', '?pg=', $links );
							$paginate_links = str_replace( '/paged/', '?pg=', $links );
						}
					}

					echo $paginate_links; //phpcs:ignore
				}
				?>

			</nav>
			<?php
		}
	}
	new Kcal_Shortcodes();
}
