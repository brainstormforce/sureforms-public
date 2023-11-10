import data from '../phoneCodes.json';
import { RichText } from '@wordpress/block-editor';

export const PhoneThemeStyle = ( {
	attributes,
	blockID,
	handleChange,
	setAttributes,
} ) => {
	const { label, placeholder, required, defaultValue, defaultCountryCode } =
		attributes;

	const isRequired = required ? 'srfm-required' : '';

	return (
		<>
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => setAttributes( { label: value } ) }
				className={ `srfm-text-primary ${ isRequired }` }
				multiline={ false }
				id={ blockID }
			/>
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
						id={ 'srfm-phone-field-' + blockID }
						placeholder="US +1"
						onChange={ ( e ) => handleChange( e ) }
					>
						{ data.map( ( country, i ) => {
							return (
								<option
									key={ i }
									value={
										country.code + ' ' + country.dial_code
									}
									selected={
										country.dial_code ===
											defaultCountryCode && true
									}
								>
									{ country.code + ' ' + country.dial_code }
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
					id={ 'srfm-phone-field-' + blockID }
					value={ defaultValue }
				/>
			</div>
		</>
	);
};
