<?php
/**
 * Sureforms Input Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sureforms Input Markup Class.
 *
 * @since 0.0.1
 */
class Input_Markup extends Base {
	/**
	 * Maximum length of text allowed for an input field.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $max_text_length;

	/**
	 * Input mask for the input field.
	 *
	 * @var string
	 * @since 0.0.11
	 */
	protected $input_mask;
	/**
	 * Custom input mask for the input field.
	 *
	 * @var string
	 * @since 0.0.11
	 */
	protected $custom_input_mask;

	/**
	 * Read-only attribute for the input field.
	 *
	 * @var bool
	 * @since 1.7.2
	 */
	protected $read_only;

	/**
	 * Initialize the properties based on block attributes.
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @since 0.0.2
	 */
	public function __construct( $attributes ) {
		$this->slug              = 'input';
		$this->max_text_length   = $attributes['textLength'] ?? '';
		$this->input_mask        = $attributes['inputMask'] ?? '';
		$this->custom_input_mask = 'custom-mask' === $this->input_mask && isset( $attributes['customInputMask'] ) ? $attributes['customInputMask'] : '';
		$this->set_properties( $attributes );
		$this->read_only = ! empty( $this->default ) && $attributes['readOnly'];
		$this->set_input_label( __( 'Text Field', 'sureforms' ) );
		$this->set_error_msg( $attributes, 'srfm_input_block_required_text' );
		$this->set_duplicate_msg( $attributes, 'srfm_input_block_unique_text' );
		$this->set_unique_slug();
		$this->set_field_name( $this->unique_slug );
		$this->set_markup_properties( $this->input_label, true );
		$this->set_aria_described_by();
		$this->set_label_as_placeholder( $this->input_label );
	}

	/**
	 * Render input markup
	 *
	 * @since 0.0.2
	 * @return string|bool
	 */
	public function markup() {
		$data_config = $this->field_config;

		$this->class_name = $this->get_field_classes( $this->read_only ? [ 'srfm-read-only' ] : [] );

		ob_start(); ?>
			<div data-block-id="<?php echo esc_attr( $this->block_id ); ?>" class="<?php echo esc_attr( $this->class_name ); ?>" <?php echo $data_config ? "data-field-config='" . esc_attr( $data_config ) . "'" : ''; ?>>
			<?php echo wp_kses_post( $this->label_markup ); ?>
			<?php echo wp_kses_post( $this->help_markup ); ?>
				<div class="srfm-block-wrap">
				<input class="srfm-input-common srfm-input-<?php echo esc_attr( $this->slug ); ?>" type="text" name="<?php echo esc_attr( $this->field_name ); ?>" id="<?php echo esc_attr( $this->unique_slug ); ?>"
					<?php echo ! empty( $this->aria_described_by ) ? "aria-describedby='" . esc_attr( trim( $this->aria_described_by ) ) . "'" : ''; ?>
					data-required="<?php echo esc_attr( strval( $this->data_require_attr ) ); ?>" data-unique="<?php echo esc_attr( $this->aria_unique ); ?>" maxlength="<?php echo esc_attr( $this->max_text_length ); ?>" value="<?php echo esc_attr( $this->default ); ?>" <?php echo wp_kses_post( $this->placeholder_attr ); ?> data-srfm-mask="<?php echo esc_attr( $this->input_mask ); ?>" <?php echo ! empty( $this->custom_input_mask ) ? 'data-custom-srfm-mask="' . esc_attr( $this->custom_input_mask ) . '"' : ''; ?> <?php echo $this->read_only ? 'readonly' : ''; ?> />
				</div>
				<div class="srfm-error-wrap">
					<?php echo wp_kses_post( $this->duplicate_msg_markup ); ?>
				</div>
			</div>
		<?php
		return ob_get_clean();
	}
}
