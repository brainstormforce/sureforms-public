import data from '../phoneCodes.json';

export const PhoneThemeStyle = ( { attributes, blockID, handleChange } ) => {
	const { label, placeholder, required, defaultValue, defaultCountryCode } =
		attributes;

	return (
		<>
			<label
				className="srfm-text-primary"
				htmlFor={ 'srfm-phone-field-' + blockID }
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
