/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	PanelRow,
	ToggleControl,
	TextControl,
} from '@wordpress/components';

/**
 * Components dependencies
 */
import { ScInput, ScSelect } from '@surecart/components-react';

import data from './phoneCodes.json';

export default function Edit( { attributes, setAttributes } ) {
	const { required, label, help } = attributes;
	const blockID = useBlockProps().id;
	// eslint-disable-next-line no-unused-vars
	const [ code, setCode ] = useState( null );
	const [ phoneNumber, setPhoneNumber ] = useState( '' );

	function handleChange( e ) {
		setCode( e.target.value );
	}

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
							label={ __( 'Help', 'sureforms' ) }
							value={ help }
							onChange={ ( value ) =>
								setAttributes( { help: value } )
							}
						/>
					</PanelRow>
				</PanelBody>
			</InspectorControls>
			<div style={ { display: 'flex', gap: '1rem' } }>
				{ data && (
					<ScSelect
						label={ label }
						required={ required }
						id={ 'phone-field-' + blockID }
						placeholder="US +1"
						search
						onScChange={ ( e ) => handleChange( e ) }
						choices={ data.map( ( country ) => {
							return {
								value: country.dial_code,
								label: country.code + ' ' + country.dial_code,
							};
						} ) }
					/>
				) }

				<ScInput
					label="&nbsp;"
					type="tel"
					placeholder="9876543210"
					pattern="[0-9]{10}"
					id={ 'phone-field-' + blockID }
					value={ phoneNumber }
					onScChange={ ( e ) => {
						setPhoneNumber( e.target.value );
					} }
					help={ help }
				/>
			</div>
		</div>
	);
}
