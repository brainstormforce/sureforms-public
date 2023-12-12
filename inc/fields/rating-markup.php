<?php
/**
 * Sureforms Rating Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Sureforms_Helper;

/**
 * Sureforms Rating Markup Class.
 *
 * @since 0.0.1
 */
class Rating_Markup extends Base {
	use Get_Instance;


	/**
	 * Render the sureforms rating classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function classic_styling( $attributes ) {
		$required     = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$field_width  = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
		$label        = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help         = isset( $attributes['ratingBoxHelpText'] ) ? $attributes['ratingBoxHelpText'] : '';
		$width        = isset( $attributes['width'] ) ? $attributes['width'] : '';
		$icon_color   = isset( $attributes['iconColor'] ) ? strval( $attributes['iconColor'] ) : '';
		$show_numbers = isset( $attributes['showNumbers'] ) ? $attributes['showNumbers'] : '';
		$icon_shape   = isset( $attributes['iconShape'] ) ? $attributes['iconShape'] : '';
		$max_value    = isset( $attributes['maxValue'] ) ? $attributes['maxValue'] : '';
		$error_msg    = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$classname    = isset( $attributes['className'] ) ? ' ' . $attributes['className'] : '';
		$block_id     = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';
		$slug        = 'rating';
        $block_width = $field_width ? ' srfm-block-width-' . str_replace(".","-",$field_width) : '';

        $aria_require_attr = $required ? 'true' : 'false';

        ob_start(); ?>
		    <div class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $slug ); ?>-block srfm-block-width-<?php echo esc_attr( $block_width ); ?><?php echo esc_attr( $classname ) ?>">
                <?php echo wp_kses_post( Sureforms_Helper::GenerateCommonFormMarkup( 'label', $label, $slug, $block_id, $required ) ); ?>
                <div class="srfm-block-wrap">
                    <input type="hidden" name="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>" aria-required="<?php echo esc_attr( $aria_require_attr ); ?>"/>
                    <ul>
                        <?php for ( $i = 0; $i < $max_value; $i++ ) { ?>
                            <li>
                                <span class=""d ata-value="<?php echo esc_attr( strval( $i + 1 ) ); ?>"><?php echo Sureforms_Helper::fetch_svg(esc_attr( $icon_shape ), 'srfm-'. esc_attr( $icon_shape ) .'-icon'); ?></span>
                                <span class="srfm-<?php echo esc_attr( $slug ); ?>-number"><?php echo esc_html( strval( $show_numbers ? $i + 1 : '' ) ); ?></span>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
                <?php echo wp_kses_post( Sureforms_Helper::GenerateCommonFormMarkup('help', '', '', '', '', $help ) ); ?>
                <?php echo wp_kses_post(Sureforms_Helper::GenerateCommonFormMarkup('error', '', '', '', $required, '', $error_msg )); ?>
            </div>
        <?php
		return ob_get_clean();

	}

}
