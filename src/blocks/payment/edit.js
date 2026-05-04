/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { SelectControl } from '@wordpress/components';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { useEffect, useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import SRFMTextControl from '@Components/text-control';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { PaymentComponent } from './components/default.js';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import { FieldsPreview } from '../FieldsPreview.jsx';
import ConditionalLogic from '@Components/conditional-logic';
import { attributeOptionsWithFilter, afterAttributePanelBody } from '@Components/hooks';
import Separator from '@Components/separator';
import MultiButtonsControl from '@Components/multi-buttons-control';
import BillingCyclesControl from './components/billing-cycles-control.js';
// BOTH MODE: "both" mode reuses the same single-select controls as subscription mode.

const Edit = ( props ) => {
	const { clientId, attributes, setAttributes, isSelected } = props;
	const {
		help,
		block_id,
		formId,
		preview,
		className,
		paymentType,
		subscriptionPlan,
		amountType,
		fixedAmount,
		minimumAmount,
		customerNameField,
		customerEmailField,
		variableAmountField,
		paymentDescription,
		// BOTH MODE: start — new attributes for "both" payment type
		oneTimeLabel,
		subscriptionLabel,
		defaultPaymentChoice,
		oneTimeAmountType,
		oneTimeFixedAmount,
		oneTimeMinimumAmount,
		oneTimeVariableAmountField,
		subscriptionAmountType,
		subscriptionFixedAmount,
		subscriptionMinimumAmount,
		subscriptionVariableAmountField,
		// BOTH MODE: end
	} = attributes;
	const currentFormId = useGetCurrentFormId( clientId );
	const [ availableFormFields, setAvailableFormFields ] = useState( {
		emailsFields: [],
		nameFields: [],
		variableAmountFields: [],
	} );
	const blockProps = useBlockProps( {
		className,
	} );

	// Get all blocks from the current form
	const { getBlocks } = useSelect(
		( select ) => select( 'core/block-editor' ),
		[]
	);

	// Function to extract and filter form fields by type
	const extractFormFields = () => {
		if ( ! currentFormId ) {
			return {
				emailsFields: [],
				nameFields: [],
				variableAmountFields: [],
			};
		}

		try {
			const blocks = getBlocks();
			const emailsFields = [];
			const nameFields = [];
			const variableAmountFields = [];

			blocks.forEach( ( block ) => {
				// Check if block has a slug (is a form field)
				if ( block.attributes?.slug ) {
					const slug = block.attributes.slug;
					const label =
						block.attributes.label ||
						block.name ||
						__( 'Form Field', 'sureforms' );
					const blockName = block.name;

					// Filter email fields - only srfm/email
					if ( blockName === 'srfm/email' ) {
						emailsFields.push( {
							slug,
							label: `${ label } (email)`,
							type: 'email',
						} );
					} else if ( blockName === 'srfm/input' ) {
						nameFields.push( {
							slug,
							label: `${ label } (input)`,
							type: 'input',
						} );
					} else if ( blockName === 'srfm/number' ) {
						variableAmountFields.push( {
							slug,
							label: `${ label } (number)`,
							type: 'number',
						} );
					} else if ( blockName === 'srfm/dropdown' ) {
						variableAmountFields.push( {
							slug,
							label: `${ label } (dropdown)`,
							type: 'dropdown',
						} );
					} else if ( blockName === 'srfm/multi-choice' ) {
						variableAmountFields.push( {
							slug,
							label: `${ label } (multi-choice)`,
							type: 'multi-choice',
						} );
					} else if ( blockName === 'srfm/hidden' ) {
						variableAmountFields.push( {
							slug,
							label: `${ label } (hidden)`,
							type: 'hidden',
						} );
					}
				}
			} );

			return { emailsFields, nameFields, variableAmountFields };
		} catch ( error ) {
			console.error( 'Error extracting form fields:', error );
			return {
				emailsFields: [],
				nameFields: [],
				variableAmountFields: [],
			};
		}
	};

	// Update available fields when the form context or selection changes.
	// Depending on currentFormId ensures the field list refreshes when the
	// editor switches between forms; the previous length-guard workaround
	// hid a stale-closure bug because the effect didn't react to formId changes.
	useEffect( () => {
		const { emailsFields, nameFields, variableAmountFields } =
			extractFormFields();
		setAvailableFormFields( {
			emailsFields,
			nameFields,
			variableAmountFields,
		} );
		// extractFormFields is declared inline and closes over the latest
		// getBlocks/currentFormId — safe to omit from deps.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ isSelected, currentFormId ] );

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );

	// show the block preview on hover
	if ( preview ) {
		const fieldName = srfm_fields_preview.payment_preview;
		return <FieldsPreview fieldName={ fieldName } />;
	}

	const attributeOptions = [
		{
			id: 'help-text',
			component: (
				<SRFMTextControl
					label={ __( 'Help Text', 'sureforms' ) }
					value={ help }
					data={ {
						value: help,
						label: 'help',
					} }
					onChange={ ( value ) => setAttributes( { help: value } ) }
				/>
			),
		},
		{
			id: 'payment-description',
			component: (
				<SRFMTextControl
					label={ __( 'Payment Description', 'sureforms' ) }
					value={ paymentDescription }
					data={ {
						value: paymentDescription,
						label: 'paymentDescription',
					} }
					onChange={ ( value ) =>
						setAttributes( { paymentDescription: value } )
					}
					help={ __(
						'Shown on payment receipts and in your payment dashboard (Stripe and PayPal). Leave blank to use the default.',
						'sureforms'
					) }
				/>
			),
		},
		{
			id: 'separator-3',
			component: <Separator />,
		},
		{
			id: 'payment-type',
			component: (
				<MultiButtonsControl
					setAttributes={ setAttributes }
					label={ __( 'Payment Type', 'sureforms' ) }
					data={ {
						value: paymentType,
						label: 'paymentType',
					} }
					options={ [
						{
							value: 'one-time',
							label: __( 'One Time', 'sureforms' ),
						},
						{
							value: 'subscription',
							label: __( 'Subscription', 'sureforms' ),
						},
						// BOTH MODE: start — new "Both" option
						{
							value: 'both',
							label: __( 'Both', 'sureforms' ),
						},
						// BOTH MODE: end
					] }
					showIcons={ false }
				/>
			),
		},
		...( paymentType === 'subscription'
			? [
				{
					id: 'subscription-plan-name',
					component: (
						<SRFMTextControl
							label={ __(
								'Subscription Plan Name',
								'sureforms'
							) }
							value={ subscriptionPlan?.name || '' }
							data={ {
								value:
										subscriptionPlan?.name ||
										'Subscription Plan',
								label: 'subscription-plan-name',
							} }
							onChange={ ( value ) => {
								setAttributes( {
									subscriptionPlan: {
										...( subscriptionPlan || {} ),
										name: value,
									},
								} );
							} }
							allowReset={ false }
						/>
					),
				},
				{
					id: 'subscription-interval',
					component: (
						<SelectControl
							__next40pxDefaultSize
							label={ __( 'Billing Interval', 'sureforms' ) }
							value={ subscriptionPlan?.interval || 'month' }
							options={ [
								{
									label: __( 'Daily', 'sureforms' ),
									value: 'day',
								},
								{
									label: __( 'Weekly', 'sureforms' ),
									value: 'week',
								},
								{
									label: __( 'Monthly', 'sureforms' ),
									value: 'month',
								},
								{
									label: __( 'Quarterly', 'sureforms' ),
									value: 'quarter',
								},
								{
									label: __( 'Yearly', 'sureforms' ),
									value: 'year',
								},
							] }
							onChange={ ( value ) => {
								setAttributes( {
									subscriptionPlan: {
										...( subscriptionPlan || {} ),
										interval: value,
									},
								} );
							} }
						/>
					),
				},
				{
					id: 'billing-cycles',
					component: (
						<BillingCyclesControl
							subscriptionPlan={ subscriptionPlan }
							setAttributes={ setAttributes }
						/>
					),
				},
			  ]
			: [] ),
		// BOTH MODE: start — entire settings block for "both" payment type
		...( paymentType === 'both'
			? [
				{
					id: 'both-one-time-label',
					component: (
						<SRFMTextControl
							label={ __( 'One-Time Label', 'sureforms' ) }
							value={ oneTimeLabel }
							data={ {
								value: oneTimeLabel,
								label: 'oneTimeLabel',
							} }
							onChange={ ( value ) =>
								setAttributes( { oneTimeLabel: value } )
							}
							help={ __(
								'Label shown to users for the one-time payment option.',
								'sureforms'
							) }
						/>
					),
				},
				{
					id: 'both-subscription-label',
					component: (
						<SRFMTextControl
							label={ __( 'Subscription Label', 'sureforms' ) }
							value={ subscriptionLabel }
							data={ {
								value: subscriptionLabel,
								label: 'subscriptionLabel',
							} }
							onChange={ ( value ) =>
								setAttributes( { subscriptionLabel: value } )
							}
							help={ __(
								'Label shown to users for the subscription option.',
								'sureforms'
							) }
						/>
					),
				},
				{
					id: 'both-default-choice',
					component: (
						<MultiButtonsControl
							setAttributes={ setAttributes }
							label={ __( 'Default Selection', 'sureforms' ) }
							data={ {
								value: defaultPaymentChoice,
								label: 'defaultPaymentChoice',
							} }
							options={ [
								{
									value: 'one-time',
									label: __( 'One Time', 'sureforms' ),
								},
								{
									value: 'subscription',
									label: __( 'Subscription', 'sureforms' ),
								},
							] }
							showIcons={ false }
							help={ __(
								'Which option is pre-selected when the form loads.',
								'sureforms'
							) }
						/>
					),
				},
				{
					id: 'both-separator-one-time',
					component: <Separator />,
				},
				{
					id: 'both-one-time-amount-type',
					component: (
						<MultiButtonsControl
							setAttributes={ setAttributes }
							label={ __( 'One-Time Amount Type', 'sureforms' ) }
							data={ {
								value: oneTimeAmountType,
								label: 'oneTimeAmountType',
							} }
							options={ [
								{
									value: 'fixed',
									label: __( 'Fixed Amount', 'sureforms' ),
								},
								{
									value: 'variable',
									label: __( 'Dynamic Amount', 'sureforms' ),
								},
							] }
							showIcons={ false }
							help={ __(
								'Set how the one-time payment amount is determined.',
								'sureforms'
							) }
						/>
					),
				},
				...( oneTimeAmountType === 'fixed'
					? [
						{
							id: 'both-one-time-fixed-amount',
							component: (
								<SRFMTextControl
									label={ __(
										'One-Time Fixed Amount',
										'sureforms'
									) }
									type="number"
									value={ oneTimeFixedAmount }
									data={ {
										value: oneTimeFixedAmount,
										label: 'oneTimeFixedAmount',
									} }
									onChange={ ( value ) =>
										setAttributes( {
											oneTimeFixedAmount:
													parseFloat( value ) || 0,
										} )
									}
									help={ __(
										'Amount charged for a one-time payment.',
										'sureforms'
									) }
								/>
							),
						},
					  ]
					: [] ),
				...( oneTimeAmountType === 'variable'
					? [
						{
							id: 'both-one-time-variable-field',
							component: (
								<SelectControl
									label={ __(
										'One-Time Amount Field',
										'sureforms'
									) }
									value={ oneTimeVariableAmountField || '' }
									options={ [
										{
											label: __(
												'Select a field…',
												'sureforms'
											),
											value: '',
										},
										...(
											availableFormFields?.variableAmountFields ||
												[]
										).map( ( field ) => ( {
											label: field.label,
											value: field.slug,
										} ) ),
									] }
									onChange={ ( value ) => {
										setAttributes( {
											oneTimeVariableAmountField: value,
										} );
									} }
									help={ __(
										'Pick a form field whose value determines the one-time payment amount.',
										'sureforms'
									) }
								/>
							),
						},
						{
							id: 'both-one-time-minimum-amount',
							component: (
								<SRFMTextControl
									label={ __(
										'One-Time Minimum Amount',
										'sureforms'
									) }
									type="number"
									value={ oneTimeMinimumAmount }
									data={ {
										value: oneTimeMinimumAmount,
										label: 'oneTimeMinimumAmount',
									} }
									onChange={ ( value ) =>
										setAttributes( {
											oneTimeMinimumAmount:
													parseFloat( value ) || 0,
										} )
									}
									help={ __(
										'Minimum amount users can enter for one-time payment (0 for no minimum).',
										'sureforms'
									) }
								/>
							),
						},
					  ]
					: [] ),
				{
					id: 'both-separator-subscription',
					component: <Separator />,
				},
				{
					id: 'both-subscription-amount-type',
					component: (
						<MultiButtonsControl
							setAttributes={ setAttributes }
							label={ __(
								'Subscription Amount Type',
								'sureforms'
							) }
							data={ {
								value: subscriptionAmountType,
								label: 'subscriptionAmountType',
							} }
							options={ [
								{
									value: 'fixed',
									label: __( 'Fixed Amount', 'sureforms' ),
								},
								{
									value: 'variable',
									label: __( 'Dynamic Amount', 'sureforms' ),
								},
							] }
							showIcons={ false }
							help={ __(
								'Set how the subscription amount is determined.',
								'sureforms'
							) }
						/>
					),
				},
				...( subscriptionAmountType === 'fixed'
					? [
						{
							id: 'both-subscription-fixed-amount',
							component: (
								<SRFMTextControl
									label={ __(
										'Subscription Fixed Amount',
										'sureforms'
									) }
									type="number"
									value={ subscriptionFixedAmount }
									data={ {
										value: subscriptionFixedAmount,
										label: 'subscriptionFixedAmount',
									} }
									onChange={ ( value ) =>
										setAttributes( {
											subscriptionFixedAmount:
													parseFloat( value ) || 0,
										} )
									}
									help={ __(
										'Recurring amount charged per billing interval.',
										'sureforms'
									) }
								/>
							),
						},
					  ]
					: [] ),
				...( subscriptionAmountType === 'variable'
					? [
						{
							id: 'both-subscription-variable-field',
							component: (
								<SelectControl
									label={ __(
										'Subscription Amount Field',
										'sureforms'
									) }
									value={
										subscriptionVariableAmountField || ''
									}
									options={ [
										{
											label: __(
												'Select a field…',
												'sureforms'
											),
											value: '',
										},
										...(
											availableFormFields?.variableAmountFields ||
												[]
										).map( ( field ) => ( {
											label: field.label,
											value: field.slug,
										} ) ),
									] }
									onChange={ ( value ) => {
										setAttributes( {
											subscriptionVariableAmountField:
													value,
										} );
									} }
									help={ __(
										'Pick a form field whose value determines the subscription amount.',
										'sureforms'
									) }
								/>
							),
						},
						{
							id: 'both-subscription-minimum-amount',
							component: (
								<SRFMTextControl
									label={ __(
										'Subscription Minimum Amount',
										'sureforms'
									) }
									type="number"
									value={ subscriptionMinimumAmount }
									data={ {
										value: subscriptionMinimumAmount,
										label: 'subscriptionMinimumAmount',
									} }
									onChange={ ( value ) =>
										setAttributes( {
											subscriptionMinimumAmount:
													parseFloat( value ) || 0,
										} )
									}
									help={ __(
										'Minimum amount users can enter for subscription (0 for no minimum).',
										'sureforms'
									) }
								/>
							),
						},
					  ]
					: [] ),
				{
					id: 'both-separator-plan',
					component: <Separator />,
				},
				{
					id: 'both-subscription-plan-name',
					component: (
						<SRFMTextControl
							label={ __(
								'Subscription Plan Name',
								'sureforms'
							) }
							value={ subscriptionPlan?.name || '' }
							data={ {
								value:
										subscriptionPlan?.name ||
										'Subscription Plan',
								label: 'subscription-plan-name',
							} }
							onChange={ ( value ) => {
								setAttributes( {
									subscriptionPlan: {
										...( subscriptionPlan || {} ),
										name: value,
									},
								} );
							} }
							allowReset={ false }
						/>
					),
				},
				{
					id: 'both-subscription-interval',
					component: (
						// BOTH MODE: single-select, same as subscription mode.
						<SelectControl
							label={ __( 'Billing Interval', 'sureforms' ) }
							value={ subscriptionPlan?.interval || 'month' }
							options={ [
								{ label: __( 'Daily', 'sureforms' ), value: 'day' },
								{ label: __( 'Weekly', 'sureforms' ), value: 'week' },
								{ label: __( 'Monthly', 'sureforms' ), value: 'month' },
								{ label: __( 'Quarterly', 'sureforms' ), value: 'quarter' },
								{ label: __( 'Yearly', 'sureforms' ), value: 'year' },
							] }
							onChange={ ( value ) => {
								setAttributes( {
									subscriptionPlan: {
										...( subscriptionPlan || {} ),
										interval: value,
									},
								} );
							} }
						/>
					),
				},
				{
					id: 'both-billing-cycles',
					component: (
						// BOTH MODE: single-select, same as subscription mode.
						<BillingCyclesControl
							subscriptionPlan={ subscriptionPlan }
							setAttributes={ setAttributes }
						/>
					),
				},
			  ]
			: [] ),
		// BOTH MODE: end
		// BOTH MODE: shared amount section hidden when paymentType === 'both'
		...( paymentType !== 'both'
			? [
				{
					id: 'separator',
					component: <Separator />,
				},
				{
					id: 'amount-type',
					component: (
						<MultiButtonsControl
							setAttributes={ setAttributes }
							label={ __( 'Amount Type', 'sureforms' ) }
							data={ {
								value: amountType,
								label: 'amountType',
							} }
							options={ [
								{
									value: 'fixed',
									label: __( 'Fixed Amount', 'sureforms' ),
								},
								{
									value: 'variable',
									label: __( 'Dynamic Amount', 'sureforms' ),
								},
							] }
							showIcons={ false }
							help={ __(
								'Choose whether to charge a fixed amount or charge the amount based on user input in other form fields.',
								'sureforms'
							) }
						/>
					),
				},
				...( amountType === 'fixed'
					? [
						{
							id: 'fixed-amount',
							component: (
								<SRFMTextControl
									label={ __( 'Fixed Amount', 'sureforms' ) }
									type="number"
									value={ fixedAmount }
									data={ {
										value: fixedAmount,
										label: 'fixedAmount',
									} }
									onChange={ ( value ) =>
										setAttributes( {
											fixedAmount: parseFloat( value ) || 0,
										} )
									}
									help={ __(
										'Set the exact amount you want to charge. Users won’t be able to change it',
										'sureforms'
									) }
								/>
							),
						},
					  ]
					: [] ),
				...( amountType === 'variable'
					? [
						{
							id: 'variable-amount-field',
							component: (
								<SelectControl
									__next40pxDefaultSize
									label={ __(
										'Choose Amount Field',
										'sureforms'
									) }
									value={ variableAmountField || '' }
									options={ [
										{
											label: __(
												'Select a field…',
												'sureforms'
											),
											value: '',
										},
										...(
											availableFormFields?.variableAmountFields ||
												[]
										).map( ( field ) => ( {
											label: field.label,
											value: field.slug,
										} ) ),
									] }
									onChange={ ( value ) => {
										setAttributes( {
											variableAmountField: value,
										} );
									} }
									help={ __(
										'Pick a field from your form like a number, dropdown, multichoice, or hidden whose value should decide the payment amount.',
										'sureforms'
									) }
								/>
							),
						},
						{
							id: 'minimum-amount',
							component: (
								<SRFMTextControl
									label={ __( 'Minimum Amount', 'sureforms' ) }
									type="number"
									value={ minimumAmount }
									data={ {
										value: minimumAmount,
										label: 'minimumAmount',
									} }
									onChange={ ( value ) =>
										setAttributes( {
											minimumAmount: parseFloat( value ) || 0,
										} )
									}
									help={ __(
										'Set the minimum amount users can enter (0 for no minimum)',
										'sureforms'
									) }
								/>
							),
						},
					  ]
					: [] ),
			  ]
			: [] ),
		// BOTH MODE: end of shared amount guard
		{
			id: 'separator-2',
			component: <Separator />,
		},
		{
			id: 'customer-name-field',
			component: (
				<SelectControl
					__next40pxDefaultSize
					label={
						// BOTH MODE: treat "both" like "subscription" for name requirement
						paymentType === 'subscription' ||
						paymentType === 'both'
							? __(
								'Customer Name Field (Required)',
								'sureforms'
							  )
							: __(
								'Customer Name Field (Optional)',
								'sureforms'
							  )
					}
					value={ customerNameField || '' }
					options={ [
						{
							label: __( 'Select a field…', 'sureforms' ),
							value: '',
						},
						...( availableFormFields?.nameFields || [] ).map(
							( field ) => ( {
								label: field.label,
								value: field.slug,
							} )
						),
					] }
					onChange={ ( value ) => {
						setAttributes( { customerNameField: value } );
					} }
					help={
						// BOTH MODE: treat "both" like "subscription" for help text
						paymentType === 'subscription' ||
						paymentType === 'both'
							? __(
								'Select the input field that contains the customer name (Required for subscriptions)',
								'sureforms'
							  )
							: __(
								'Select the input field that contains the customer name',
								'sureforms'
							  )
					}
				/>
			),
		},
		{
			id: 'customer-email-field',
			component: (
				<SelectControl
					__next40pxDefaultSize
					label={ __(
						'Customer Email Field (Required)',
						'sureforms'
					) }
					value={ customerEmailField || '' }
					options={ [
						{
							label: __( 'Select a field…', 'sureforms' ),
							value: '',
						},
						...( availableFormFields?.emailsFields || [] ).map(
							( field ) => ( {
								label: field.label,
								value: field.slug,
							} )
						),
					] }
					onChange={ ( value ) => {
						setAttributes( { customerEmailField: value } );
					} }
					help={ __(
						'Select the email field that contains the customer email',
						'sureforms'
					) }
				/>
			),
		},
	];

	const filterOptions = attributeOptionsWithFilter( attributeOptions, props );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<InspectorTabs
					tabs={ [ 'general', 'advance' ] }
					defaultTab={ 'general' }
				>
					<InspectorTab { ...SRFMTabs.general }>
						<SRFMAdvancedPanelBody
							title={ __( 'Attributes', 'sureforms' ) }
							initialOpen={ true }
						>
							{ filterOptions.map( ( option ) =>
								option?.component ? option.component : null
							) }
						</SRFMAdvancedPanelBody>
						{ afterAttributePanelBody( props ).map(
							( panel ) => panel.component
						) }
					</InspectorTab>
					<InspectorTab { ...SRFMTabs.advance }>
						<ConditionalLogic
							{ ...{ setAttributes, attributes } }
						/>
					</InspectorTab>
				</InspectorTabs>
			</InspectorControls>
			<>
				<PaymentComponent
					blockID={ block_id }
					setAttributes={ setAttributes }
					attributes={ attributes }
					availableFormFields={ availableFormFields }
				/>
				<div className="srfm-error-wrap"></div>
			</>
		</div>
	);
};

export default compose( AddInitialAttr )( Edit );
