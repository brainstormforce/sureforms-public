export const MultichoiceClassicStyle = ( { attributes, blockID } ) => {
	const { label, required, options, single_selection } = attributes;
	return (
		<>
			<label className="srfm-classic-label-text" htmlFor="text">
				{ label }
				{ required && label && (
					<span className="srfm-text-red-500"> *</span>
				) }
			</label>
			<div className="srfm-radio-buttons srfm-flex srfm-flex-wrap srfm-mt-[2">
				{ options.map( ( option, key, i = 0 ) => {
					i++;
					return (
						<label key={ key } className="srfm-classic-radio">
							<input
								type={ single_selection ? 'radio' : 'checkbox' }
								name={ single_selection ? 'sf-radio-$id' : '' }
								id={ `srfm-multi-choice-${ blockID }-${ i }` }
								className="srfm-multi-choice"
							/>
							<div className="srfm-flex srfm-items-start srfm-classic-radio-btn srfm-classic-multi-choice">
								<div className="srfm-pr-[5px] srfm-mt-[3px] srfm-relative srfm-flex">
									<i
										className="fa fa-check-circle srfm-text-base"
										aria-hidden="true"
									></i>
									<i
										className="fa-regular fa-circle srfm-text-sm srfm-absolute srfm-text-gray-300"
										aria-hidden="true"
									></i>
								</div>
								<div>
									<article
										id={ `srfm-multi-choice-option-${ blockID }-${ i }` }
										className="srfm-text-sm srfm-font-medium srfm-leading-6 srfm-text-gray-900 srfm-mt-[-0.5px]"
									>
										{ option }
									</article>
								</div>
							</div>
						</label>
					);
				} ) }
			</div>
		</>
	);
};
