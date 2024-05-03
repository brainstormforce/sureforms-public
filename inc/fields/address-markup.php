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
	 * @return string|boolean
	 */
	public function markup( $content = '' ) {
		ob_start(); ?>
			<div data-block-id="<?php echo esc_attr( $this->block_id ); ?>" class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $this->slug ); ?>-block srf-<?php echo esc_attr( $this->slug ); ?>-<?php echo esc_attr( $this->block_id ); ?>-block<?php echo esc_attr( $this->block_width ); ?><?php echo esc_attr( $this->class_name ); ?> <?php echo esc_attr( $this->conditional_class ); ?>">
				<div class="srfm-address-label-ctn">
					<?php echo wp_kses_post( $this->label_markup ); ?>
				</div>
				<div class="srfm-block-wrap">
					<?php
                        // phpcs:ignore
                        echo $content;
                        // phpcs:ignoreEnd
					?>
				</div>
				<div class="srfm-address-help-ctn">
					<?php echo wp_kses_post( $this->help_markup ); ?>
				</div>
			</div>
		<?php

		return ob_get_clean();

	}

}
