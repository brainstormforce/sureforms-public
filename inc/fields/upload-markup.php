<?php
/**
 * Sureforms Upload Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Sureforms_Helper;

/**
 * Sureforms Upload Markup Class.
 *
 * @since 0.0.1
 */
class Upload_Markup extends Base {
	use Get_Instance;

	/**
	 * Render the sureforms upload default styling block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function default_styling( $attributes ) {
		$block_id         = isset( $attributes['block_id'] ) ? Sureforms_Helper::get_string_value( $attributes['block_id'] ) : '';
		$required         = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$file_size        = isset( $attributes['fileSizeLimit'] ) ? $attributes['fileSizeLimit'] : '';
		$allowed_formats  = isset( $attributes['allowedFormats'] ) && is_array( $attributes['allowedFormats'] ) ? implode(
			', ',
			array_map(
				function( $obj ) {
					return $obj['value'];
				},
				$attributes['allowedFormats']
			)
		) . '...' : 'All types';
		$accepted_formats = str_replace( '...', '', $allowed_formats );
		$label            = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help             = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$error_msg        = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$classname        = isset( $attributes['className'] ) ? $attributes['className'] : '';

		return '<div id="srfm-upload-container" class="srfm-upload-container srfm-main-container srfm-frontend-inputs-holder ' . esc_attr( $classname ) . '">
    <label class="srfm-text-primary">' . esc_html( $label ) . ' 
        ' . ( $required && $label ? '<span style="color:red;"> *</span>' : '' ) . '
    </label>
    <input name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-upload' . $block_id ) ) . '" id="srfm-upload-index-' . esc_attr( $block_id ) . '" value="" type="hidden" />
    <input name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-upload' . $block_id ) ) . '" class="srfm-upload-field" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" type="file" hidden id="srfm-upload-' . esc_attr( $block_id ) . '" 
    accept=".' . esc_attr( str_replace( ' ', ' .', $accepted_formats ) ) . '"
    />
    <input class="srfm-upload-size" value="' . esc_attr( $file_size ) . '" type="hidden" />
    <div class="srfm-upload-inner-div" style="border: 1px solid #d1d5db; border-radius:4px;">
        <label id="srfm-upload-label" for="srfm-upload-' . esc_attr( $block_id ) . '">
            <div id="srfm-upload-title-' . esc_attr( $block_id ) . '" class="srfm-upload-title">
                <i class="fa-solid fa-cloud-arrow-up srfm-text-primary"></i>
                <span class="srfm-text-primary">Click to choose the file</span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 1rem;">
                <div style="display: flex; flex-direction: column;">
                    <span class="srfm-text-primary">Size Limit</span>
                    <span class="srfm-text-primary"><strong>' . esc_html( $file_size ? $file_size . ' MB' : 'Not Defined' ) . '</strong></span>
                </div>
                <div style="display: flex; flex-direction: column;" class="srfm-text-primary">
                    <span>Allowed Types</span>
                    <span><strong>' . esc_html( $allowed_formats ) . '</strong></span>
                </div>
            </div>
        </label>
    </div>
    ' . ( '' !== $help ? '<label class="srfm-text-secondary srfm-helper-txt">' . esc_html( $help ) . '</label>' : '' ) . '
    <span class="srfm-upload-file-size-error" id="srfm-upload-field-error-' . esc_attr( $block_id ) . '" hidden style="color: red;">' . esc_html__( 'File Size Exceeded The Limit', 'sureforms' ) . '</span>
    <span style="display:none" class="srfm-error-message">' . esc_html( $error_msg ) . '</span>
</div>';
	}

	/**
	 * Render the sureforms upload classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function classic_styling( $attributes ) {
		$block_id        = isset( $attributes['block_id'] ) ? Sureforms_Helper::get_string_value( $attributes['block_id'] ) : '';
		$required        = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$file_size       = isset( $attributes['fileSizeLimit'] ) ? $attributes['fileSizeLimit'] : '';
		$allowed_formats = isset( $attributes['allowedFormats'] ) && is_array( $attributes['allowedFormats'] ) ? implode(
			', ',
			array_map(
				function( $obj ) {
					return $obj['value'];
				},
				$attributes['allowedFormats']
			)
		) : 'All types';
		if ( is_array( $attributes['allowedFormats'] ) && 5 <= count( $attributes['allowedFormats'] ) ) {
			$many_types_symbol = '...';
		} else {
			$many_types_symbol = '';
		}
		$accepted_formats = str_replace( '...', '', $allowed_formats );
		$field_width      = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
		$label            = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help             = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$error_msg        = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$classname        = isset( $attributes['className'] ) ? $attributes['className'] : '';

		return '<div id="srfm-upload-container" class="srfm-upload-container srfm-main-container srfm-classic-inputs-holder srfm-frontend-inputs-holder ' . esc_attr( $classname ) . '"  style="width:calc(' . esc_attr( $field_width ) . '% - 20px);" >
        <div class="srfm-col-span-full">
            <label for="srfm-upload-' . esc_attr( $block_id ) . '" class="srfm-classic-label-text">
                ' . esc_html( $label ) . ' 
                ' . ( $required && $label ? '<span style="color:red;"> *</span>' : '' ) . '
            </label>
            <div class="srfm-upload-inner-div srfm-classic-upload-div">
                <div class="srfm-text-center">
                    <div style="font-size:35px" class="srfm-text-center srfm-text-gray-300">
                        <i class="fa fa-cloud-upload" aria-hidden="true"></i>
                    </div>
                    <div class="srfm-mt-2 srfm-flex srfm-text-sm srfm-leading-6 srfm-text-gray-600">
                        <input class="srfm-upload-size" value="' . esc_attr( $file_size ) . '" type="hidden" />
                        <label for="srfm-upload-' . esc_attr( $block_id ) . '" class="srfm-classic-upload-label">
                            <span>Click to upload the file</span>
                            <input id="srfm-upload-' . esc_attr( $block_id ) . '" name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-upload' . $block_id ) ) . '" type="file" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" class="srfm-upload-field srfm-sr-only" accept=".' . esc_attr( str_replace( ' ', ' .', $accepted_formats ) ) . '">
                        </label>
                    </div>
                    <p class="srfm-m-1 srfm-text-xs srfm-leading-5 srfm-text-gray-600"> <span class="srfm-font-semibold">' . ( 'All types' !== $allowed_formats ? esc_html( $allowed_formats ) . $many_types_symbol : __( 'All types', 'sureforms' ) ) . '</span> up to ' . esc_attr( $file_size ? $file_size . ' MB' : 'Not Defined' ) . '</p>
                </div>
            </div>
        </div>
        ' . ( '' !== $help ? '<p class="srfm-helper-txt" id="srfm-text-description">' . esc_html( $help ) . '</p>' : '' ) . '
        <p style="display:none" class="srfm-error-message">' . esc_html( $error_msg ) . '</p>
        <span class="srfm-upload-file-size-error srfm-error-message" id="srfm-upload-field-error-' . esc_attr( $block_id ) . '" hidden >' . esc_html__( 'File Size Exceeded The Limit', 'sureforms' ) . '</span>
        <div style="display:none" id="srfm-upload-field-result-' . esc_attr( $block_id ) . '" class="srfm-classic-upload-result">
        </div>
    </div>';
	}

}
