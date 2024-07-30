<?php
/**
 * Sureforms - Icons
 *
 * @package Sureforms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Spec_Icon' ) ) {

	/**
	 * Class Spec_Icon.
	 */
	class Spec_Icon {

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
			add_action( 'init', [ $this, 'register_blocks' ] );
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

			$attr = [
				'icon'                             =>
				[
					'type'    => 'string',
					'default' => 'circle-check',
				],
				'iconSize'                         =>
				[
					'type'    => 'number',
					'default' => 40,
				],
				'iconSizeTablet'                   =>
				[
					'type' => 'number',
				],
				'iconSizeMobile'                   =>
				[
					'type' => 'number',
				],
				'iconSizeUnit'                     =>
				[
					'type'    => 'string',
					'default' => 'px',
				],
				'align'                            =>
				[
					'type'    => 'string',
					'default' => 'center',
				],
				'alignTablet'                      =>
				[
					'type'    => 'string',
					'default' => '',
				],
				'alignMobile'                      =>
				[
					'type'    => 'string',
					'default' => '',
				],
				'iconColor'                        =>
				[
					'type'    => 'string',
					'default' => '#333',
				],
				'iconBorderColor'                  =>
				[
					'type'    => 'string',
					'default' => '',
				],
				'iconBackgroundColorType'          =>
				[
					'type'    => 'string',
					'default' => 'classic',
				],
				'iconBackgroundColor'              =>
				[
					'type'    => 'string',
					'default' => '',
				],
				'iconBackgroundGradientColor'      =>
				[
					'type'    => 'string',
					'default' => 'linear-gradient(90deg, rgb(155, 81, 224) 0%, rgb(6, 147, 227) 100%)',
				],
				'iconHoverColor'                   =>
				[
					'type'    => 'string',
					'default' => '',
				],
				'iconHoverBackgroundColorType'     =>
				[
					'type'    => 'string',
					'default' => 'classic',
				],
				'iconHoverBackgroundColor'         =>
				[
					'type' => 'string',
				],
				'iconHoverBackgroundGradientColor' =>
				[
					'type'    => 'string',
					'default' => 'linear-gradient(90deg, rgb(155, 81, 224) 0%, rgb(6, 147, 227) 100%)',
				],
				'rotation'                         =>
				[
					'type'    => 'number',
					'default' => 0,
				],
				'rotationUnit'                     =>
				[
					'type'    => 'string',
					'default' => 'deg',
				],
				'block_id'                         =>
				[
					'type' => 'string',
				],
				'link'                             =>
				[
					'type'    => 'string',
					'default' => '',
				],
				'target'                           =>
				[
					'type'    => 'boolean',
					'default' => false,
				],
				'disableLink'                      =>
				[
					'type'    => 'boolean',
					'default' => false,
				],
				'iconTopPadding'                   =>
				[
					'type'    => 'number',
					'default' => 5,
				],
				'iconRightPadding'                 =>
				[
					'type'    => 'number',
					'default' => 5,
				],
				'iconLeftPadding'                  =>
				[
					'type'    => 'number',
					'default' => 5,
				],
				'iconBottomPadding'                =>
				[
					'type'    => 'number',
					'default' => 5,
				],
				'iconTopTabletPadding'             =>
				[
					'type' => 'number',
				],
				'iconRightTabletPadding'           =>
				[
					'type' => 'number',
				],
				'iconLeftTabletPadding'            =>
				[
					'type' => 'number',
				],
				'iconBottomTabletPadding'          =>
				[
					'type' => 'number',
				],
				'iconTopMobilePadding'             =>
				[
					'type' => 'number',
				],
				'iconRightMobilePadding'           =>
				[
					'type' => 'number',
				],
				'iconLeftMobilePadding'            =>
				[
					'type' => 'number',
				],
				'iconBottomMobilePadding'          =>
				[
					'type' => 'number',
				],
				'iconPaddingUnit'                  =>
				[
					'type'    => 'string',
					'default' => 'px',
				],
				'iconTabletPaddingUnit'            =>
				[
					'type'    => 'string',
					'default' => 'px',
				],
				'iconMobilePaddingUnit'            =>
				[
					'type'    => 'string',
					'default' => 'px',
				],
				'iconPaddingLink'                  =>
				[
					'type'    => 'boolean',
					'default' => false,
				],
				'iconTopMargin'                    =>
				[
					'type' => 'number',
				],
				'iconRightMargin'                  =>
				[
					'type' => 'number',
				],
				'iconLeftMargin'                   =>
				[
					'type' => 'number',
				],
				'iconBottomMargin'                 =>
				[
					'type' => 'number',
				],
				'iconTopTabletMargin'              =>
				[
					'type' => 'number',
				],
				'iconRightTabletMargin'            =>
				[
					'type' => 'number',
				],
				'iconLeftTabletMargin'             =>
				[
					'type' => 'number',
				],
				'iconBottomTabletMargin'           =>
				[
					'type' => 'number',
				],
				'iconTopMobileMargin'              =>
				[
					'type' => 'number',
				],
				'iconRightMobileMargin'            =>
				[
					'type' => 'number',
				],
				'iconLeftMobileMargin'             =>
				[
					'type' => 'number',
				],
				'iconBottomMobileMargin'           =>
				[
					'type' => 'number',
				],
				'iconMarginUnit'                   =>
				[
					'type'    => 'string',
					'default' => 'px',
				],
				'iconTabletMarginUnit'             =>
				[
					'type'    => 'string',
					'default' => 'px',
				],
				'iconMobileMarginUnit'             =>
				[
					'type'    => 'string',
					'default' => 'px',
				],
				'iconMarginLink'                   =>
				[
					'type'    => 'boolean',
					'default' => false,
				],
				'isPreview'                        =>
				[
					'type'    => 'boolean',
					'default' => false,
				],
				'iconBorderStyle'                  =>
				[
					'type'    => 'string',
					'default' => 'default',
				],
				'useSeparateBoxShadows'            =>
				[
					'type'    => 'boolean',
					'default' => true,
				],
				'iconShadowColor'                  =>
				[
					'type'    => 'string',
					'default' => '#00000070',
				],
				'iconShadowHOffset'                =>
				[
					'type'    => 'number',
					'default' => 0,
				],
				'iconShadowVOffset'                =>
				[
					'type'    => 'number',
					'default' => 0,
				],
				'iconShadowBlur'                   =>
				[
					'type'    => 'number',
					'default' => 0,
				],
				'iconBoxShadowColor'               =>
				[
					'type'    => 'string',
					'default' => '#00000070',
				],
				'iconBoxShadowHOffset'             =>
				[
					'type'    => 'number',
					'default' => 0,
				],
				'iconBoxShadowVOffset'             =>
				[
					'type'    => 'number',
					'default' => 0,
				],
				'iconBoxShadowBlur'                =>
				[
					'type' => 'number',
				],
				'iconBoxShadowSpread'              =>
				[
					'type' => 'number',
				],
				'iconBoxShadowPosition'            =>
				[
					'type'    => 'string',
					'default' => 'outset',
				],
				'iconShadowColorHover'             =>
				[
					'type'    => 'string',
					'default' => '#00000070',
				],
				'iconShadowHOffsetHover'           =>
				[
					'type'    => 'number',
					'default' => 0,
				],
				'iconShadowVOffsetHover'           =>
				[
					'type'    => 'number',
					'default' => 0,
				],
				'iconShadowBlurHover'              =>
				[
					'type'    => 'number',
					'default' => 0,
				],
				'iconBoxShadowColorHover'          =>
				[
					'type' => 'string',
				],
				'iconBoxShadowHOffsetHover'        =>
				[
					'type'    => 'number',
					'default' => 0,
				],
				'iconBoxShadowVOffsetHover'        =>
				[
					'type'    => 'number',
					'default' => 0,
				],
				'iconBoxShadowBlurHover'           =>
				[
					'type' => 'number',
				],
				'iconBoxShadowSpreadHover'         =>
				[
					'type' => 'number',
				],
				'iconBoxShadowPositionHover'       =>
				[
					'type'    => 'string',
					'default' => 'outset',
				],
				'iconAccessabilityMode'            =>
				[
					'type'    => 'string',
					'default' => 'svg',
				],
				'iconAccessabilityDesc'            =>
				[
					'type'    => 'string',
					'default' => '',
				],
			];

			$icon_border_attr = Spec_Gb_Helper::get_instance()->generate_php_border_attribute( 'icon' );

			$attr = array_merge( $icon_border_attr, $attr );

			$attributes = apply_filters( 'srfm_gutenberg_icon_attributes_filters', $attr );

			register_block_type(
				'srfm/icon',
				[
					'attributes'      => $attributes,
					'render_callback' => [ $this, 'render_html' ],
				]
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

			$icon         = isset( $attributes['icon'] ) && $attributes['icon'] ? $attributes['icon'] : 'circle-check';
			$link         = isset( $attributes['link'] ) && $attributes['link'] ? $attributes['link'] : '';
			$target       = isset( $attributes['target'] ) && $attributes['target'] ? $attributes['target'] : '';
			$disable_link = isset( $attributes['disableLink'] ) && $attributes['disableLink'] ? $attributes['disableLink'] : '';
			$hash         = '#';
			ob_start();
			$icon_html = Spec_Gb_Helper::render_svg_html( $icon ? $icon : 'circle-check' );
			$icon_html = ob_get_clean();

			if ( $icon_html ) {
				$role_attr        = ( 'image' === $attributes['iconAccessabilityMode'] ) ? ' role="img"' : ( ( 'svg' === $attributes['iconAccessabilityMode'] ) ? ' role="graphics-symbol"' : '' );
				$aria_hidden_attr = ( 'presentation' === $attributes['iconAccessabilityMode'] ) ? 'true' : 'false';
				$aria_label_attr  = ( 'presentation' !== $attributes['iconAccessabilityMode'] ) ? ' aria-label="' . esc_attr( $attributes['iconAccessabilityDesc'] ) . '"' : '';

				$icon_html = preg_replace(
					'/<svg(.*?)>/',
					'<svg$1' . $role_attr . ' aria-hidden="' . $aria_hidden_attr . '"' . $aria_label_attr . '>',
					$icon_html
				);
			}

			$target_val = $target ? '_blank' : '_self';
			$link_url   = $disable_link ? $link : '#';

			if ( '#' !== $link_url ) { // need to fix.
				$link_url = $link_url ? $link_url : $link_url;
			}

			$main_classes = [
				'wp-block-uagb-icon',
				'uagb-block',
				'uagb-icon-wrapper',
				'uagb-block-' . $block_id,
			];

			if ( isset( $attributes['className'] ) ) {
				$main_classes[] = $attributes['className'];
			}

			ob_start();
			?>
				<div class="<?php echo esc_attr( implode( ' ', $main_classes ) ); ?>">
					<span class="uagb-svg-wrapper">
					<?php
					$link_condition = $disable_link && $link_url;

					if ( $link_condition ) {
							$href = $link_url ? $link_url : $hash;
						?>
						<a rel="noopener noreferrer" href="<?php echo esc_url( $href ); ?>" target="<?php echo esc_attr( $target_val ); ?>" >
					<?php } ?>
						<?php
							echo $icon_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					<?php if ( $link_condition ) { ?>
						</a>
					<?php } ?>
					</span>
				</div>
			<?php
			return ob_get_clean();
		}
	}

	/**
	 *  Prepare if class 'Spec_Icon' exist.
	 *  Kicking this off by calling 'get_instance()' method
	 */
	Spec_Icon::get_instance();
}
