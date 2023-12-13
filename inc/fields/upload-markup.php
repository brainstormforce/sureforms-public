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
		$classname        = isset( $attributes['className'] ) ? ' '. $attributes['className'] : '';
		$slug        = 'upload';

		$block_width = $field_width ? ' srfm-block-width-' . str_replace(".","-",$field_width) : '';
		$aria_require_attr = $required ? 'true' : 'false';

		ob_start(); ?>
			<div class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $slug ); ?>-block srf-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-block<?php echo esc_attr( $block_width ); ?><?php echo esc_attr( $classname ); ?>">
			<?php echo wp_kses_post( Sureforms_Helper::GenerateCommonFormMarkup( 'label', $label, $slug, $block_id, $required ) ); ?>
			<div class="srfm-block-wrap">
				<?php echo Sureforms_Helper::fetch_svg('upload', 'srfm-'. esc_attr( $slug ) .'-icon'); ?>
				<div class="srfm-<?php echo esc_attr( $slug ); ?>-wrap">
					<input class="srfm-<?php echo esc_attr( $slug ); ?>-size" value="<?php echo esc_attr( $file_size ) ?>" type="hidden" />
					<label for="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo  esc_attr( $block_id ); ?>">
						<p><?php _e('Click to upload the file', 'sureforms'); ?></p>
						<input class="srfm-input-<?php echo esc_attr( $slug ); ?>" name="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo  esc_attr( $block_id ); ?>" type="file" aria-required="<?php echo esc_attr( $aria_require_attr ); ?>"  accept="<?php echo esc_attr( str_replace( ' ', ' .', $accepted_formats ) ); ?>">
					</label>
				</div>
				<p><span><?php ( 'All types' !== $allowed_formats ? esc_html( $allowed_formats ) . $many_types_symbol : __( 'All types', 'sureforms' ) ); ?></span> <?php _e('up to ', 'sureforms' ); echo esc_attr( $file_size ? $file_size . __(' MB', 'sureforms') : __('Not Defined', 'sureforms') ); ?></p>
			</div>
			<?php echo wp_kses_post( Sureforms_Helper::GenerateCommonFormMarkup( 'help', '', '', '', '', $help ) ); ?>
			<?php echo wp_kses_post( Sureforms_Helper::GenerateCommonFormMarkup( 'error', '', '', '', $required, '', $error_msg ) ); ?>
			</div>
		<?php
		return ob_get_clean();
	}
}
