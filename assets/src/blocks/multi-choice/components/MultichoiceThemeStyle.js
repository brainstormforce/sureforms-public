import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { RichText } from '@wordpress/block-editor';

export const MultichoiceThemeStyle = ( {
	attributes,
	blockID,
	handleClick,
	selected,
	isSelected,
	addOption,
	changeOption,
	deleteOption,
	setAttributes,
} ) => {
	const { required, options, label, singleSelection, style } = attributes;

	const editView = options.map( ( option, index ) => {
		return (
			<div key={ index }>
				<label htmlFor={ option.optiontitle }></label>
				<input
					aria-label={ option.optiontitle }
					onChange={ ( e ) =>
						changeOption(
							{
								optiontitle: e.target.value,
							},
							index
						)
					}
					type="text"
					value={ option.optiontitle }
				/>
				<Button
					icon="trash"
					label="Remove"
					onClick={ () => deleteOption( index ) }
				/>
			</div>
		);
	} );

	const OriginalView = () => {
		return (
			<>
				{ options.map( ( option, i ) => {
					return (
						<div
							key={ i }
							style={ { display: 'flex', alignItems: 'center' } }
						>
							<input
								style={ {
									display:
										style === 'buttons'
											? 'none'
											: 'inherit',
								} }
								id={
									'srfm-multi-choice-' + blockID + '-i-' + i
								}
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
								htmlFor={
									'srfm-multi-choice-' + blockID + '-i-' + i
								}
								className={ 'srfm-multi-choice-label-button' }
								style={
									style === 'buttons'
										? {
											border: '2px solid',
											borderRadius: '10px',
											padding:
													'.5rem 1rem .5rem 1rem',
											width: '100%',
											backgroundColor:
													selected.includes( i )
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
									className={
										'srfm-multi-choice-option' + blockID
									}
									id={
										'srfm-multi-choice-option' +
										blockID +
										'-i-' +
										i
									}
								>
									{ option.optiontitle }
								</span>
							</label>
						</div>
					);
				} ) }
			</>
		);
	};

	const isRequired = required ? 'srfm-required' : '';

	return (
		<>
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => setAttributes( { label: value } ) }
				className={ `srfm-text-primary ${ isRequired }` }
				multiline={ false }
				id={ blockID }
			/>
			{ isSelected && (
				<>
					<div>
						{ editView }
						<div>
							<Button isSecondary onClick={ addOption }>
								{ __( ' + Add Option ', 'sureforms' ) }
							</Button>
						</div>
					</div>
				</>
			) }
			{ ! isSelected && <OriginalView /> }
		</>
	);
};
