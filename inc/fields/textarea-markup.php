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
	 * Number of columns for the textarea.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $cols;

	/**
	 * HTML attribute string for the number of columns.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $cols_attr;

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
		$this->max_length = isset( $attributes['maxLength'] ) ? $attributes['maxLength'] : '';
		$this->help       = isset( $attributes['textAreaHelpText'] ) ? $attributes['textAreaHelpText'] : '';
		$this->rows       = isset( $attributes['rows'] ) ? $attributes['rows'] : '';
		$this->cols       = isset( $attributes['cols'] ) ? $attributes['cols'] : '';
		// html attributes.
		$this->max_length_attr = $this->max_length ? ' maxLength="' . $this->max_length . '" ' : '';
		$this->rows_attr       = $this->rows ? ' rows="' . $this->rows . '" ' : '';
		$this->cols_attr       = $this->cols ? ' cols="' . $this->cols . '" ' : '';
		$this->max_length_html = '' !== $this->max_length ? '0/' . $this->max_length : '';
		$this->set_unique_slug();
		$this->set_field_name( $this->unique_slug );
		$this->set_markup_properties( $this->input_label );
	}

	/**
	 * Render the sureforms textarea classic styling
	 *
	 * @since 0.0.2
	 * @return string|boolean
	 */
	public function markup() {
		ob_start(); ?>
		<div data-block-id="<?php echo esc_attr( $this->block_id ); ?>" class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $this->slug ); ?>-block srf-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id ); ?>-block<?php echo esc_attr( $this->block_width ); ?><?php echo esc_attr( $this->class_name ); ?> <?php echo esc_attr( $this->conditional_class ); ?>">
			<?php echo wp_kses_post( $this->label_markup ); ?>
			<div class="srfm-block-wrap">
				<?php if ( $this->max_length_html ) { ?>
					<div class="srfm-text-counter"><?php echo esc_html( $this->max_length_html ); ?></div>
				<?php } ?>
				<textarea class="srfm-input-common srfm-input-<?php echo esc_attr( $this->slug ); ?>" name="<?php echo esc_attr( $this->field_name ); ?>" id="<?php echo esc_attr( $this->unique_slug ); ?>" aria-required="<?php echo esc_attr( $this->aria_require_attr ); ?>" <?php echo wp_kses_post( $this->placeholder_attr . '' . $this->max_length_attr . '' . $this->cols_attr . '' . $this->rows_attr ); ?> ><?php echo esc_html( $this->default ); ?></textarea>
			</div>
			<?php echo wp_kses_post( $this->help_markup ); ?>
			<?php echo wp_kses_post( $this->error_msg_markup ); ?>
		</div>

		<?php
		return ob_get_clean();

	}
}
