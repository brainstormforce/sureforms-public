<?php
/**
 * PHP render form upload Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Upload;

use SureForms\Inc\Blocks\Base;
use SureForms\Inc\Sureforms_Helper;

/**
 * Address Block.
 */
class Block extends Base {
	/**
	 * Render the block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param string       $content Post content.
	 *
	 * @return string|boolean
	 */
	public function render( $attributes, $content = '' ) {
		$sureforms_helper_instance = new Sureforms_Helper();

		if ( ! empty( $attributes ) ) {
			$id               = isset( $attributes['id'] ) ? $sureforms_helper_instance->get_string_value( $attributes['id'] ) : '';
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
			ob_start(); ?>
			<!-- <div id="sureforms-upload-container" class="sureforms-upload-container main-container frontend-inputs-holder <?php echo esc_attr( $classname ); ?>">
				<label class="sf-text-primary"><?php echo esc_html( $label ); ?> 
					<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
				</label>
				<input name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-upload' . $id ) ); ?>" id="sureforms-upload-index-<?php echo esc_attr( $id ); ?>" value="" type="hidden" />
				<input name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-upload' . $id ) ); ?>" class="sureforms-upload-field" area-required=<?php echo esc_attr( $required ? 'true' : 'false' ); ?> type="file" hidden id="sureforms-upload-<?php echo esc_attr( $id ); ?>" 
				accept=".<?php echo esc_attr( str_replace( ' ', ' .', $accepted_formats ) ); ?>"
				/>
				<input class="sureforms-upload-size" value="<?php echo esc_attr( $file_size ); ?>" type="hidden" />
				<div class="sureforms-upload-inner-div" style="border: 1px solid #d1d5db; border-radius:4px;">
					<label id="sureforms-upload-label" for="sureforms-upload-<?php echo esc_attr( $id ); ?>">
						<div id="sureforms-upload-title-<?php echo esc_attr( $id ); ?>" class="sureforms-upload-title">
							<i class="fa-solid fa-cloud-arrow-up sf-text-primary"></i>
							<span class="sf-text-primary">Click to choose the file</span>
						</div>
						<div style="display: flex; justify-content: space-between; padding: 1rem;">
							<div style="display: flex; flex-direction: column;">
								<span class="sf-text-primary">Size Limit</span>
								<span class="sf-text-primary"><strong><?php echo esc_attr( $file_size ? $file_size . ' MB' : 'Not Defined' ); ?></strong></span>
							</div>
							<div style="display: flex; flex-direction: column;" class="sf-text-primary">
								<span>Allowed Types</span>
								<span><strong><?php echo esc_attr( $allowed_formats ); ?></strong></span>
							</div>
						</div>
					</label>
				</div>
				<?php echo '' !== $help ? '<label class="sf-text-secondary sforms-helper-txt">' . esc_html( $help ) . '</label>' : ''; ?>
				<span id="upload-field-error-<?php echo esc_attr( $id ); ?>" hidden style="color: red;">File Size Exceeded The Limit</span>
				<span style="display:none" class="error-message"><?php echo esc_html( $error_msg ); ?></span>
			</div> -->
			<div id="sureforms-upload-container" class="sureforms-upload-container main-container sf-classic-inputs-holder frontend-inputs-holder <?php echo esc_attr( $classname ); ?>">
				<div class="col-span-full">
					<label class="sf-classic-label-text">
						<?php echo esc_html( $label ); ?> 
						<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
					</label>
					<div class="sureforms-upload-inner-div sf-classic-upload-div">
						<div class="text-center">
							<div style="font-size:35px" class="text-center text-gray-300">
								<i class="fa fa-cloud-upload" aria-hidden="true"></i>
							</div>
							<div class="mt-2 flex text-sm leading-6 text-gray-600">
								<input class="sureforms-upload-size" value="<?php echo esc_attr( $file_size ); ?>" type="hidden" />
								<label for="sureforms-upload-<?php echo esc_attr( $id ); ?>" class="sf-classic-upload-label">
									<span>Click to upload the file</span>
									<input id="sureforms-upload-<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-upload' . $id ) ); ?>" type="file" area-required=<?php echo esc_attr( $required ? 'true' : 'false' ); ?> class="sureforms-upload-field sr-only" accept=".<?php echo esc_attr( str_replace( ' ', ' .', $accepted_formats ) ); ?>">
								</label>
							</div>
							<p class="mb-1 text-xs leading-5 text-gray-600"><?php echo esc_attr( $allowed_formats ); ?> up to <?php echo esc_attr( $file_size ? $file_size . ' MB' : 'Not Defined' ); ?></p>
						</div>
					</div>
				</div>
				<span id="upload-field-error-<?php echo esc_attr( $id ); ?>" hidden style="color: red;">File Size Exceeded The Limit</span>
				<p style="display:none" class="error-message"><?php echo esc_html( $error_msg ); ?></p>
				<div style="display:none" id="sureforms-upload-field-result-<?php echo esc_attr( $id ); ?>"class="sf-classic-upload-result">
				</div>
			</div>
			<?php
		}
			return ob_get_clean();
	}
}
