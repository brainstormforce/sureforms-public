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
					<span className="text-red-500"> *</span>
				) }
			</label>
			<div className="mt-2">
				<input
					type="text"
					className=" srfm-classic-address-element !top-[3px] !rounded-t-md "
					id={ `srfm-address-line-1-${ blockID }` }
					aria-required={ required ? 'true' : 'false' }
					placeholder={ lineOnePlaceholder }
				/>
			</div>
			<div>
				<input
					type="text"
					className=" srfm-classic-address-element !top-[2px] "
					id={ `srfm-address-line-2-${ blockID }` }
					placeholder={ lineTwoPlaceholder }
				/>
			</div>
			<div className="flex -space-x-px">
				<div className="w-1/2 min-w-0 flex-1">
					<input
						type="text"
						className=" srfm-classic-address-element !top-[1px] "
						id={ `srfm-address-city-${ blockID }` }
						aria-required={ required ? 'true' : 'false' }
						placeholder={ cityPlaceholder }
					/>
				</div>
				<div className="min-w-0 flex-1">
					<input
						type="text"
						className=" srfm-classic-address-element !top-[1px] "
						id={ `srfm-address-state-${ blockID }` }
						aria-required={ required ? 'true' : 'false' }
						placeholder={ statePlaceholder }
					/>
				</div>
			</div>
			<div className="-space-y-px rounded-md shadow-sm">
				<div>
					<label htmlFor="country" className="sr-only">
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
					<label htmlFor="postal-code" className="sr-only">
						{ __( 'ZIP / Postal code', 'sureforms' ) }
					</label>
					<input
						type="text"
						autoComplete="postal-code"
						className="srfm-classic-address-element !rounded-b-md"
						id={ `srfm-address-postal-${ blockID }` }
						aria-required={ required ? 'true' : 'false' }
						placeholder={ postalPlaceholder }
					/>
				</div>
			</div>
		</>
	);
};
