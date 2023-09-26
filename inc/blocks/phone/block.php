<?php
/**
 * PHP render form Phone Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Phone;

use SureForms\Inc\Blocks\Base;
use SureForms\Inc\Sureforms_Helper;

/**
 * Phone Block.
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

		$upload_dir = wp_upload_dir();
		$file_path  = plugin_dir_url( __FILE__ ) . '/phone_codes.json';
		$response   = wp_remote_get( $file_path );

		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
			$json_string = wp_remote_retrieve_body( $response );
			$data        = json_decode( $json_string, true );
		} else {
			$data = array();
		}
		if ( ! empty( $attributes ) ) {
			$id              = isset( $attributes['id'] ) ? $sureforms_helper_instance->get_string_value( $attributes['id'] ) : '';
			$default         = isset( $attributes['defaultValue'] ) ? $attributes['defaultValue'] : '';
			$default_country = isset( $attributes['defaultCountryCode'] ) ? $attributes['defaultCountryCode'] : '';
			$required        = isset( $attributes['required'] ) ? $attributes['required'] : false;
			$placeholder     = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
			$label           = isset( $attributes['label'] ) ? $attributes['label'] : '';
			$help            = isset( $attributes['help'] ) ? $attributes['help'] : '';
			$error_msg       = isset( $attributes['errorMsg'] ) ? $attributes['errorMsg'] : '';
			$is_unique       = isset( $attributes['isUnique'] ) ? $attributes['isUnique'] : false;
			$dulicate_msg    = isset( $attributes['duplicateMsg'] ) ? $attributes['duplicateMsg'] : '';
			$classname       = isset( $attributes['className'] ) ? $attributes['className'] : '';
			ob_start();
			?>
			<!-- <div class="sureforms-input-phone-container main-container frontend-inputs-holder <?php echo esc_attr( $classname ); ?>" id="sureforms-input-phone-<?php echo esc_attr( $id ); ?>">
				<label class="text-primary"><?php echo esc_html( $label ); ?>
					<?php echo $required && $label ? '<span style="color:red;"> *</span>' : ''; ?>
				</label>
				<div class="sureforms-input-phone-holder">
					<input name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>" type="hidden" area-unique="<?php echo esc_attr( $is_unique ? 'true' : 'false' ); ?>" id="fullPhoneNumber-<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( ! empty( $default ) ? "($default_country)$default" : '' ); ?>" />
					<select id="sureforms-country-code-<?php echo esc_attr( $id ); ?>" <?php echo esc_attr( $required ? 'required' : '' ); ?>>
					<?php if ( $default_country ) : ?>
					<option value="<?php echo esc_attr( $default_country ); ?>"><?php echo esc_html( $default_country ); ?></option>
					<?php endif; ?>
						<?php
						if ( is_array( $data ) ) {
							foreach ( $data as $country ) {
								if ( isset( $country['code'] ) && isset( $country['dial_code'] ) ) {
									?>
						<option value="<?php echo esc_attr( $country['dial_code'] ); ?>"><?php echo esc_html( $country['code'] . ' ' . $country['dial_code'] ); ?></option>
									<?php
								}
							}
						}
						?>
					</select>
					<input type="tel" area-required="<?php echo esc_attr( $required ? 'true' : 'false' ); ?>" area-unique="<?php echo esc_attr( $is_unique ? 'true' : 'false' ); ?>" value="<?php echo esc_attr( $default ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>"
						id="sureforms-phone-number-<?php echo esc_attr( $id ); ?>"
						class="sureforms-input-field" />
				</div>
				<?php echo '' !== $help ? '<label class="text-secondary sforms-helper-txt">' . esc_html( $help ) . '</label>' : ''; ?>
				<span style="display:none" class="error-message"><?php echo esc_html( $error_msg ); ?></span>
				<span style="display:none" class="error-message duplicate-message"><?php echo esc_html( $dulicate_msg ); ?></span>
			</div> -->
			<!-- class layout -->
			<div class="sureforms-input-phone-container main-container sf-classic-inputs-holder <?php echo esc_attr( $classname ); ?>" id="sureforms-input-phone-<?php echo esc_attr( $id ); ?>">
				<label for="sureforms-phone-number-<?php echo esc_attr( $id ); ?>" class="block text-sm font-medium leading-6 text-primary_color">
					<?php echo esc_html( $label ); ?> 
					<?php echo $required && $label ? '<span class="text-required_icon_color"> *</span>' : ''; ?></label>
					<div class="relative mt-2">
						<div id="sureforms-phone-parent" class="group relative !ring-1 !ring-gray-300 !border-[#D1D5DB] rounded-md !border-0 !outline-0 focus-within:!ring-primary_color focus-within:!ring-2 focus-within:!border-solid focus-within:!border-0 focus-within:!border-primary_color focus-within:!outline-0">
							<div class="absolute inset-y-0 left-0 flex items-center">
								<input name="<?php echo esc_attr( str_replace( ' ', '_', $label . 'SF-divider' . $id ) ); ?>" type="hidden" area-unique="<?php echo esc_attr( $is_unique ? 'true' : 'false' ); ?>" id="fullPhoneNumber-<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( ! empty( $default ) ? "($default_country)$default" : '' ); ?>" />
								<select class="h-full !rounded-md !border-0 !bg-transparent !py-0 !pl-3 !pr-7 !text-gray-900 focus:outline-none !ring-0 focus:ring-inset focus:ring-0 focus:!outline-0 focus:!border-0 sm:text-sm
								" id="sureforms-country-code-<?php echo esc_attr( $id ); ?>">
								<?php
								if ( is_array( $data ) ) {
									foreach ( $data as $country ) {
										if ( isset( $country['code'] ) && isset( $country['dial_code'] ) ) {
											?>
											<option value="<?php echo esc_attr( $country['dial_code'] ); ?>" <?php echo esc_html( $country['dial_code'] === $default_country ? 'selected' : '' ); ?>><?php echo esc_html( $country['code'] ); ?></option>
											<?php
										}
									}
								}
								?>
								</select>
							</div>
							<input type="tel" id="sureforms-phone-number-<?php echo esc_attr( $id ); ?>" class="block !w-full !rounded-md !py-1.5 !pl-16 text-gray-900 !shadow-sm !ring-0 !border-0 !bg-white !outline-0 placeholder:!text-gray-500 sm:!text-sm sm:!leading-6 focus:!ring-0 focus:!border-0" area-required="<?php echo esc_attr( $required ? 'true' : 'false' ); ?>" value="<?php echo esc_attr( $default ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>">
						</div>
					</div>
				<?php echo '' !== $help ? '<p class="mt-2 text-sm text-gray-500" id="text-description">' . esc_html( $help ) . '</p>' : ''; ?>
				<p style="display:none;" class="error-message mt-2 text-sm text-red-600"><?php echo esc_html( $error_msg ); ?></p>
				<p style="display:none" class="duplicate-message mt-2 text-sm text-red-600"><?php echo esc_html( $dulicate_msg ); ?></p>
			</div>
			<?php
		}
			return ob_get_clean();
	}
}
