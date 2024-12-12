<?php
/**
 * Sureforms Url Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sureforms Url Field Markup Class.
 *
 * @since 0.0.1
 */
class Url_Markup extends Base {
	/**
	 * Initialize the properties based on block attributes.
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @since 0.0.2
	 */
	public function __construct( $attributes ) {
		$this->slug = 'url';
		$this->set_properties( $attributes );
		$this->set_input_label( __( 'Url', 'sureforms' ) );
		$this->set_error_msg( $attributes, 'srfm_url_block_required_text' );
		$this->set_unique_slug();
		$this->set_field_name( $this->unique_slug );
		$this->set_markup_properties( $this->input_label, true );
		$this->set_aria_described_by();
		$this->set_label_as_placeholder( $this->input_label );
	}

	/**
	 * Render the sureforms url classic styling
	 *
	 * @since 0.0.2
	 * @return string|bool
	 */
	public function markup() {
		ob_start(); ?>
		<div data-block-id="<?php echo esc_attr( $this->block_id ); ?>" class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $this->slug ); ?>-block<?php echo esc_attr( $this->block_width ); ?><?php echo esc_attr( $this->class_name ); ?> <?php echo esc_attr( $this->conditional_class ); ?>">
			<?php echo wp_kses_post( $this->label_markup ); ?>
			<?php echo wp_kses_post( $this->help_markup ); ?>
				<div class="srfm-block-wrap">
					<input class="srfm-input-common srfm-input-<?php echo esc_attr( $this->slug ); ?>" type="text" name="<?php echo esc_attr( $this->field_name ); ?>" id="<?php echo esc_attr( $this->unique_slug ); ?>"
					<?php echo ! empty( $this->aria_described_by ) ? "aria-describedby='" . esc_attr( trim( $this->aria_described_by ) ) . "'" : ''; ?>
					data-required="<?php echo esc_attr( $this->data_require_attr ); ?>" <?php echo wp_kses_post( $this->default_value_attr ); ?> <?php echo wp_kses_post( $this->placeholder_attr ); ?>/>
					<?php echo $this->error_svg; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Ignored to render svg ?>
				</div>
				<div class="srfm-error-wrap">
					<?php echo wp_kses_post( $this->error_msg_markup ); ?>
				</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
