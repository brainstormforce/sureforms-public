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
				className={ `sf-classic-label-text ${ isRequired }` }
				multiline={ false }
				id={ blockID }
			/>
			<div className="radio-buttons flex flex-wrap mt-2">
				{ options.map( ( option, key, i = 0 ) => {
					i++;
					return (
						<label key={ key } className="classic-sf-radio">
							<input
								type={ single_selection ? 'radio' : 'checkbox' }
								name={ single_selection ? 'sf-radio-$id' : '' }
								id={ `sureforms-multi-choice-${ blockID }-${ i }` }
								className="sureforms-multi-choice"
							/>
							<div className="flex items-start classic-radio-btn sf-classic-multi-choice">
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
										id={ `multi-choice-option-${ blockID }-${ i }` }
										className="text-sm font-medium leading-6 text-primary_color mt-[-0.5px]"
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
