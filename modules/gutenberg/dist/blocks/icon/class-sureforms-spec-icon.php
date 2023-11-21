<?php
/**
 * Sureforms - Icons
 *
 * @package Sureforms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Sureforms_Spec_Icon' ) ) {

	/**
	 * Class Sureforms_Spec_Icon.
	 */
	class Sureforms_Spec_Icon {

		/**
		 * Member Variable
		 *
		 * @var instance
		 */
		private static $instance;

		/**
		 *  Initiator
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

			// Activation hook.
			add_action( 'init', array( $this, 'register_blocks' ) );
		}

		/**
		 * Registers the `core/latest-posts` block on server.
		 *
		 * @since 0.0.1
		 */
		public function register_blocks() {

			// Check if the register function exists.
			if ( ! function_exists( 'register_block_type' ) ) {
				return;
			}

			$attr = array_merge(
				array(
					'icon'                             =>
					array(
						'type'    => 'string',
						'default' => 'circle-check',
					),
					'iconSize'                         =>
					array(
						'type'    => 'number',
						'default' => 40,
					),
					'iconSizeTablet'                   =>
					array(
						'type' => 'number',
					),
					'iconSizeMobile'                   =>
					array(
						'type' => 'number',
					),
					'iconSizeUnit'                     =>
					array(
						'type'    => 'string',
						'default' => 'px',
					),
					'align'                            =>
					array(
						'type'    => 'string',
						'default' => 'center',
					),
					'alignTablet'                      =>
					array(
						'type'    => 'string',
						'default' => '',
					),
					'alignMobile'                      =>
					array(
						'type'    => 'string',
						'default' => '',
					),
					'iconColor'                        =>
					array(
						'type'    => 'string',
						'default' => '#333',
					),
					'iconBorderColor'                  =>
					array(
						'type'    => 'string',
						'default' => '',
					),
					'iconBackgroundColorType'          =>
					array(
						'type'    => 'string',
						'default' => 'classic',
					),
					'iconBackgroundColor'              =>
					array(
						'type'    => 'string',
						'default' => '',
					),
					'iconBackgroundGradientColor'      =>
					array(
						'type'    => 'string',
						'default' => 'linear-gradient(90deg, rgb(155, 81, 224) 0%, rgb(6, 147, 227) 100%)',
					),
					'iconHoverColor'                   =>
					array(
						'type'    => 'string',
						'default' => '',
					),
					'iconHoverBackgroundColorType'     =>
					array(
						'type'    => 'string',
						'default' => 'classic',
					),
					'iconHoverBackgroundColor'         =>
					array(
						'type' => 'string',
					),
					'iconHoverBackgroundGradientColor' =>
					array(
						'type'    => 'string',
						'default' => 'linear-gradient(90deg, rgb(155, 81, 224) 0%, rgb(6, 147, 227) 100%)',
					),
					'rotation'                         =>
					array(
						'type'    => 'number',
						'default' => 0,
					),
					'rotationUnit'                     =>
					array(
						'type'    => 'string',
						'default' => 'deg',
					),
					'block_id'                         =>
					array(
						'type' => 'string',
					),
					'link'                             =>
					array(
						'type'    => 'string',
						'default' => '',
					),
					'target'                           =>
					array(
						'type'    => 'boolean',
						'default' => false,
					),
					'disableLink'                      =>
					array(
						'type'    => 'boolean',
						'default' => false,
					),
					'iconTopPadding'                   =>
					array(
						'type'    => 'number',
						'default' => 5,
					),
					'iconRightPadding'                 =>
					array(
						'type'    => 'number',
						'default' => 5,
					),
					'iconLeftPadding'                  =>
					array(
						'type'    => 'number',
						'default' => 5,
					),
					'iconBottomPadding'                =>
					array(
						'type'    => 'number',
						'default' => 5,
					),
					'iconTopTabletPadding'             =>
					array(
						'type' => 'number',
					),
					'iconRightTabletPadding'           =>
					array(
						'type' => 'number',
					),
					'iconLeftTabletPadding'            =>
					array(
						'type' => 'number',
					),
					'iconBottomTabletPadding'          =>
					array(
						'type' => 'number',
					),
					'iconTopMobilePadding'             =>
					array(
						'type' => 'number',
					),
					'iconRightMobilePadding'           =>
					array(
						'type' => 'number',
					),
					'iconLeftMobilePadding'            =>
					array(
						'type' => 'number',
					),
					'iconBottomMobilePadding'          =>
					array(
						'type' => 'number',
					),
					'iconPaddingUnit'                  =>
					array(
						'type'    => 'string',
						'default' => 'px',
					),
					'iconTabletPaddingUnit'            =>
					array(
						'type'    => 'string',
						'default' => 'px',
					),
					'iconMobilePaddingUnit'            =>
					array(
						'type'    => 'string',
						'default' => 'px',
					),
					'iconPaddingLink'                  =>
					array(
						'type'    => 'boolean',
						'default' => false,
					),
					'iconTopMargin'                    =>
					array(
						'type' => 'number',
					),
					'iconRightMargin'                  =>
					array(
						'type' => 'number',
					),
					'iconLeftMargin'                   =>
					array(
						'type' => 'number',
					),
					'iconBottomMargin'                 =>
					array(
						'type' => 'number',
					),
					'iconTopTabletMargin'              =>
					array(
						'type' => 'number',
					),
					'iconRightTabletMargin'            =>
					array(
						'type' => 'number',
					),
					'iconLeftTabletMargin'             =>
					array(
						'type' => 'number',
					),
					'iconBottomTabletMargin'           =>
					array(
						'type' => 'number',
					),
					'iconTopMobileMargin'              =>
					array(
						'type' => 'number',
					),
					'iconRightMobileMargin'            =>
					array(
						'type' => 'number',
					),
					'iconLeftMobileMargin'             =>
					array(
						'type' => 'number',
					),
					'iconBottomMobileMargin'           =>
					array(
						'type' => 'number',
					),
					'iconMarginUnit'                   =>
					array(
						'type'    => 'string',
						'default' => 'px',
					),
					'iconTabletMarginUnit'             =>
					array(
						'type'    => 'string',
						'default' => 'px',
					),
					'iconMobileMarginUnit'             =>
					array(
						'type'    => 'string',
						'default' => 'px',
					),
					'iconMarginLink'                   =>
					array(
						'type'    => 'boolean',
						'default' => false,
					),
					'isPreview'                        =>
					array(
						'type'    => 'boolean',
						'default' => false,
					),
					'iconBorderStyle'                  =>
					array(
						'type'    => 'string',
						'default' => 'default',
					),
					'useSeparateBoxShadows'            =>
					array(
						'type'    => 'boolean',
						'default' => true,
					),
					'iconShadowColor'                  =>
					array(
						'type'    => 'string',
						'default' => '#00000070',
					),
					'iconShadowHOffset'                =>
					array(
						'type'    => 'number',
						'default' => 0,
					),
					'iconShadowVOffset'                =>
					array(
						'type'    => 'number',
						'default' => 0,
					),
					'iconShadowBlur'                   =>
					array(
						'type'    => 'number',
						'default' => 0,
					),
					'iconBoxShadowColor'               =>
					array(
						'type'    => 'string',
						'default' => '#00000070',
					),
					'iconBoxShadowHOffset'             =>
					array(
						'type'    => 'number',
						'default' => 0,
					),
					'iconBoxShadowVOffset'             =>
					array(
						'type'    => 'number',
						'default' => 0,
					),
					'iconBoxShadowBlur'                =>
					array(
						'type' => 'number',
					),
					'iconBoxShadowSpread'              =>
					array(
						'type' => 'number',
					),
					'iconBoxShadowPosition'            =>
					array(
						'type'    => 'string',
						'default' => 'outset',
					),
					'iconShadowColorHover'             =>
					array(
						'type'    => 'string',
						'default' => '#00000070',
					),
					'iconShadowHOffsetHover'           =>
					array(
						'type'    => 'number',
						'default' => 0,
					),
					'iconShadowVOffsetHover'           =>
					array(
						'type'    => 'number',
						'default' => 0,
					),
					'iconShadowBlurHover'              =>
					array(
						'type'    => 'number',
						'default' => 0,
					),
					'iconBoxShadowColorHover'          =>
					array(
						'type' => 'string',
					),
					'iconBoxShadowHOffsetHover'        =>
					array(
						'type'    => 'number',
						'default' => 0,
					),
					'iconBoxShadowVOffsetHover'        =>
					array(
						'type'    => 'number',
						'default' => 0,
					),
					'iconBoxShadowBlurHover'           =>
					array(
						'type' => 'number',
					),
					'iconBoxShadowSpreadHover'         =>
					array(
						'type' => 'number',
					),
					'iconBoxShadowPositionHover'       =>
					array(
						'type'    => 'string',
						'default' => 'outset',
					),
				),
			);

			$icon_border_attr = Sureforms_Spec_Gb_Helper::get_instance()->generate_php_border_attribute( 'icon' );

			$attr = array_merge( $icon_border_attr, $attr );

			$attributes = apply_filters( 'sureforms_gutenberg_icon_attributes_filters', $attr );

			register_block_type(
				'sureforms/icon',
				array(
					'attributes'      => $attributes,
					'render_callback' => array( $this, 'render_html' ),
				)
			);
		}

		/**
		 * Render CF HTML.
		 *
		 * @param array $attributes Array of block attributes.
		 *
		 * @since 0.0.1
		 */
		public function render_html( $attributes ) {

			$block_id = '';

			if ( isset( $attributes['block_id'] ) ) {
				$block_id = $attributes['block_id'];
			}

			$icon         = isset( $attributes['icon'] ) && $attributes['icon'] ? $attributes['icon'] : '';
			$link         = isset( $attributes['link'] ) && $attributes['link'] ? $attributes['link'] : '';
			$target       = isset( $attributes['target'] ) && $attributes['target'] ? $attributes['target'] : '';
			$disable_link = isset( $attributes['disableLink'] ) && $attributes['disableLink'] ? $attributes['disableLink'] : '';
			$hash         = '#';
			$icon_html    = '';

			$target_val = $target ? '_blank' : '_self';
			$link_url   = $disable_link ? $link : '#';

			if ( '#' !== $link_url ) { // need to fix.
				$link_url = $link_url ? $link_url : $link_url;
			}

			$main_classes = array(
				'uagb-icon-wrapper',
				'uagb-block-' . $block_id,
			);

			if ( isset( $attributes['className'] ) ) {
				$main_classes[] = $attributes['className'];
			}

			ob_start();
			?>
				<div class="<?php echo esc_attr( implode( ' ', $main_classes ) ); ?>">
					<span class="uagb-svg-wrapper">
					<?php
					if ( $disable_link && $link_url ) {
						$href = $link_url || $hash;
						echo "<a rel='noopener noreferrer' href='" . esc_attr( $href ) . "' target='" . esc_attr( $target_val ) . "'></a>";
					} else {
						Sureforms_Spec_Gb_Helper::render_svg_html( $icon ? $icon : 'circle-check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, Generic.Commenting.DocComment.MissingShort
					}
					?>
					</span>
				</div>
			<?php
			return ob_get_clean();
		}
	}

	/**
	 *  Prepare if class 'Sureforms_Spec_Icon' exist.
	 *  Kicking this off by calling 'get_instance()' method
	 */
	Sureforms_Spec_Icon::get_instance();
}
