/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { ToggleControl } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import UAGTextControl from '@Components/text-control';
import UAGAdvancedPanelBody from '@Components/advanced-panel-body';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	UAGTabs,
} from '@Components/inspector-tabs/InspectorTab.js';

import countries from './countries.json';

export default function Edit( { attributes, setAttributes, isSelected } ) {
	const blockID = useBlockProps().id.split( '-' ).join( '' );
	const {
		required,
		label,
		id,
		errorMsg,
		lineOnePlaceholder,
		lineTwoPlaceholder,
		cityPlaceholder,
		statePlaceholder,
		postalPlaceholder,
		lineOneLabel,
		lineTwoLabel,
		cityLabel,
		stateLabel,
		countryLabel,
		countryPlaceholder,
		postalLabel,
	} = attributes;

	const inputStyles = {
		marginTop: '14px',
	};

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
								data={ {
									value: label,
									label: 'label',
								} }
								value={ label }
								onChange={ ( value ) => {
									setAttributes( { label: value } );
								} }
							/>
							<ToggleControl
								label={ __( 'Required', 'sureforms' ) }
								checked={ required }
								onChange={ ( checked ) =>
									setAttributes( { required: checked } )
								}
							/>
							{ required && (
								<UAGTextControl
									data={ {
										value: errorMsg,
										label: 'errorMsg',
									} }
									label={ __( 'Error message', 'sureforms' ) }
									value={ errorMsg }
									onChange={ ( value ) =>
										setAttributes( { errorMsg: value } )
									}
								/>
							) }
							<UAGAdvancedPanelBody
								title={ __( 'Address Line 1', 'sureforms' ) }
								initialOpen={ false }
							>
								<UAGTextControl
									data={ {
										value: lineOneLabel,
										label: 'lineOneLabel',
									} }
									label={ __( 'Label', 'sureforms' ) }
									value={ lineOneLabel }
									onChange={ ( value ) =>
										setAttributes( {
											lineOneLabel: value,
										} )
									}
								/>
								<UAGTextControl
									data={ {
										value: lineOnePlaceholder,
										label: 'lineOnePlaceholder',
									} }
									label={ __( 'Placeholder', 'sureforms' ) }
									value={ lineOnePlaceholder }
									onChange={ ( value ) =>
										setAttributes( {
											lineOnePlaceholder: value,
										} )
									}
								/>
							</UAGAdvancedPanelBody>
							<UAGAdvancedPanelBody
								title={ __( 'Address Line 2', 'sureforms' ) }
								initialOpen={ false }
							>
								<UAGTextControl
									data={ {
										value: lineTwoLabel,
										label: 'lineTwoLabel',
									} }
									label={ __( 'Label', 'sureforms' ) }
									value={ lineTwoLabel }
									onChange={ ( value ) =>
										setAttributes( {
											lineTwoLabel: value,
										} )
									}
								/>
								<UAGTextControl
									data={ {
										value: lineTwoPlaceholder,
										label: 'lineTwoPlaceholder',
									} }
									label={ __( 'Placeholder', 'sureforms' ) }
									value={ lineTwoPlaceholder }
									onChange={ ( value ) =>
										setAttributes( {
											lineTwoPlaceholder: value,
										} )
									}
								/>
							</UAGAdvancedPanelBody>
							<UAGAdvancedPanelBody
								title={ __( 'City', 'sureforms' ) }
								initialOpen={ false }
							>
								<UAGTextControl
									data={ {
										value: cityLabel,
										label: 'cityLabel',
									} }
									label={ __( 'Label', 'sureforms' ) }
									value={ cityLabel }
									onChange={ ( value ) =>
										setAttributes( {
											cityLabel: value,
										} )
									}
								/>
								<UAGTextControl
									data={ {
										value: cityPlaceholder,
										label: 'cityPlaceholder',
									} }
									label={ __( 'Placeholder', 'sureforms' ) }
									value={ cityPlaceholder }
									onChange={ ( value ) =>
										setAttributes( {
											cityPlaceholder: value,
										} )
									}
								/>
							</UAGAdvancedPanelBody>
							<UAGAdvancedPanelBody
								title={ __( 'State', 'sureforms' ) }
								initialOpen={ false }
							>
								<UAGTextControl
									data={ {
										value: stateLabel,
										label: 'stateLabel',
									} }
									label={ __( 'Label', 'sureforms' ) }
									value={ stateLabel }
									onChange={ ( value ) =>
										setAttributes( {
											stateLabel: value,
										} )
									}
								/>
								<UAGTextControl
									data={ {
										value: statePlaceholder,
										label: 'statePlaceholder',
									} }
									label={ __( 'Placeholder', 'sureforms' ) }
									value={ statePlaceholder }
									onChange={ ( value ) =>
										setAttributes( {
											statePlaceholder: value,
										} )
									}
								/>
							</UAGAdvancedPanelBody>
							<UAGAdvancedPanelBody
								title={ __( 'Postal Code', 'sureforms' ) }
								initialOpen={ false }
							>
								<UAGTextControl
									data={ {
										value: postalLabel,
										label: 'postalLabel',
									} }
									label={ __( 'Label', 'sureforms' ) }
									value={ postalLabel }
									onChange={ ( value ) =>
										setAttributes( {
											postalLabel: value,
										} )
									}
								/>
								<UAGTextControl
									data={ {
										value: postalPlaceholder,
										label: 'postalPlaceholder',
									} }
									label={ __( 'Placeholder', 'sureforms' ) }
									value={ postalPlaceholder }
									onChange={ ( value ) =>
										setAttributes( {
											postalPlaceholder: value,
										} )
									}
								/>
							</UAGAdvancedPanelBody>
							<UAGAdvancedPanelBody
								title={ __( 'Country', 'sureforms' ) }
								initialOpen={ false }
							>
								<UAGTextControl
									data={ {
										value: countryLabel,
										label: 'countryLabel',
									} }
									label={ __( 'Label', 'sureforms' ) }
									value={ countryLabel }
									onChange={ ( value ) =>
										setAttributes( {
											countryLabel: value,
										} )
									}
								/>
								<UAGTextControl
									data={ {
										value: countryPlaceholder,
										label: 'countryPlaceholder',
									} }
									label={ __( 'Placeholder', 'sureforms' ) }
									value={ countryPlaceholder }
									onChange={ ( value ) =>
										setAttributes( {
											countryPlaceholder: value,
										} )
									}
								/>
							</UAGAdvancedPanelBody>
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
				<label htmlFor={ 'address-field-' + blockID }>
					{ label }
					{ required && label && (
						<span style={ { color: 'red' } }> *</span>
					) }
				</label>
				<div>
					<div
						id={ 'address-field-' + blockID }
						style={ {
							display: 'flex',
							flexDirection: 'column',
							gap: '.5px',
						} }
					>
						<label
							className="sf-text-secondary text-size"
							htmlFor={ 'address-line-1-' + blockID }
						>
							{ lineOneLabel }
						</label>
						<input
							type="text"
							id={ 'address-line-1-' + blockID }
							required={ required }
							placeholder={ lineOnePlaceholder }
						/>
					</div>
					<div
						style={ {
							display: 'flex',
							flexDirection: 'column',
							gap: '.5px',
						} }
					>
						<label
							className="sf-text-secondary text-size"
							htmlFor={ 'address-line-2-' + blockID }
							style={ inputStyles }
						>
							{ lineTwoLabel }
						</label>
						<input
							type="text"
							id={ 'address-line-2-' + blockID }
							required={ required }
							placeholder={ lineTwoPlaceholder }
						/>
					</div>
					<div style={ { display: 'flex', gap: '1rem' } }>
						<div
							style={ {
								display: 'flex',
								flexDirection: 'column',
								gap: '.5px',
								width: '100%',
							} }
						>
							<label
								className="sf-text-secondary text-size"
								htmlFor={ 'address-city-' + blockID }
								style={ inputStyles }
							>
								{ cityLabel }
							</label>
							<input
								type="text"
								id={ 'address-city-' + blockID }
								required={ required }
								placeholder={ cityPlaceholder }
							/>
						</div>
						<div
							style={ {
								display: 'flex',
								flexDirection: 'column',
								gap: '.5px',
								width: '100%',
							} }
						>
							<label
								className="sf-text-secondary text-size"
								htmlFor={ 'address-state-' + blockID }
								style={ inputStyles }
							>
								{ stateLabel }
							</label>
							<input
								type="text"
								id={ 'address-state-' + blockID }
								required={ required }
								placeholder={ statePlaceholder }
							/>
						</div>
					</div>
					<div
						style={ {
							display: 'flex',
							gap: '1rem',
							width: '100%',
						} }
					>
						<div
							style={ {
								display: 'flex',
								flexDirection: 'column',
								gap: '.5px',
								width: '50%',
							} }
						>
							<label
								className="sf-text-secondary text-size"
								htmlFor={ 'address-city-postal-' + blockID }
								style={ inputStyles }
							>
								{ postalLabel }
							</label>
							<input
								type="text"
								id={ 'address-city-postal-' + blockID }
								required={ required }
								placeholder={ postalPlaceholder }
							/>
						</div>
						<div
							style={ {
								display: 'flex',
								flexDirection: 'column',
								gap: '.5px',
								width: '50%',
							} }
						>
							<label
								className="sf-text-secondary text-size"
								htmlFor={ 'address-country-' + blockID }
								style={ inputStyles }
							>
								{ countryLabel }
							</label>
							<select
								id={ 'address-country-' + blockID }
								required={ required }
							>
								{ countryPlaceholder && (
									<option> { countryPlaceholder }</option>
								) }
								{ countries.map( ( country, i ) => {
									return (
										<option
											key={ i }
											value={ country.name }
										>
											{ country.name }
										</option>
									);
								} ) }
							</select>
						</div>
					</div>
				</div>
			</div>
		</>
	);
}
