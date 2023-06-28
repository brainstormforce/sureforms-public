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
			$id               = isset( $attributes['id'] ) ? strval( $attributes['id'] ) : '';
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
			ob_start(); ?>
			<div id="sureforms-upload-container" class="main-container" style="display: flex; flex-direction: column; gap: 0.5rem;">
				<label class="text-primary"><?php echo esc_attr( $label ); ?> 
					<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
				</label>
				<input name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-upload' . $id ) ); ?>" id="sureforms-upload-index-<?php echo esc_attr( $id ); ?>" value="" type="hidden" />
				<input name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-upload' . $id ) ); ?>" class="sureforms-upload-field" <?php echo esc_attr( $required ? 'required' : '' ); ?> type="file" hidden id="sureforms-upload-<?php echo esc_attr( $id ); ?>" 
				accept=".<?php echo esc_attr( str_replace( ' ', ' .', $accepted_formats ) ); ?>"
				/>
				<input class="sureforms-upload-size" value="<?php echo esc_attr( $file_size ); ?>" type="hidden" />
				<div class="sureforms-upload-inner-div" style="border: 2px solid black;">
					<label id="sureforms-upload-label" for="sureforms-upload-<?php echo esc_attr( $id ); ?>">
						<div id="sureforms-upload-title-<?php echo esc_attr( $id ); ?>" style="display: flex; align-items: center; margin-left: 12px; margin-top: 12px; font-size: 25px; gap: 10px;">
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
				<span id="upload-field-error-<?php echo esc_attr( $id ); ?>" hidden style="color: red;">File Size Exceeded The Limit</span>
				<?php echo '' !== $help ? '<label class="text-secondary">' . esc_attr( $help ) . '</label>' : ''; ?>
			</div>
			<?php
		}
			return ob_get_clean();
	}
}
