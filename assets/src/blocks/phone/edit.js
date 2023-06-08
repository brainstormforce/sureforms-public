/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	PanelRow,
	ToggleControl,
	TextControl,
} from '@wordpress/components';

import data from './phoneCodes.json';

export default function Edit( { attributes, setAttributes } ) {
	const { required, label, help, placeholder, id } = attributes;
	const blockID = useBlockProps().id.split( '-' ).join( '' );
	// eslint-disable-next-line no-unused-vars
	const [ code, setCode ] = useState( null );
	const [ phoneNumber, setPhoneNumber ] = useState( '' );

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
					<PanelRow>
						<TextControl
							label={ __( 'Placeholder', 'sureforms' ) }
							value={ placeholder }
							onChange={ ( value ) =>
								setAttributes( { placeholder: value } )
							}
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={ __( 'Help', 'sureforms' ) }
							value={ help }
							onChange={ ( value ) =>
								setAttributes( { help: value } )
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
				<label htmlFor={ 'phone-field-' + blockID }>
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
				>
					{ data && (
						<select
							style={ { width: 'fit-content' } }
							required={ required }
							id={ 'phone-field-' + blockID }
							placeholder="US +1"
							onChange={ ( e ) => handleChange( e ) }
						>
							{ data.map( ( country, i ) => {
								return (
									<option
										key={ i }
										value={ country.dial_code }
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
						value={ phoneNumber }
						onChange={ ( e ) => {
							setPhoneNumber( e.target.value );
						} }
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
		</div>
	);
}
