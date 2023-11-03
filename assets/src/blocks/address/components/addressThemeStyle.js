import { RichText } from '@wordpress/block-editor';

export const AddressThemeStyle = ( {
	attributes,
	countries,
	blockID,
	setAttributes,
} ) => {
	const {
		required,
		label,
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

	const isRequired = required ? 'required' : '';
	const inputStyles = {
		marginTop: '14px',
	};
	return (
		<>
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => setAttributes( { label: value } ) }
				className={ isRequired }
				multiline={ false }
				id={ blockID }
			/>
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
						className="srfm-text-secondary text-size"
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
						className="srfm-text-secondary text-size"
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
							className="srfm-text-secondary text-size"
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
							className="srfm-text-secondary text-size"
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
							className="srfm-text-secondary text-size"
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
							className="srfm-text-secondary text-size"
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
									<option key={ i } value={ country.name }>
										{ country.name }
									</option>
								);
							} ) }
						</select>
					</div>
				</div>
			</div>
		</>
	);
};
