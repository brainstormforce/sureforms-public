<?php
/**
 * Sureforms Dropdown Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sureforms Dropdown Markup Class.
 *
 * @since 0.0.1
 */
class Dropdown_Markup extends Base {

	/**
	 * Stores the placeholder text of a select option, defaults to 'Select option' if no placeholder is provided.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $placeholder_html;

	/**
	 * Initialize the properties based on block attributes.
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @since 0.0.2
	 */
	public function __construct( $attributes ) {
		$this->set_properties( $attributes );
		$this->set_input_label( __( 'Dropdown', 'sureforms' ) );
		$this->set_error_msg( $attributes, 'srfm_dropdown_block_required_text' );
		$this->slug        = 'dropdown';
		$this->placeholder = $this->label ? $this->label : __( 'Select option', 'sureforms' );
		$this->set_markup_properties();
		$this->set_aria_described_by();
		$this->set_label_as_placeholder( $this->input_label );

	}

	/**
	 * Render the sureforms dropdown classic styling
	 *
	 * @since 0.0.2
	 * @return string|boolean
	 */
	public function markup() {
		ob_start(); ?>
			<div data-block-id="<?php echo esc_attr( $this->block_id ); ?>" class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $this->slug ); ?>-block srf-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id ); ?>-block<?php echo esc_attr( $this->block_width ); ?><?php echo esc_attr( $this->class_name ); ?> <?php echo esc_attr( $this->conditional_class ); ?>">
				<fieldset>
					<legend><?php echo wp_kses_post( $this->label_markup ); ?></legend>
					<div class="srfm-block-wrap srfm-dropdown-common-wrap">
					<?php
					if ( is_array( $this->options ) ) {
						?>
					<select class="srfm-dropdown-common srfm-<?php echo esc_attr( $this->slug ); ?>-input"
						<?php echo ! empty( $this->aria_described_by ) ? "aria-describedby='" . esc_attr( trim( $this->aria_described_by ) ) . "'" : ''; ?>
				aria-required="<?php echo esc_attr( $this->aria_require_attr ); ?>" name="srfm-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id ); ?><?php echo esc_attr( $this->field_name ); ?>" tabindex="0" aria-hidden="true">
					<option class="srfm-dropdown-placeholder" value="" disabled selected><?php echo esc_html( $this->placeholder ); ?></option>
						<?php foreach ( $this->options as $option ) { ?>
							<option value="<?php echo esc_html( $option ); ?>"><?php echo esc_html( $option ); ?></option>
						<?php } ?>
					</select>
					<?php } ?>
					</div>
					<?php echo wp_kses_post( $this->help_markup ); ?>
					<?php echo wp_kses_post( $this->error_msg_markup ); ?>
				</fieldset>
			</div>

		<?php
		return ob_get_clean();

	}

}
