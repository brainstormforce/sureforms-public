/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToggleControl, SelectControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
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
import { useErrMessage } from '@Blocks/util';
import ConditionalLogic from '@Components/conditional-logic';
import { attributeOptionsWithFilter } from '@Components/hooks';
import Separator from '@Components/separator';
import MultiButtonsControl from '@Components/multi-buttons-control';

const Edit = ( props ) => {
	const { clientId, attributes, setAttributes, isSelected } = props;
	const {
		help,
		required,
		block_id,
		// description,
		errorMsg,
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
		// amountLabel,
	} = attributes;
	const currentFormId = useGetCurrentFormId( clientId );
	const [ availableFormFields, setAvailableFormFields ] = useState( {
		emailsFields: [],
		nameFields: [],
	} );

	console.log( 'availableFormFields', availableFormFields );

	// Get all blocks from the current form
	const { getBlocks } = useSelect(
		( select ) => select( 'core/block-editor' ),
		[]
	);

	// Function to extract and filter form fields by type
	const extractFormFields = () => {
		if ( ! currentFormId ) {
			return { emailsFields: [], nameFields: [] };
		}

		try {
			const blocks = getBlocks();
			const emailsFields = [];
			const nameFields = [];

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
					}

					// Filter name/input fields - only srfm/input
					if ( blockName === 'srfm/input' ) {
						nameFields.push( {
							slug,
							label: `${ label } (input)`,
							type: 'input',
						} );
					}
				}
			} );

			return { emailsFields, nameFields };
		} catch ( error ) {
			console.error( 'Error extracting form fields:', error );
			return { emailsFields: [], nameFields: [] };
		}
	};

	// Update available fields when form changes
	useEffect( () => {
		if ( isSelected || ! availableFormFields?.emailsFields?.length ) {
			const { emailsFields, nameFields } = extractFormFields();
			setAvailableFormFields( { emailsFields, nameFields } );
		}
	}, [ isSelected ] );

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );

	const {
		currentMessage: currentErrorMsg,
		setCurrentMessage: setCurrentErrorMsg,
	} = useErrMessage( 'srfm_payment_block_required_text', errorMsg );

	// Show the block preview on hover.
	if ( preview ) {
		const fieldName = __( 'Payment Field Preview', 'sureforms' );
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
		},,
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
							label: __( 'Checkout', 'sureforms' ),
						},
						{
							value: 'subscription',
							label: __( 'Subscription', 'sureforms' ),
						},
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
							value={
								subscriptionPlan?.name ||
									'Subscription Plan'
							}
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
						<SelectControl
							label={ __(
								'Stop Subscription After',
								'sureforms'
							) }
							value={
								subscriptionPlan?.billingCycles || 'ongoing'
							}
							options={ [
								{
									label: __( 'Ongoing', 'sureforms' ),
									value: 'ongoing',
								},
								...Array.from(
									{ length: 23 },
									( _, i ) => ( {
										label: `${ i + 2 } cycles`,
										value: i + 2,
									} )
								),
							] }
							onChange={ ( value ) => {
								setAttributes( {
									subscriptionPlan: {
										...( subscriptionPlan || {} ),
										billingCycles: value,
									},
								} );
							} }
							help={ __(
								'Select the number of billing cycles or ongoing for unlimited',
								'sureforms'
							) }
						/>
					),
				},
			  ]
			: [] ),
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
							value: 'user-defined',
							label: __( 'User-Defined', 'sureforms' ),
						},
					] }
					showIcons={ false }
					help={ __(
						'Choose whether to use a fixed amount or let users enter their own amount',
						'sureforms'
					) }
				/>
			),
		},
		{
			id: 'fixed-amount',
			component: (
				<SRFMTextControl
					label={
						amountType === 'fixed'
							? __( 'Fixed Amount', 'sureforms' )
							: __( 'Default Amount', 'sureforms' )
					}
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
					help={
						amountType === 'fixed'
							? __(
								'Enter the fixed payment amount',
								'sureforms'
							  )
							: __(
								'Enter the default amount (users can change this)',
								'sureforms'
							  )
					}
				/>
			),
		},
		...( amountType === 'user-defined'
			? [
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
				{
					id: 'required',
					component: (
						<ToggleControl
							label={ __( 'Required', 'sureforms' ) }
							checked={ required }
							onChange={ ( checked ) =>
								setAttributes( { required: checked } )
							}
						/>
					),
				},
				{
					id: 'error-message',
					component: required ? (
						<SRFMTextControl
							label={ __( 'Error Message', 'sureforms' ) }
							data={ {
								value: errorMsg,
								label: 'errorMsg',
							} }
							value={ currentErrorMsg }
							onChange={ ( value ) => {
								setCurrentErrorMsg( value );
								setAttributes( { errorMsg: value } );
							} }
						/>
					) : null,
				},
			  ]
			: [] ),
		{
			id: 'separator-2',
			component: <Separator />,
		},
		{
			id: 'customer-name-field',
			component: (
				<SelectControl
					label={
						paymentType === 'subscription'
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
						paymentType === 'subscription'
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
		<div className={ className }>
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
							{ filterOptions.map(
								( option ) => option.component
							) }
						</SRFMAdvancedPanelBody>
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
