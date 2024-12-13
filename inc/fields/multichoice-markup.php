<?php
/**
 * Sureforms Multichoice Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc\Fields;

use Spec_Gb_Helper;
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
	 * SVG type for the input field.
	 *
	 * @var string
	 * @since 0.0.7
	 */
	protected $svg_type;

	/**
	 * HTML attribute string for the name attribute of the input field.
	 *
	 * @var string
	 * @since 0.0.2
	 */
	protected $name_attr;

	/**
	 * Flag indicating if the layout is vertical.
	 *
	 * @var bool
	 * @since 0.0.7
	 */
	protected $vertical_layout;

	/**
	 * Contains value if the option contains icon / image
	 *
	 * @var mixed
	 * @since 0.0.7
	 */
	protected $option_type;

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
		$this->single_selection  = $attributes['singleSelection'] ?? false;
		$this->choice_width      = $attributes['choiceWidth'] ?? '';
		$this->vertical_layout   = $attributes['verticalLayout'] ?? false;
		$this->option_type       = ! empty( $attributes['optionType'] ) && in_array( $attributes['optionType'], [ 'icon', 'image' ], true ) ? $attributes['optionType'] : 'icon';
		$this->type_attr         = $this->single_selection ? 'radio' : 'checkbox';
		$this->svg_type          = $this->single_selection ? 'circle' : 'square';
		$this->name_attr         = $this->single_selection ? 'name="srfm-input-' . esc_attr( $this->slug ) . '-' . esc_attr( $this->block_id ) . '"' : '';
		$this->choice_width_attr = $this->choice_width ? 'srfm-choice-width-' . str_replace( '.', '-', $this->choice_width ) : '';
		$this->set_markup_properties();
		$this->set_aria_described_by();
	}

	/**
	 * Render the sureforms Multichoice classic styling
	 *
	 * @since 0.0.2
	 * @return string|bool
	 */
	public function markup() {
		$check_svg     = Helper::fetch_svg( $this->svg_type . '-checked', 'srfm-' . $this->slug . '-icon', 'aria-hidden="true"' );
		$unchecked_svg = Helper::fetch_svg( $this->svg_type . '-unchecked', 'srfm-' . $this->slug . '-icon-unchecked', 'aria-hidden="true"' );

		ob_start(); ?>
		<div data-block-id="<?php echo esc_attr( $this->block_id ); ?>" class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $this->type_attr ); ?>-mode srfm-<?php echo esc_attr( $this->slug ); ?>-block srf-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id ); ?>-block<?php echo wp_kses_post( $this->block_width ); ?><?php echo esc_attr( $this->class_name ); ?> <?php echo esc_attr( $this->conditional_class ); ?>">
			<fieldset>
				<input class="srfm-input-<?php echo esc_attr( $this->slug ); ?>-hidden" data-required="<?php echo esc_attr( $this->data_require_attr ); ?>" <?php echo wp_kses_post( $this->data_attribute_markup() ); ?> name="srfm-input-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id ); ?><?php echo esc_attr( $this->field_name ); ?>" type="hidden" value=""/>
				<legend class="srfm-block-legend">
					<?php echo wp_kses_post( $this->label_markup ); ?>
				</legend>
				<?php echo wp_kses_post( $this->help_markup ); ?>
					<?php if ( is_array( $this->options ) ) { ?>
						<div class="srfm-block-wrap <?php echo esc_attr( $this->choice_width_attr ); ?> <?php echo $this->vertical_layout ? 'srfm-vertical-layout' : ''; ?>">
							<?php foreach ( $this->options as $i => $option ) { ?>
								<div class="srfm-<?php echo esc_attr( $this->slug ); ?>-single">
									<input type="<?php echo esc_attr( $this->type_attr ); ?>"
										id="srfm-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id . '-' . $i ); ?>"
										class="srfm-input-<?php echo esc_attr( $this->slug ); ?>-single" <?php echo wp_kses_post( $this->name_attr ); ?>
										<?php echo 0 === $i ? 'aria-describedby="' . ( ! empty( $this->aria_described_by ) ? esc_attr( trim( $this->aria_described_by ) ) : '' ) . '"' : ''; ?>
									/>
									<div class="srfm-block-content-wrap">
										<div class="srfm-option-container">
											<?php if ( 'icon' === $this->option_type && ! empty( $option['icon'] ) ) { ?>
											<span class="srfm-option-icon" aria-hidden="true">
												<?php Spec_Gb_Helper::render_svg_html( $option['icon'] ); ?>
											</span>
											<?php } elseif ( 'image' === $this->option_type && ! empty( $option['image'] ) ) { ?>
											<span class="srfm-option-image" aria-hidden="true">
												<img src="<?php echo esc_url( $option['image'] ); ?>"/>
											</span>
											<?php } ?>
											<label for="srfm-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id . '-' . $i ); ?>"><?php echo isset( $option['optionTitle'] ) ? esc_html( $option['optionTitle'] ) : ''; ?></label>
										</div>
										<div class="srfm-icon-container"><?php echo $check_svg . $unchecked_svg; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Ignored to render svg ?></div>
									</div>
								</div>
							<?php } ?>
						</div>
					<?php } ?>
				<div class="srfm-error-wrap"><?php echo wp_kses_post( $this->error_msg_markup ); ?></div>
			</fieldset>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Data attribute markup for min and max value
	 *
	 * @since 0.0.13
	 * @return string
	 */
	protected function data_attribute_markup() {
		$data_attr = '';
		if ( $this->single_selection ) {
			return '';
		}

		if ( $this->min_selection ) {
			$data_attr .= 'data-min-selection="' . esc_attr( $this->min_selection ) . '"';
		}
		if ( $this->max_selection ) {
			$data_attr .= 'data-max-selection="' . esc_attr( $this->max_selection ) . '"';
		}

		return $data_attr;
	}
}
