<?php
/**
 * Form Base File.
 *
 * @package sureforms.
 */

/**
 * WP Travel Engine Form.
 *
 * @since 2.2.6
 */
class Form_Base {

	/**
	 * Form Option.
	 *
	 * @var array
	 * @since 2.2.6
	 */
	private $form_attributes;

	/**
	 * Form form_fields
	 *
	 * @var array
	 * @since 2.2.6
	 */
	private $form_fields;

	/**
	 * Init Function.
	 *
	 * @param array $form_attributes Attributes of form.
	 * @since 2.2.6
	 * @return Obj
	 */
	public function init( $form_attributes = array() ) {

		$this->form_attributes['form_title']             = isset( $form_attributes['form_title'] ) ? $form_attributes['form_title'] : '';
		$this->form_attributes['form_title_attr']        = isset( $form_attributes['form_title_attr'] ) ? $form_attributes['form_title_attr'] : 'div';
		$this->form_attributes['form_title_class']       = isset( $form_attributes['form_title_class'] ) ? $form_attributes['form_title_class'] : '';
		$this->form_attributes['action']                 = isset( $form_attributes['action'] ) ? $form_attributes['action'] : '';
		$this->form_attributes['fields_wrapper']         = isset( $form_attributes['fields_wrapper'] ) ? $form_attributes['fields_wrapper'] : '';
		$this->form_attributes['name']                   = isset( $form_attributes['name'] ) ? $form_attributes['name'] : '';
		$this->form_attributes['id']                     = isset( $form_attributes['id'] ) ? $form_attributes['id'] : '';
		$this->form_attributes['class']                  = isset( $form_attributes['class'] ) ? $form_attributes['class'] : $form_attributes['id'];
		$this->form_attributes['wrapper_class']          = isset( $form_attributes['wrapper_class'] ) ? $form_attributes['wrapper_class'] : $form_attributes['id'] . '-wrap';
		$this->form_attributes['hook_prefix']            = isset( $form_attributes['hook_prefix'] ) ? $form_attributes['hook_prefix'] : $this->slugify( $form_attributes['id'], array(), '_' );
		$this->form_attributes['submit_button']['id']    = isset( $form_attributes['submit_button']['id'] ) ? $form_attributes['submit_button']['id'] : '';
		$this->form_attributes['submit_button']['name']  = isset( $form_attributes['submit_button']['name'] ) ? $form_attributes['submit_button']['name'] : '';
		$this->form_attributes['submit_button']['value'] = isset( $form_attributes['submit_button']['value'] ) ? $form_attributes['submit_button']['value'] : '';
		$this->form_attributes['submit_button']['class'] = isset( $form_attributes['submit_button']['class'] ) ? $form_attributes['submit_button']['class'] : '';
		$this->form_attributes['nonce']['field']         = isset( $form_attributes['nonce']['field'] ) ? $form_attributes['nonce']['field'] : '';
		$this->form_attributes['nonce']['action']        = isset( $form_attributes['nonce']['action'] ) ? $form_attributes['nonce']['action'] : '';
		$this->form_attributes['multipart']              = isset( $form_attributes['multipart'] ) && $form_attributes['multipart'] ? true : false;

		return $this;
	}

	/**
	 * Array list of all form form_fields.
	 *
	 * @param array $form_fields form form_fields.
	 * @since 2.2.6
	 * @return obj
	 */
	public function form_fields( $form_fields ) {

		$this->form_fields = $form_fields;

		$field_priority = array();

		foreach ( $form_fields as $key => $field_row ) {
			$field_priority[ $key ] = isset( $field_row['priority'] ) ? $field_row['priority'] : 1;
		}

		// array_multisort( $field_priority, SORT_ASC, $this->form_fields );

		return $this;
	}

