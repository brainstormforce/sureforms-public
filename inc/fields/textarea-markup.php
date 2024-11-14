<?php
/**
 * Sureforms Textarea Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sureforms Textarea Markup Class.
 *
 * @since 0.0.1
 */
class Textarea_Markup extends Base {
	/**
	 * Maximum length of text allowed for the textarea.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $max_length;

	/**
	 * HTML attribute string for the maximum length.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $max_length_attr;

	/**
	 * HTML string for displaying the maximum length in the UI.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $max_length_html;

	/**
	 * Number of rows for the textarea.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $rows;

	/**
	 * HTML attribute string for the number of rows.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $rows_attr;

	/**
	 * Initialize the properties based on block attributes.
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @since 0.0.2
	 */
	public function __construct( $attributes ) {
		$this->set_properties( $attributes );
		$this->set_input_label( __( 'Textarea', 'sureforms' ) );
		$this->set_error_msg( $attributes, 'srfm_textarea_block_required_text' );
		$this->slug       = 'textarea';
		$this->max_length = $attributes['maxLength'] ?? '';
		$this->rows       = $attributes['rows'] ?? '';
		// html attributes.
		$this->max_length_attr = $this->max_length ? ' maxLength="' . $this->max_length . '" ' : '';
		$this->rows_attr       = $this->rows ? ' rows="' . $this->rows . '" ' : '';
		$this->max_length_html = '' !== $this->max_length ? '0/' . $this->max_length : '';
		$this->set_unique_slug();
		$this->set_field_name( $this->unique_slug );
		$this->set_markup_properties( $this->input_label );
		$this->set_aria_described_by();
		$this->set_label_as_placeholder( $this->input_label );
	}

	/**
	 * Render the sureforms textarea classic styling
	 *
	 * @since 0.0.2
	 * @return string|bool
	 */
	public function markup() {
		ob_start(); ?>
		<div data-block-id="<?php echo esc_attr( $this->block_id ); ?>" class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $this->slug ); ?>-block srf-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id ); ?>-block<?php echo esc_attr( $this->block_width ); ?><?php echo esc_attr( $this->class_name ); ?> <?php echo esc_attr( $this->conditional_class ); ?>">
			<?php echo wp_kses_post( $this->label_markup ); ?>
			<?php echo wp_kses_post( $this->help_markup ); ?>
			<div class="srfm-block-wrap">
				<textarea class="srfm-input-common srfm-input-<?php echo esc_attr( $this->slug ); ?>" name="<?php echo esc_attr( $this->field_name ); ?>" id="<?php echo esc_attr( $this->unique_slug ); ?>"
				<?php echo ! empty( $this->aria_described_by ) ? "aria-describedby='" . esc_attr( trim( $this->aria_described_by ) ) . "'" : ''; ?>
				data-required="<?php echo esc_attr( $this->data_require_attr ); ?>" <?php echo wp_kses_post( $this->max_length_attr . '' . $this->rows_attr ); ?> <?php echo wp_kses_post( $this->placeholder_attr ); ?>><?php echo esc_html( $this->default ); ?></textarea>
			</div>
			<div class="srfm-error-wrap">
				<?php echo wp_kses_post( $this->error_msg_markup ); ?>
			</div>
		</div>

		<?php
		return ob_get_clean();
	}
}
