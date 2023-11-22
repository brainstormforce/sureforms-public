import { RichText } from '@wordpress/block-editor';
import IntlTelInput from 'react-intl-tel-input';
import 'react-intl-tel-input/dist/main.css';

export const PhoneClassicStyle = ( { setAttributes, attributes, blockID } ) => {
	const { label, placeholder, required, autoCountry } = attributes;

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
				<IntlTelInput
				containerClassName="intl-tel-input srfm-group srfm-classic-phone-parent"
				inputClassName="srfm-classic-phone-element"
				fieldId={ `sfrm-classic-phone-${ blockID }` }
				placeholder={placeholder}
				pattern="[0-9]{10}"
				/>
			</div>
		</>
	);
};
