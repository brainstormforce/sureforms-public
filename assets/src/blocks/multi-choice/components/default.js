import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { RichText } from '@wordpress/block-editor';

export const MultiChoiceComponent = ( {
	attributes,
	blockID,
	isSelected,
	addOption,
	changeOption,
	deleteOption,
	setAttributes,
} ) => {
	const { label, required, options } = attributes;
	const isRequired = required ? ' srfm-required' : '';
	const slug = "multi-choice";

	const editView = options.map( ( option, index ) => {
		return (
			<div key={ index } className={`srfm-multi-choice-single`}>
				<label htmlFor={ option.optiontitle }></label>
				<input
					className="srfm-input-common"
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
			<div className="srfm-block-wrap">
				{ options.map( ( option, key, i = 0 ) => {
					i++;
					return (
						<label key={ key } className="srfm-multi-choice-single">
							<div className="srfm-block-content-wrap">
								<span className={`srfm-icon srfm-${slug}-icon`}></span>
								<p>{ option.optiontitle }</p>
							</div>
						</label>
					);
				} ) }
			</div>
		);
	};

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
			{ isSelected && (
				<>
					<div className="srfm-block-wrap">
						{ editView }
						<div class={`srfm-${slug}-add-option-wrapper`}>
							<Button isPrimary onClick={ addOption }>
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
