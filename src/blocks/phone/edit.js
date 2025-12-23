/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useMemo } from '@wordpress/element';
import { ToggleControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import SRFMTextControl from '@Components/text-control';
import SRFMSelectControl from '@Components/select-control';
import MultiButtonsControl from '@Components/multi-buttons-control';
import { PhoneComponent } from './components/default';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import { FieldsPreview } from '../FieldsPreview.jsx';
import { useErrMessage } from '@Blocks/util';
import ConditionalLogic from '@Components/conditional-logic';
import countries from './countries.json';
import Select from 'react-select';

const Edit = ( { attributes, setAttributes, clientId } ) => {
	const {
		required,
		help,
		block_id,
		isUnique,
		duplicateMsg,
		preview,
		errorMsg,
		formId,
		autoCountry,
		defaultCountry,
		className,
		enableCountryFilter,
		countryFilterType,
		includeCountries,
		excludeCountries,
	} = attributes;
	const currentFormId = useGetCurrentFormId( clientId );
	// Create translatable country options using useMemo
	const countryOptions = useMemo(
		() => [
			{ label: __( 'Select Country', 'sureforms' ), value: '' },
			...Object.entries( countries ).map( ( [ code, name ] ) => ( {
				label: name,
				value: code,
			} ) ),
		],
		[]
	);

	// Create array of all countries for the multi-select
	const allCountriesForSelect = useMemo(
		() =>
			Object.entries( countries ).map( ( [ code, name ] ) => ( {
				value: code,
				label: name,
			} ) ),
		[]
	);

	// Convert stored country codes to react-select format
	const includeCountriesOptions = useMemo(
		() =>
			includeCountries
				.map( ( code ) =>
					allCountriesForSelect.find(
						( country ) => country.value === code
					)
				)
				.filter( Boolean ),
		[ includeCountries, allCountriesForSelect ]
	);

	const excludeCountriesOptions = useMemo(
		() =>
			excludeCountries
				.map( ( code ) =>
					allCountriesForSelect.find(
						( country ) => country.value === code
					)
				)
				.filter( Boolean ),
		[ excludeCountries, allCountriesForSelect ]
	);

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );

	const {
		currentMessage: currentErrorMsg,
		setCurrentMessage: setCurrentErrorMsg,
	} = useErrMessage( 'srfm_phone_block_required_text', errorMsg );

	const {
		currentMessage: currentUniqueMessage,
		setCurrentMessage: setCurrentUniqueMessage,
	} = useErrMessage( 'srfm_phone_block_unique_text', duplicateMsg );

	// show the block preview on hover.
	if ( preview ) {
		const fieldName = srfm_fields_preview.phone_preview;
		return <FieldsPreview fieldName={ fieldName } />;
	}

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
							<ToggleControl
								label={ __( 'Required', 'sureforms' ) }
								checked={ required }
								onChange={ ( checked ) =>
									setAttributes( { required: checked } )
								}
							/>
							{ required && (
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
							) }
							<SRFMTextControl
								variant="textarea"
								label={ __( 'Help Text', 'sureforms' ) }
								value={ help }
								data={ {
									value: help,
									label: 'help',
								} }
								onChange={ ( value ) =>
									setAttributes( { help: value } )
								}
							/>
							<div className="srfm-settings-separator" />
							<ToggleControl
								label={ __( 'Unique Entry', 'sureforms' ) }
								checked={ isUnique }
								onChange={ ( checked ) =>
									setAttributes( { isUnique: checked } )
								}
							/>
							{ isUnique && (
								<SRFMTextControl
									label={ __(
										'Validation Message for Duplicate ',
										'sureforms'
									) }
									value={ currentUniqueMessage }
									data={ {
										value: duplicateMsg,
										label: 'duplicateMsg',
									} }
									onChange={ ( value ) => {
										setCurrentUniqueMessage( value );
										setAttributes( {
											duplicateMsg: value,
										} );
									} }
								/>
							) }
							<ToggleControl
								label={ __(
									'Enable Auto Country Detection',
									'sureforms'
								) }
								checked={ autoCountry }
								onChange={ ( value ) =>
									setAttributes( { autoCountry: value } )
								}
							/>
							{ ! autoCountry && (
								<SRFMSelectControl
									label={ __(
										'Default Country',
										'sureforms'
									) }
									data={ {
										value: defaultCountry,
										label: 'defaultCountry',
									} }
									setAttributes={ setAttributes }
									options={ countryOptions }
								/>
							) }
							<ToggleControl
								label={ __(
									'Include/Exclude Countries',
									'sureforms'
								) }
								checked={ enableCountryFilter }
								onChange={ ( value ) =>
									setAttributes( {
										enableCountryFilter: value,
									} )
								}
							/>
							{ enableCountryFilter && (
								<div className="srfm-phone-country-filter-controls">
									<MultiButtonsControl
										setAttributes={ setAttributes }
										label={ __(
											'Filter Type',
											'sureforms'
										) }
										data={ {
											value: countryFilterType,
											label: 'countryFilterType',
										} }
										options={ [
											{
												value: 'include',
												label: __(
													'Include',
													'sureforms'
												),
											},
											{
												value: 'exclude',
												label: __(
													'Exclude',
													'sureforms'
												),
											},
										] }
										showIcons={ false }
									/>
									{ countryFilterType === 'include' && (
										<>
											<div className="srfm-control-label">
												{ __(
													'Include Countries',
													'sureforms'
												) }
											</div>
											<Select
												options={
													allCountriesForSelect
												}
												value={
													includeCountriesOptions
												}
												isMulti
												isClearable
												classNamePrefix="srfm-select"
												placeholder={ __(
													'Select countries to include (leave empty to include all)',
													'sureforms'
												) }
												onChange={ ( values ) => {
													const codes = values
														? values.map(
															( item ) =>
																item.value
														  )
														: [];
													setAttributes( {
														includeCountries: codes,
													} );
												} }
											/>
											<p className="srfm-help-text">
												{ __(
													'Only these countries will be available in the dropdown.',
													'sureforms'
												) }
											</p>
										</>
									) }
									{ countryFilterType === 'exclude' && (
										<>
											<div className="srfm-control-label">
												{ __(
													'Exclude Countries',
													'sureforms'
												) }
											</div>
											<Select
												options={
													allCountriesForSelect
												}
												value={
													excludeCountriesOptions
												}
												isMulti
												isClearable
												classNamePrefix="srfm-select"
												placeholder={ __(
													'Select countries to exclude',
													'sureforms'
												) }
												onChange={ ( values ) => {
													const codes = values
														? values.map(
															( item ) =>
																item.value
														  )
														: [];
													setAttributes( {
														excludeCountries: codes,
													} );
												} }
											/>
											<p className="srfm-help-text">
												{ __(
													'These countries will be hidden from the dropdown.',
													'sureforms'
												) }
											</p>
										</>
									) }
								</div>
							) }
						</SRFMAdvancedPanelBody>
					</InspectorTab>
					<InspectorTab { ...SRFMTabs.style }></InspectorTab>
					<InspectorTab { ...SRFMTabs.advance }>
						<ConditionalLogic
							{ ...{ setAttributes, attributes } }
						/>
					</InspectorTab>
				</InspectorTabs>
			</InspectorControls>
			<PhoneComponent
				attributes={ attributes }
				blockID={ block_id }
				setAttributes={ setAttributes }
			/>
			<div className="srfm-error-wrap"></div>
		</div>
	);
};

export default compose( AddInitialAttr )( Edit );
