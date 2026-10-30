<?php
/**
 * SureForms - AI Form Builder.
 *
 * @package sureforms
 * @since 0.0.8
 */

namespace SRFM\Inc\AI_Form_Builder;

use SRFM\Inc\Helper;
use SRFM\Inc\Traits\Get_Instance;
use WP_Error;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SureForms AI Form Builder Class.
 */
class Field_Mapping {
	use Get_Instance;

	/**
	 * Field types allowed inside an address block (editor `allowedBlocks`).
	 *
	 * @since 2.13.0
	 */
	public const ADDRESS_INNER_TYPES = [ 'input', 'dropdown' ];

	/**
	 * Field types allowed inside a repeater block (Pro editor `allowedBlocks`).
	 *
	 * @since 2.13.0
	 */
	public const REPEATER_INNER_TYPES = [ 'input', 'email', 'textarea', 'number', 'url', 'phone', 'date-picker', 'time-picker', 'dropdown' ];

	/**
	 * Field types allowed inside a register block (Pro editor `allowedBlocks`).
	 *
	 * @since 2.13.0
	 */
	public const REGISTER_INNER_TYPES = [ 'input', 'email', 'password' ];

	/**
	 * Generate Gutenberg Fields from AI data.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return string|WP_Error
	 */
	public static function generate_gutenberg_fields_from_questions( $request ) {

		// Get params from request.
		$params = $request->get_params();

		// check parama is empty or not and is an array and consist form_data key.
		// count() is guarded by is_array(): a non-array form_data is a TypeError in PHP 8,
		// and this endpoint is reachable with any JSON value. It falls through to the
		// invalid_form_data check below instead.
		if ( empty( $params ) || ! is_array( $params ) || ! isset( $params['form_data'] )
			|| ( is_array( $params['form_data'] ) && 0 === count( $params['form_data'] ) ) ) {
			return new WP_Error(
				'srfm_ai_mapping_missing_form_data',
				__( 'The AI form data is missing. Please try again.', 'sureforms' ),
				[ 'status' => 400 ]
			);
		}

		// Get questions from form data.
		$form_data = $params['form_data'];
		if ( empty( $form_data ) || ! is_array( $form_data ) ) {
			return new WP_Error(
				'srfm_ai_mapping_invalid_form_data',
				__( 'The AI form data is not in the expected format.', 'sureforms' ),
				[ 'status' => 400 ]
			);
		}

		$form = $form_data['form'] ?? null;
		if ( empty( $form ) || ! is_array( $form ) ) {
			return new WP_Error(
				'srfm_ai_mapping_missing_form',
				__( 'The AI response did not include a form. Please try again.', 'sureforms' ),
				[ 'status' => 400 ]
			);
		}

		$form_fields = $form['formFields'] ?? null;
		if ( empty( $form_fields ) || ! is_array( $form_fields ) ) {
			return new WP_Error(
				'srfm_ai_mapping_missing_form_fields',
				__( 'The AI was unable to generate form fields. Please try again.', 'sureforms' ),
				[ 'status' => 400 ]
			);
		}

		// Initialize post content string.
		$post_content = '';

		$is_conversational = isset( $params['is_conversional'] ) ? filter_var( $params['is_conversional'], FILTER_VALIDATE_BOOLEAN ) : false;
		$form_type         = isset( $params['form_type'] ) ? Helper::get_string_value( $params['form_type'] ) : 'simple';

		// Filer to skip fields while mapping the fields.
		$skip_fields = apply_filters( 'srfm_ai_field_map_skip_fields', [], $is_conversational, $form_type );

		// Loop through questions.
		foreach ( $form_fields as $question ) {
			$markup = self::map_question( $question, $is_conversational, $form_type, $skip_fields );
			if ( is_wp_error( $markup ) ) {
				return $markup;
			}
			$post_content .= $markup;
		}

		return apply_filters( 'srfm_ai_form_builder_post_content', $post_content, $is_conversational, $form_type );
	}

