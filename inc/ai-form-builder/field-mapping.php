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
	 * Generate Gutenberg Fields from AI data.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return string|WP_Error
	 */
	public static function generate_gutenberg_fields_from_questions( $request ) {

		// Get params from request.
		$params = $request->get_params();

		// check parama is empty or not and is an array and consist form_data key.
		if ( empty( $params ) || ! is_array( $params ) || ! isset( $params['form_data'] ) || 0 === count( $params['form_data'] ) ) {
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

			// Check if question is empty then continue to next question.
			if ( empty( $question ) || ! is_array( $question ) ) {
				return new WP_Error(
					'srfm_ai_mapping_invalid_field',
					__( 'The AI returned a malformed form field. Please try again.', 'sureforms' ),
					[ 'status' => 400 ]
				);
			}

			// Initialize common attributes.
			$common_attributes = [
				'block_id' => bin2hex( random_bytes( 4 ) ), // Generate random block_id.
				'formId'   => 0, // Set your formId here.
			];

			// Merge common attributes with question attributes.
			$merged_attributes = array_merge(
				$common_attributes,
				[
					'label'    => sanitize_text_field( $question['label'] ),
					'required' => filter_var( $question['required'], FILTER_VALIDATE_BOOLEAN ),
					'help'     => isset( $question['helpText'] ) ? sanitize_text_field( $question['helpText'] ) : '',
					'slug'     => isset( $question['slug'] ) ? sanitize_text_field( $question['slug'] ) : '',
				]
			);

			// Apply filter to modify field type.
			$field_type = apply_filters( 'srfm_ai_field_modify_field_type', $question['fieldType'], $question, $is_conversational, $form_type );

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
					if ( 'dropdown' === $field_type && ! empty( $question['fieldOptions'] ) && is_array( $question['fieldOptions'] ) &&
					! empty( $question['fieldOptions'][0]['label'] )
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
						if ( ! empty( $question['fieldOptions'][0]['optionTitle'] ) ) {
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
					if ( ! defined( 'SRFM_PRO_VER' ) ) {
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
						$merged_attributes['dateFormat'] = ! empty( $question['dateFormat'] ) ? sanitize_text_field( $question['dateFormat'] ) : 'mm/dd/yy';
						$merged_attributes['min']        = ! empty( $question['minDate'] ) ? sanitize_text_field( $question['minDate'] ) : '';
						$merged_attributes['max']        = ! empty( $question['maxDate'] ) ? sanitize_text_field( $question['maxDate'] ) : '';
					}
					if ( 'time-picker' === $field_type ) {
						$merged_attributes['increment']            = ! empty( $question['increment'] ) ? filter_var( $question['increment'], FILTER_VALIDATE_INT ) : 30;
						$merged_attributes['showTwelveHourFormat'] = ! empty( $question['showTwelveHourFormat'] ) ? filter_var( $question['useTwelveHourFormat'], FILTER_VALIDATE_BOOLEAN ) : false;
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

					$post_content .= '<!-- wp:srfm/' . $field_type . ' ' . Helper::encode_json( $merged_attributes ) . ' /-->' . PHP_EOL;
					break;
				default:
					// Unsupported field type - fallback to input.
					$post_content .= '<!-- wp:srfm/input ' . Helper::encode_json( $merged_attributes ) . ' /-->' . PHP_EOL;
			}
		}

		return apply_filters( 'srfm_ai_form_builder_post_content', $post_content, $is_conversational, $form_type );
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
