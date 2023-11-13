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

	const isRequired = required ? 'srfm-required' : '';

	return (
		<>
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => setAttributes( { label: value } ) }
				className={ `srfm-classic-label-text ${ isRequired }` }
				multiline={ false }
				id={ blockID }
			/>
			<div className="srfm-relative srfm-mt-2">
				<div className="srfm-group srfm-classic-phone-parent">
					<div className="srfm-absolute srfm-inset-y-0 srfm-left-0 srfm-flex srfm-items-center">
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
