/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	ToggleControl,
	SelectControl,
} from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import SRFMTextControl from '@Components/text-control';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { PaymentComponent } from './components/default.js';
import Range from '@Components/range/Range.js';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import { FieldsPreview } from '../FieldsPreview.jsx';
import { useErrMessage } from '@Blocks/util';
import ConditionalLogic from '@Components/conditional-logic';
import { attributeOptionsWithFilter } from '@Components/hooks';

const Edit = ( props ) => {
	const { clientId, attributes, setAttributes } = props;
	const {
		help,
		required,
		block_id,
		amount,
		currency,
		description,
		errorMsg,
		formId,
		preview,
		className,
		applicationFee,
	} = attributes;
	const currentFormId = useGetCurrentFormId( clientId );

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

	// Available currencies
	const currencyOptions = [
		{ label: __( 'USD - US Dollar', 'sureforms' ), value: 'USD' },
		{ label: __( 'EUR - Euro', 'sureforms' ), value: 'EUR' },
		{ label: __( 'GBP - British Pound', 'sureforms' ), value: 'GBP' },
		{ label: __( 'JPY - Japanese Yen', 'sureforms' ), value: 'JPY' },
		{ label: __( 'AUD - Australian Dollar', 'sureforms' ), value: 'AUD' },
		{ label: __( 'CAD - Canadian Dollar', 'sureforms' ), value: 'CAD' },
		{ label: __( 'CHF - Swiss Franc', 'sureforms' ), value: 'CHF' },
		{ label: __( 'CNY - Chinese Yuan', 'sureforms' ), value: 'CNY' },
		{ label: __( 'SEK - Swedish Krona', 'sureforms' ), value: 'SEK' },
		{ label: __( 'NZD - New Zealand Dollar', 'sureforms' ), value: 'NZD' },
		{ label: __( 'MXN - Mexican Peso', 'sureforms' ), value: 'MXN' },
		{ label: __( 'SGD - Singapore Dollar', 'sureforms' ), value: 'SGD' },
		{ label: __( 'HKD - Hong Kong Dollar', 'sureforms' ), value: 'HKD' },
		{ label: __( 'NOK - Norwegian Krone', 'sureforms' ), value: 'NOK' },
		{ label: __( 'KRW - South Korean Won', 'sureforms' ), value: 'KRW' },
		{ label: __( 'INR - Indian Rupee', 'sureforms' ), value: 'INR' },
		{ label: __( 'BRL - Brazilian Real', 'sureforms' ), value: 'BRL' },
		{ label: __( 'ZAR - South African Rand', 'sureforms' ), value: 'ZAR' },
		{ label: __( 'AED - UAE Dirham', 'sureforms' ), value: 'AED' },
		{ label: __( 'THB - Thai Baht', 'sureforms' ), value: 'THB' },
	];

	const attributeOptions = [
		{
			id: 'payment-amount',
			component: (
				<Range
					label={ __( 'Payment Amount', 'sureforms' ) }
					displayUnit={ false }
					value={ amount }
					min={ 1 }
					max={ 10000 }
					step={ 0.01 }
					data={ {
						value: amount,
						label: 'amount',
					} }
					onChange={ ( value ) =>
						setAttributes( { amount: parseFloat( value ) } )
					}
				/>
			),
		},
		{
			id: 'payment-currency',
			component: (
				<SelectControl
					label={ __( 'Currency', 'sureforms' ) }
					value={ currency }
					options={ currencyOptions }
					onChange={ ( value ) => {
						setAttributes( { currency: value } );
					} }
					help={ __(
						'Note: The default currency from payment settings will be used if different from this setting.',
						'sureforms'
					) }
				/>
			),
		},
		{
			id: 'payment-description',
			component: (
				<SRFMTextControl
					label={ __( 'Payment Description', 'sureforms' ) }
					value={ description }
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
		// {
		// 	id: 'application-fee',
		// 	component: (
		// 		<Range
		// 			label={ __( 'Application Fee (%)', 'sureforms' ) }
		// 			displayUnit={ false }
		// 			value={ applicationFee }
		// 			min={ 0 }
		// 			max={ 10 }
		// 			step={ 0.1 }
		// 			data={ {
		// 				value: applicationFee,
		// 				label: 'applicationFee',
		// 			} }
		// 			onChange={ ( value ) =>
		// 				setAttributes( { applicationFee: parseFloat( value ) } )
		// 			}
		// 			help={ __(
		// 				'Percentage fee to be added to the payment amount.',
		// 				'sureforms'
		// 			) }
		// 		/>
		// 	),
		// },
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
							title={ __( 'Payment Settings', 'sureforms' ) }
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
