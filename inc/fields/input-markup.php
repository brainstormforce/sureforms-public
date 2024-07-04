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
	 * Initialize the properties based on block attributes.
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @since 0.0.2
	 */
	public function __construct( $attributes ) {
		$this->slug            = 'input';
		$this->max_text_length = isset( $attributes['textLength'] ) ? $attributes['textLength'] : '';
		$this->set_properties( $attributes );
		$this->set_input_label( __( 'Text Field', 'sureforms' ) );
		$this->set_error_msg( $attributes, 'srfm_input_block_required_text' );
		$this->set_duplicate_msg( $attributes, 'srfm_input_block_unique_text' );
		$this->set_unique_slug();
		$this->set_field_name( $this->unique_slug );
		$this->set_markup_properties( $this->input_label );
	}

	/**
	 * Render input markup
	 *
	 * @since 0.0.2
	 * @return string|boolean
	 */
	public function markup() {
		ob_start(); ?>
			<div data-block-id="<?php echo esc_attr( $this->block_id ); ?>" class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $this->slug ); ?>-block srf-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id ); ?>-block<?php echo esc_attr( $this->block_width ); ?><?php echo esc_attr( $this->class_name ); ?> <?php echo esc_attr( $this->conditional_class ); ?>">
			<?php echo wp_kses_post( $this->label_markup ); ?>
			<?php echo wp_kses_post( $this->help_markup ); ?>
				<div class="srfm-block-wrap">
					<input class="srfm-input-common srfm-input-<?php echo esc_attr( $this->slug ); ?>" type="text" name="<?php echo esc_attr( $this->field_name ); ?>" id="<?php echo esc_attr( $this->unique_slug ); ?>" aria-required="<?php echo esc_attr( strval( $this->aria_require_attr ) ); ?>" data-unique="<?php echo esc_attr( $this->aria_unique ); ?>" maxlength="<?php echo esc_attr( $this->max_text_length ); ?>" value="<?php echo esc_attr( $this->default ); ?>" />
				</div>
				<div class="srfm-error-wrap">
					<?php echo wp_kses_post( $this->duplicate_msg_markup ); ?>
				</div>
			</div>
		<?php
		return ob_get_clean();
	}
}
