<?php
/**
 * Sureforms Address Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sureforms Address Markup Class.
 *
 * @since 0.0.1
 */
class Address_Markup extends Base {
	/**
	 * Initialize the properties based on block attributes.
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @since 0.0.2
	 */
	public function __construct( $attributes ) {
		$this->set_properties( $attributes );
		$this->set_input_label( __( 'Address', 'sureforms' ) );
		$this->slug = 'address';
		$this->set_markup_properties();
	}

	/**
	 * Render the sureforms address classic styling
	 *
	 * @param string $content inner block content.
	 * @since 0.0.2
	 * @return string|bool
	 */
	public function markup( $content = '' ) {
		/**
		 * Filter extra CSS classes for the address field wrapper.
		 *
		 * @param array<string> $extra_classes Extra CSS classes.
		 * @param array<mixed>  $attributes    Block attributes.
		 * @since 2.8.0
		 */
		$extra_classes = apply_filters( 'srfm_address_field_classes', [], $this->attributes );

		$this->class_name = $this->get_field_classes( $extra_classes );

		/**
		 * Filter additional HTML attributes for the address block wrapper div.
		 *
		 * IMPORTANT: Callbacks MUST escape all values with esc_attr() before
		 * returning. The output is echoed directly into a <div> tag.
		 *
		 * @param string       $extra_attrs Additional HTML attributes string (pre-escaped).
		 * @param array<mixed> $attributes  Block attributes.
		 * @since 2.8.0
		 */
		$extra_attrs = apply_filters( 'srfm_address_block_attributes', '', $this->attributes );

		ob_start(); ?>
			<div data-block-id="<?php echo esc_attr( $this->block_id ); ?>" class="<?php echo esc_attr( $this->class_name ); ?>" data-slug="<?php echo esc_attr( $this->block_slug ); ?>"
			<?php
				echo $extra_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by the filter callback.
			?>
			>
				<fieldset>
					<legend class="srfm-block-legend">
						<?php echo wp_kses_post( $this->label_markup ); ?>
						<?php echo wp_kses_post( $this->help_markup ); ?>
					</legend>
					<?php
					/**
					 * Fires after the address field legend, before the inner block content.
					 * Used by pro to render the autocomplete search input.
					 *
					 * @param array<mixed> $attributes Block attributes.
					 * @param string       $block_id   Block ID.
					 * @since 2.8.0
					 */
					do_action( 'srfm_address_before_fields', $this->attributes, $this->block_id );
					?>
					<div class="srfm-block-wrap">
					<?php
						// phpcs:ignore
						echo $content;
						// phpcs:ignoreEnd
					?>
					</div>
					<?php
					/**
					 * Fires after the inner block content, before the closing fieldset.
					 * Used by pro to render hidden fields and map container.
					 *
					 * @param array<mixed> $attributes Block attributes.
					 * @param string       $block_id   Block ID.
					 * @since 2.8.0
					 */
					do_action( 'srfm_address_after_fields', $this->attributes, $this->block_id );
					?>
				</fieldset>
			</div>
		<?php
		return ob_get_clean();
	}
}
