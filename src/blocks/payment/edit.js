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
		description,
		errorMsg,
		formId,
		preview,
		className,
		paymentItems,
		paymentType,
		subscriptionPlan,
	} = attributes;
	const currentFormId = useGetCurrentFormId( clientId );
	const [ availableNumberFields, setAvailableNumberFields ] = useState( [] );
	const [ availableFormFields, setAvailableFormFields ] = useState( [] );

	// Get all blocks from the current form
	const { getBlocks } = useSelect(
		( select ) => select( 'core/block-editor' ),
		[]
	);

	// Function to extract both number fields and all form fields in a single traversal
	const extractFormFields = () => {
		if ( ! currentFormId ) {
			return { numberFields: [], allFields: [] };
		}

		try {
			const blocks = getBlocks();
			const numberFields = [];
			const allFields = [];

			blocks.forEach( ( block ) => {
				// Check if block has a slug (is a form field)
				if ( block.attributes?.slug ) {
					const slug = block.attributes.slug;
					const label =
						block.attributes.label ||
						block.name ||
						__( 'Form Field', 'sureforms' );
					const fieldType =
						block.name?.replace( 'srfm/', '' ) || 'field';

					// Add to all fields array
					allFields.push( {
						slug,
						label: `${ label } (${ fieldType })`,
						type: fieldType,
					} );

					// If it's a number field, also add to number fields array
					if ( block.name === 'srfm/number' ) {
						numberFields.push( {
							slug,
							label:
								block.attributes.label ||
								__( 'Number Field', 'sureforms' ),
							selected: paymentItems?.includes( slug ) || false,
						} );
					}
				}
			} );

			return { numberFields, allFields };
		} catch ( error ) {
			console.error( 'Error extracting form fields:', error );
			return { numberFields: [], allFields: [] };
		}
	};

	// Update available fields when form changes
	useEffect( () => {
		if ( isSelected ) {
			const { numberFields, allFields } = extractFormFields();
			setAvailableNumberFields( numberFields );
			setAvailableFormFields( allFields );
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

	// Handler for updating selected item (single selection)
	const handleItemChange = ( selectedValue ) => {
		// Keep only one value in the array
		const newItems = selectedValue ? [ selectedValue ] : [];
		setAttributes( { paymentItems: newItems } );
	};

	// Show the block preview on hover.
	if ( preview ) {
		const fieldName = __( 'Payment Field Preview', 'sureforms' );
		return <FieldsPreview fieldName={ fieldName } />;
	}

	const attributeOptions = [
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
			id: 'separator',
			component: <Separator />,
		},
		{
			id: 'payment-items',
			component: (
				<div className="components-base-control">
					{ availableNumberFields.length === 0 ? (
						<p
							style={ {
								fontSize: '12px',
								color: '#757575',
								fontStyle: 'italic',
							} }
						>
							{ __(
								'No number fields found in the form. Add number input fields to configure payment items.',
								'sureforms'
							) }
						</p>
					) : (
						<SelectControl
							label={ __( 'Select Payment Item', 'sureforms' ) }
							value={
								paymentItems && paymentItems.length > 0
									? paymentItems[ 0 ]
									: ''
							}
							options={ [
								{
									label: __( 'Select a field…', 'sureforms' ),
									value: '',
								},
								...availableNumberFields.map( ( field ) => ( {
									label: `${ field.label } (${ field.slug })`,
									value: field.slug,
								} ) ),
							] }
							onChange={ handleItemChange }
							help={ __(
								'Select one number field to include in payment calculations',
								'sureforms'
							) }
						/>
					) }
				</div>
			),
		},
		{
			id: 'payment-description',
			component: (
				<SRFMTextControl
					label={ __( 'Payment Description', 'sureforms' ) }
					value={ description }
					variant="textarea"
					data={ {
						value: description,
						label: 'description',
					} }
					onChange={ ( value ) =>
						setAttributes( { description: value } )
					}
					help={ __(
						'This will appear on the payment receipt and in Stripe dashboard.',
						'sureforms'
					) }
				/>
			),
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
							label: __( 'One-time', 'sureforms' ),
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
							label={ __( 'Billing Cycles', 'sureforms' ) }
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
				{
					id: 'customer-name-field',
					component: (
						<SelectControl
							label={ __(
								'Customer Name Field',
								'sureforms'
							) }
							value={ subscriptionPlan?.customer_name || '' }
							options={ [
								{
									label: __(
										'Select a field…',
										'sureforms'
									),
									value: '',
								},
								...availableFormFields.map( ( field ) => ( {
									label: field.label,
									value: field.slug,
								} ) ),
							] }
							onChange={ ( value ) => {
								setAttributes( {
									subscriptionPlan: {
										...( subscriptionPlan || {} ),
										customer_name: value,
									},
								} );
							} }
							help={ __(
								'Select the field that contains the customer name',
								'sureforms'
							) }
						/>
					),
				},
				{
					id: 'customer-email-field',
					component: (
						<SelectControl
							label={ __(
								'Customer Email Field',
								'sureforms'
							) }
							value={ subscriptionPlan?.customer_email || '' }
							options={ [
								{
									label: __(
										'Select a field…',
										'sureforms'
									),
									value: '',
								},
								...availableFormFields.map( ( field ) => ( {
									label: field.label,
									value: field.slug,
								} ) ),
							] }
							onChange={ ( value ) => {
								setAttributes( {
									subscriptionPlan: {
										...( subscriptionPlan || {} ),
										customer_email: value,
									},
								} );
							} }
							help={ __(
								'Select the field that contains the customer email',
								'sureforms'
							) }
						/>
					),
				},
			  ]
			: [] ),
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
				/>
				<div className="srfm-error-wrap"></div>
			</>
		</div>
	);
};

export default compose( AddInitialAttr )( Edit );