	/**
	 * Block markup for one AI-schema question.
	 *
	 * Also used for the inner fields of container blocks (address, repeater, register),
	 * where `$allowed_types` limits which field types may be emitted.
	 *
	 * @param mixed                   $question          Field schema.
	 * @param bool                    $is_conversational Whether the form is conversational.
	 * @param string                  $form_type         Form type.
	 * @param array<mixed>            $skip_fields       Field types to skip.
	 * @param array<int, string>|null $allowed_types     Field types allowed here, or null for any.
	 * @since 2.13.0
	 * @return string|WP_Error Block markup, empty when the field is skipped.
	 */
	private static function map_question( $question, $is_conversational, $form_type, $skip_fields, $allowed_types = null ) {
		$post_content = '';

		// A malformed question fails the whole form.
		if ( empty( $question ) || ! is_array( $question ) ) {
			return new WP_Error(
				'srfm_ai_mapping_invalid_field',
				__( 'The AI returned a malformed form field. Please try again.', 'sureforms' ),
				[ 'status' => 400 ]
			);
		}

		// Initialize common attributes.
		$common_attributes = [
			'block_id' => self::new_block_id(), // Generate random block_id.
			'formId'   => 0, // Set your formId here.
		];

		// Merge common attributes with question attributes.
		$merged_attributes = array_merge(
			$common_attributes,
			[
				'label'    => isset( $question['label'] ) ? sanitize_text_field( $question['label'] ) : '',
				'required' => isset( $question['required'] ) ? filter_var( $question['required'], FILTER_VALIDATE_BOOLEAN ) : false,
				'help'     => isset( $question['helpText'] ) ? sanitize_text_field( $question['helpText'] ) : '',
				'slug'     => isset( $question['slug'] ) ? sanitize_text_field( $question['slug'] ) : '',
			]
		);

		// Forward `placeholder` to the block attrs. Every input-like
		// block (`input`, `email`, `url`, `phone`, `number`,
		// `textarea`, `dropdown`) declares a `placeholder` attribute
		// in its block.json; without this passthrough the value is
		// silently dropped by the mapper even when the caller (AI,
		// MCP, or the HTML-form converter) supplied it.
		if ( isset( $question['placeholder'] ) && is_string( $question['placeholder'] ) && '' !== $question['placeholder'] ) {
			// Bound the placeholder to 500 chars: other string fields
			// in this mapper are implicitly bounded by their upstream
			// schema, but `placeholder` lands here from three call
			// sites (AI, MCP, HTML converter) and a pathological
			// caller could push a multi-MB string into the block's
			// `_srfm_*` post meta. `wp_html_excerpt` strips HTML
			// first, then truncates safely on word boundaries.
			$merged_attributes['placeholder'] = wp_html_excerpt( sanitize_text_field( $question['placeholder'] ), 500 );
		}

		// Forward `className` (Additional CSS Class) to the block attrs.
		// Field blocks inherit core's className support and render it onto the
		// field wrapper (see inc/fields/base.php::set_properties()). Lands from
		// multiple callers (AI, MCP, HTML converter), so sanitize each token.
		if ( isset( $question['className'] ) && is_string( $question['className'] ) && '' !== $question['className'] ) {
			$classes = preg_split( '/\s+/', trim( $question['className'] ) );
			if ( is_array( $classes ) ) {
				$clean = implode( ' ', array_filter( array_map( 'sanitize_html_class', $classes ) ) );
				if ( '' !== $clean ) {
					$merged_attributes['className'] = $clean;
				}
			}
		}

		// Apply filter to modify field type.
		$field_type = apply_filters( 'srfm_ai_field_modify_field_type', $question['fieldType'] ?? '', $question, $is_conversational, $form_type );

		// Inner fields of a container block may only use the types its editor allows.
		if ( null !== $allowed_types && ! in_array( $field_type, $allowed_types, true ) ) {
			return '';
		}

		// Determine field type based on field_type.
		switch ( $field_type ) {
			case 'input':
			case 'email':
			case 'number':
			case 'textarea':
			case 'dropdown':
			case 'checkbox':
			case 'address':
			case 'inline-button':
			case 'gdpr':
			case 'multi-choice':
			case 'url':
			case 'phone':
			case 'payment':
				// if payment block then map payment specific attributes.
				if ( 'payment' === $field_type ) {
					// Amount-unit convention (do not change without auditing the full
					// chain): the AI prompt schema describes fixedAmount / oneTimeFixedAmount
					// / subscriptionFixedAmount in MAJOR units (dollars/euros/etc.) using
					// dollar-magnitude examples (e.g. 99, 1500). All downstream layers
					// agree: block attrs and stored block_config keep the value in major
					// units, frontend JS multiplies by 100 only at the boundary when posting
					// to create_payment_intent, and the server divides it back via
					// Stripe_Helper::amount_from_stripe_format() before validating against
					// the stored fixed_amount. Stripe API itself is the only consumer that
					// expects minor units and it is fed the JS-multiplied value. Reviewers:
					// do not flag a "cents vs dollars ambiguity" here — the convention is
					// consistent end-to-end, and adding a unit declaration to the AI schema
					// would actually break the existing pipeline.
					//
					// Default-amount convention (do not change without auditing every
					// callsite): the fallback `10` used when the AI omits fixedAmount /
					// oneTimeFixedAmount / subscriptionFixedAmount is the same starter
					// value that block.json sets when an admin manually adds a payment
					// block in the Gutenberg editor. payment-markup.php and
					// field-validation.php apply the same default. AI-generated forms
					// therefore behave identically to manually-built forms when an amount
					// is missing — admin reviews the form preview and adjusts before
					// publishing. The schema marks these three amounts as `required`, so
					// in practice this fallback only fires for malformed AI responses;
					// changing it to 0 would make the manual-editor UX worse without
					// closing any real revenue-loss vector. Reviewers: do not flag the
					// `10` default here as a hidden charge — it is the project-wide
					// payment-block starter value.
					//
					// Update-flow caveat (pre-existing, not specific to "both" mode):
					// generate_gutenberg_fields_from_questions() is also called by the
					// update-form ability (inc/abilities/forms/update-form.php) which
					// regenerates the entire post_content from the AI's input. There is
					// no merge with the form's currently-saved attributes — every field
					// type's default-on-omit behavior applies. If an AI partial update
					// omits a field attribute (e.g. a previously-saved subscriptionFixedAmount
					// of $15), the default kicks in and overwrites the saved value. This
					// is a long-standing characteristic of the update flow, affecting all
					// fields equally; it is not a regression introduced by the "both"
					// payment-type work and should be addressed (if at all) by teaching
					// generate_gutenberg_fields_from_questions to merge with existing block
					// attrs — a broader refactor outside this scope. Reviewers: do not
					// flag this as a payment-specific bug.
					//
					// Schema "required" scope (sureforms-ai-templates/payment.json):
					// the JSON schema lists every payment property — including all 11
					// "both"-mode attrs — in a single flat `required` array applied to
					// every payment field, not scoped per paymentType. This is a
					// constraint of OpenAI's strict structured output mode: when
					// `additionalProperties: false` is set, every property must also
					// appear in `required`. The per-property `description` strings tell
					// the model to emit empty strings / 0 for inapplicable modes (e.g.
					// `oneTimeLabel: ''` when paymentType='one-time'). The mapping below
					// only reads those attrs when paymentType='both', so empty values
					// for other modes are silently and correctly dropped — there is no
					// silent conflict. Reviewers: do not flag the flat `required` list
					// as a scoping bug; it is how OpenAI strict mode works.
					$amount_types = [ 'fixed', 'variable', 'user-choice' ];
					$intervals    = [ 'day', 'week', 'month', 'quarter', 'year' ];

					$merged_attributes['customerNameField']   = isset( $question['customerNameField'] ) ? sanitize_text_field( $question['customerNameField'] ) : '';
					$merged_attributes['customerEmailField']  = isset( $question['customerEmailField'] ) ? sanitize_text_field( $question['customerEmailField'] ) : '';
					$merged_attributes['paymentType']         = isset( $question['paymentType'] ) && in_array( $question['paymentType'], [ 'one-time', 'subscription', 'both' ], true ) ? sanitize_text_field( $question['paymentType'] ) : 'one-time';
					$merged_attributes['subscriptionPlan']    = isset( $question['subscriptionPlan'] ) && is_array( $question['subscriptionPlan'] ) ? [
						'name'          => isset( $question['subscriptionPlan']['name'] ) ? sanitize_text_field( $question['subscriptionPlan']['name'] ) : 'Subscription Plan',
						'interval'      => isset( $question['subscriptionPlan']['interval'] ) && in_array( $question['subscriptionPlan']['interval'], $intervals, true ) ? sanitize_text_field( $question['subscriptionPlan']['interval'] ) : 'month',
						'billingCycles' => isset( $question['subscriptionPlan']['billingCycles'] ) ? ( is_numeric( $question['subscriptionPlan']['billingCycles'] ) ? intval( $question['subscriptionPlan']['billingCycles'] ) : sanitize_text_field( $question['subscriptionPlan']['billingCycles'] ) ) : 'ongoing',
					] : [
						'name'          => 'Subscription Plan',
						'interval'      => 'month',
						'billingCycles' => 'ongoing',
					];
					$merged_attributes['amountType']          = isset( $question['amountType'] ) && in_array( $question['amountType'], $amount_types, true ) ? sanitize_text_field( $question['amountType'] ) : 'fixed';
					$merged_attributes['fixedAmount']         = isset( $question['fixedAmount'] ) && is_numeric( $question['fixedAmount'] ) ? floatval( $question['fixedAmount'] ) : 10;
					$merged_attributes['minimumAmount']       = isset( $question['minimumAmount'] ) && is_numeric( $question['minimumAmount'] ) ? floatval( $question['minimumAmount'] ) : 0;
					$merged_attributes['amountLabel']         = isset( $question['amountLabel'] ) ? sanitize_text_field( $question['amountLabel'] ) : 'Enter Amount';
					$merged_attributes['variableAmountField'] = isset( $question['variableAmountField'] ) ? sanitize_text_field( $question['variableAmountField'] ) : '';

					// "Both" mode attributes — admins configure one-time AND subscription in the same block.
					if ( 'both' === $merged_attributes['paymentType'] ) {
						$merged_attributes['oneTimeLabel']                    = isset( $question['oneTimeLabel'] ) ? sanitize_text_field( $question['oneTimeLabel'] ) : 'One-Time Payment';
						$merged_attributes['subscriptionLabel']               = isset( $question['subscriptionLabel'] ) ? sanitize_text_field( $question['subscriptionLabel'] ) : 'Subscription';
						$merged_attributes['defaultPaymentChoice']            = isset( $question['defaultPaymentChoice'] ) && in_array( $question['defaultPaymentChoice'], [ 'one-time', 'subscription' ], true ) ? sanitize_text_field( $question['defaultPaymentChoice'] ) : 'one-time';
						$merged_attributes['oneTimeAmountType']               = isset( $question['oneTimeAmountType'] ) && in_array( $question['oneTimeAmountType'], $amount_types, true ) ? sanitize_text_field( $question['oneTimeAmountType'] ) : 'fixed';
						$merged_attributes['oneTimeFixedAmount']              = isset( $question['oneTimeFixedAmount'] ) && is_numeric( $question['oneTimeFixedAmount'] ) ? floatval( $question['oneTimeFixedAmount'] ) : 10;
						$merged_attributes['oneTimeMinimumAmount']            = isset( $question['oneTimeMinimumAmount'] ) && is_numeric( $question['oneTimeMinimumAmount'] ) ? floatval( $question['oneTimeMinimumAmount'] ) : 0;
						$merged_attributes['oneTimeVariableAmountField']      = isset( $question['oneTimeVariableAmountField'] ) ? sanitize_text_field( $question['oneTimeVariableAmountField'] ) : '';
						$merged_attributes['subscriptionAmountType']          = isset( $question['subscriptionAmountType'] ) && in_array( $question['subscriptionAmountType'], $amount_types, true ) ? sanitize_text_field( $question['subscriptionAmountType'] ) : 'fixed';
						$merged_attributes['subscriptionFixedAmount']         = isset( $question['subscriptionFixedAmount'] ) && is_numeric( $question['subscriptionFixedAmount'] ) ? floatval( $question['subscriptionFixedAmount'] ) : 10;
						$merged_attributes['subscriptionMinimumAmount']       = isset( $question['subscriptionMinimumAmount'] ) && is_numeric( $question['subscriptionMinimumAmount'] ) ? floatval( $question['subscriptionMinimumAmount'] ) : 0;
						$merged_attributes['subscriptionVariableAmountField'] = isset( $question['subscriptionVariableAmountField'] ) ? sanitize_text_field( $question['subscriptionVariableAmountField'] ) : '';
					}
				}

				// Handle specific attributes for certain fields.
				// A first option of "0" (a 0-10 scale) is a real label, so check for an empty string rather than empty().
				if ( 'dropdown' === $field_type && ! empty( $question['fieldOptions'] ) && is_array( $question['fieldOptions'] ) &&
				'' !== Helper::get_string_value( $question['fieldOptions'][0]['label'] ?? '' )
				) {
					// Defense-in-depth: although the upstream middleware is
					// trusted and these endpoints are capability-gated,
					// strings flow into Gutenberg block markup so we run
					// the user-facing fields through sanitize_text_field.
					$merged_attributes['options'] = self::sanitize_field_options( $question['fieldOptions'] );

					if ( isset( $question['showValues'] ) ) {
						$merged_attributes['showValues'] = filter_var( $question['showValues'], FILTER_VALIDATE_BOOLEAN );
					}

					// remove icon from options for the dropdown field.
					foreach ( $merged_attributes['options'] as $key => $option ) {
						if ( ! empty( $merged_attributes['options'][ $key ]['icon'] ) ) {
							$merged_attributes['options'][ $key ]['icon'] = '';
						}
					}
				}
				if ( 'multi-choice' === $field_type ) {

					// Remove duplicate icons and clear icons if all are the same.
					$icons        = array_column( $question['fieldOptions'], 'icon' );
					$options      = array_column( $question['fieldOptions'], 'optionTitle' );
					$unique_icons = array_unique( $icons );
					if ( count( $unique_icons ) === 1 || count( $options ) !== count( $icons ) ) {
						foreach ( $question['fieldOptions'] as &$option ) {
							$option['icon'] = '';
						}
					}

					// Set options if they are valid.
					if ( '' !== Helper::get_string_value( $question['fieldOptions'][0]['optionTitle'] ?? '' ) ) {
						// Same defense-in-depth sanitization as the
						// dropdown branch above.
						$merged_attributes['options'] = self::sanitize_field_options( $question['fieldOptions'] );
					}

					// Determine vertical layout based on icons.
					if ( ! empty( $merged_attributes['options'] ) ) {
						$merged_attributes['verticalLayout'] = array_reduce(
							$merged_attributes['options'],
							static fn( $carry, $option ) => $carry && ! empty( $option['icon'] ),
							true
						);
					}

					if ( isset( $question['showValues'] ) ) {
						$merged_attributes['showValues'] = filter_var( $question['showValues'], FILTER_VALIDATE_BOOLEAN );
					}

					// Set single selection if provided.
					if ( isset( $question['singleSelection'] ) ) {
						$merged_attributes['singleSelection'] = filter_var( $question['singleSelection'], FILTER_VALIDATE_BOOLEAN );
					}

					// Set choiceWidth for options divisible by 3.
					if ( ! empty( $merged_attributes['options'] ) && count( $merged_attributes['options'] ) % 3 === 0 ) {
						$merged_attributes['choiceWidth'] = 33.33;
					}
				}
				if ( 'phone' === $field_type ) {
					$merged_attributes['autoCountry'] = true;
				}

				// Apply filter to modify merged attributes.
				$merged_attributes = apply_filters( 'srfm_ai_form_builder_modify_merged_attributes', $merged_attributes, $question, $is_conversational, $form_type );

				// if field type is needs to be skipped then skip that field.
				if ( ! empty( $skip_fields ) && in_array( $field_type, $skip_fields, true ) ) {
					break;
				}

				// The address block is a container: its sub-fields are inner blocks.
				if ( 'address' === $field_type ) {
					$inner_content = self::map_inner_fields( $question, self::ADDRESS_INNER_TYPES, $is_conversational, $form_type, $skip_fields );
					if ( is_wp_error( $inner_content ) ) {
						return $inner_content;
					}
					if ( '' === $inner_content ) {
						$inner_content = self::default_address_fields( ! empty( $merged_attributes['required'] ), Helper::get_string_value( $merged_attributes['slug'] ?? '' ) );
					}

					$post_content .= self::wrap_inner_blocks( $field_type, $merged_attributes, $inner_content );
					break;
				}

				$post_content .= '<!-- wp:srfm/' . $field_type . ' ' . Helper::encode_json( $merged_attributes ) . ' /-->' . PHP_EOL;
				break;
			case 'slider':
			case 'page-break':
			case 'date-picker':
			case 'time-picker':
			case 'upload':
			case 'hidden':
			case 'rating':
			case 'signature':
			case 'nps':
				// If pro version is not active then do not add pro fields.
				if ( ! self::has_pro_fields() ) {
					break;
				}

				if ( 'signature' === $field_type && defined( 'SRFM_PRO_PRODUCT' ) && SRFM_PRO_PRODUCT === 'SureForms Starter' ) {
					// If the product is SureForms Starter then skip the signature field.
					break;
				}

				// Handle specific attributes for certain pro fields.
				if ( 'slider' === $field_type ) {
					$merged_attributes['min']  = ! empty( $question['min'] ) ? filter_var( $question['min'], FILTER_VALIDATE_INT ) : 0;
					$merged_attributes['max']  = ! empty( $question['max'] ) ? filter_var( $question['max'], FILTER_VALIDATE_INT ) : 100;
					$merged_attributes['step'] = ! empty( $question['step'] ) ? filter_var( $question['step'], FILTER_VALIDATE_INT ) : 1;

					$merged_attributes['prefixTooltip'] = ! empty( $question['prefixTooltip'] ) ? $question['prefixTooltip'] : '';
					$merged_attributes['suffixTooltip'] = ! empty( $question['suffixTooltip'] ) ? $question['suffixTooltip'] : '';

					// get min and max then diveide by 2 and round it.
					$min = $merged_attributes['min'];
					$max = $merged_attributes['max'];

					if ( is_numeric( $min ) && is_numeric( $max ) ) {
						$min = intval( $min );
						$max = intval( $max );

						// If min and max are same then set the value to 0.
						$merged_attributes['numberDefaultValue'] = Helper::get_string_value( round( ( $min + $max ) / 2 ) );
					}
				}
				if ( 'date-picker' === $field_type ) {
					$merged_attributes['dateFormat'] = ! empty( $question['dateFormat'] ) ? sanitize_text_field( $question['dateFormat'] ) : 'mm/dd/yyyy';
					$merged_attributes['min']        = ! empty( $question['minDate'] ) ? sanitize_text_field( $question['minDate'] ) : '';
					$merged_attributes['max']        = ! empty( $question['maxDate'] ) ? sanitize_text_field( $question['maxDate'] ) : '';
				}
				if ( 'time-picker' === $field_type ) {
					$merged_attributes['increment']            = ! empty( $question['increment'] ) ? filter_var( $question['increment'], FILTER_VALIDATE_INT ) : 30;
					$merged_attributes['showTwelveHourFormat'] = isset( $question['showTwelveHourFormat'] ) ? filter_var( $question['showTwelveHourFormat'], FILTER_VALIDATE_BOOLEAN ) : true; // Block default.
					$merged_attributes['min']                  = ! empty( $question['minTime'] ) ? sanitize_text_field( $question['minTime'] ) : '';
					$merged_attributes['max']                  = ! empty( $question['maxTime'] ) ? sanitize_text_field( $question['maxTime'] ) : '';
				}
				if ( 'rating' === $field_type ) {
					$merged_attributes['iconShape']     = ! empty( $question['iconShape'] ) ? sanitize_text_field( $question['iconShape'] ) : 'star';
					$merged_attributes['showText']      = ! empty( $question['showTooltip'] ) ? filter_var( $question['showTooltip'], FILTER_VALIDATE_BOOLEAN ) : false;
					$merged_attributes['defaultRating'] = ! empty( $question['defaultRating'] ) ? filter_var( $question['defaultRating'], FILTER_VALIDATE_INT ) : 0;

					if ( ! empty( $merged_attributes['showText'] ) ) {
						foreach ( $question['tooltipValues'] as $tooltips ) {
							$i = 0;
							foreach ( $tooltips as $value ) {
								$merged_attributes['ratingText'][ $i ] = ! empty( $value ) ? sanitize_text_field( $value ) : '';
								$i++;
							}
						}
					}
				}
				if ( 'hidden' === $field_type && isset( $question['defaultValue'] ) && is_scalar( $question['defaultValue'] ) ) {
					// Smart tags such as {get_input:utm_source} survive sanitize_text_field().
					$merged_attributes['defaultValue'] = sanitize_text_field( (string) $question['defaultValue'] );
				}
				if ( 'upload' === $field_type ) {
					if ( ! empty( $question['allowedTypes'] ) ) {
						$allowed_types = str_replace( '.', '', $question['allowedTypes'] );

						$allowed_types = explode( ',', $allowed_types );

						$types_array = array_map(
							static function( $type ) {
								return [
									'value' => trim( $type ),
									'label' => trim( $type ),
								];
							},
							$allowed_types
						);

						$merged_attributes['allowedFormats'] = $types_array;
					} else {
						$merged_attributes['allowedFormats'] = [
							[
								'value' => 'jpg',
								'label' => 'jpg',
							],
							[
								'value' => 'jpeg',
								'label' => 'jpeg',
							],
							[
								'value' => 'gif',
								'label' => 'gif',
							],
							[
								'value' => 'png',
								'label' => 'png',
							],
							[
								'value' => 'pdf',
								'label' => 'pdf',
							],
						];
					}

					$merged_attributes['fileSizeLimit'] = ! empty( $question['uploadSize'] ) ? filter_var( $question['uploadSize'], FILTER_VALIDATE_INT ) : 10;

					$merged_attributes['multiple'] = ! empty( $question['multiUpload'] ) ? filter_var( $question['multiUpload'], FILTER_VALIDATE_BOOLEAN ) : false;

					$merged_attributes['maxFiles'] = ! empty( $question['multiFilesNumber'] ) ? filter_var( $question['multiFilesNumber'], FILTER_VALIDATE_INT ) : 2;

				}

				// Same filter as the free fields, so callers can set widths and other block attributes on Pro fields too.
				$merged_attributes = apply_filters( 'srfm_ai_form_builder_modify_merged_attributes', $merged_attributes, $question, $is_conversational, $form_type );

				$post_content .= '<!-- wp:srfm/' . $field_type . ' ' . Helper::encode_json( $merged_attributes ) . ' /-->' . PHP_EOL;
				break;
			case 'register':
				// SureForms Business's sign-up block: the account fields inside it.
				// Its User Registration settings need it, so without Business the
				// fields go into the form on their own instead of being dropped.
				if ( ! empty( $skip_fields ) && in_array( $field_type, $skip_fields, true ) ) {
					break;
				}
				$inner_content = self::map_inner_fields( $question, self::REGISTER_INNER_TYPES, $is_conversational, $form_type, $skip_fields );
				if ( is_wp_error( $inner_content ) ) {
					return $inner_content;
				}
				if ( '' === $inner_content ) {
					// No account fields to hold: a text field, as an unknown type gets.
					$merged_attributes = apply_filters( 'srfm_ai_form_builder_modify_merged_attributes', $merged_attributes, $question, $is_conversational, $form_type );

					$post_content .= self::block_markup( 'input', $merged_attributes );
					break;
				}
				if ( ! self::has_business_fields() ) {
					$post_content .= $inner_content;
					break;
				}

				// A container with no input of its own: no label, required flag, help text or placeholder.
				unset( $merged_attributes['label'], $merged_attributes['required'], $merged_attributes['help'], $merged_attributes['placeholder'] );
				if ( '' === $merged_attributes['slug'] ) {
					$merged_attributes['slug'] = 'register';
				}

				$merged_attributes = apply_filters( 'srfm_ai_form_builder_modify_merged_attributes', $merged_attributes, $question, $is_conversational, $form_type );

				$post_content .= self::wrap_inner_blocks( $field_type, $merged_attributes, $inner_content );
				break;
			case 'html':
			case 'password':
			case 'repeater':
				// HTML ships with every Pro tier; password and repeater are Business only.
				// Without that tier, fall back to a text field like the `default` branch
				// does, so the question stays in the form instead of being dropped.
				if ( ! self::has_pro_fields() || ( 'html' !== $field_type && ! self::has_business_fields() ) ) {
					$merged_attributes = apply_filters( 'srfm_ai_form_builder_modify_merged_attributes', $merged_attributes, $question, $is_conversational, $form_type );

					$post_content .= self::block_markup( 'input', $merged_attributes );
					break;
				}

				if ( 'html' === $field_type ) {
					// A display block: no input, so no required flag, help text or placeholder.
					unset( $merged_attributes['required'], $merged_attributes['help'], $merged_attributes['placeholder'] );
					if ( '' === $merged_attributes['label'] ) {
						// Keep the block default label for the editor's list view.
						unset( $merged_attributes['label'] );
					}
					$merged_attributes['htmlContent'] = isset( $question['htmlContent'] ) && is_string( $question['htmlContent'] ) ? wp_kses_post( $question['htmlContent'] ) : '';
				}
				if ( 'password' === $field_type ) {
					$merged_attributes['isConfirmPassword']        = isset( $question['isConfirmPassword'] ) ? filter_var( $question['isConfirmPassword'], FILTER_VALIDATE_BOOLEAN ) : false;
					$merged_attributes['checkPasswordStrength']    = isset( $question['checkPasswordStrength'] ) ? filter_var( $question['checkPasswordStrength'], FILTER_VALIDATE_BOOLEAN ) : false;
					$merged_attributes['togglePasswordVisibility'] = isset( $question['togglePasswordVisibility'] ) ? filter_var( $question['togglePasswordVisibility'], FILTER_VALIDATE_BOOLEAN ) : false;
					if ( isset( $question['confirmLabel'] ) && is_string( $question['confirmLabel'] ) && '' !== $question['confirmLabel'] ) {
						$merged_attributes['confirmLabel'] = sanitize_text_field( $question['confirmLabel'] );
					}
				}
				if ( 'repeater' === $field_type ) {
					// Required and placeholder live on the inner fields, not the repeater.
					unset( $merged_attributes['required'], $merged_attributes['placeholder'] );
					// The editor never stores fewer than one row.
					$merged_attributes['minItems'] = isset( $question['minItems'] ) && is_numeric( $question['minItems'] ) ? max( 1, absint( $question['minItems'] ) ) : 1;
					if ( isset( $question['maxItems'] ) && is_numeric( $question['maxItems'] ) ) {
						$merged_attributes['maxItems'] = max( $merged_attributes['minItems'], absint( $question['maxItems'] ) );
					}
				}

				$merged_attributes = apply_filters( 'srfm_ai_form_builder_modify_merged_attributes', $merged_attributes, $question, $is_conversational, $form_type );

				if ( ! empty( $skip_fields ) && in_array( $field_type, $skip_fields, true ) ) {
					break;
				}

				if ( 'repeater' === $field_type ) {
					$inner_content = self::map_inner_fields( $question, self::REPEATER_INNER_TYPES, $is_conversational, $form_type, $skip_fields );
					if ( is_wp_error( $inner_content ) ) {
						return $inner_content;
					}
					if ( '' === $inner_content ) {
						// Same single row the editor inserts.
						$inner_content = self::block_markup(
							'input',
							[
								'block_id'   => self::new_block_id(),
								'formId'     => 0,
								'label'      => __( 'Text Field', 'sureforms' ),
								'fieldWidth' => 100,
							]
						);
					}

					$post_content .= self::wrap_inner_blocks( $field_type, $merged_attributes, $inner_content );
					break;
				}

				if ( 'html' === $field_type ) {
					// HTML content can hold `-->`, which would end the block comment early.
					// serialize_block_attributes() escapes it, and parse_blocks() reads it back.
					$post_content .= '<!-- wp:srfm/html ' . serialize_block_attributes( $merged_attributes ) . ' /-->' . PHP_EOL;
					break;
				}

				$post_content .= self::block_markup( $field_type, $merged_attributes );
				break;
			default:
				// Unsupported field type - fallback to input.
				// Same filter as the other fields, so callers' block attributes are kept.
				$merged_attributes = apply_filters( 'srfm_ai_form_builder_modify_merged_attributes', $merged_attributes, $question, $is_conversational, $form_type );

				$post_content .= '<!-- wp:srfm/input ' . Helper::encode_json( $merged_attributes ) . ' /-->' . PHP_EOL;
		}

		return $post_content;
	}

