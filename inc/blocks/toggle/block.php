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
		$sureforms_helper_instance = new Sureforms_Helper();

		if ( ! empty( $attributes ) ) {
			$id        = isset( $attributes['id'] ) ? $sureforms_helper_instance->get_string_value( $attributes['id'] ) : '';
			$required  = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$label     = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help      = isset( $attributes['switchHelpText'] ) ? $attributes['switchHelpText'] : '';
			$checked   = isset( $attributes['checked'] ) ? $attributes['checked'] : '';
			$error_msg = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$classname = isset( $attributes['className'] ) ? $attributes['className'] : '';
			ob_start(); ?>
<div class="sureforms-switch-container main-container frontend-inputs-holder <?php echo esc_attr( $classname ); ?>">
	<label style="width:max-content" for="sureforms-switch-<?php echo esc_attr( $id ); ?>">
		<div style="display:flex; align-items:center; gap:0.5rem;" class="text-primary">
			<div class="switch-background" style="background-color: <?php echo $checked ? '#007CBA' : '#dcdcdc'; ?>;">
				<input class="sureforms-switch"
					name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>"
					id="sureforms-switch-<?php echo esc_attr( $id ); ?>"
					<?php echo esc_attr( $checked ? 'checked' : '' ); ?> type="checkbox"
					area-required=<?php echo esc_attr( $required ? 'true' : 'false' ); ?> />
				<div class="switch-toggle" style="left: <?php echo $checked ? '27px' : '2px'; ?>;"></div>
			</div>
			<span style="color: var(--primary-color)"><?php echo esc_html( $label ); ?></span>
			<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
		</div>
	</label>
			<?php echo '' !== $help ? '<label class="text-secondary sforms-helper-txt">' . esc_html( $help ) . '</label>' : ''; ?>
	<span style="margin-top:5px;display:none" class="error-message"><?php echo esc_html( $error_msg ); ?></span>
</div>
			<?php
		}
			return ob_get_clean();
	}
}
