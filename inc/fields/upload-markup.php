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
		$id               = isset( $attributes['id'] ) ? Sureforms_Helper::get_string_value( $attributes['id'] ) : '';
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

		return '<div id="sureforms-upload-container" class="sureforms-upload-container main-container frontend-inputs-holder ' . esc_attr( $classname ) . '">
    <label class="sf-text-primary">' . esc_html( $label ) . ' 
        ' . ( $required && $label ? '<span style="color:red;"> *</span>' : '' ) . '
    </label>
    <input name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-upload' . $id ) ) . '" id="sureforms-upload-index-' . esc_attr( $id ) . '" value="" type="hidden" />
    <input name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-upload' . $id ) ) . '" class="sureforms-upload-field" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" type="file" hidden id="sureforms-upload-' . esc_attr( $id ) . '" 
    accept=".' . esc_attr( str_replace( ' ', ' .', $accepted_formats ) ) . '"
    />
    <input class="sureforms-upload-size" value="' . esc_attr( $file_size ) . '" type="hidden" />
    <div class="sureforms-upload-inner-div" style="border: 1px solid #d1d5db; border-radius:4px;">
        <label id="sureforms-upload-label" for="sureforms-upload-' . esc_attr( $id ) . '">
            <div id="sureforms-upload-title-' . esc_attr( $id ) . '" class="sureforms-upload-title">
                <i class="fa-solid fa-cloud-arrow-up sf-text-primary"></i>
                <span class="sf-text-primary">Click to choose the file</span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 1rem;">
                <div style="display: flex; flex-direction: column;">
                    <span class="sf-text-primary">Size Limit</span>
                    <span class="sf-text-primary"><strong>' . esc_html( $file_size ? $file_size . ' MB' : 'Not Defined' ) . '</strong></span>
                </div>
                <div style="display: flex; flex-direction: column;" class="sf-text-primary">
                    <span>Allowed Types</span>
                    <span><strong>' . esc_html( $allowed_formats ) . '</strong></span>
                </div>
            </div>
        </label>
    </div>
    ' . ( '' !== $help ? '<label class="sf-text-secondary sforms-helper-txt">' . esc_html( $help ) . '</label>' : '' ) . '
    <span class="srfm-upload-file-size-error" id="upload-field-error-' . esc_attr( $id ) . '" hidden style="color: red;">' . esc_html__( 'File Size Exceeded The Limit', 'sureforms' ) . '</span>
    <span style="display:none" class="error-message">' . esc_html( $error_msg ) . '</span>
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
		$id              = isset( $attributes['id'] ) ? Sureforms_Helper::get_string_value( $attributes['id'] ) : '';
		$required        = isset( $attributes['required'] ) ? $attributes['required'] : false;
		$file_size       = isset( $attributes['fileSizeLimit'] ) ? $attributes['fileSizeLimit'] : '';
		$allowed_formats = isset( $attributes['allowedFormats'] ) && is_array( $attributes['allowedFormats'] ) && 0 !== count( $attributes['allowedFormats'] ) ? implode(
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
		$label            = isset( $attributes['label'] ) ? $attributes['label'] : '';
		$help             = isset( $attributes['help'] ) ? $attributes['help'] : '';
		$error_msg        = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
		$classname        = isset( $attributes['className'] ) ? $attributes['className'] : '';

		return '<div id="sureforms-upload-container" class="sureforms-upload-container main-container sf-classic-inputs-holder frontend-inputs-holder ' . esc_attr( $classname ) . '">
        <div class="col-span-full">
            <label class="sf-classic-label-text">
                ' . esc_html( $label ) . ' 
                ' . ( $required && $label ? '<span style="color:red;"> *</span>' : '' ) . '
            </label>
            <div class="sureforms-upload-inner-div sf-classic-upload-div">
                <div class="text-center">
                    <div style="font-size:35px" class="text-center text-gray-300">
                        <i class="fa fa-cloud-upload" aria-hidden="true"></i>
                    </div>
                    <div class="mt-2 flex text-sm leading-6 text-gray-600">
                        <input class="sureforms-upload-size" value="' . esc_attr( $file_size ) . '" type="hidden" />
                        <label for="sureforms-upload-' . esc_attr( $id ) . '" class="sf-classic-upload-label">
                            <span>Click to upload the file</span>
                            <input id="sureforms-upload-' . esc_attr( $id ) . '" name="' . esc_attr( str_replace( ' ', '_', $label . 'SF-upload' . $id ) ) . '" type="file" aria-required="' . esc_attr( $required ? 'true' : 'false' ) . '" class="sureforms-upload-field sr-only" accept=".' . esc_attr( str_replace( ' ', ' .', $accepted_formats ) ) . '">
                        </label>
                    </div>
                    <p class="mb-1 text-xs leading-5 text-gray-600"> <span class="font-semibold">' . ( 'All types' !== $allowed_formats ? esc_html( $allowed_formats ) . $many_types_symbol : __( 'All types', 'sureforms' ) ) . '</span> up to ' . esc_attr( $file_size ? $file_size . ' MB' : 'Not Defined' ) . '</p>
                </div>
            </div>
        </div>
        ' . ( '' !== $help ? '<p class="sforms-helper-txt" id="text-description">' . esc_html( $help ) . '</p>' : '' ) . '
        <p style="display:none" class="error-message">' . esc_html( $error_msg ) . '</p>
        <span class="srfm-upload-file-size-error error-message" id="upload-field-error-' . esc_attr( $id ) . '" hidden >' . esc_html__( 'File Size Exceeded The Limit', 'sureforms' ) . '</span>
        <div style="display:none" id="sureforms-upload-field-result-' . esc_attr( $id ) . '" class="sf-classic-upload-result">
        </div>
    </div>';
	}

}
