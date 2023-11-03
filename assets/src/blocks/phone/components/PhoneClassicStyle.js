import data from '../phoneCodes.json';

export const PhoneClassicStyle = ( { attributes, blockID, handleChange } ) => {
	const { label, placeholder, required, defaultValue, defaultCountryCode } =
		attributes;

	return (
		<>
			<label
				className="srfm-classic-label-text"
				htmlFor={ 'srfm-text-input-' + blockID }
			>
				{ label }
				{ required && label && (
					<span style={ { color: 'red' } }> *</span>
				) }
			</label>
			<div className="relative mt-2">
				<div className="group srfm-classic-phone-parent">
					<div className="absolute inset-y-0 left-0 flex items-center">
						<select
							placeholder="US +1"
							onChange={ ( e ) => handleChange( e ) }
							className="srfm-classic-phone-select"
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
										{ country.code }
									</option>
								);
							} ) }
						</select>
					</div>
					<input
						type="tel"
						className="srfm-classic-phone-element"
						placeholder={ placeholder }
						pattern="[0-9]{10}"
						value={ defaultValue }
					/>
				</div>
			</div>
		</>
	);
};
