<?php
/**
 * Sureforms Multichoice Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc\Fields;

use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Sureforms_Helper;

/**
 * SureForms Multichoice Markup Class.
 *
 * @since 0.0.1
 */
class Multichoice_Markup extends Base {
	use Get_Instance;

	/**
	 * Render the sureforms Multichoice classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function classic_styling( $attributes ) {
			$required         = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$single_selection = isset( $attributes['singleSelection'] ) ? $attributes['singleSelection'] : false;
			$options          = isset( $attributes['options'] ) ? $attributes['options'] : array();
			$label            = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help             = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$style            = isset( $attributes['style'] ) ? $attributes['style'] : '';
			$error_msg        = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$classname        = isset( $attributes['className'] ) ? '' . $attributes['className'] : '';
			$block_id         = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';
			$field_width      = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
			$output           = '';
			$slug = 'multi-choice';

			$block_width = $field_width ? ' srfm-block-width-' . str_replace(".","-",$field_width) : '';
			$aria_require_attr  = $required ? 'true' : 'false';
			$type_attr  = $single_selection ? 'radio' : 'checkbox';
			$name_attr = $single_selection ? 'name="srfm-input-'. esc_attr( $slug ) . '-' . esc_attr( $block_id ).'"' : '';

			ob_start(); ?>
			<div class="srfm-block-single srfm-block srfm-<?php echo esc_attr($type_attr); ?>-mode srfm-<?php echo esc_attr( $slug ); ?>-block srf-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-block<?php echo wp_kses_post( $block_width ); ?><?php echo esc_attr( $classname ) ?>">
			<input class="srfm-input-<?php echo esc_attr( $slug ); ?>-hidden" aria-required="<?php echo esc_attr( $aria_require_attr ); ?>" name="srfm-input-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr($block_id); ?>" type="hidden" value=""/>
			<?php echo wp_kses_post( Sureforms_Helper::GenerateCommonFormMarkup( 'label', $label, $slug, $block_id, $required ) ); ?>	
				<?php if ( is_array( $options ) ) { ?>
					<div class="srfm-block-wrap">
						<?php foreach ( $options as $i => $option ) { ?>
							<label class="srfm-<?php echo esc_attr( $slug ); ?>-single">
								<input type="<?php echo $type_attr; ?>" id="srfm-input-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id .'-'. $i ); ?>" class="srfm-input-<?php echo esc_attr( $slug ); ?>-single" <?php echo wp_kses_post( $name_attr ); ?>/>
								<div class="srfm-block-content-wrap">
									<?php echo Sureforms_Helper::fetch_svg('check', 'srfm-'. $slug .'-icon'); ?>
									<p><?php echo isset( $option['optiontitle'] ) ? esc_html( $option['optiontitle'] ) : ''; ?></p>
								</div>
							</label>
						<?php } ?>
					</div>
				<?php } ?>
			<?php echo wp_kses_post( Sureforms_Helper::GenerateCommonFormMarkup('help', '', '', '', '', $help ) ); ?>
			<?php echo wp_kses_post(Sureforms_Helper::GenerateCommonFormMarkup('error', '', '', '', $required, '', $error_msg )); ?>
		</div>
		<?php
		return ob_get_clean();

	}
}