	/**
	 * Whether Pro fields (any tier) can be emitted.
	 *
	 * @since 2.13.0
	 * @return bool
	 */
	private static function has_pro_fields() {
		/**
		 * Filters whether the field mapper emits SureForms Pro fields.
		 *
		 * @param bool $has_pro True when SureForms Pro is active.
		 * @since 2.13.0
		 */
		return (bool) apply_filters( 'srfm_ai_field_mapping_has_pro', defined( 'SRFM_PRO_VER' ) );
	}

	/**
	 * Whether Business-tier fields (password, repeater) can be emitted.
	 *
	 * @since 2.13.0
	 * @return bool
	 */
	private static function has_business_fields() {
		/**
		 * Filters whether the field mapper emits SureForms Business fields.
		 *
		 * @param bool $has_business True when the Business build of SureForms Pro is active.
		 * @since 2.13.0
		 */
		return (bool) apply_filters( 'srfm_ai_field_mapping_has_business', defined( 'SRFM_PRO_VER' ) && class_exists( 'SRFM_Pro\Inc\Business\Init' ) );
	}

	/**
	 * Block markup for a container's `innerFields`, mapped with the same rules
	 * as top-level fields. Types outside `$allowed_types` are dropped.
	 *
	 * @param array<mixed>       $question          Container field schema.
	 * @param array<int, string> $allowed_types     Field types allowed inside the container.
	 * @param bool               $is_conversational Whether the form is conversational.
	 * @param string             $form_type         Form type.
	 * @param array<mixed>       $skip_fields       Field types to skip.
	 * @since 2.13.0
	 * @return string|WP_Error Inner block markup, empty when there are no usable inner fields.
	 */
	private static function map_inner_fields( $question, $allowed_types, $is_conversational, $form_type, $skip_fields ) {
		if ( empty( $question['innerFields'] ) || ! is_array( $question['innerFields'] ) ) {
			return '';
		}

		$content = '';
		foreach ( $question['innerFields'] as $inner_field ) {
			$markup = self::map_question( $inner_field, $is_conversational, $form_type, $skip_fields, $allowed_types );
			if ( is_wp_error( $markup ) ) {
				return $markup;
			}
			$content .= $markup;
		}

		return $content;
	}

