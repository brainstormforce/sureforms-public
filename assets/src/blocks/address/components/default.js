import { __ } from '@wordpress/i18n';
import { RichText } from '@wordpress/block-editor';

export const AddressBlock = ( {
	attributes,
	setAttributes,
	countries,
	blockID,
} ) => {
	const {
		required,
		label,
		lineOnePlaceholder,
		lineTwoPlaceholder,
		cityPlaceholder,
		statePlaceholder,
		postalPlaceholder,
		countryPlaceholder,
	} = attributes;

	const isRequired = required ? ' srfm-required' : '';
	const slug = "address";
	return (
		<>
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => setAttributes( { label: value } ) }
				className={ `srfm-block-label${ isRequired }` }
				multiline={ false }
				id={ blockID }
			/>
			<div className="srfm-block-wrap">
				<input
					type="text"
					className={`srfm-input-common srfm-input-${slug}-line-1`}
					id={ `srfm-${slug}-line-1-${ blockID }` }
					aria-required={ required ? 'true' : 'false' }
					placeholder={ lineOnePlaceholder }
					readOnly
				/>
				<input
					type="text"
					className={`srfm-input-common srfm-input-${slug}-line-2`}
					id={ `srfm-${slug}-line-2-${ blockID }` }
					placeholder={ lineTwoPlaceholder }
					readOnly
				/>
				<input
					type="text"
					className={`srfm-input-common srfm-input-${slug}-city`}
					id={ `srfm-${slug}-city-${ blockID }` }
					aria-required={ required ? 'true' : 'false' }
					placeholder={ cityPlaceholder }
					readOnly
				/>
				<input
					type="text"
					className={`srfm-input-common srfm-input-${slug}-state`}
					id={ `srfm-${slug}-state-${ blockID }` }
					aria-required={ required ? 'true' : 'false' }
					placeholder={ statePlaceholder }
					readOnly
				/>
				<div className={`srfm-${slug}-country-wrap srfm-dropdown-common-wrap`}>
					<select
						id={ `srfm-${slug}-country-${ blockID }` }
						autoComplete="country-name"
						className="srfm-input-common srfm-input-address-country srfm-dropdown-common"
						readOnly
					>
						{ countryPlaceholder === '' ? (
							''
						) : (
							<option value="" selected disabled hidden>
								{ countryPlaceholder }
							</option>
						) }
					</select>
				</div>
				<input 
					type="text"
					className={`srfm-input-common srfm-input-${slug}-postal-code`}
					id={ `srfm-${slug}-postal-${ blockID }` }
					aria-required={ required ? 'true' : 'false' }
					placeholder={ postalPlaceholder }
					readOnly
				/>
			</div>
		</>
	);
};