	/**
	 * Form Template
	 *
	 * @return void
	 */
	public function template() {

		$multipart = '';

		if ( $this->form_attributes['multipart'] ) {

			$multipart = 'enctype="multipart/form-data"';

		}
		?>
			<div class="<?php echo esc_attr( $this->form_attributes['wrapper_class'] ); ?>">
				<form name="<?php echo esc_attr( $this->form_attributes['name'] ); ?>" action="<?php echo esc_attr( $this->form_attributes['action'] ); ?>" method="post" id="<?php echo esc_attr( $this->form_attributes['id'] ); ?>"  class="<?php echo esc_attr( $this->form_attributes['class'] ); ?>" <?php echo esc_html( $multipart ); ?>>
					<?php

						/**
						 * Hook - Before form field
						 */
						do_action( $this->form_attributes['hook_prefix'] . '_before_form_field' );

					?>
						
						<<?php echo esc_attr( $this->form_attributes['form_title_attr'] ); ?> class="<?php echo esc_attr( $this->form_attributes['form_title_class'] ); ?>">
							<?php echo esc_html( $this->form_attributes['form_title'] ); ?>
						</<?php echo esc_attr( $this->form_attributes['form_title_attr'] ); ?>>

						<?php if ( $this->form_attributes['fields_wrapper'] ) : ?>
							<div class="<?php echo esc_attr( $this->form_attributes['fields_wrapper'] ); ?>">
							<?php
							endif;

								$form_fields = new WP_Travel_Engine_Form_Field();

								$form_fields->init( $this->form_fields )->render();

						if ( $this->form_attributes['fields_wrapper'] ) :
							?>
							</div>
						<?php endif; ?>
						
					
					<div class="wp-travel-engine-submit-wrap">
						<?php
							/**
							 * Hook - before form submit buttton.
							 */
							do_action( $this->form_attributes['hook_prefix'] . '_before_submit_button' );

								// Add nonce security.
								wp_nonce_field( $this->form_attributes['nonce']['action'], $this->form_attributes['nonce']['field'] );
						if ( isset( $this->form_attributes['submit_button'] ) && ! empty( $this->form_attributes['submit_button'] ) ) {
							printf( '<input type="submit" class="%s" name="%s" id="%s" value="%s">', esc_attr( $this->form_attributes['submit_button']['class'] ), esc_attr( $this->form_attributes['submit_button']['name'] ), esc_attr( $this->form_attributes['submit_button']['id'] ), esc_attr( $this->form_attributes['submit_button']['value'] ) );
						}

							/**
							 * Hook - After form submit button.
							 */
							do_action( $this->form_attributes['hook_prefix'] . '_after_submit_button' );
						?>
					</div>
					<?php do_action( $this->form_attributes['hook_prefix'] . '_after_form_field' ); ?>
				</form>
			</div>
		<?php
	}

	/**
	 * Slugify strings.
	 *
	 * @param string $string String.
	 * @param array  $replace Replace String.
	 * @param string $delimiter Delimiter.
	 * @return String.
	 */
	private function slugify( $string, $replace = array(), $delimiter = '-' ) {

		// Save the old locale and set the new locale to UTF-8.
		$old_locale = setlocale( LC_ALL, '0' );

		setlocale( LC_ALL, 'en_US.UTF-8' );

		if ( extension_loaded( 'iconv' ) ) {

			$clean = iconv( 'UTF-8', 'ASCII//TRANSLIT', $string );

		} else {

			$clean = sanitize_title( $string );

			$clean = str_replace( '-', '_', $clean );

		}

		if ( ! empty( $replace ) ) {

			$clean = str_replace( (array) $replace, ' ', $clean );

		}

		$clean = preg_replace( '/[^a-zA-Z0-9\/_|+ -]/', '', $clean );
		$clean = strtolower( $clean );
		$clean = preg_replace( '/[\/_|+ -]+/', $delimiter, $clean );
		$clean = trim( $clean, $delimiter );

		// Revert back to the old locale.
		setlocale( LC_ALL, $old_locale );

		return $clean;
	}

}