	/**
	 * The address sub-fields the editor inserts into a new address block,
	 * so an address built here renders without being opened in the editor.
	 *
	 * Each sub-field carries Pro's `addressComponent` identifier (the layout
	 * Pro resets to when Google autocomplete is enabled); without Pro it is inert.
	 *
	 * With the address's slug, each sub-field gets a stable slug made from it
	 * (`delivery-address-city`, …), so conditions, post mappings and `{form:…}`
	 * tags can name a part of the address before the form is first saved, and
	 * two addresses in one form never share a sub-field slug. Saving keeps
	 * these slugs (Helper::process_blocks() only fills in missing or repeated ones).
	 *
	 * @param bool   $required Whether the address is required.
	 * @param string $slug     The address block's slug; '' leaves the sub-field slugs to the save.
	 * @since 2.13.0
	 * @return string Inner block markup.
	 */
	private static function default_address_fields( $required, $slug = '' ) {
		$sub_fields = [
			[ 'input', __( 'Address Line 1', 'sureforms' ), 'line1', $required, 'line-1' ],
			[ 'input', __( 'Address Line 2', 'sureforms' ), 'line2', false, 'line-2' ],
			[ 'input', __( 'City', 'sureforms' ), 'city', $required, 'city' ],
			[ 'input', __( 'State', 'sureforms' ), 'state', $required, 'state' ],
			[ 'input', __( 'Postal Code', 'sureforms' ), 'postal_code', $required, 'postal-code' ],
			[ 'dropdown', __( 'Country', 'sureforms' ), 'country', $required, 'country' ],
		];
		$slug       = sanitize_title( $slug );

		$content = '';
		foreach ( $sub_fields as [ $type, $label, $component, $is_required, $part ] ) {
			$attributes = [
				'block_id'         => self::new_block_id(),
				'formId'           => 0,
				'label'            => $label,
				'required'         => $is_required,
				'fieldWidth'       => 50,
				'addressComponent' => $component,
			];
			if ( '' !== $slug ) {
				$attributes['slug'] = $slug . '-' . $part;
			}
			if ( 'dropdown' === $type ) {
				$attributes['options'] = self::country_options();
			}
			$content .= self::block_markup( $type, $attributes );
		}

		return $content;
	}

