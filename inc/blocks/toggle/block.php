<?php
/**
 * PHP render form Toggle Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Toggle;

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

		if ( ! empty( $attributes ) ) {
			$id            = isset( $attributes['id'] ) ? Sureforms_Helper::get_string_value( $attributes['id'] ) : '';
			$form_id       = isset( $attributes['formId'] ) ? Sureforms_Helper::get_integer_value( $attributes['formId'] ) : '';
			$required      = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$label         = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help          = isset( $attributes['switchHelpText'] ) ? $attributes['switchHelpText'] : '';
			$checked       = isset( $attributes['checked'] ) ? $attributes['checked'] : '';
			$error_msg     = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$classname     = isset( $attributes['className'] ) ? $attributes['className'] : '';
			$color_primary = get_post_meta( Sureforms_Helper::get_integer_value( $form_id ), '_sureforms_color1', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $form_id ), '_sureforms_color1', true ) ) : '';
			$checked_color = ! empty( $color_primary ) ? $color_primary : '#0084C7';

			ob_start(); ?>
<!-- <div class="sureforms-switch-container main-container frontend-inputs-holder <?php echo esc_attr( $classname ); ?>">
	<label style="width:max-content" class="sureforms-switch-label" for="sureforms-switch-<?php echo esc_attr( $id ); ?>">
		<div style="display:flex; align-items:center; gap:0.5rem;" class="sf-text-primary">
			<div class="switch-background" style="background-color: <?php echo $checked ? '#007CBA' : '#dcdcdc'; ?>;">
				<input class="sureforms-switch"
					name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>"
					id="sureforms-switch-<?php echo esc_attr( $id ); ?>"
					<?php echo esc_attr( $checked ? 'checked' : '' ); ?> type="checkbox"
					aria-required=<?php echo esc_attr( $required ? 'true' : 'false' ); ?> />
				<div class="switch-toggle" style="left: <?php echo $checked ? '27px' : '2px'; ?>;"></div>
			</div>
			<span style="color: var(--sf-primary-color)"><?php echo esc_html( $label ); ?></span>
			<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
		</div>
	</label>
			<?php echo '' !== $help ? '<label class="sf-text-secondary sforms-helper-txt">' . esc_html( $help ) . '</label>' : ''; ?>
	<span style="margin-top:5px;display:none" class="error-message"><?php echo esc_html( $error_msg ); ?></span>
</div> -->
			<div class="sureforms-switch-container main-container frontend-inputs-holder <?php echo esc_attr( $classname ); ?>">
				<label class="sureforms-switch-label !flex !gap-2 !items-start !max-w-fit" for="sureforms-switch-<?php echo esc_attr( $id ); ?>">
					<div class="!flex !items-center !gap-2 !text-sf_primary_color !w-max !mt-1">
						<div style="background-color: <?php echo esc_attr( $checked ? $checked_color : '#dcdcdc;' ); ?>" class="switch-background sf-classic-toggle-bg">
							<input class="sureforms-switch sf-classic-switch-input !p-0"
								name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>"
								id="sureforms-switch-<?php echo esc_attr( $id ); ?>"
								<?php echo esc_attr( $checked ? 'checked' : '' ); ?> type="checkbox"
								aria-required=<?php echo esc_attr( $required ? 'true' : 'false' ); ?> />
							<div class="switch-toggle !-top-[3px] !shadow !border !border-gray-200 !h-5 !w-5" style="left: <?php echo $checked ? '24px' : '0'; ?>;"> 
								<span class="sf-classic-toggle-icon-container sf-classic-toggle-icon <?php echo esc_attr( $checked ? '!opacity-100' : '!opacity-0' ); ?>" aria-hidden="true">
									<svg style="fill: <?php echo esc_attr( $checked ? $checked_color : '#dcdcdc;' ); ?>" class="!h-3 !w-3 sf-classic-toggle-icon" viewBox="0 0 12 12">
										<path d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
									</svg>
								</span>
							</div>
						</div>
					</div>
					<span class="sf-classic-label-text"><?php echo esc_html( $label ); ?> <?php echo $required && $label ? '<span class="!text-required_icon_color"> *</span>' : ''; ?></span>
				</label>
				<?php echo '' !== $help ? '<p for="sureforms-checkbox" class="sforms-helper-txt">' . esc_html( $help ) . '</p>' : ''; ?>
				<span style="display:none;" class="error-message"><?php echo esc_html( $error_msg ); ?></span>
			</div>
			<?php
		}
			return ob_get_clean();
	}
}
