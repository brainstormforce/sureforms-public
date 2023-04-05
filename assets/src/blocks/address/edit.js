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

import countries from './countries.json';

export default function Edit( { attributes, setAttributes } ) {
	const blockID = useBlockProps().id;
	const { required, label } = attributes;

	const labelStyles = {
		color: '#737373',
		fontSize: '14px',
	};

	const inputStyles = {
		marginTop: '14px',
		height: '43px',
	};

	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<PanelBody title="Attributes">
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
			<label htmlFor={ 'address-field-' + blockID }>
				{ label }
				{ required && <span style={ { color: 'red' } }> *</span> }
			</label>
			<div
				id={ 'address-field-' + blockID }
				style={ {
					display: 'flex',
					flexDirection: 'column',
					gap: '4px',
				} }
			>
				<input
					type="text"
					style={ inputStyles }
					id={ 'address-line-1-' + blockID }
					required={ required }
				/>
				<label
					style={ labelStyles }
					htmlFor={ 'address-line-1-' + blockID }
				>
					Address Line 1
				</label>
			</div>
			<div
				style={ {
					display: 'flex',
					flexDirection: 'column',
					gap: '4px',
				} }
			>
				<input
					type="text"
					style={ inputStyles }
					id={ 'address-line-2-' + blockID }
					required={ required }
				/>
				<label
					style={ labelStyles }
					htmlFor={ 'address-line-2-' + blockID }
				>
					Address Line 2
				</label>
			</div>
			<div style={ { display: 'flex', gap: '4px' } }>
				<div
					style={ {
						display: 'flex',
						flexDirection: 'column',
						gap: '4px',
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
						style={ labelStyles }
						htmlFor={ 'address-city-' + blockID }
					>
						City
					</label>
				</div>
				<div
					style={ {
						display: 'flex',
						flexDirection: 'column',
						gap: '4px',
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
						style={ labelStyles }
						htmlFor={ 'address-state-' + blockID }
					>
						State / Province / Region
					</label>
				</div>
			</div>
			<div style={ { display: 'flex', gap: '4px' } }>
				<div
					style={ {
						display: 'flex',
						flexDirection: 'column',
						gap: '4px',
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
						style={ labelStyles }
						htmlFor={ 'address-city-' + blockID }
					>
						Postal Code
					</label>
				</div>
				<div
					style={ {
						display: 'flex',
						flexDirection: 'column',
						gap: '4px',
						width: '100%',
					} }
				>
					<select
						style={ inputStyles }
						id={ 'address-country-' + blockID }
						required={ required }
					>
						{ countries.map( ( country, i ) => {
							return (
								<option key={ i } value={ country.name }>
									{ country.name }
								</option>
							);
						} ) }
					</select>
					<label
						style={ labelStyles }
						htmlFor={ 'address-country-' + blockID }
					>
						Country
					</label>
				</div>
			</div>
		</div>
	);
}
