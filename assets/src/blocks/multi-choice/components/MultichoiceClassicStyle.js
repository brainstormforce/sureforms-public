import { useEffect } from '@wordpress/element';

export const MultichoiceClassicStyle = ( { attributes, blockID } ) => {
	const { label, required, options, single_selection } = attributes;
	useEffect( () => {
		const multichoiceOptions = document.getElementsByClassName(
			'sf-classic-multi-choice'
		);
		if ( window.innerWidth > 630 ) {
			for ( let x = 0; x < multichoiceOptions.length - 1; x++ ) {
				const optionHeight = multichoiceOptions[ x ].offsetHeight;
				const adjacentOptionHeight =
					multichoiceOptions[ x + 1 ].offsetHeight;
				if ( optionHeight > adjacentOptionHeight ) {
					multichoiceOptions[ x ].style.height = optionHeight + 'px';
					multichoiceOptions[ x + 1 ].style.height =
						optionHeight + 'px';
				} else {
					multichoiceOptions[ x ].style.height =
						adjacentOptionHeight + 'px';
					multichoiceOptions[ x + 1 ].style.height =
						adjacentOptionHeight + 'px';
				}
				x++;
			}
		}
	}, [] );
	return (
		<>
			<label className="srfm-classic-label-text" htmlFor="text">
				{ label }
				{ required && label && (
					<span className="text-red-500"> *</span>
				) }
			</label>
			<div className="srfm-radio-buttons flex flex-wrap mt-2 justify-between">
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
							<div className="flex items-start srfm-classic-radio-btn srfm-classic-multi-choice">
								<div className="pr-[5px] mt-[3px] relative flex">
									<i
										className="fa fa-check-circle text-base"
										aria-hidden="true"
									></i>
									<i
										className="fa-regular fa-circle text-sm absolute text-gray-300"
										aria-hidden="true"
									></i>
								</div>
								<div>
									<article
										id={ `srfm-multi-choice-option-${ blockID }-${ i }` }
										className="text-sm font-medium leading-6 text-gray-900 mt-[-0.5px]"
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
