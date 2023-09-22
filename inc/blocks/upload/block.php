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
			<div id="sureforms-upload-container" class="sureforms-upload-container main-container frontend-inputs-holder <?php echo esc_attr( $classname ); ?>">
				<label class="text-primary"><?php echo esc_html( $label ); ?> 
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
							<i class="fa-solid fa-cloud-arrow-up text-primary"></i>
							<span class="text-primary">Click to choose the file</span>
						</div>
						<div style="display: flex; justify-content: space-between; padding: 1rem;">
							<div style="display: flex; flex-direction: column;">
								<span class="text-primary">Size Limit</span>
								<span class="text-primary"><strong><?php echo esc_attr( $file_size ? $file_size . ' MB' : 'Not Defined' ); ?></strong></span>
							</div>
							<div style="display: flex; flex-direction: column;" class="text-primary">
								<span>Allowed Types</span>
								<span><strong><?php echo esc_attr( $allowed_formats ); ?></strong></span>
							</div>
						</div>
					</label>
				</div>
				<?php echo '' !== $help ? '<label class="text-secondary sforms-helper-txt">' . esc_html( $help ) . '</label>' : ''; ?>
				<span id="upload-field-error-<?php echo esc_attr( $id ); ?>" hidden style="color: red;">File Size Exceeded The Limit</span>
				<span style="display:none" class="error-message"><?php echo esc_html( $error_msg ); ?></span>
			</div>
			<?php
		}
			return ob_get_clean();
	}
}
