<?php
/**
 * Sureforms Input Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Sureforms_Helper;

/**
 * Sureforms Input Markup Class.
 *
 * @since 0.0.1
 */
class Input_Markup extends Base {
	use Get_Instance;

	/**
	 * Render input markup
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function classic_styling( $attributes ) {
		$block_id      = isset( $attributes['block_id'] ) ? strval( $attributes['block_id'] ) : '';
		$default       = isset( $attributes['defaultValue'] ) ? $attributes['defaultValue'] : '';
		$required      = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$is_unique     = isset( $attributes['isUnique'] ) ? $attributes['isUnique'] : false;
		$duplicate_msg = isset( $attributes['duplicateMsg'] ) ? $attributes['duplicateMsg'] : '';
		$placeholder   = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$label         = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$field_width   = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
		$help          = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$error_msg     = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$max_text_length  = isset( $attributes['textLength'] ) ? $attributes['textLength'] : '';
		$classname     = isset( $attributes['className'] ) ? ' ' . $attributes['className'] : '';
		$slug = 'input';

		$block_width = $field_width ? ' srfm-block-width-' . str_replace(".","-",$field_width) : '';

		//Attributes
		$placeholder = $placeholder ? $placeholder : '';
		$max_length = $max_text_length ? $max_text_length : '';
		$aria_require = $required ? 'true' : 'false';
		$aria_unique = $is_unique ? 'true' : 'false';
		$default_value = $default ? $default : '';

		ob_start(); ?>

	<div class="srfm-block srfm-<?php echo esc_attr( $slug ); ?>-block srf-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-block<?php echo esc_attr( $block_width ); ?><?php echo esc_attr( $classname ); ?>">
			<?php echo wp_kses_post( Sureforms_Helper::GenerateCommonFormMarkup( 'label', $label, $slug, $block_id, $required ) ); ?>
				<div class="srfm-block-wrap">
					<input class="srfm-input-common srfm-input-<?php echo esc_attr( $slug ); ?>" type="text" name="srfm-text-input-<?php echo esc_attr( $block_id ); ?>" aria-required="<?php echo esc_attr( $aria_require ); ?>" aria-unique="<?php echo esc_attr( $aria_unique ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" maxlength="<?php echo esc_attr( $max_length ); ?>" value="<?php echo esc_attr( $default_value ); ?>" />
					<?php echo Sureforms_Helper::fetch_svg('error_icon', 'srfm-error-icon'); ?>
				</div>
				<?php echo wp_kses_post( Sureforms_Helper::GenerateCommonFormMarkup( 'help', '', '', '', '', $help ) ); ?>
				<?php echo wp_kses_post( Sureforms_Helper::GenerateCommonFormMarkup( 'error', '', '', '', $required, '', $error_msg, '', $duplicate_msg ) ); ?>
			</div>
		<?php 
		return ob_get_clean();
	}

}
