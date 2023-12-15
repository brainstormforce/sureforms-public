<?php
/**
 * Sureforms Dropdown Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Sureforms_Helper;

/**
 * Sureforms Dropdown Markup Class.
 *
 * @since 0.0.1
 */
class Dropdown_Markup extends Base {
	use Get_Instance;

	/**
	 * Render the sureforms dropdown classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function classic_styling( $attributes ) {
		$required    = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$options     = isset( $attributes['options'] ) ? $attributes['options'] : '';
		$field_width = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
		$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help        = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$error_msg   = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$classname   = isset( $attributes['className'] ) ? ' ' . $attributes['className'] : '';
		$placeholder = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$block_id    = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';
		$slug        = 'dropdown';

		$block_width = $field_width ? ' srfm-block-width-' . str_replace(".","-",$field_width) : '';

		$aria_require_attr  = $required ? 'true' : 'false';
		$placeholder_attr   = $placeholder ? ' placeholder="' . $placeholder . '" ' : '';

		ob_start(); ?>
			<div class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $slug ); ?>-block srf-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-block<?php echo esc_attr( $block_width ); ?><?php echo esc_attr( $classname ); ?>">
				<?php echo wp_kses_post( Sureforms_Helper::GenerateCommonFormMarkup( 'label', $label, $slug, $block_id, $required ) ); ?>
					<div class="srfm-block-wrap">
						<div class="select-box">
							<div class="srfm-dropdown-current" tabindex="1">
								<div class="srfm-dropdown-value">
									<input class="srfm-dropdown-input" type="radio" id="0" value="1" name="Ben" checked="checked"/>
									<p class="srfm-dropdown-input-text">Cream</p>
								</div>
								<div class="srfm-dropdown-value">
									<input class="srfm-dropdown-input" type="radio" id="1" value="2" name="Ben"/>
									<p class="srfm-dropdown-input-text">Cheese</p>
								</div>
								<?php echo Sureforms_Helper::fetch_svg('angle-down', 'srfm-'. $slug .'-icon'); ?>
							</div>
							<ul class="srfm-dropdown-list">
								<li>
								<label class="srfm-dropdown-option" for="0" aria-hidden="aria-hidden">Cream</label>
								</li>
								<li>
								<label class="srfm-dropdown-option" for="1" aria-hidden="aria-hidden">Cheese</label>
								</li>
							</ul>
						</div>
					</div>
				<?php echo wp_kses_post( Sureforms_Helper::GenerateCommonFormMarkup( 'help', '', '', '', '', $help ) ); ?>
				<?php echo wp_kses_post( Sureforms_Helper::GenerateCommonFormMarkup( 'error', '', '', '', $required, '', $error_msg ) ); ?>
			</div>

		<?php
		return ob_get_clean();

	}

}