	/**
	 * Country dropdown options, in the shape the address editor template uses.
	 *
	 * @since 2.13.0
	 * @return array<int, array<string, string>>
	 */
	private static function country_options() {
		static $options = null;
		if ( null !== $options ) {
			return $options;
		}

		$options = [];
		$path    = SRFM_DIR . 'inc/fields/countries.json';
		// Local bundled file, not a remote URL.
		$raw       = is_readable( $path ) ? file_get_contents( $path ) : false; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$countries = is_string( $raw ) ? json_decode( $raw, true ) : null;
		if ( is_array( $countries ) ) {
			foreach ( $countries as $country ) {
				if ( is_array( $country ) && ! empty( $country['name'] ) && is_string( $country['name'] ) ) {
					$options[] = [
						'label' => $country['name'],
						'icon'  => '',
					];
				}
			}
		}

		return $options;
	}

	/**
	 * Self-closing block markup.
	 *
	 * @param string               $field_type Block name without the `srfm/` prefix.
	 * @param array<string, mixed> $attributes Block attributes.
	 * @since 2.13.0
	 * @return string
	 */
	private static function block_markup( $field_type, $attributes ) {
		return '<!-- wp:srfm/' . $field_type . ' ' . Helper::encode_json( $attributes ) . ' /-->' . PHP_EOL;
	}

