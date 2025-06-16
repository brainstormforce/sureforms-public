<?php
/**
 * Sureforms Number Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sureforms Number Field Markup Class.
 *
 * @since 0.0.1
 */
class Number_Markup extends Base {
	/**
	 * Minimum value allowed for the input field.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $min_value;

	/**
	 * Maximum value allowed for the input field.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $max_value;

	/**
	 * Format type for the input field.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $format_type;

	/**
	 * HTML attribute string for the format type.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $format_attr;

	/**
	 * HTML attribute string for the minimum value.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $min_value_attr;

	/**
	 * HTML attribute string for the maximum value.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $max_value_attr;

	/**
	 * Prefix for the input field.
	 *
	 * @var string
	 * @since 1.5.0
	 */
	protected $prefix;

	/**
	 * Suffix for the input field.
	 *
	 * @var string
	 * @since 1.5.0
	 */
	protected $suffix;

	/**
	 * Read-only attribute for the number field.
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
		$this->slug = 'number';
		$this->set_properties( $attributes );
		$this->min_value      = $attributes['minValue'] ?? '';
		$this->max_value      = $attributes['maxValue'] ?? '';
		$this->format_type    = $attributes['formatType'] ?? '';
		$this->format_attr    = $this->format_type ? ' format-type="' . $this->format_type . '" ' : '';
		$this->min_value_attr = $this->min_value ? ' min="' . $this->min_value . '" ' : '';
		$this->max_value_attr = $this->max_value ? ' max="' . $this->max_value . '" ' : '';
		$this->prefix         = $attributes['prefix'] ?? '';
		$this->suffix         = $attributes['suffix'] ?? '';
		$this->read_only      = ! empty( trim( $this->default ) ) && $attributes['readOnly'];
		$this->set_input_label( __( 'Number', 'sureforms' ) );
		$this->set_error_msg( $attributes, 'srfm_number_block_required_text' );
		$this->set_unique_slug();
		$this->set_field_name( $this->unique_slug );
		$this->set_markup_properties( $this->input_label );
		$this->set_aria_described_by();
		$this->set_label_as_placeholder( $this->input_label );
	}

	/**
	 * Render the sureforms number classic styling
	 *
	 * @since 0.0.2
	 * @return string|bool
	 */
	public function markup() {
		$data_config      = $this->field_config;
		$this->class_name = $this->get_field_classes( $this->read_only ? [ 'srfm-read-only' ] : [] );

		ob_start(); ?>
			<div data-block-id="<?php echo esc_attr( $this->block_id ); ?>" class="<?php echo esc_attr( $this->class_name ); ?>" <?php echo $data_config ? "data-field-config='" . esc_attr( $data_config ) . "'" : ''; ?>>
				<?php echo wp_kses_post( $this->label_markup ); ?>
				<?php echo wp_kses_post( $this->help_markup ); ?>
				<div class="srfm-block-wrap">
					<div class="srfm-input-content">
						<?php if ( ! empty( $this->prefix ) ) { ?>
							<span class="srfm-number-prefix" aria-hidden="true"><?php echo esc_html( $this->prefix ); ?></span>
						<?php } ?>
						<input class="srfm-input-common srfm-input-<?php echo esc_attr( $this->slug ); ?>" type="text" name="<?php echo esc_attr( $this->field_name ); ?>" id="<?php echo esc_attr( $this->unique_slug ); ?>"
						<?php echo ! empty( $this->aria_described_by ) ? "aria-describedby='" . esc_attr( trim( $this->aria_described_by ) ) . "'" : ''; ?>
						data-required="<?php echo esc_attr( $this->data_require_attr ); ?>" <?php echo wp_kses_post( $this->placeholder_attr . '' . $this->default_value_attr . '' . $this->format_attr . '' . $this->min_value_attr . '' . $this->max_value_attr ); ?> <?php echo $this->read_only ? 'readonly' : ''; ?> />
						<?php if ( ! empty( $this->suffix ) ) { ?>
							<span class="srfm-number-suffix" aria-hidden="true"><?php echo esc_html( $this->suffix ); ?></span>
						<?php } ?>
					</div>
				</div>
				<div class="srfm-error-wrap">
					<?php echo wp_kses_post( $this->error_msg_markup ); ?>
				</div>
			</div>
		<?php
		return ob_get_clean();
	}

}
