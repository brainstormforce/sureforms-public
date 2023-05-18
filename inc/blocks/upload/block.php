<?php
/**
 * PHP render form upload Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Upload;

use SureForms\Inc\Blocks\Base;

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
		if ( ! empty( $attributes ) ) {
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
			) . '...' : 'All types';
			$label           = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help            = isset( $attributes['help'] ) ? $attributes['help'] : '';
			ob_start(); ?>
			<div id="sureform-upload-container" style="display: flex; flex-direction: column; gap: 0.5rem;">
				<label for="sureform-upload"><?php echo esc_attr( $label ); ?> 
					<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
				</label>    
				<?php echo '' !== $help ? '<label for="sureform-upload" style="color:#ddd;">' . esc_attr( $help ) . '</label>' : ''; ?>
				<input required="<?php echo esc_attr( $required ? 'true' : 'false' ); ?>" type="file" id="sureform-upload" hidden />
				<div style="border: 1px solid black;">
					<label id="sureform-upload-label" for="sureform-upload">
						<div style="display: flex; align-items: center; margin-left: 12px; margin-top: 12px; font-size: 25px; gap: 10px;">
						<i class="fa-solid fa-cloud-arrow-up"></i>
						Click to choose the file
						<i class="fa-sharp fa-solid fa-trash-can"></i>
						</div>
						<div style="display: flex; justify-content: space-between; padding: 1rem;">
							<div style="display: flex; flex-direction: column;">
								<span>Size Limit</span>
								<span><strong><?php echo esc_attr( $file_size ? $file_size . ' MB' : 'Not Defined' ); ?></strong></span>
							</div>
							<div style="display: flex; flex-direction: column;">
								<span>Allowed Types</span>
								<span><strong><?php echo esc_attr( $allowed_formats ); ?></strong></span>
							</div>
						</div>
					</label>
				</div>
				<p hidden style="color: red;">File Size Exceeded The Limit</p>
			</div>
			<?php
		}
			return ob_get_clean();
	}
}
