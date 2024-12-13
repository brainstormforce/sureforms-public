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
		$this->set_markup_properties();
		$this->set_aria_described_by();
	}

	/**
	 * Render the sureforms checkbox classic styling
	 *
	 * @since 0.0.2
	 * @return string|bool
	 */
	public function markup() {

		$label_random_id = 'srfm-' . $this->slug . '-' . wp_rand();

		ob_start(); ?>
			<div data-block-id="<?php echo esc_attr( $this->block_id ); ?>" class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $this->slug ); ?>-block srf-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id ); ?>-block<?php echo esc_attr( $this->block_width ); ?><?php echo esc_attr( $this->class_name ); ?> <?php echo esc_attr( $this->conditional_class ); ?>">
				<div class="srfm-block-wrap">
					<input class="srfm-input-common screen-reader-text srfm-input-<?php echo esc_attr( $this->slug ); ?>" id="<?php echo esc_attr( $label_random_id ); ?>"
					<?php echo ! empty( $this->aria_described_by ) ? "aria-describedby='" . esc_attr( trim( $this->aria_described_by ) ) . "'" : ''; ?>
					name="srfm-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id ); ?><?php echo esc_attr( $this->field_name ); ?>" data-required="<?php echo esc_attr( $this->data_require_attr ); ?>" type="checkbox" <?php echo esc_attr( $this->checked_attr ); ?>/>
					<label class="srfm-cbx" for="<?php echo esc_attr( $label_random_id ); ?>">
						<span class="srfm-span-wrap">
							<svg class="srfm-check-icon" width="12px" height="10px" aria-hidden="true">
								<use xlink:href="#srfm-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id ); ?>-check"></use>
							</svg>
						</span>
						<span class="srfm-span-wrap srfm-block-label"><?php echo wp_kses( $this->label, $this->allowed_tags ); ?>
						<?php if ( $this->required ) { ?>
							<span class="srfm-required" aria-label="<?php echo esc_html__( 'Required', 'sureforms' ); ?>"><span aria-hidden="true"> *</span></span>
						<?php } ?>
						</span>
					</label>
					<svg class="srfm-inline-svg" aria-hidden="true">
						<symbol id="srfm-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id ); ?>-check" viewbox="0 0 12 10">
						<polyline points="1.5 6 4.5 9 10.5 1"></polyline>
						</symbol>
					</svg>
				</div>
				<?php echo wp_kses_post( $this->help_markup ); ?>
				<div class="srfm-error-wrap">
					<?php echo wp_kses_post( $this->error_msg_markup ); ?>
				</div>
			</div>
		<?php

		return ob_get_clean();
	}

}
