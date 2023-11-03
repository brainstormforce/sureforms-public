import { RichText } from '@wordpress/block-editor';

export const MultichoiceClassicStyle = ( {
	attributes,
	blockID,
	setAttributes,
} ) => {
	const { label, required, options, single_selection } = attributes;

	const isRequired = required ? 'required' : '';

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
			<div className="srfm-radio-buttons flex flex-wrap mt-2">
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
