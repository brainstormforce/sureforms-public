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
						<div class="select-box__current" tabindex="1">
							<div class="select-box__value">
							<input class="select-box__input" type="radio" id="0" value="1" name="Ben" checked="checked"/>
							<p class="select-box__input-text">Cream</p>
							</div>
							<div class="select-box__value">
							<input class="select-box__input" type="radio" id="1" value="2" name="Ben"/>
							<p class="select-box__input-text">Cheese</p>
							</div>
							<div class="select-box__value">
							<input class="select-box__input" type="radio" id="2" value="3" name="Ben"/>
							<p class="select-box__input-text">Milk</p>
							</div>
							<div class="select-box__value">
							<input class="select-box__input" type="radio" id="3" value="4" name="Ben"/>
							<p class="select-box__input-text">Honey</p>
							</div>
							<div class="select-box__value">
							<input class="select-box__input" type="radio" id="4" value="5" name="Ben"/>
							<p class="select-box__input-text">Toast</p>
							</div><img class="select-box__icon" src="http://cdn.onlinewebfonts.com/svg/img_295694.svg" alt="Arrow Icon" aria-hidden="true"/>
						</div>
						<ul class="select-box__list">
							<li>
							<label class="select-box__option" for="0" aria-hidden="aria-hidden">Cream</label>
							</li>
							<li>
							<label class="select-box__option" for="1" aria-hidden="aria-hidden">Cheese</label>
							</li>
							<li>
							<label class="select-box__option" for="2" aria-hidden="aria-hidden">Milk</label>
							</li>
							<li>
							<label class="select-box__option" for="3" aria-hidden="aria-hidden">Honey</label>
							</li>
							<li>
							<label class="select-box__option" for="4" aria-hidden="aria-hidden">Toast</label>
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
