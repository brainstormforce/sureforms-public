import { RichText } from '@wordpress/block-editor';

export const MultichoiceThemeStyle = ( {
	attributes,
	blockID,
	handleClick,
	selected,
	setAttributes,
} ) => {
	const { required, options, label, singleSelection, style } = attributes;
	const isRequired = required ? 'required' : '';

	return (
		<>
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => setAttributes( { label: value } ) }
				className={ `sf-text-primary ${ isRequired }` }
				multiline={ false }
				id={ blockID }
			/>
			{ options.map( ( option, i ) => {
				return (
					<div
						key={ i }
						style={ { display: 'flex', alignItems: 'center' } }
					>
						<input
							style={ {
								display:
									style === 'buttons' ? 'none' : 'inherit',
							} }
							id={ 'multi-choice-' + blockID + '-i-' + i }
							type={ singleSelection ? 'radio' : 'checkbox' }
							key={ i }
							name={
								singleSelection
									? 'radio' + blockID
									: 'checkbox-' + blockID + '-' + i
							}
							onClick={ () => handleClick( i ) }
						/>
						<label
							htmlFor={ 'multi-choice-' + blockID + '-i-' + i }
							className={ 'sureforms-multi-choice-label-button' }
							style={
								style === 'buttons'
									? {
										border: '2px solid',
										borderRadius: '10px',
										padding: '.5rem 1rem .5rem 1rem',
										width: '100%',
										backgroundColor: selected.includes(
											i
										)
											? 'black'
											: 'white',
										color: selected.includes( i )
											? 'white'
											: 'black',
									  }
									: null
							}
						>
							<span
								className={ 'multi-choice-option' + blockID }
								id={
									'multi-choice-option' + blockID + '-i-' + i
								}
							>
								{ option }
							</span>
						</label>
					</div>
				);
			} ) }
		</>
	);
};
