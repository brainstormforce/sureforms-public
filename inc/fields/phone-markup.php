<?php
/**
 * Sureforms Phone Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sureforms_Phone_Markup Class.
 *
 * @since 0.0.1
 */
class Phone_Markup extends Base {
	/**
	 * Stores the boolean string indicating if the country should be automatically determined.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $auto_country;

	/**
	 * Initialize the properties based on block attributes.
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @since 0.0.2
	 */
	public function __construct( $attributes ) {
		$this->set_properties( $attributes );
		$this->set_input_label( __( 'Phone', 'sureforms' ) );
		$this->set_error_msg( $attributes, 'srfm_phone_block_required_text' );
		$this->set_duplicate_msg( $attributes, 'srfm_phone_block_unique_text' );
		$this->slug         = 'phone';
		$this->auto_country = $attributes['autoCountry'] ?? '';
		$this->set_unique_slug();
		$this->set_field_name( $this->unique_slug );
		$this->set_markup_properties( $this->input_label, true );
		$this->set_aria_described_by();
		$this->set_label_as_placeholder( $this->input_label );
	}

	/**
	 * Render the sureforms phone classic styling
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
				<input type="tel" class="srfm-input-common srfm-input-<?php echo esc_attr( $this->slug ); ?>" name="<?php echo esc_attr( $this->field_name ); ?>" id="<?php echo esc_attr( $this->unique_slug ); ?>"
				<?php echo ! empty( $this->aria_described_by ) ? "aria-describedby='" . esc_attr( trim( $this->aria_described_by ) ) . "'" : ''; ?>
				data-required="<?php echo esc_attr( $this->data_require_attr ); ?>" auto-country="<?php echo esc_attr( $this->auto_country ? 'true' : 'false' ); ?>" value="<?php echo esc_attr( $this->default ); ?>" <?php echo wp_kses_post( $this->placeholder_attr ); ?> data-unique="<?php echo esc_attr( $this->aria_unique ); ?>">
			</div>
			<div class="srfm-error-wrap">
				<?php echo wp_kses_post( $this->duplicate_msg_markup ); ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
