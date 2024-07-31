<?php
/**
 * Sureforms Dropdown Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc\Fields;

require SRFM_DIR . 'modules/gutenberg/classes/class-spec-gb-helper.php';
use Spec_Gb_Helper;
use SRFM\Inc\Helper;

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
	 * Stores the multi select attribute value.
	 *
	 * @var string
	 * @since x.x.x
	 */
	protected $multi_select_attr;

	/**
	 * Stores the search attribute value.
	 *
	 * @var string
	 * @since x.x.x
	 */
	protected $search_attr;

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
		$this->slug              = 'dropdown';
		$this->multi_select_attr = ! empty( $attributes['multiSelect'] ) ? 'true' : 'false';
		$this->search_attr       = ! empty( $attributes['searchable'] ) ? 'true' : 'false';
		$this->placeholder       = $this->label ? $this->label : __( 'Select an option', 'sureforms' );
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
					<input class="srfm-input-<?php echo esc_attr( $this->slug ); ?>-hidden" aria-required="<?php echo esc_attr( $this->aria_require_attr ); ?>" name="srfm-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id ); ?><?php echo esc_attr( $this->field_name ); ?>" type="hidden" value=""/>
					<legend>
						<?php echo wp_kses_post( $this->label_markup ); ?>
						<?php echo wp_kses_post( $this->help_markup ); ?>
					</legend>
					<div class="srfm-block-wrap srfm-dropdown-common-wrap">
					<?php
					if ( is_array( $this->options ) ) {
						?>
					<select class="srfm-dropdown-common srfm-<?php echo esc_attr( $this->slug ); ?>-input"
						<?php echo ! empty( $this->aria_described_by ) ? "aria-describedby='" . esc_attr( trim( $this->aria_described_by ) ) . "'" : ''; ?>
				aria-required="<?php echo esc_attr( $this->aria_require_attr ); ?>" name="srfm-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id ); ?><?php echo esc_attr( $this->field_name ); ?>" data-multiple="<?php echo esc_attr( $this->multi_select_attr ); ?>" data-searchable="<?php echo esc_attr( $this->search_attr ); ?>" tabindex="0" aria-hidden="true">
					<option class="srfm-dropdown-placeholder" value="" disabled selected><?php echo esc_html( $this->placeholder ); ?></option>
						<?php foreach ( $this->options as $option ) { ?>
							<?php
								$icon_svg         = Spec_Gb_Helper::render_svg_html( isset( $option['icon'] ) ? $option['icon'] : '', true );
								$escaped_icon_svg = htmlspecialchars( Helper::get_string_value( $icon_svg ), ENT_QUOTES, 'UTF-8' );
							?>
								<option value="<?php echo esc_html( $option['label'] ); ?>" data-icon="<?php echo ! empty( $escaped_icon_svg ) ? esc_attr( $escaped_icon_svg ) : ''; ?>"><?php echo esc_html( $option['label'] ); ?></option>
								<?php
						}
						?>
					</select>
					<?php } ?>
					</div>
					<div class="srfm-error-wrap">
						<?php echo wp_kses_post( $this->error_msg_markup ); ?>
					</div>
				</fieldset>
			</div>
		<?php
		return ob_get_clean();
	}
}
