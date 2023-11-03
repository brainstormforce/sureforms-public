/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { InspectorControls } from '@wordpress/block-editor';
import { ToggleControl } from '@wordpress/components';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	UAGTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import UAGAdvancedPanelBody from '@Components/advanced-panel-body';
import UAGTextControl from '@Components/text-control';
import data from './phoneCodes.json';
import { PhoneClassicStyle } from './components/PhoneClassicStyle';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { useGetSureFormsKeys } from '../../blocks-attributes/getMetakeys';
import { PhoneThemeStyle } from './components/PhoneThemeStyle';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';

const Edit = ( { attributes, setAttributes, clientId } ) => {
	const {
		required,
		label,
		help,
		placeholder,
		block_id,
		defaultValue,
		defaultCountryCode,
		isUnique,
		duplicateMsg,
		errorMsg,
		formId,
	} = attributes;
	const currentFormId = useGetCurrentFormId( clientId );
	const sureforms_keys = useGetSureFormsKeys( formId );
	// eslint-disable-next-line no-unused-vars
	const [ code, setCode ] = useState( null );

	function handleChange( e ) {
		setCode( e.target.value );
	}

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );

	return (
		<>
			<InspectorControls>
				<InspectorTabs
					tabs={ [ 'general', 'advance' ] }
					defaultTab={ 'general' }
				>
					<InspectorTab { ...UAGTabs.general }>
						<UAGAdvancedPanelBody
							title={ __( 'Attributes', 'sureforms' ) }
							initialOpen={ true }
						>
							<UAGTextControl
								label={ __( 'Label', 'sureforms' ) }
								value={ label }
								data={ {
									value: label,
									label: 'label',
								} }
								onChange={ ( value ) =>
									setAttributes( { label: value } )
								}
							/>
							<UAGTextControl
								label={ __( 'Placeholder', 'sureforms' ) }
								value={ placeholder }
								data={ {
									value: placeholder,
									label: 'placeholder',
								} }
								onChange={ ( value ) =>
									setAttributes( { placeholder: value } )
								}
							/>
							<div className="components-base-control">
								<lable>
									{ __( 'Default Value', 'sureforms' ) }
								</lable>
								<div
									style={ {
										display: 'flex',
										alignItems: 'center',
										marginTop: '10px',
									} }
								>
									{ data && (
										<select
											style={ {
												minHeight: '32px',
												color: '#50575e',
												borderColor: '#e6e7e9',
											} }
											placeholder="US +1"
											value={ defaultCountryCode }
											onChange={ ( e ) => {
												const value = e.target.value;
												setAttributes( {
													defaultCountryCode: value,
												} );
											} }
										>
											{ data.map( ( country, i ) => {
												return (
													<option
														key={ i }
														value={
															country.dial_code
														}
													>
														{ country.code +
															' ' +
															country.dial_code }
													</option>
												);
											} ) }
										</select>
									) }
									<UAGTextControl
										value={ defaultValue }
										data={ {
											value: defaultValue,
											label: 'defaultValue',
										} }
										showHeaderControls={ false }
										onChange={ ( value ) =>
											setAttributes( {
												defaultValue: value,
											} )
										}
									/>
								</div>
							</div>
							<ToggleControl
								label={ __( 'Required', 'sureforms' ) }
								checked={ required }
								onChange={ ( checked ) =>
									setAttributes( { required: checked } )
								}
							/>
							{ required && (
								<UAGTextControl
									label={ __( 'Error message', 'sureforms' ) }
									value={ errorMsg }
									data={ {
										value: errorMsg,
										label: 'errorMsg',
									} }
									onChange={ ( value ) =>
										setAttributes( { errorMsg: value } )
									}
								/>
							) }
							<ToggleControl
								label={ __(
									'Validate as unique',
									'sureforms'
								) }
								checked={ isUnique }
								onChange={ ( checked ) =>
									setAttributes( { isUnique: checked } )
								}
							/>
							{ isUnique && (
								<UAGTextControl
									label={ __(
										'Validation Message for Duplicate ',
										'sureforms'
									) }
									value={ duplicateMsg }
									data={ {
										value: duplicateMsg,
										label: 'duplicateMsg',
									} }
									onChange={ ( value ) =>
										setAttributes( { duplicateMsg: value } )
									}
								/>
							) }
							<UAGTextControl
								label={ __( 'Help', 'sureforms' ) }
								value={ help }
								data={ {
									value: help,
									label: 'help',
								} }
								onChange={ ( value ) =>
									setAttributes( { help: value } )
								}
							/>
						</UAGAdvancedPanelBody>
					</InspectorTab>
					<InspectorTab { ...UAGTabs.style }></InspectorTab>
				</InspectorTabs>
			</InspectorControls>
			<div
				className={ 'srfm-main-container srfm-classic-inputs-holder' }
				style={ {
					display: 'flex',
					flexDirection: 'column',
					gap: '.5rem',
				} }
			>
				{ 'classic' === sureforms_keys?._srfm_form_styling ? (
					<PhoneClassicStyle
						attributes={ attributes }
						blockID={ block_id }
						handleChange={ handleChange }
					/>
				) : (
					<PhoneThemeStyle
						attributes={ attributes }
						blockID={ block_id }
						handleChange={ handleChange }
					/>
				) }
				{ help !== '' && (
					<label
						htmlFor={ 'phone-help-' + block_id }
						className={
							'classic' === sureforms_keys?._srfm_form_styling
								? 'srfm-helper-txt'
								: 'srfm-text-secondary'
						}
					>
						{ help }
					</label>
				) }
			</div>
		</>
	);
};

export default compose( AddInitialAttr )( Edit );
