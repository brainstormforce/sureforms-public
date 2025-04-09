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
	 * @since 0.0.7
	 */
	protected $multi_select_attr;

	/**
	 * Stores the search attribute value.
	 *
	 * @var string
	 * @since 0.0.7
	 */
	protected $search_attr;

	/**
	 * Flag indicating if the value should be shown.
	 *
	 * @var bool
	 * @since 1.5.0
	 */
	protected $show_values;

	/**
	 * Initialize the properties based on block attributes.
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @since 0.0.2
	 */
	public function __construct( $attributes ) {
		$this->slug = 'dropdown';
		$this->set_properties( $attributes );
		$this->set_input_label( __( 'Dropdown', 'sureforms' ) );
		$this->set_error_msg( $attributes, 'srfm_dropdown_block_required_text' );
		$this->multi_select_attr = ! empty( $attributes['multiSelect'] ) ? 'true' : 'false';
		$this->search_attr       = ! empty( $attributes['searchable'] ) ? 'true' : 'false';
		$this->set_markup_properties();
		$this->set_aria_described_by();
		$this->set_label_as_placeholder( $this->input_label );
		$this->placeholder = ! empty( $this->placeholder_attr ) ? $this->label : __( 'Select an option', 'sureforms' );
		$this->show_values = apply_filters( 'srfm_show_options_values', false, $attributes['showValues'] ?? false );
	}

	/**
	 * Render the sureforms dropdown classic styling
	 *
	 * @since 0.0.2
	 * @return string|bool
	 */
	public function markup() {
		$this->class_name = $this->get_field_classes();

		ob_start(); ?>
			<div data-block-id="<?php echo esc_attr( $this->block_id ); ?>" class="<?php echo esc_attr( $this->class_name ); ?>">
				<fieldset>
					<input class="srfm-input-<?php echo esc_attr( $this->slug ); ?>-hidden" data-required="<?php echo esc_attr( $this->data_require_attr ); ?>" <?php echo wp_kses_post( $this->data_attribute_markup() ); ?> name="srfm-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id ); ?><?php echo esc_attr( $this->field_name ); ?>" type="hidden" value=""/>
					<legend class="srfm-block-legend">
						<?php echo wp_kses_post( $this->label_markup ); ?>
						<?php echo wp_kses_post( $this->help_markup ); ?>
					</legend>
					<div class="srfm-block-wrap srfm-dropdown-common-wrap">
					<?php
					if ( is_array( $this->options ) ) {
						?>
					<select
						class="srfm-dropdown-common srfm-<?php echo esc_attr( $this->slug ); ?>-input"
						<?php echo ! empty( $this->aria_described_by ) ? "aria-describedby='" . esc_attr( trim( $this->aria_described_by ) ) . "'" : ''; ?>
				data-required="<?php echo esc_attr( $this->data_require_attr ); ?>" <?php echo wp_kses_post( $this->data_attribute_markup() ); ?> name="srfm-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id ); ?><?php echo esc_attr( $this->field_name ); ?>" data-multiple="<?php echo esc_attr( $this->multi_select_attr ); ?>" data-searchable="<?php echo esc_attr( $this->search_attr ); ?>" tabindex="0" aria-hidden="true">
					<option class="srfm-dropdown-placeholder" value="" disabled selected><?php echo esc_html( $this->placeholder ); ?></option>
						<?php foreach ( $this->options as $option ) { ?>
							<?php
								$icon_svg         = Spec_Gb_Helper::render_svg_html( $option['icon'] ?? '', true );
								$escaped_icon_svg = htmlspecialchars( Helper::get_string_value( $icon_svg ), ENT_QUOTES, 'UTF-8' );
							?>
								<option value="<?php echo isset( $option['label'] ) ? esc_html( $option['label'] ) : ''; ?>" data-icon="<?php echo ! empty( $escaped_icon_svg ) ? esc_attr( $escaped_icon_svg ) : ''; ?>" <?php echo $this->show_values && isset( $option['value'] ) ? 'option-value="' . esc_attr( $option['value'] ) . '"' : ''; ?>><?php echo isset( $option['label'] ) ? esc_html( $option['label'] ) : ''; ?></option>
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
		$markup = ob_get_clean();

		return apply_filters(
			'srfm_block_field_markup',
			$markup,
			[
				'slug'       => $this->slug,
				'field_name' => $this->field_name,
				'is_editing' => $this->is_editing,
				'attributes' => $this->attributes,
			]
		);
	}

	/**
	 * Data attribute markup for min and max value
	 *
	 * @since 0.0.13
	 * @return string
	 */
	protected function data_attribute_markup() {
		$data_attr = '';
		if ( 'false' === $this->multi_select_attr ) {
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
