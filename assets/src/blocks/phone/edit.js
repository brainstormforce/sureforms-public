/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { ToggleControl } from '@wordpress/components';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	UAGTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import UAGAdvancedPanelBody from '@Components/advanced-panel-body';
import UAGTextControl from '@Components/text-control';
import data from './phoneCodes.json';

export default function Edit( { attributes, setAttributes, isSelected } ) {
	const {
		required,
		label,
		help,
		placeholder,
		id,
		defaultValue,
		defaultCountryCode,
		isUnique,
		duplicateMsg,
		errorMsg,
	} = attributes;
	const blockID = useBlockProps().id.split( '-' ).join( '' );
	// eslint-disable-next-line no-unused-vars
	const [ code, setCode ] = useState( null );

	function handleChange( e ) {
		setCode( e.target.value );
	}

	useEffect( () => {
		if ( id !== '' ) {
			return;
		}
		setAttributes( { id: blockID } );
	}, [ blockID, id, setAttributes ] );
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
				className={
					'main-container' + ( isSelected ? ' sf--focus' : '' )
				}
				style={ {
					display: 'flex',
					flexDirection: 'column',
					gap: '.5rem',
				} }
			>
				<label
					className="text-primary"
					htmlFor={ 'phone-field-' + blockID }
				>
					{ label }
					{ required && label && (
						<span style={ { color: 'red' } }> *</span>
					) }
				</label>
				<div
					style={ {
						display: 'flex',
						gap: '.5rem',
					} }
					className="phonufield-with-country-code"
				>
					{ data && (
						<select
							style={ { width: '124px' } }
							required={ required }
							id={ 'phone-field-' + blockID }
							placeholder="US +1"
							onChange={ ( e ) => handleChange( e ) }
						>
							{ data.map( ( country, i ) => {
								return (
									<option
										key={ i }
										value={
											country.code +
											' ' +
											country.dial_code
										}
										selected={
											country.dial_code ===
												defaultCountryCode && true
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
					<input
						label="&nbsp;"
						type="tel"
						placeholder={ placeholder }
						pattern="[0-9]{10}"
						id={ 'phone-field-' + blockID }
						value={ defaultValue }
					/>
				</div>
			</div>
			{ help !== '' && (
				<label
					htmlFor={ 'phone-help-' + blockID }
					className="text-secondary"
				>
					{ help }
				</label>
			) }
		</>
	);
}
