import { __ } from '@wordpress/i18n';

export const AddressClassicStyle = ( { attributes, countries, blockID } ) => {
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

	return (
		<>
			<label htmlFor="text" className="srfm-classic-label-text">
				{ label }
				{ required && label && (
					<span className="srfm-text-red-500"> *</span>
				) }
			</label>
			<div className="srfm-mt-2">
				<input
					type="text"
					className=" srfm-classic-address-element !srfm-top-[3px] !srfm-rounded-t-md "
					id={ `srfm-address-line-1-${ blockID }` }
					aria-required={ required ? 'true' : 'false' }
					placeholder={ lineOnePlaceholder }
				/>
			</div>
			<div>
				<input
					type="text"
					className=" srfm-classic-address-element !srfm-top-[2px] "
					id={ `srfm-address-line-2-${ blockID }` }
					placeholder={ lineTwoPlaceholder }
				/>
			</div>
			<div className="flex -space-x-px">
				<div className="srfm-w-1/2 srfm-min-w-0 srfm-flex-1">
					<input
						type="text"
						className=" srfm-classic-address-element !srfm-top-[1px] "
						id={ `srfm-address-city-${ blockID }` }
						aria-required={ required ? 'true' : 'false' }
						placeholder={ cityPlaceholder }
					/>
				</div>
				<div className="srfm-min-w-0 srfm-flex-1">
					<input
						type="text"
						className=" srfm-classic-address-element !srfm-top-[1px] "
						id={ `srfm-address-state-${ blockID }` }
						aria-required={ required ? 'true' : 'false' }
						placeholder={ statePlaceholder }
					/>
				</div>
			</div>
			<div className="-space-y-px srfm-rounded-md srfm-shadow-sm">
				<div>
					<label htmlFor="country" className="srfm-sr-only">
						{ __( 'Country', 'sureforms' ) }
					</label>
					<select
						id={ `srfm-address-country-${ blockID }` }
						autoComplete="country-name"
						className="srfm-classic-adress-select"
					>
						{ countryPlaceholder === '' ? (
							''
						) : (
							<option value="" selected disabled hidden>
								{ countryPlaceholder }
							</option>
						) }
						{ countries.map( ( country, key ) => {
							return (
								<option key={ key } value={ country.name }>
									{ country.name }
								</option>
							);
						} ) }
					</select>
				</div>
				<div>
					<label htmlFor="postal-code" className="srfm-sr-only">
						{ __( 'ZIP / Postal code', 'sureforms' ) }
					</label>
					<input
						type="text"
						autoComplete="postal-code"
						className="srfm-classic-address-element !srfm-rounded-b-md"
						id={ `srfm-address-postal-${ blockID }` }
						aria-required={ required ? 'true' : 'false' }
						placeholder={ postalPlaceholder }
					/>
				</div>
			</div>
		</>
	);
};
