/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	PanelRow,
	ToggleControl,
	TextControl,
} from '@wordpress/components';
import { useEffect } from '@wordpress/element';

import countries from './countries.json';

export default function Edit( { attributes, setAttributes } ) {
	const blockID = useBlockProps().id.split( '-' ).join( '' );
	const { required, label, id } = attributes;

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
		<div { ...useBlockProps() }>
			<InspectorControls>
				<PanelBody title={ __( 'Attributes', 'sureforms' ) }>
					<PanelRow>
						<ToggleControl
							label={ __( 'Required', 'sureforms' ) }
							checked={ required }
							onChange={ ( checked ) =>
								setAttributes( { required: checked } )
							}
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={ __( 'Label', 'sureforms' ) }
							value={ label }
							onChange={ ( value ) =>
								setAttributes( { label: value } )
							}
						/>
					</PanelRow>
				</PanelBody>
			</InspectorControls>
			<div
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
						<input
							type="text"
							id={ 'address-line-1-' + blockID }
							required={ required }
						/>
						<label
							className="text-secondary text-size"
							htmlFor={ 'address-line-1-' + blockID }
						>
							{ __( 'Address Line 1', 'sureforms' ) }
						</label>
					</div>
					<div
						style={ {
							display: 'flex',
							flexDirection: 'column',
							gap: '.5px',
						} }
					>
						<input
							type="text"
							style={ inputStyles }
							id={ 'address-line-2-' + blockID }
							required={ required }
						/>
						<label
							className="text-secondary text-size"
							htmlFor={ 'address-line-2-' + blockID }
						>
							{ __( 'Address Line 2', 'sureforms' ) }
						</label>
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
							<input
								type="text"
								style={ { ...inputStyles, width: '100%' } }
								id={ 'address-city-' + blockID }
								required={ required }
							/>
							<label
								className="text-secondary text-size"
								htmlFor={ 'address-city-' + blockID }
							>
								{ __( 'City', 'sureforms' ) }
							</label>
						</div>
						<div
							style={ {
								display: 'flex',
								flexDirection: 'column',
								gap: '.5px',
								width: '100%',
							} }
						>
							<input
								type="text"
								style={ { ...inputStyles, width: '100%' } }
								id={ 'address-state-' + blockID }
								required={ required }
							/>
							<label
								className="text-secondary text-size"
								htmlFor={ 'address-state-' + blockID }
							>
								{ __(
									'State / Province / Region',
									'sureforms'
								) }
							</label>
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
							<input
								type="text"
								style={ { ...inputStyles, width: '100%' } }
								id={ 'address-city-postal-' + blockID }
								required={ required }
							/>
							<label
								className="text-secondary text-size"
								htmlFor={ 'address-city-postal-' + blockID }
							>
								{ __( 'Postal Code', 'sureforms' ) }
							</label>
						</div>
						<div
							style={ {
								display: 'flex',
								flexDirection: 'column',
								gap: '.5px',
								width: '50%',
							} }
						>
							<select
								style={ { ...inputStyles, maxWidth: '540px' } }
								id={ 'address-country-' + blockID }
								required={ required }
							>
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
							<label
								className="text-secondary text-size"
								htmlFor={ 'address-country-' + blockID }
							>
								{ __( 'Country', 'sureforms' ) }
							</label>
						</div>
					</div>
				</div>
			</div>
		</div>
	);
}
