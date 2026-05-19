<?php
/**
 * Multilingual String Translator.
 *
 * Provides convenient, name-scheme-aware helpers for translating SureForms'
 * form-level metadata strings through the active multilingual provider.
 *
 * @package sureforms.
 * @since x.x.x
 */

namespace SRFM\Inc\Compatibility\Multilingual;

use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * String_Translator.
 *
 * Bridges SureForms render paths to the multilingual provider using a stable naming
 * scheme that matches the strings registered on save by the string-collector.
 *
 * Naming scheme (domain `sureforms`):
 *  - Submit button text:           `form_{form_id}_submit_button`
 *  - Confirmation message:         `form_{form_id}_confirmation_{N}_message`
 *  - Notification subject:         `form_{form_id}_notification_{N}_subject`
 *  - Notification body:            `form_{form_id}_notification_{N}_body`
 *  - Notification from_name:       `form_{form_id}_notification_{N}_from_name`
 *  - Notification reply_to:        `form_{form_id}_notification_{N}_reply_to`
 *  - Compliance message:           `form_{form_id}_compliance_{N}_message`
 *  - Form restriction message:     `form_{form_id}_restriction_message`
 *
 * @since x.x.x
 */
class String_Translator {
	use Get_Instance;

	/**
	 * Translation domain used for every SureForms string.
	 *
	 * @since x.x.x
	 */
	public const DOMAIN = 'sureforms';

	/**
	 * Translate a form's submit button text.
	 *
	 * @param int    $form_id Form post ID.
	 * @param string $value   Original value (used as fallback when no translation exists).
	 * @since x.x.x
	 * @return string Translated value, or the original when no provider/translation is available.
	 */
	public function translate_submit_button( int $form_id, string $value ): string {
		if ( '' === $value ) {
			return $value;
		}

		$name = 'form_' . $form_id . '_submit_button';
		return $this->dispatch( $value, $name );
	}

	/**
	 * Translate a per-confirmation message.
	 *
	 * @param int    $form_id Form post ID.
	 * @param int    $index   Zero-based index of the confirmation within the form's confirmation set.
	 * @param string $value   Original value.
	 * @since x.x.x
	 * @return string Translated value, or the original when no provider/translation is available.
	 */
	public function translate_confirmation_message( int $form_id, int $index, string $value ): string {
		if ( '' === $value ) {
			return $value;
		}

		$name = 'form_' . $form_id . '_confirmation_' . $index . '_message';
		return $this->dispatch( $value, $name );
	}

	/**
	 * Translate an email notification subject.
	 *
	 * @param int    $form_id Form post ID.
	 * @param int    $index   Zero-based index of the notification within the form's notification set.
	 * @param string $value   Original value.
	 * @since x.x.x
	 * @return string Translated value, or the original when no provider/translation is available.
	 */
	public function translate_notification_subject( int $form_id, int $index, string $value ): string {
		if ( '' === $value ) {
			return $value;
		}

		$name = 'form_' . $form_id . '_notification_' . $index . '_subject';
		return $this->dispatch( $value, $name );
	}

	/**
	 * Translate an email notification body.
	 *
	 * @param int    $form_id Form post ID.
	 * @param int    $index   Zero-based index of the notification within the form's notification set.
	 * @param string $value   Original value.
	 * @since x.x.x
	 * @return string Translated value, or the original when no provider/translation is available.
	 */
	public function translate_notification_body( int $form_id, int $index, string $value ): string {
		if ( '' === $value ) {
			return $value;
		}

		$name = 'form_' . $form_id . '_notification_' . $index . '_body';
		return $this->dispatch( $value, $name );
	}

	/**
	 * Translate an email notification "from name".
	 *
	 * @param int    $form_id Form post ID.
	 * @param int    $index   Zero-based index of the notification within the form's notification set.
	 * @param string $value   Original value.
	 * @since x.x.x
	 * @return string Translated value, or the original when no provider/translation is available.
	 */
	public function translate_notification_from_name( int $form_id, int $index, string $value ): string {
		if ( '' === $value ) {
			return $value;
		}

		$name = 'form_' . $form_id . '_notification_' . $index . '_from_name';
		return $this->dispatch( $value, $name );
	}

	/**
	 * Translate an email notification reply-to address.
	 *
	 * @param int    $form_id Form post ID.
	 * @param int    $index   Zero-based index of the notification within the form's notification set.
	 * @param string $value   Original value.
	 * @since x.x.x
	 * @return string Translated value, or the original when no provider/translation is available.
	 */
	public function translate_notification_reply_to( int $form_id, int $index, string $value ): string {
		if ( '' === $value ) {
			return $value;
		}

		$name = 'form_' . $form_id . '_notification_' . $index . '_reply_to';
		return $this->dispatch( $value, $name );
	}

	/**
	 * Translate a compliance/GDPR message.
	 *
	 * @param int    $form_id Form post ID.
	 * @param int    $index   Zero-based index of the compliance entry within the form's compliance set.
	 * @param string $value   Original value.
	 * @since x.x.x
	 * @return string Translated value, or the original when no provider/translation is available.
	 */
	public function translate_compliance_message( int $form_id, int $index, string $value ): string {
		if ( '' === $value ) {
			return $value;
		}

		$name = 'form_' . $form_id . '_compliance_' . $index . '_message';
		return $this->dispatch( $value, $name );
	}

	/**
	 * Translate the form restriction message.
	 *
	 * @param int    $form_id Form post ID.
	 * @param string $value   Original value.
	 * @since x.x.x
	 * @return string Translated value, or the original when no provider/translation is available.
	 */
	public function translate_restriction_message( int $form_id, string $value ): string {
		if ( '' === $value ) {
			return $value;
		}

		$name = 'form_' . $form_id . '_restriction_message';
		return $this->dispatch( $value, $name );
	}

	/**
	 * Dispatch the translate call to the active multilingual provider.
	 *
	 * @param string $value Original value (used as fallback when no translation exists).
	 * @param string $name  Fully built string name per the documented naming scheme.
	 * @since x.x.x
	 * @return string Translated value, or the original value when the provider has no translation.
	 */
	private function dispatch( string $value, string $name ): string {
		$provider = Multilingual_Manager::get_instance()->provider();
		return $provider->translate( $value, $name, self::DOMAIN );
	}
}
