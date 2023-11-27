<?php
/**
 * Sureforms Rating Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;

/**
 * Sureforms Rating Markup Class.
 *
 * @since 0.0.1
 */
class Rating_Markup extends Base {
	use Get_Instance;

	/**
	 * Render the sureforms rating default styling block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function default_styling( $attributes ) {
		$required     = isset( $attributes['required'] ) ? $attributes['required'] : false;
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

		$output  = '';
		$output .= '<div class="srfm-rating-container srfm-main-container srfm-frontend-inputs-holder ' . esc_attr( $classname ) . '">
        <label class="srfm-text-primary">' .
			esc_html( $label ) . ( $required && $label ? '<span style="color:red;"> *</span>' : '' ) . '
        </label>
        <input type="hidden" class="srfm-rating-random-id" aria-required="' .
			esc_attr( $required ? 'true' : 'false' ) . '" value="' .
			esc_attr( $block_id ) . '" />
        <input type="hidden" class="srfm-rating-icon-color-' .
			esc_attr( $block_id ) . '" value="' . esc_attr( $icon_color ) . '" />
        <div style="justify-content: ' . ( 'fullWidth' === $width ? 'space-between' : 'space-evenly' ) . '; display: flex; align-items: center;">';

		$icon = '';
		switch ( $icon_shape ) {
			case 'star':
				$icon .= '<i class="fa fa-star"></i>';
				break;
			case 'heart':
				$icon .= '<i class="fa fa-heart"></i>';
				break;
			case 'smiley':
				$icon .= '<i class="fa fa-smile"></i>';
				break;
			default:
				$icon .= '<i class="fa fa-star"></i>';
				break;
		}

		for ( $i = 0; $i < $max_value; $i++ ) {
			$output .= '
                <input style="display:contents;" name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '" class="srfm-rating-field" value="' .
					esc_attr( strval( $i + 1 ) ) . '" id="srfm-rating-' .
					esc_attr( $block_id . '-' . $i ) . '" type="radio" aria-required=' . esc_attr( $required && 0 === $i ? 'true' : 'false' ) . ' />
                <div style="display:flex; flex-direction:column; align-items: center;">
                    <label color-data="#ddd" style="color:#ddd; font-size:25px;" class="srfm-rating-' .
						esc_attr( $block_id ) . '" for="srfm-rating-' . esc_attr( $block_id . '-' . $i ) . '">' .
						wp_kses_post( $icon ) . '
                    </label>
                    <div>' . esc_html( strval( $show_numbers ? $i + 1 : '' ) ) . '</div>
                </div>';
		}

		$output .= '
            </div>' . ( '' !== $help ? '<label class="srfm-text-secondary srfm-helper-txt">' . esc_html( $help ) . '</label>' : '' ) . '
            <span style="display:none" class="srfm-error-message">' . esc_html( $error_msg ) . '</span>
        </div>';

		return $output;
	}

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

		$output  = '';
		$output .= '<div class="srfm-classic-rating-container srfm-main-container srfm-classic-inputs-holder ' . esc_attr( $classname ) . '"  style="width:calc(' . esc_attr( $field_width ) . '% - 20px);"	>';
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

		$output .= '
            <label class="srfm-classic-label-text">
                ' . esc_html( $label ) . ( $required && $label ? '<span style="color:red;"> *</span>' : '' ) . '
            </label>
            <input type="hidden" name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $block_id ) ) . '" value="" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" id="srfm-classic-rating-field-' . esc_attr( $block_id ) . '" class="srfm-rating-field-result"/>
            <ul class="srfm-classic-event srfm-rating-icon-wrapper srfm-mt-2 srfm-ml-0 srfm-mb-0 srfm-flex srfm-flex-wrap srfm-list-none srfm-gap-3 !srfm-p-0">';
		for ( $i = 0; $i < $max_value; $i++ ) {
			$output .= '
                <li class="srfm-flex srfm-items-center srfm-flex-col-reverse" >
                <span class="srfm-text-primary">' . esc_html( strval( $show_numbers ? $i + 1 : '' ) ) . '</span>
                    <span
                    class="srfm-text-primary srfm-rating-icon srfm-cursor-pointer" data-value="' . esc_attr( strval( $i + 1 ) ) . '">
                    ' . $svg . ' 
                    </span>
                </li>';
		}

		$output .= '
            </ul>' . ( '' !== $help ? '<p class="srfm-helper-txt" id="srfm-text-description">' . esc_html( $help ) . '</p>' : '' ) . '
            <p style="display:none" class="srfm-error-message">' . esc_html( $error_msg ) . '</p>
        </div>';

		return $output;

	}

}
