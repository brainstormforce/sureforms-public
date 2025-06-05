<?php
/**
 * Sureforms Gb Helper.
 *
 * @package Sureforms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Spec_Gb_Helper' ) ) {

	/**
	 * Class Spec_Gb_Helper.
	 */
	final class Spec_Gb_Helper {


		/**
		 * Member Variable
		 *
		 * @since 0.0.1
		 * @var instance
		 */
		private static $instance;

		/**
		 * Member Variable
		 *
		 * @since 0.0.1
		 * @var instance
		 */
		public static $block_list;

		/**
		 * Current Block List
		 *
		 * @since 0.0.1
		 * @var current_block_list
		 */
		public static $current_block_list = [];

		/**
		 * Page Blocks Variable
		 *
		 * @since 0.0.1
		 * @var instance
		 */
		public static $page_blocks;

		/**
		 * Stylesheet
		 *
		 * @since 0.0.1
		 * @var stylesheet
		 */
		public static $stylesheet;

		/**
		 * Script
		 *
		 * @since 0.0.1
		 * @var script
		 */
		public static $script;

		/**
		 * Sureforms Block Flag
		 *
		 * @since 0.0.1
		 * @var srfm_flag
		 */
		public static $srfm_flag = false;

		/**
		 * Static CSS Added Array
		 *
		 * @since 0.0.1
		 * @var array
		 */
		public $static_css_blocks = [];

		/**
		 * Google fonts to enqueue
		 *
		 * @since 0.0.1
		 * @var array
		 */
		public static $gfonts = [];

		/**
		 * As our svg icon is too long array so we will divide that into number of icon chunks.
		 *
		 * @var int
		 * @since 0.0.1
		 */
		public static $number_of_icon_chunks = 4;

		/**
		 * Store Json variable
		 *
		 * @since 0.0.1
		 * @var instance
		 */
		public static $icon_json;

		/**
		 * We have icon list in chunks in this variable we will merge all insides array into one single array.
		 *
		 * @var array
		 * @since 0.0.1
		 */
		public static $icon_array_merged = [];

		/**
		 * Seen Refs Array
		 * This array will store the block IDs which have already been processed.
		 *
		 * @since 1.4.2
		 * @var array
		 */
		private static $seen_refs = [];

		/**
		 *  Initiator
		 *
		 * @since 0.0.1
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 */
		public function __construct() {
			require SRFM_DIR . 'modules/gutenberg/classes/class-spec-block-config.php';
			require SRFM_DIR . 'modules/gutenberg/classes/class-spec-block-helper.php';
			require SRFM_DIR . 'modules/gutenberg/classes/class-spec-block-js.php';

			/**
			 * Introduce this action to execute Spec_Block_Config::get_block_attributes().
			 * This is necessary because translation functions are called within it.
			 * WordPress 6.7 introduced warnings when hook execution order is incorrect,
			 * which can lead to notices. This action ensures proper hook sequence.
			 */
			add_action( 'init', [ $this, 'init_block_attributes' ] );

			$theme_name = wp_get_theme()->get( 'Name' );

			if ( 'Divi' === $theme_name ) {
				add_action( 'et_after_main_content', [ $this, 'wp_actions' ], 10 );
			} else {
				add_action( 'wp', [ $this, 'wp_actions' ], 10 );
			}

			add_filter( 'render_block', [ $this, 'generate_render_styles' ], 10, 2 );
		}

		/**
		 * Initialize block attributes.
		 *
		 * @since 1.0.6
		 * @return void
		 */
		public static function init_block_attributes() {
			self::$block_list = Spec_Block_Config::get_block_attributes();
		}

		/**
		 * Get form id content.
		 *
		 * @param string $id form id.
		 * @since 0.0.1
		 * @return void
		 */
		public function form_content_by_id( $id ) {
			$args = [
				'p'         => $id,
				'post_type' => 'sureforms_form',
			];
			$loop = new WP_Query( $args );

			if ( isset( $loop->posts[0] ) && $loop->posts[0] ) {

				/**
				 * Filters the post to build stylesheet for.
				 *
				 * @param \WP_Post $this_post The global post.
				 */
				$this_post = apply_filters( 'srfm_post_for_stylesheet', $loop->posts[0] );

				$this->get_generated_stylesheet( $this_post );
			}
		}

		/**
		 * Render function.
		 *
		 * @param string $block_content Entire Block Content.
		 * @param array  $block Block Properties As An Array.
		 * @return string
		 */
		public function generate_render_styles( $block_content, $block ) {

			if ( isset( $block['blockName'] ) && ( 'srfm/form' === $block['blockName'] ) ) {

				if ( isset( $block['attrs']['id'] ) && $block['attrs']['id'] ) {
					self::form_content_by_id( $block['attrs']['id'] );
				}
			}

			if ( isset( $block['blockName'] ) && ( 'core/shortcode' === $block['blockName'] ) ) {

				if ( isset( $block['innerHTML'] ) ) {
					$start_explode = ( explode( 'form-id="', $block['innerHTML'] ) );
					if ( isset( $start_explode[1] ) ) {
						$end_explode = explode( '"', $start_explode[1] );
						if ( isset( $end_explode ) ) {
							self::form_content_by_id( $end_explode[0] );
						}
					}
				}
			}

			return $block_content;
		}

		/**
		 * WP Actions.
		 */
		public function wp_actions() {
			$this->generate_assets();
			add_action( 'wp_enqueue_scripts', [ $this, 'block_assets' ], 10 );
			add_action( 'wp_head', [ $this, 'frontend_gfonts' ], 120 );
			add_action( 'wp_head', [ $this, 'print_stylesheet' ], 80 );
			add_action( 'wp_footer', [ $this, 'print_script' ], 1000 );
		}

		/**
		 * Load the front end Google Fonts.
		 */
		public function frontend_gfonts() {

			if ( empty( self::$gfonts ) ) {
				return;
			}
			$show_google_fonts = apply_filters( 'srfm_blocks_show_google_fonts', true );
			if ( ! $show_google_fonts ) {
				return;
			}
			$link    = '';
			$subsets = [];
			foreach ( self::$gfonts as $key => $gfont_values ) {
				if ( ! empty( $link ) ) {
					$link .= '%7C'; // Append a new font to the string.
				}
				$link .= $gfont_values['fontfamily'];
				if ( ! empty( $gfont_values['fontvariants'] ) ) {
					$link .= ':';
					$link .= implode( ',', $gfont_values['fontvariants'] );
				}
				if ( ! empty( $gfont_values['fontsubsets'] ) ) {
					foreach ( $gfont_values['fontsubsets'] as $subset ) {
						if ( ! in_array( $subset, $subsets, true ) ) {
							array_push( $subsets, $subset );
						}
					}
				}
			}
			if ( ! empty( $subsets ) ) {
				$link .= '&amp;subset=' . implode( ',', $subsets );
			}

			if ( isset( $link ) && ! empty( $link ) ) {
				wp_enqueue_style( 'srfm-google-fonts', '//fonts.googleapis.com/css?family=' . esc_attr( str_replace( '|', '%7C', $link ) ), [], SRFM_VER, 'all' );
			}
		}

		/**
		 * Set alignment css function.
		 *
		 * @param string $align passed.
		 * @since 0.0.1
		 * @return array
		 */
		public static function alignment_css( $align ) {
			$align_css = [];
			switch ( $align ) {
				case 'left':
					$align_css = [
						'margin-left'  => 0,
						'margin-right' => 'auto',
					];
					break;
				case 'center':
					$align_css = [
						'margin-left'  => 'auto',
						'margin-right' => 'auto',
					];
					break;
				case 'right':
					$align_css = [
						'margin-right' => 0,
						'margin-left'  => 'auto',
					];
					break;
			}
			return $align_css;
		}

		/**
		 * Print the Script in footer.
		 */
		public function print_script() {

			if ( is_null( self::$script ) || '' === self::$script ) {
				return;
			}

			$js_uri = SRFM_URL . 'modules/gutenberg/assets/js/';

			wp_enqueue_script( SRFM_SLUG . '-gutenberg', $js_uri . 'gutenberg.js', [], SRFM_VER, true );

			$srfm_inline_js = self::$script;
			if ( $srfm_inline_js ) {
				wp_add_inline_script( SRFM_SLUG . '-gutenberg', $srfm_inline_js );
			}

		}

		/**
		 * Print the Stylesheet in header.
		 */
		public function print_stylesheet() {

			if ( is_null( self::$stylesheet ) || '' === self::$stylesheet ) {
				return;
			}

			$css_uri = SRFM_URL . 'modules/gutenberg/assets/css/';

			wp_enqueue_style( SRFM_SLUG . '-gutenberg', $css_uri . 'gutenberg.css', [], SRFM_VER );

			$srfm_inline_css = self::$stylesheet;
			if ( $srfm_inline_css ) {
				wp_add_inline_style( SRFM_SLUG . '-gutenberg', $srfm_inline_css );
			}

		}

		/**
		 * Generates stylesheet and appends in head tag.
		 *
		 * @since 0.0.1
		 */
		public function generate_assets() {

			$this_post = [];

			global $post;
			$this_post = $post;

			if ( ! is_object( $this_post ) ) {
				return;
			}

			/**
			 * Filters the post to build stylesheet for.
			 *
			 * @param \WP_Post $this_post The global post.
			 */
			$this_post = apply_filters( 'srfm_post_for_stylesheet', $this_post );

			$this->get_generated_stylesheet( $this_post );

		}

		/**
		 * Generates stylesheet in loop.
		 *
		 * @param object $this_post Current Post Object.
		 * @since 0.0.1
		 */
		public function get_generated_stylesheet( $this_post ) {

			if ( is_object( $this_post ) && isset( $this_post->ID ) && has_blocks( $this_post->ID ) && isset( $this_post->post_content ) ) {

				$blocks = $this->parse( $this_post->post_content );

				$count = count( $blocks );
				for ( $i = 0; $i < $count; $i++ ) {
					if ( isset( $blocks[ $i ]['blockName'] ) && 'srfm/form' === $blocks[ $i ]['blockName'] ) {
						if ( isset( $blocks[ $i ]['attrs']['id'] ) && $blocks[ $i ]['attrs']['id'] ) {
							$form_post = get_post( $blocks[ $i ]['attrs']['id'] );
							if ( $form_post ) {
								$blocks = array_merge( $blocks, $this->parse( $form_post->post_content ) );
							}
						}
					}

					if ( has_shortcode( $this_post->post_content, 'sureforms' ) && isset( $blocks[ $i ]['blockName'] ) && 'core/shortcode' === $blocks[ $i ]['blockName'] ) {
						if ( isset( $blocks[ $i ]['innerHTML'] ) && $blocks[ $i ]['innerHTML'] && str_contains( $blocks[ $i ]['innerHTML'], '[sureforms' ) ) {
							$form_id = preg_replace( '/\D+/', '', $blocks[ $i ]['innerHTML'] );
							if ( $form_id ) {
								$form_post = get_post( $form_id );
								if ( $form_post ) {
									$blocks = array_merge( $blocks, $this->parse( $form_post->post_content ) );
								}
							}
						}
					}
				}

				self::$page_blocks = $blocks;

				if ( ! is_array( $blocks ) || empty( $blocks ) ) {
					return;
				}

				$assets = $this->get_assets( $blocks );

				self::$stylesheet .= $assets['css'];
				self::$script     .= $assets['js'];
			}
		}

		/**
		 * Enqueue Gutenberg block assets for both frontend + backend.
		 *
		 * @since 0.0.1
		 */
		public function block_assets() {

			$block_list_for_assets = self::$current_block_list;

			$blocks = Spec_Block_Config::get_block_attributes();

			foreach ( $block_list_for_assets as $key => $curr_block_name ) {

				$js_assets = ( isset( $blocks[ $curr_block_name ]['js_assets'] ) ) ? $blocks[ $curr_block_name ]['js_assets'] : [];

				$css_assets = ( isset( $blocks[ $curr_block_name ]['css_assets'] ) ) ? $blocks[ $curr_block_name ]['css_assets'] : [];

				foreach ( $js_assets as $asset_handle => $val ) {
					// Scripts.
					wp_enqueue_script( $val );
				}

				foreach ( $css_assets as $asset_handle => $val ) {
					// Styles.
					wp_enqueue_style( $val );
				}
			}

		}

		/**
		 * Parse Guten Block.
		 *
		 * @param string $content the content string.
		 * @since 0.0.1
		 */
		public function parse( $content ) {

			global $wp_version;

			return ( version_compare( $wp_version, '5', '>=' ) ) ? parse_blocks( $content ) : gutenberg_parse_blocks( $content );
		}

		/**
		 * Generates stylesheet for reusable blocks.
		 *
		 * @param array $blocks Blocks array.
		 * @since 0.0.1
		 */
		public function get_assets( $blocks ) {

			$desktop = '';
			$tablet  = '';
			$mobile  = '';

			$tab_styling_css = '';
			$mob_styling_css = '';

			$js = '';

			foreach ( $blocks as $i => $block ) {

				if ( is_array( $block ) ) {

					if ( '' === $block['blockName'] ) {
						continue;
					}
					if ( 'core/block' === $block['blockName'] ) {
						$id = ( isset( $block['attrs']['ref'] ) ) ? $block['attrs']['ref'] : 0;

						$is_block_seen = in_array( $id, self::$seen_refs, true );

						if ( $id && ! $is_block_seen ) {
							self::$seen_refs[] = $id;
							$content           = get_post_field( 'post_content', $id );

							$reusable_blocks = $this->parse( $content );

							$assets = $this->get_assets( $reusable_blocks );

							self::$stylesheet .= $assets['css'];
							self::$script     .= $assets['js'];

						}
					} else {

						$block_assets = $this->get_block_css_and_js( $block );

						// Get CSS for the Block.
						$css = $block_assets['css'];

						if ( ! empty( $css['common'] ) ) {
							$desktop .= $css['common'];
						}

						if ( isset( $css['desktop'] ) ) {
							$desktop .= $css['desktop'];
							$tablet  .= $css['tablet'];
							$mobile  .= $css['mobile'];
						}

						$js .= $block_assets['js'];
					}
				}
			}

			if ( ! empty( $tablet ) ) {
				$tab_styling_css .= '@media only screen and (max-width: ' . SRFM_TABLET_BREAKPOINT . 'px) {';
				$tab_styling_css .= $tablet;
				$tab_styling_css .= '}';
			}

			if ( ! empty( $mobile ) ) {
				$mob_styling_css .= '@media only screen and (max-width: ' . SRFM_MOBILE_BREAKPOINT . 'px) {';
				$mob_styling_css .= $mobile;
				$mob_styling_css .= '}';
			}

			return [
				'css' => $desktop . $tab_styling_css . $mob_styling_css,
				'js'  => $js,
			];
		}

		/**
		 * Get Typography Dynamic CSS.
		 *
		 * @param  array  $attr The Attribute array.
		 * @param  string $slug The field slug.
		 * @param  string $selector The selector array.
		 * @param  array  $combined_selectors The combined selector array.
		 * @since  0.0.1
		 * @return bool|string
		 */
		public static function get_typography_css( $attr, $slug, $selector, $combined_selectors ) {

			$typo_css_desktop = [];
			$typo_css_tablet  = [];
			$typo_css_mobile  = [];

			$already_selectors_desktop = ( isset( $combined_selectors['desktop'][ $selector ] ) ) ? $combined_selectors['desktop'][ $selector ] : [];
			$already_selectors_tablet  = ( isset( $combined_selectors['tablet'][ $selector ] ) ) ? $combined_selectors['tablet'][ $selector ] : [];
			$already_selectors_mobile  = ( isset( $combined_selectors['mobile'][ $selector ] ) ) ? $combined_selectors['mobile'][ $selector ] : [];

			$family_slug     = ( '' === $slug ) ? 'fontFamily' : $slug . 'FontFamily';
			$weight_slug     = ( '' === $slug ) ? 'fontWeight' : $slug . 'FontWeight';
			$transform_slug  = ( '' === $slug ) ? 'fontTransform' : $slug . 'Transform';
			$decoration_slug = ( '' === $slug ) ? 'fontDecoration' : $slug . 'Decoration';
			$style_slug      = ( '' === $slug ) ? 'fontStyle' : $slug . 'FontStyle';

			$l_ht_slug      = ( '' === $slug ) ? 'lineHeight' : $slug . 'LineHeight';
			$f_sz_slug      = ( '' === $slug ) ? 'fontSize' : $slug . 'FontSize';
			$l_ht_type_slug = ( '' === $slug ) ? 'lineHeightType' : $slug . 'LineHeightType';
			$f_sz_type_slug = ( '' === $slug ) ? 'fontSizeType' : $slug . 'FontSizeType';
			$l_sp_slug      = ( '' === $slug ) ? 'letterSpacing' : $slug . 'LetterSpacing';
			$l_sp_type_slug = ( '' === $slug ) ? 'letterSpacingType' : $slug . 'LetterSpacingType';

			$text_transform  = isset( $attr[ $transform_slug ] ) ? $attr[ $transform_slug ] : 'normal';
			$text_decoration = isset( $attr[ $decoration_slug ] ) ? $attr[ $decoration_slug ] : 'none';
			$font_style      = isset( $attr[ $style_slug ] ) ? $attr[ $style_slug ] : 'normal';

			$typo_css_desktop[ $selector ] = [
				'font-family'     => $attr[ $family_slug ],
				'text-transform'  => $text_transform,
				'text-decoration' => $text_decoration,
				'font-style'      => $font_style,
				'font-weight'     => $attr[ $weight_slug ],
				'font-size'       => ( isset( $attr[ $f_sz_slug ] ) ) ? self::get_css_value( $attr[ $f_sz_slug ], $attr[ $f_sz_type_slug ] ) : '',
				'line-height'     => ( isset( $attr[ $l_ht_slug ] ) ) ? self::get_css_value( $attr[ $l_ht_slug ], $attr[ $l_ht_type_slug ] ) : '',
				'letter-spacing'  => ( isset( $attr[ $l_sp_slug ] ) ) ? self::get_css_value( $attr[ $l_sp_slug ], $attr[ $l_sp_type_slug ] ) : '',
			];

			$typo_css_desktop[ $selector ] = array_merge(
				$typo_css_desktop[ $selector ],
				$already_selectors_desktop
			);

			$typo_css_tablet[ $selector ] = [
				'font-size'      => ( isset( $attr[ $f_sz_slug . 'Tablet' ] ) ) ? self::get_css_value( $attr[ $f_sz_slug . 'Tablet' ], $attr[ $f_sz_type_slug ] ) : '',
				'line-height'    => ( isset( $attr[ $l_ht_slug . 'Tablet' ] ) ) ? self::get_css_value( $attr[ $l_ht_slug . 'Tablet' ], $attr[ $l_ht_type_slug ] ) : '',
				'letter-spacing' => ( isset( $attr[ $l_sp_slug . 'Tablet' ] ) ) ? self::get_css_value( $attr[ $l_sp_slug . 'Tablet' ], $attr[ $l_sp_type_slug ] ) : '',
			];

			$typo_css_tablet[ $selector ] = array_merge(
				$typo_css_tablet[ $selector ],
				$already_selectors_tablet
			);

			$typo_css_mobile[ $selector ] = [
				'font-size'      => ( isset( $attr[ $f_sz_slug . 'Mobile' ] ) ) ? self::get_css_value( $attr[ $f_sz_slug . 'Mobile' ], $attr[ $f_sz_type_slug ] ) : '',
				'line-height'    => ( isset( $attr[ $l_ht_slug . 'Mobile' ] ) ) ? self::get_css_value( $attr[ $l_ht_slug . 'Mobile' ], $attr[ $l_ht_type_slug ] ) : '',
				'letter-spacing' => ( isset( $attr[ $l_sp_slug . 'Mobile' ] ) ) ? self::get_css_value( $attr[ $l_sp_slug . 'Mobile' ], $attr[ $l_sp_type_slug ] ) : '',
			];

			$typo_css_mobile[ $selector ] = array_merge(
				$typo_css_mobile[ $selector ],
				$already_selectors_mobile
			);

			return [
				'desktop' => array_merge(
					$combined_selectors['desktop'],
					$typo_css_desktop
				),
				'tablet'  => array_merge(
					$combined_selectors['tablet'],
					$typo_css_tablet
				),
				'mobile'  => array_merge(
					$combined_selectors['mobile'],
					$typo_css_mobile
				),
			];
		}

		/**
		 * Get CSS value
		 *
		 * Syntax:
		 *
		 *  get_css_value( VALUE, UNIT );
		 *
		 * E.g.
		 *
		 *  get_css_value( VALUE, 'em' );
		 *
		 * @param string $value  CSS value.
		 * @param string $unit  CSS unit.
		 * @since 0.0.1
		 */
		public static function get_css_value( $value = '', $unit = '' ) {

			$css_val = '';

			if ( '' !== $value ) {
				$css_val = esc_attr( $value ) . $unit;
			}

			return $css_val;
		}

		/**
		 * Parse CSS into correct CSS syntax.
		 *
		 * @param array  $combined_selectors The combined selector array.
		 * @param string $id The selector ID.
		 * @since 0.0.1
		 */
		public static function generate_all_css( $combined_selectors, $id ) {

			return [
				'desktop' => self::generate_css( $combined_selectors['desktop'], $id ),
				'tablet'  => self::generate_css( $combined_selectors['tablet'], $id ),
				'mobile'  => self::generate_css( $combined_selectors['mobile'], $id ),
			];
		}

		/**
		 * Generate the Box Shadow or Text Shadow CSS.
		 *
		 * For Text Shadow CSS:
		 * ( 'spread', 'position' ) should not be sent as params during the function call.
		 * ( 'spread_unit' ) will have no effect.
		 *
		 * For Box/Text Shadow Hover CSS:
		 * ( 'alt_color' ) should be set as the attribute used for ( 'color' ) in Box/Text Shadow Normal CSS.
		 *
		 * @param array $shadow_properties  Array containing the necessary shadow properties.
		 * @return string                   The generated border CSS or an empty string on early return.
		 *
		 * @since 0.0.1
		 */
		public static function generate_shadow_css( $shadow_properties ) {
			// Get the Object Properties.
			$horizontal      = isset( $shadow_properties['horizontal'] ) ? $shadow_properties['horizontal'] : '';
			$vertical        = isset( $shadow_properties['vertical'] ) ? $shadow_properties['vertical'] : '';
			$blur            = isset( $shadow_properties['blur'] ) ? $shadow_properties['blur'] : '';
			$spread          = isset( $shadow_properties['spread'] ) ? $shadow_properties['spread'] : '';
			$horizontal_unit = isset( $shadow_properties['horizontal_unit'] ) ? $shadow_properties['horizontal_unit'] : 'px';
			$vertical_unit   = isset( $shadow_properties['vertical_unit'] ) ? $shadow_properties['vertical_unit'] : 'px';
			$blur_unit       = isset( $shadow_properties['blur_unit'] ) ? $shadow_properties['blur_unit'] : 'px';
			$spread_unit     = isset( $shadow_properties['spread_unit'] ) ? $shadow_properties['spread_unit'] : 'px';
			$color           = isset( $shadow_properties['color'] ) ? $shadow_properties['color'] : '';
			$position        = isset( $shadow_properties['position'] ) ? $shadow_properties['position'] : 'outset';
			$alt_color       = isset( $shadow_properties['alt_color'] ) ? $shadow_properties['alt_color'] : '';

			// Although optional, color is required for Sarafi on PC. Return early if color isn't set.
			if ( ! $color && ! $alt_color ) {
				return '';
			}

			// Get the CSS units for the number properties.

			$horizontal = self::get_css_value( $horizontal, $horizontal_unit );
			if ( '' === $horizontal ) {
				$horizontal = 0;
			}

			$vertical = self::get_css_value( $vertical, $vertical_unit );
			if ( '' === $vertical ) {
				$vertical = 0;
			}

			$blur = self::get_css_value( $blur, $blur_unit );
			if ( '' === $blur ) {
				$blur = 0;
			}

			$spread = self::get_css_value( $spread, $spread_unit );
			if ( '' === $spread ) {
				$spread = 0;
			}

			// If all numeric unit values are exactly 0, don't render the CSS.
			if ( ( 0 === $horizontal && 0 === $vertical ) && ( 0 === $blur && 0 === $spread ) ) {
				return '';
			}

			// Return the CSS with horizontal, vertical, blur, and color - and conditionally render spread and position.
			return (
				$horizontal . ' ' . $vertical . ' ' . $blur . ( $spread ? " {$spread}" : '' ) . ' ' . ( $color ? $color : $alt_color ) . ( 'outset' === $position ? '' : " {$position}" )
			);
		}

		/**
		 * Parse CSS into correct CSS syntax.
		 *
		 * @param array  $selectors The block selectors.
		 * @param string $id The selector ID.
		 * @since 0.0.1
		 */
		public static function generate_css( $selectors, $id ) {
			$styling_css = '';

			if ( ! empty( $selectors ) ) {
				foreach ( $selectors as $key => $value ) {

					$css = '';

					foreach ( $value as $j => $val ) {

						if ( 'font-family' === $j && 'Default' === $val ) {
							continue;
						}

						if ( ! empty( $val ) && is_array( $val ) ) {
							foreach ( $val as $key => $css_value ) {
								$css .= $key . ': ' . $css_value . ';';
							}
						} elseif ( ! empty( $val ) || 0 === $val ) {
							if ( 'font-family' === $j ) {
								$css .= $j . ': "' . $val . '";';
							} else {
								$css .= $j . ': ' . $val . ';';
							}
						}
					}

					if ( ! empty( $css ) ) {
						$styling_css     .= $id;
						$styling_css     .= $key . '{';
							$styling_css .= $css . '}';
					}
				}
			}

			return $styling_css;
		}

		/**
		 * Adds Google fonts all blocks.
		 *
		 * @param array  $load_google_font the blocks attr.
		 * @param string $font_family the blocks attr.
		 * @param array  $font_weight the blocks attr.
		 */
		public static function blocks_google_font( $load_google_font, $font_family, $font_weight ) {

			if ( true === $load_google_font ) {
				if ( ! array_key_exists( $font_family, self::$gfonts ) ) {
					$add_font                     = [
						'fontfamily'   => $font_family,
						'fontvariants' => ( isset( $font_weight ) && ! empty( $font_weight ) ? [ $font_weight ] : [] ),
					];
					self::$gfonts[ $font_family ] = $add_font;
				} else {
					if ( isset( $font_weight ) && ! empty( $font_weight ) && ! in_array( $font_weight, self::$gfonts[ $font_family ]['fontvariants'], true ) ) {
						array_push( self::$gfonts[ $font_family ]['fontvariants'], $font_weight );
					}
				}
			}
		}

		/**
		 * Generates CSS recurrsively.
		 *
		 * @param object $block The block object.
		 * @since 0.0.1
		 */
		public function get_block_css_and_js( $block ) {

			$block = (array) $block;

			$name     = $block['blockName'];
			$css      = [];
			$js       = '';
			$block_id = '';

			if ( isset( $name ) ) {

				if ( isset( $block['attrs'] ) && is_array( $block['attrs'] ) ) {
					$blockattr = $block['attrs'];
					if ( isset( $blockattr['block_id'] ) ) {
						$block_id = $blockattr['block_id'];
					}
				}

				self::$current_block_list[] = $name;

				if ( strpos( $name, 'srfm/' ) !== false ) {
					self::$srfm_flag = true;
				}

				switch ( $name ) {
					case 'srfm/separator':
						$css = Spec_Block_Helper::get_separator_css( $blockattr, $block_id );
						Spec_Block_JS::blocks_separator_gfont( $blockattr );
						break;

					case 'srfm/image':
						$css = Spec_Block_Helper::get_image_css( $blockattr, $block_id );
						Spec_Block_JS::blocks_image_gfont( $blockattr );
						break;

					case 'srfm/icon':
						$css = Spec_Block_Helper::get_icon_css( $blockattr, $block_id );
						break;

					case 'srfm/advanced-heading':
						$css = Spec_Block_Helper::get_advanced_heading_css( $blockattr, $block_id );
						Spec_Block_JS::blocks_advanced_heading_gfont( $blockattr );
						break;

					default:
						// Nothing to do here.
						break;
				}

				// Add static css here.
				$block_css_arr = Spec_Block_Config::get_block_assets_css();

				if ( isset( $block_css_arr[ $name ] ) && ! in_array( $block_css_arr[ $name ]['name'], $this->static_css_blocks, true ) ) {

					$common_css = [
						'common' => $this->get_block_static_css( $block_css_arr[ $name ]['name'] ),
					];
					$css       += $common_css;
				}

				if ( isset( $block['innerBlocks'] ) ) {
					foreach ( $block['innerBlocks'] as $j => $inner_block ) {
						if ( 'core/block' === $inner_block['blockName'] ) {
							$id = ( isset( $inner_block['attrs']['ref'] ) ) ? $inner_block['attrs']['ref'] : 0;

							if ( $id ) {
								$content = get_post_field( 'post_content', $id );

								$reusable_blocks = $this->parse( $content );

								$assets = $this->get_assets( $reusable_blocks );

								self::$stylesheet .= $assets['css'];
								self::$script     .= $assets['js'];
							}
						} else {
							// Get CSS for the Block.
							$inner_assets    = $this->get_block_css_and_js( $inner_block );
							$inner_block_css = $inner_assets['css'];

							$css_common  = ( isset( $css['common'] ) ? $css['common'] : '' );
							$css_desktop = ( isset( $css['desktop'] ) ? $css['desktop'] : '' );
							$css_tablet  = ( isset( $css['tablet'] ) ? $css['tablet'] : '' );
							$css_mobile  = ( isset( $css['mobile'] ) ? $css['mobile'] : '' );

							if ( isset( $inner_block_css['common'] ) ) {
								$css['common'] = $css_common . $inner_block_css['common'];
							}

							if ( isset( $inner_block_css['desktop'] ) ) {
								$css['desktop'] = $css_desktop . $inner_block_css['desktop'];
								$css['tablet']  = $css_tablet . $inner_block_css['tablet'];
								$css['mobile']  = $css_mobile . $inner_block_css['mobile'];
							}

							$js .= $inner_assets['js'];
						}
					}
				}

				self::$current_block_list = array_unique( self::$current_block_list );
			}

			return [
				'css' => $css,
				'js'  => $js,
			];

		}

		/**
		 * Get block dynamic CSS selector with filters applied for extending it.
		 *
		 * @param string $block_name Block name to filter.
		 * @param array  $selectors Array of selectors to filter.
		 * @param array  $attr Attributes.
		 * @return array Combined selectors array.
		 * @since 0.0.1
		 */
		public static function get_combined_selectors( $block_name, $selectors, $attr ) {
			if ( ! is_array( $selectors ) ) {
				return $selectors;
			}

			$combined_selectors = [];

			foreach ( $selectors as $key => $selector ) {
				$hook_prefix                = ( 'desktop' === $key ) ? '' : '_' . $key;
				$combined_selectors[ $key ] = apply_filters( 'srfm_' . $block_name . $hook_prefix . '_styling', $selector, $attr );
			}

			return $combined_selectors;
		}

		/**
		 * Get Static CSS of Block.
		 *
		 * @param string $block_name Block Name.
		 *
		 * @return string Static CSS.
		 * @since 0.0.1
		 */
		public function get_block_static_css( $block_name ) {

			$css = '';

			$block_static_css_path = SRFM_DIR . 'modules/gutenberg/assets/css/blocks/' . $block_name . '.css';

			if ( file_exists( $block_static_css_path ) ) {

				$file_system = spec_filesystem();

				$css = $file_system->get_contents( $block_static_css_path );
			}

			array_push( $this->static_css_blocks, $block_name );

			return $css;
		}

		/**
		 * Border attribute generation Function.
		 *
		 * @since 0.0.1
		 * @param  array $prefix   Attribute Prefix.
		 * @param array $default_args  default attributes args.
		 * @return array
		 */
		public function generate_php_border_attribute( $prefix, $default_args = [] ) {

			$border_attr = [];

			$device = [ '', 'Tablet', 'Mobile' ];

			foreach ( $device as $slug => $data ) {

				$border_attr[ "{$prefix}BorderTopWidth{$data}" ]          = [
					'type' => 'number',
				];
				$border_attr[ "{$prefix}BorderLeftWidth{$data}" ]         = [
					'type' => 'number',
				];
				$border_attr[ "{$prefix}BorderRightWidth{$data}" ]        = [
					'type' => 'number',
				];
				$border_attr[ "{$prefix}BorderBottomWidth{$data}" ]       = [
					'type' => 'number',
				];
				$border_attr[ "{$prefix}BorderTopLeftRadius{$data}" ]     = [
					'type' => 'number',
				];
				$border_attr[ "{$prefix}BorderTopRightRadius{$data}" ]    = [
					'type' => 'number',
				];
				$border_attr[ "{$prefix}BorderBottomLeftRadius{$data}" ]  = [
					'type' => 'number',
				];
				$border_attr[ "{$prefix}BorderBottomRightRadius{$data}" ] = [
					'type' => 'number',
				];
				$border_attr[ "{$prefix}BorderRadiusUnit{$data}" ]        = [
					'type' => 'number',
				];
			}

			$border_attr[ "{$prefix}BorderStyle" ]      = [
				'type' => 'string',
			];
			$border_attr[ "{$prefix}BorderColor" ]      = [
				'type' => 'string',
			];
			$border_attr[ "{$prefix}BorderHColor" ]     = [
				'type' => 'string',
			];
			$border_attr[ "{$prefix}BorderLink" ]       = [
				'type'    => 'boolean',
				'default' => true,
			];
			$border_attr[ "{$prefix}BorderRadiusLink" ] = [
				'type'    => 'boolean',
				'default' => true,
			];

			return $border_attr;
		}

		/**
		 * Get Json Data.
		 *
		 * @since 0.0.1
		 * @return array
		 */
		public static function backend_load_font_awesome_icons() {

			if ( null !== self::$icon_json ) {
				return self::$icon_json;
			}

			$icons_chunks = [];
			for ( $i = 0; $i < self::$number_of_icon_chunks; $i++ ) {
				$json_file = SRFM_DIR . "modules/gutenberg/icons/icons-v6-{$i}.php";

				if ( file_exists( $json_file ) ) {
					$icons_chunks[] = include $json_file;
				}
			}

			if ( empty( $icons_chunks ) ) {
				return [];
			}

			self::$icon_json = $icons_chunks;
			return self::$icon_json;
		}

		/**
		 * Generate SVG.
		 *
		 * @since 0.0.1
		 * @param  array $icon Decoded fontawesome json file data.
		 * @param  bool  $return The parameter decides whether to return or echo the svg markup.
		 */
		public static function render_svg_html( $icon, $return = false ) {
			$icon = sanitize_text_field( esc_attr( $icon ) );

			$json = self::backend_load_font_awesome_icons();

			if ( ! empty( $json ) ) {
				if ( empty( $icon_array_merged ) ) {
					foreach ( $json as $value ) {
						self::$icon_array_merged = array_merge( self::$icon_array_merged, $value );
					}
				}
				$json = self::$icon_array_merged;
			}

			$icon_brand_or_solid = isset( $json[ $icon ]['svg']['brands'] ) ? $json[ $icon ]['svg']['brands'] : ( isset( $json[ $icon ]['svg']['solid'] ) ? $json[ $icon ]['svg']['solid'] : [] );
			$path                = isset( $icon_brand_or_solid['path'] ) ? $icon_brand_or_solid['path'] : '';
			$view                = isset( $icon_brand_or_solid['width'] ) && isset( $icon_brand_or_solid['height'] ) ? '0 0 ' . $icon_brand_or_solid['width'] . ' ' . $icon_brand_or_solid['height'] : null;

			if ( $path && $view ) {
				if ( $return ) {
					ob_start();
					?>
				<svg xmlns="https://www.w3.org/2000/svg" viewBox= "<?php echo esc_attr( $view ); ?>"><path d="<?php echo esc_attr( $path ); ?>"></path></svg>
					<?php
					return ob_get_clean();
				} else {
					?>
				<svg xmlns="https://www.w3.org/2000/svg" viewBox= "<?php echo esc_attr( $view ); ?>"><path d="<?php echo esc_attr( $path ); ?>"></path></svg>
					<?php
				}
			}
		}

	}

	/**
	 *  Prepare if class 'Spec_Gb_Helper' exist.
	 *  Kicking this off by calling 'get_instance()' method
	 */
	Spec_Gb_Helper::get_instance();
}
