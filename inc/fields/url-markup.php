<?php
/**
 * Sureforms Url Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Sureforms_Helper;

/**
 * Sureforms Url Field Markup Class.
 *
 * @since 0.0.1
 */
class Url_Markup extends Base {
	use Get_Instance;

	/**
	 * Render the sureforms url classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function classic_styling( $attributes ) {
			$block_id    = isset( $attributes['block_id'] ) ? strval( $attributes['block_id'] ) : '';
			$default     = isset( $attributes['defaultValue'] ) ? $attributes['defaultValue'] : '';
			$required    = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$placeholder = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
			$field_width = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
			$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help        = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$error_msg   = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$classname   = isset( $attributes['className'] ) ? ' '. $attributes['className'] : '';
			$slug        = 'url';

			$block_width = $field_width ? ' srfm-block-width-' . str_replace(".","-",$field_width) : '';

			// html attributes
			$placeholder_attr = $placeholder ? ' placeholder="'. $placeholder .'" ' : '';
			$default_value_attr = $default ? ' value="'. $default .'" ' : '';
			$aria_require_attr = $required ? 'true' : 'false';

			ob_start(); ?>
			<div class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $slug ); ?>-block<?php echo esc_attr( $block_width ); ?><?php echo esc_attr( $classname ); ?>">
				<?php echo wp_kses_post( Sureforms_Helper::GenerateCommonFormMarkup( 'label', $label, $slug, $block_id, $required ) ); ?>
					<div class="srfm-block-wrap">
						<span class="srfm-protocol"><?php _e('https://', 'sureforms') ?></span>
						<input class="srfm-input-common srfm-input-<?php echo esc_attr( $slug ); ?>" type="text" name="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>" aria-required="<?php echo esc_attr( $aria_require_attr ); ?>" <?php echo wp_kses_post( $default_value_attr .' '. $placeholder_attr ); ?> />
						<?php echo Sureforms_Helper::fetch_svg('error', 'srfm-error-icon'); ?>
					</div>
				<?php echo wp_kses_post( Sureforms_Helper::GenerateCommonFormMarkup( 'help', '', '', '', '', $help ) ); ?>
				<?php echo wp_kses_post( Sureforms_Helper::GenerateCommonFormMarkup( 'error', '', '', '', $required, '', $error_msg ) ); ?>
			</div>
		<?php
		return ob_get_clean();

	}

}