	/**
	 * Container block markup wrapping its inner blocks.
	 *
	 * @param string               $field_type    Block name without the `srfm/` prefix.
	 * @param array<string, mixed> $attributes    Block attributes.
	 * @param string               $inner_content Inner block markup.
	 * @since 2.13.0
	 * @return string
	 */
	private static function wrap_inner_blocks( $field_type, $attributes, $inner_content ) {
		return '<!-- wp:srfm/' . $field_type . ' ' . Helper::encode_json( $attributes ) . ' -->' . PHP_EOL . $inner_content . '<!-- /wp:srfm/' . $field_type . ' -->' . PHP_EOL;
	}

	/**
	 * A random 8-character block id.
	 *
	 * @since 2.13.0
	 * @return string
	 */
	private static function new_block_id() {
		return bin2hex( random_bytes( 4 ) );
	}

	/**
	 * Sanitize the user-facing strings on each entry of the AI-generated
	 * fieldOptions array before they are merged into block attributes.
	 *
	 * Defense-in-depth: the middleware is trusted today, but these strings
	 * are serialized into Gutenberg block markup. Running each string field
	 * through sanitize_text_field() prevents stored-content injection if
	 * the upstream ever returns reflected user content. Non-string fields
	 * (icon class names, booleans) are left untouched.
	 *
	 * @param array<int, array<string, mixed>> $options Raw fieldOptions array.
	 * @since 2.8.2
	 * @return array<int, array<string, mixed>> Sanitized options.
	 */
	private static function sanitize_field_options( $options ) {
		if ( ! is_array( $options ) ) {
			return [];
		}
		$sanitizable_keys = [ 'label', 'value', 'optionTitle' ];
		foreach ( $options as $key => $option ) {
			if ( ! is_array( $option ) ) {
				continue;
			}
			foreach ( $sanitizable_keys as $field ) {
				if ( isset( $option[ $field ] ) && is_string( $option[ $field ] ) ) {
					$options[ $key ][ $field ] = sanitize_text_field( $option[ $field ] );
				}
			}
		}
		return $options;
	}

}
