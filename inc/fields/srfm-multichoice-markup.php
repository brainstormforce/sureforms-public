<?php
/**
 * Sureforms Multichoice Markup Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM_\Inc\Fields;

use SRFM\Inc\Traits\Get_Instance;
use SRFM\Inc\SRFM_Helper;

/**
 * SureForms Multichoice Markup Class.
 *
 * @since 0.0.1
 */
class SRFM_Multichoice_Markup extends SRFM_Base {
	use Get_Instance;

	/**
	 * Render the sureforms Multichoice classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param int|string   $form_id form id.
	 *
	 * @return string|boolean
	 */
	public function markup( $attributes, $form_id ) {
			$required         = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$single_selection = isset( $attributes['singleSelection'] ) ? $attributes['singleSelection'] : false;
			$options          = isset( $attributes['options'] ) ? $attributes['options'] : [];
			$label            = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help             = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$style            = isset( $attributes['style'] ) ? $attributes['style'] : '';
			$error_msg        = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$classname        = isset( $attributes['className'] ) ? '' . $attributes['className'] : '';
			$block_id         = isset( $attributes['block_id'] ) ? $attributes['block_id'] : '';
			$field_width      = isset( $attributes['fieldWidth'] ) ? $attributes['fieldWidth'] : '';
			$choice_width     = isset( $attributes['choiceWidth'] ) ? $attributes['choiceWidth'] : '';
			$output           = '';
			$slug             = 'multi-choice';

			$block_width          = $field_width ? ' srfm-block-width-' . str_replace( '.', '-', $field_width ) : '';
			$aria_require_attr    = $required ? 'true' : 'false';
			$type_attr            = $single_selection ? 'radio' : 'checkbox';
			$name_attr            = $single_selection ? 'name="srfm-input-' . esc_attr( $slug ) . '-' . esc_attr( $block_id ) . '"' : '';
			$input_label_fallback = $label ? $label : __( 'Multi Choice', 'sureforms' );
			$input_label          = '-lbl-' . SRFM_Helper::encrypt( $input_label_fallback );
			$choice_width_attr    = $choice_width ? 'srfm-choice-width-' . str_replace( '.', '-', $choice_width ) : '';

			ob_start(); ?>
			<div class="srfm-block-single srfm-block srfm-<?php echo esc_attr( $type_attr ); ?>-mode srfm-<?php echo esc_attr( $slug ); ?>-block srf-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?>-block<?php echo wp_kses_post( $block_width ); ?><?php echo esc_attr( $classname ); ?>">
			<input class="srfm-input-<?php echo esc_attr( $slug ); ?>-hidden" aria-required="<?php echo esc_attr( $aria_require_attr ); ?>" name="srfm-input-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id ); ?><?php echo esc_attr( $input_label ); ?>" type="hidden" value=""/>
			<?php echo wp_kses_post( SRFM_Helper::generate_common_form_markup( $form_id, 'label', $label, $slug, $block_id, boolval( $required ) ) ); ?>
				<?php if ( is_array( $options ) ) { ?>
					<div class="srfm-block-wrap <?php echo esc_attr( $choice_width_attr ); ?>">
						<?php foreach ( $options as $i => $option ) { ?>
							<label class="srfm-<?php echo esc_attr( $slug ); ?>-single">
								<input type="<?php echo esc_attr( $type_attr ); ?>" id="srfm-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $block_id . '-' . $i ); ?>" class="srfm-input-<?php echo esc_attr( $slug ); ?>-single" <?php echo wp_kses_post( $name_attr ); ?>/>
								<div class="srfm-block-content-wrap">
									<?php echo SRFM_Helper::fetch_svg( 'check-circle-solid', 'srfm-' . $slug . '-icon' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Ignored to render svg ?>
									<p><?php echo isset( $option['optionTitle'] ) ? esc_html( $option['optionTitle'] ) : ''; ?></p>
								</div>
							</label>
						<?php } ?>
					</div>
				<?php } ?>
			<?php echo wp_kses_post( SRFM_Helper::generate_common_form_markup( $form_id, 'help', '', '', '', false, $help ) ); ?>
			<?php echo wp_kses_post( SRFM_Helper::generate_common_form_markup( $form_id, 'error', '', '', '', boolval( $required ), '', $error_msg ) ); ?>
		</div>
		<?php
		return ob_get_clean();

	}
}
