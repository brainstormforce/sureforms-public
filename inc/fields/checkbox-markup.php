<?php
/**
 * Sureforms Checkbox Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sureforms Checkbox Markup Class.
 *
 * @since 0.0.1
 */
class Checkbox_Markup extends Base {

	/**
	 * Initialize the properties based on block attributes.
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @since 0.0.2
	 */
	public function __construct( $attributes ) {
		$this->set_properties( $attributes );
		$this->set_input_label( __( 'Checkbox', 'sureforms' ) );
		$this->set_error_msg( $attributes, 'srfm_checkbox_block_required_text' );
		$this->slug = 'checkbox';
		$this->help = isset( $attributes['checkboxHelpText'] ) ? $attributes['checkboxHelpText'] : '';
		$this->set_markup_properties();
	}

	/**
	 * Render the sureforms checkbox classic styling
	 *
	 * @since 0.0.2
	 * @return string|boolean
	 */
	public function markup() {
		ob_start(); ?>
			<div data-block-id="<?php echo esc_attr( $this->block_id ); ?>" class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $this->slug ); ?>-block srf-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id ); ?>-block<?php echo esc_attr( $this->block_width ); ?><?php echo esc_attr( $this->class_name ); ?> <?php echo esc_attr( $this->conditional_class ); ?>">
					<div class="srfm-block-wrap">
						<input class="srfm-input-common srfm-input-<?php echo esc_attr( $this->slug ); ?>" id="srfm-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id ); ?>" name="srfm-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id ); ?><?php echo esc_attr( $this->field_name ); ?>" aria-required="<?php echo esc_attr( $this->aria_require_attr ); ?>" type="checkbox" <?php echo esc_attr( $this->checked_attr ); ?>/>
						<label class="srfm-cbx" for="srfm-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id ); ?>">
							<span class="srfm-span-wrap">
								<svg class="srfm-check-icon" width="12px" height="10px">
									<use xlink:href="#srfm-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id ); ?>-check"></use>
								</svg>
							</span>
							<span class="srfm-block-text srfm-span-wrap"><?php echo wp_kses( $this->label, $this->allowed_tags ); ?>
							<?php if ( $this->required ) { ?>
								<span class="srfm-required"> *</span>
							<?php } ?>
							</span>
						</label>
						<svg class="srfm-inline-svg">
							<symbol id="srfm-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id ); ?>-check" viewbox="0 0 12 10">
							<polyline points="1.5 6 4.5 9 10.5 1"></polyline>
							</symbol>
						</svg>
					</div>
				<?php echo wp_kses_post( $this->help_markup ); ?>
				<?php echo wp_kses_post( $this->error_msg_markup ); ?>
			</div>
		<?php

		return ob_get_clean();

	}

}
