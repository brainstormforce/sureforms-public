/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToggleControl, SelectControl, Button } from '@wordpress/components';
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

const Edit = ( props ) => {
	const { clientId, attributes, setAttributes } = props;
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
		subscriptionPlans,
	} = attributes;
	const currentFormId = useGetCurrentFormId( clientId );
	const [ availableNumberFields, setAvailableNumberFields ] = useState( [] );
	const [ availableFormFields, setAvailableFormFields ] = useState( [] );

	// Get all blocks from the current form
	const { getBlocks } = useSelect(
		( select ) => select( 'core/block-editor' ),
		[]
	);

	// Function to extract number input field slugs from form blocks
	const extractNumberFieldSlugs = () => {
		if ( ! currentFormId ) {
			return [];
		}

		try {
			const blocks = getBlocks();
			const numberFields = [];

			const findNumberFields = ( blockList ) => {
				blockList.forEach( ( block ) => {
					// Check if block is a number input field
					if (
						block.name === 'srfm/number' &&
						block.attributes?.slug
					) {
						const slug = block.attributes.slug;
						const label =
							block.attributes.label ||
							__( 'Number Field', 'sureforms' );
						numberFields.push( {
							slug,
							label,
							selected: paymentItems?.includes( slug ) || false,
						} );
					}
					// Recursively check inner blocks
					if ( block.innerBlocks?.length > 0 ) {
						findNumberFields( block.innerBlocks );
					}
				} );
			};

			findNumberFields( blocks );
			return numberFields;
		} catch ( error ) {
			console.error( 'Error extracting number field slugs:', error );
			return [];
		}
	};

	// Function to extract all form field slugs for customer mapping
	const extractAllFieldSlugs = () => {
		if ( ! currentFormId ) {
			return [];
		}

		try {
			const blocks = getBlocks();
			const formFields = [];

			const findFormFields = ( blockList ) => {
				blockList.forEach( ( block ) => {
					// Check if block has a slug (is a form field)
					if ( block.attributes?.slug ) {
						const slug = block.attributes.slug;
						const label =
							block.attributes.label ||
							block.name ||
							__( 'Form Field', 'sureforms' );
						const fieldType =
							block.name?.replace( 'srfm/', '' ) || 'field';

						formFields.push( {
							slug,
							label: `${ label } (${ fieldType })`,
							type: fieldType,
						} );
					}
					// Recursively check inner blocks
					if ( block.innerBlocks?.length > 0 ) {
						findFormFields( block.innerBlocks );
					}
				} );
			};

			findFormFields( blocks );
			return formFields;
		} catch ( error ) {
			console.error( 'Error extracting form field slugs:', error );
			return [];
		}
	};

	// Update available fields when form changes
	useEffect( () => {
		const numberFields = extractNumberFieldSlugs();
		const allFields = extractAllFieldSlugs();
		setAvailableNumberFields( numberFields );
		setAvailableFormFields( allFields );
	}, [ currentFormId, paymentItems ] );

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

	// Handler for refreshing available fields
	const refreshNumberFields = () => {
		const fields = extractNumberFieldSlugs();
		setAvailableNumberFields( fields );
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
					<div className="components-base-control srfm-text-control srfm-size-type-field-tabs">
						<label
							style={ {
								display: 'block',
								marginBottom: '8px',
								fontWeight: '600',
							} }
						>
							{ __( 'Payment Items Configuration', 'sureforms' ) }
						</label>
						<p
							style={ {
								fontSize: '12px',
								color: '#757575',
								margin: '0 0 12px 0',
							} }
						>
							{ __(
								'Select a number field to include in payment calculations.',
								'sureforms'
							) }
						</p>
						<Button
							variant="secondary"
							size="small"
							onClick={ refreshNumberFields }
							style={ { marginBottom: '12px' } }
						>
							{ __( 'Refresh Available Fields', 'sureforms' ) }
						</Button>
					</div>

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
				<SelectControl
					label={ __( 'Payment Type', 'sureforms' ) }
					value={ paymentType || 'one-time' }
					options={ [
						{
							label: __( 'One-time Payment', 'sureforms' ),
							value: 'one-time',
						},
						{
							label: __( 'Subscription', 'sureforms' ),
							value: 'subscription',
						},
					] }
					onChange={ ( value ) => {
						setAttributes( { paymentType: value } );
					} }
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
								subscriptionPlans?.[ 0 ]?.name ||
									'Subscription Plan'
							}
							data={ {
								value:
										subscriptionPlans?.[ 0 ]?.name ||
										'Subscription Plan',
								label: 'subscription-plan-name',
							} }
							onChange={ ( value ) => {
								const updatedPlans = [
									...( subscriptionPlans || [] ),
								];
								if ( updatedPlans.length === 0 ) {
									updatedPlans.push( {
										name: value,
										interval: 'month',
										customer_name: '',
										customer_email: '',
									} );
								} else {
									updatedPlans[ 0 ] = {
										...updatedPlans[ 0 ],
										name: value,
									};
								}
								setAttributes( {
									subscriptionPlans: updatedPlans,
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
							value={
								subscriptionPlans?.[ 0 ]?.interval ||
									'month'
							}
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
								const updatedPlans = [
									...( subscriptionPlans || [] ),
								];
								if ( updatedPlans.length === 0 ) {
									updatedPlans.push( {
										name: 'Subscription Plan',
										interval: value,
										customer_name: '',
										customer_email: '',
									} );
								} else {
									updatedPlans[ 0 ] = {
										...updatedPlans[ 0 ],
										interval: value,
									};
								}
								setAttributes( {
									subscriptionPlans: updatedPlans,
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
								subscriptionPlans?.[ 0 ]?.billingCycles ||
									'ongoing'
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
								const updatedPlans = [
									...( subscriptionPlans || [] ),
								];
								if ( updatedPlans.length === 0 ) {
									updatedPlans.push( {
										name: 'Subscription Plan',
										interval: 'month',
										billingCycles: value,
										customer_name: '',
										customer_email: '',
									} );
								} else {
									updatedPlans[ 0 ] = {
										...updatedPlans[ 0 ],
										billingCycles: value,
									};
								}
								setAttributes( {
									subscriptionPlans: updatedPlans,
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
							value={
								subscriptionPlans?.[ 0 ]?.customer_name ||
									''
							}
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
								const updatedPlans = [
									...( subscriptionPlans || [] ),
								];
								if ( updatedPlans.length === 0 ) {
									updatedPlans.push( {
										name: 'Subscription Plan',
										interval: 'month',
										customer_name: value,
										customer_email: '',
									} );
								} else {
									updatedPlans[ 0 ] = {
										...updatedPlans[ 0 ],
										customer_name: value,
									};
								}
								setAttributes( {
									subscriptionPlans: updatedPlans,
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
							value={
								subscriptionPlans?.[ 0 ]?.customer_email ||
									''
							}
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
								const updatedPlans = [
									...( subscriptionPlans || [] ),
								];
								if ( updatedPlans.length === 0 ) {
									updatedPlans.push( {
										name: 'Subscription Plan',
										interval: 'month',
										customer_name: '',
										customer_email: value,
									} );
								} else {
									updatedPlans[ 0 ] = {
										...updatedPlans[ 0 ],
										customer_email: value,
									};
								}
								setAttributes( {
									subscriptionPlans: updatedPlans,
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
