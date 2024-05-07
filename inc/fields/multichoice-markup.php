<?php
/**
 * Sureforms Multichoice Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc\Fields;

use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * SureForms Multichoice Markup Class.
 *
 * @since 0.0.1
 */
class Multichoice_Markup extends Base {

	/**
	 * Flag indicating if only a single selection is allowed.
	 *
	 * @var bool
	 * @since 0.0.2
	 */
	protected $single_selection;

	/**
	 * Width of the choice input field.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $choice_width;

	/**
	 * HTML attribute string for the choice width.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $choice_width_attr;

	/**
	 * HTML attribute string for the input type (radio or checkbox).
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $type_attr;

	/**
	 * HTML attribute string for the name attribute of the input field.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $name_attr;

	/**
	 * Initialize the properties based on block attributes.
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @since 0.0.2
	 */
	public function __construct( $attributes ) {
		$this->set_properties( $attributes );
		$this->set_input_label( __( 'Multi Choice', 'sureforms' ) );
		$this->set_error_msg( $attributes, 'srfm_multi_choice_block_required_text' );
		$this->slug              = 'multi-choice';
		$this->single_selection  = isset( $attributes['singleSelection'] ) ? $attributes['singleSelection'] : false;
		$this->choice_width      = isset( $attributes['choiceWidth'] ) ? $attributes['choiceWidth'] : '';
		$this->type_attr         = $this->single_selection ? 'radio' : 'checkbox';
		$this->name_attr         = $this->single_selection ? 'name="srfm-input-' . esc_attr( $this->slug ) . '-' . esc_attr( $this->block_id ) . '"' : '';
		$this->choice_width_attr = $this->choice_width ? 'srfm-choice-width-' . str_replace( '.', '-', $this->choice_width ) : '';
		$this->set_markup_properties();
	}

	/**
	 * Render the sureforms Multichoice classic styling
	 *
	 * @since 0.0.2
	 * @return string|boolean
	 */
	public function markup() {
		$check_svg = Helper::fetch_svg( 'check-circle-solid', 'srfm-' . $this->slug . '-icon' );
		ob_start(); ?>
		<div data-block-id="<?php echo esc_attr( $this->block_id ); ?>" class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $this->type_attr ); ?>-mode srfm-<?php echo esc_attr( $this->slug ); ?>-block srf-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id ); ?>-block<?php echo wp_kses_post( $this->block_width ); ?><?php echo esc_attr( $this->class_name ); ?> <?php echo esc_attr( $this->conditional_class ); ?>">
		<input class="srfm-input-<?php echo esc_attr( $this->slug ); ?>-hidden" aria-required="<?php echo esc_attr( $this->aria_require_attr ); ?>" name="srfm-input-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id ); ?><?php echo esc_attr( $this->field_name ); ?>" type="hidden" value=""/>
		<?php echo wp_kses_post( $this->label_markup ); ?>
			<?php if ( is_array( $this->options ) ) { ?>
				<div class="srfm-block-wrap <?php echo esc_attr( $this->choice_width_attr ); ?>">
					<?php foreach ( $this->options as $i => $option ) { ?>
						<label class="srfm-<?php echo esc_attr( $this->slug ); ?>-single">
							<input type="<?php echo esc_attr( $this->type_attr ); ?>" id="srfm-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id . '-' . $i ); ?>" class="srfm-input-<?php echo esc_attr( $this->slug ); ?>-single" <?php echo wp_kses_post( $this->name_attr ); ?>/>
							<div class="srfm-block-content-wrap">
								<?php echo $check_svg; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Ignored to render svg ?>
								<p><?php echo isset( $option['optionTitle'] ) ? esc_html( $option['optionTitle'] ) : ''; ?></p>
							</div>
						</label>
					<?php } ?>
				</div>
			<?php } ?>
		<?php echo wp_kses_post( $this->help_markup ); ?>
		<?php echo wp_kses_post( $this->error_msg_markup ); ?>
		</div>
		<?php
		return ob_get_clean();

	}
}
