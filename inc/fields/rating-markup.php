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
		$classname    = isset( $attributes['className'] ) ? $attributes['className'] : '';
		$block_id     = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';
		$slug        = 'rating';

		$inline_style = '';

		// Append Dynamic styles here.
		$inline_style .= $field_width ? 'width:' . $field_width . '%;' : '';
		$style         = $inline_style ? 'style="' . $inline_style . '"' : '';


        $svg     = '';
		switch ( $icon_shape ) {
			case 'star':
				$svg .= '<svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="srfm-h-8 srfm-w-8"
                            >
                            <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                        </svg>';
				break;
			case 'heart':
				$svg .= '<svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="srfm-h-8 srfm-w-8" >
                            <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                        </svg>';
				break;
			case 'smiley':
				$svg .= '<svg 
                                version="1.1" 
                                xmlns="http://www.w3.org/2000/svg" 
                                xmlns:xlink="http://www.w3.org/1999/xlink" 
                                fill="none"
                                stroke-width="1.5"
                                stroke="currentColor"
                                class="srfm-h-8 srfm-w-8"
                                x="0px" y="0px" 
                                viewBox="0 0 122.88 122.88" 
                                xml:space="preserve">
                                <g>
                                    <path style="fill-rule:evenodd;clip-rule:evenodd;" d="M45.54,2.11c32.77-8.78,66.45,10.67,75.23,43.43c8.78,32.77-10.67,66.45-43.43,75.23 c-32.77,8.78-66.45-10.67-75.23-43.43C-6.67,44.57,12.77,10.89,45.54,2.11L45.54,2.11z"/>
                                    <path style="fill-rule:evenodd;clip-rule:evenodd;fill:#141518;" d="M45.78,31.71c4.3,0,7.78,6.6,7.78,14.75c0,8.15-3.48,14.75-7.78,14.75S38,54.61,38,46.46 C38,38.32,41.48,31.71,45.78,31.71L45.78,31.71z M22.43,80.59c0.42-7.93,4.53-11.46,11.83-11.76l-5.96,5.93 c16.69,21.63,51.01,21.16,65.78-0.04l-5.47-5.44c7.3,0.30,11.4,3.84,11.83,11.76l-3.96-3.93c-16.54,28.07-51.56,29.07-70.70,0.15 L22.43,80.59L22.43,80.59z M77.1,31.71c4.3,0,7.78,6.6,7.78,14.75c0,8.15-3.49,14.75-7.78,14.75s-7.78-6.6-7.78-14.75 C69.31,38.32,72.8,31.71,77.1,31.71L77.1,31.71z"/>
                                </g>
                            </svg>';
				break;
			default:
				$svg .= '<svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="srfm-h-8 srfm-w-8"
                            >
                            <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                        </svg>';
				break;
		}

        ob_start(); ?>
		<div class="srfm-block srfm-<?php echo esc_attr( $slug ); ?>-block <?php echo esc_attr( $classname ); ?>" <?php echo wp_kses_post( $style ); ?>>
            <?php echo wp_kses_post( Sureforms_Helper::GenerateCommonFormMarkup( 'label', $label, $slug, $block_id, $required ) ); ?>
            <input type="hidden" name="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>" aria-required="<?php echo esc_attr( $aria_require_attr ); ?>"/>
            <ul>
                <?php for ( $i = 0; $i < $max_value; $i++ ) { ?>
                    <li>
                        <span class="srfm-number"><?php echo esc_html( strval( $show_numbers ? $i + 1 : '' ) ); ?></span>
                        <span data-value="<?php echo esc_attr( strval( $i + 1 ) ); ?>"><?php echo $svg; ?></span>
                    </li>
                <?php } ?>
            </ul>
        <?php
		return ob_get_clean();

	}

}
