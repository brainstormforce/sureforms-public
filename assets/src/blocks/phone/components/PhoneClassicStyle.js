import data from '../phoneCodes.json';
import { RichText } from '@wordpress/block-editor';

export const PhoneClassicStyle = ( {
	attributes,
	blockID,
	handleChange,
	setAttributes,
} ) => {
	const { label, placeholder, required, defaultValue, defaultCountryCode } =
		attributes;

	const isRequired = required ? 'required' : '';

	return (
		<>
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => setAttributes( { label: value } ) }
				className={ `sf-classic-label-text ${ isRequired }` }
				multiline={ false }
				id={ blockID }
			/>
			<div className="relative mt-2">
				<div className="group sf-classic-phone-parent">
					<div className="absolute inset-y-0 left-0 flex items-center">
						<select
							placeholder="US +1"
							onChange={ ( e ) => handleChange( e ) }
							className="sf-classic-phone-select"
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
						className="sf-classic-phone-element"
						placeholder={ placeholder }
						pattern="[0-9]{10}"
						value={ defaultValue }
					/>
				</div>
			</div>
		</>
	);
};
