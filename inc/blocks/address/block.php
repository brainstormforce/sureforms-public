<?php
/**
 * PHP render form Address Block.
 *
 * @package SureForms.
 */

namespace SRFM\Inc\Blocks\Address;

use SRFM\Inc\Blocks\Base;
use SRFM\Inc\Helper;
use SRFM\Inc\Fields\Address_Markup;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Address Block.
 */
class Block extends Base {
	/**
	 * Render the block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param string       $content Post content.
	 *
	 * @return string|boolean
	 */
	public function render( $attributes, $content = '' ) {
		$required             = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$options              = isset( $attributes['options'] ) ? $attributes['options'] : '';
		$field_width          = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
		$label                = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help                 = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$error_msg            = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$line_one_placeholder = isset( $attributes['lineOnePlaceholder'] ) ? $attributes['lineOnePlaceholder'] : '';
		$line_two_placeholder = isset( $attributes['lineTwoPlaceholder'] ) ? $attributes['lineTwoPlaceholder'] : '';
		$city_placeholder     = isset( $attributes['cityPlaceholder'] ) ? $attributes['cityPlaceholder'] : '';
		$state_placeholder    = isset( $attributes['statePlaceholder'] ) ? $attributes['statePlaceholder'] : '';
		$postal_placeholder   = isset( $attributes['postalPlaceholder'] ) ? $attributes['postalPlaceholder'] : '';
		$country_placeholder  = isset( $attributes['countryPlaceholder'] ) ? $attributes['countryPlaceholder'] : '';
		$class_name           = isset( $attributes['className'] ) ? ' ' . $attributes['className'] : '';
		$block_id             = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';
		$form_id              = isset( $attributes['formId'] ) ? $attributes['formId'] : '';
		$help                 = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$slug                 = 'address';

		$block_width = $field_width ? ' srfm-block-width-' . str_replace( '.', '-', $field_width ) : '';

		// html attributes.
		$line_one_placeholder_attr = $line_one_placeholder ? ' placeholder="' . esc_attr( $line_one_placeholder ) . '" ' : '';
		$line_two_placeholder_attr = $line_two_placeholder ? ' placeholder="' . esc_attr( $line_two_placeholder ) . '" ' : '';
		$city_placeholder_attr     = $city_placeholder ? ' placeholder="' . esc_attr( $city_placeholder ) . '" ' : '';
		$state_placeholder_attr    = $state_placeholder ? ' placeholder="' . esc_attr( $state_placeholder ) . '" ' : '';
		$postal_placeholder_attr   = $postal_placeholder ? ' placeholder="' . esc_attr( $postal_placeholder ) . '" ' : '';
		$input_label_fallback      = $label ? $label : __( 'Address', 'sureforms' );
		$input_label               = '-lbl-' . Helper::encrypt( $input_label_fallback );

		$aria_require_attr = $required ? 'true' : 'false';
		$conditional_class = apply_filters( 'srfm_conditional_logic_classes', $form_id, $block_id );

		// $data = $this->get_countries();

		if ( ! empty( $attributes ) ) {
			$markup_class = new Address_Markup();
			ob_start();
			// phpcs:ignore
			// echo $markup_class->markup( $attributes );
			?>
			<div data-block-id="<?php echo esc_attr( $block_id ); ?>" class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $slug ); ?>-block srf-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-block<?php echo esc_attr( $block_width ); ?><?php echo esc_attr( $class_name ); ?> <?php echo esc_attr( $conditional_class ); ?>">
			<?php echo wp_kses_post( Helper::generate_common_form_markup( $form_id, 'label', $label, $slug, $block_id, boolval( $required ) ) ); ?>
			<input class="srfm-input-common srfm-input-<?php echo esc_attr( $slug ); ?>-hidden" type="hidden" name="srfm-<?php echo esc_attr( $slug ); ?>-hidden-<?php echo esc_attr( $block_id ); ?><?php echo esc_attr( $input_label ); ?>"/>
			<div class="srfm-block-wrap">
				<?php echo $content; ?>
			</div>
			</div>
			<?
		}
		return ob_get_clean();
	}
}
