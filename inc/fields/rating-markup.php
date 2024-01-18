<?php
/**
 * Sureforms Rating Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Sureforms_Helper;

/**
 * Sureforms Rating Markup Class.
 *
 * @since 0.0.1
 */
class Rating_Markup extends Base {
	use Get_Instance;


	/**
	 * Render the sureforms rating classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function default( $attributes ) {
		$required     = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$field_width  = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
		$label        = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help         = isset( $attributes['ratingBoxHelpText'] ) ? $attributes['ratingBoxHelpText'] : '';
		$show_numbers = isset( $attributes['showNumbers'] ) ? $attributes['showNumbers'] : '';
		$icon_shape   = isset( $attributes['iconShape'] ) ? $attributes['iconShape'] : '';
		$max_value    = isset( $attributes['maxValue'] ) ? $attributes['maxValue'] : '';
		$error_msg    = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$classname    = isset( $attributes['className'] ) ? ' ' . $attributes['className'] : '';
		$block_id     = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';
		$slug         = 'rating';
		$block_width  = $field_width ? ' srfm-block-width-' . str_replace( '.', '-', $field_width ) : '';

		$aria_require_attr    = $required ? 'true' : 'false';
		$input_label_fallback = $label ? $label : 'Ratings';
		$input_label          = '-lbl-' . Sureforms_Helper::encrypt( $input_label_fallback );

		ob_start(); ?>
			<div class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $slug ); ?>-block srf-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-block<?php echo esc_attr( $block_width ); ?><?php echo esc_attr( $classname ); ?>">
				<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( 'label', $label, $slug, $block_id, boolval( $required ) ) ); ?>
				<div class="srfm-block-wrap">
					<input type="hidden" class="srfm-input-common srfm-input-<?php echo esc_attr( $slug ); ?>" name="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?><?php echo esc_attr( $input_label ); ?>" aria-required="<?php echo esc_attr( $aria_require_attr ); ?>" value=""/>
					<ul>
						<?php for ( $i = 0; $i < $max_value; $i++ ) { ?>
							<li>
								<?php echo Sureforms_Helper::fetch_svg( esc_attr( $icon_shape ), 'srfm-' . esc_attr( $icon_shape ) . '-icon', 'data-value="' . esc_attr( strval( $i + 1 ) ) . '"' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Ignored to render svg ?>
								<span class="srfm-<?php echo esc_attr( $slug ); ?>-number"><?php echo esc_html( strval( $show_numbers ? $i + 1 : '' ) ); ?></span>
							</li>
						<?php } ?>
					</ul>
				</div>
				<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( 'help', '', '', '', false, $help ) ); ?>
				<?php echo wp_kses_post( Sureforms_Helper::generate_common_form_markup( 'error', '', '', '', boolval( $required ), '', $error_msg ) ); ?>
			</div>
		<?php
		return ob_get_clean();

	}

}
