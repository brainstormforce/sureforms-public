import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { RichText } from '@wordpress/block-editor';

export const MultichoiceClassicStyle = ( {
	attributes,
	blockID,
	isSelected,
	addOption,
	changeOption,
	deleteOption,
	setAttributes,
} ) => {
	const { label, required, options } = attributes;

	const editView = options.map( ( option, index ) => {
		return (
			<div key={ index }>
				<label htmlFor={ option.optiontitle }></label>
				<input
					className="!srfm-rounded-md !srfm-border-gray-300"
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
			<div className="srfm-radio-buttons srfm-flex srfm-flex-wrap srfm-mt-2">
				{ options.map( ( option, key, i = 0 ) => {
					i++;
					return (
						<label key={ key } className="srfm-classic-radio">
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
										{ option.optiontitle }
									</article>
								</div>
							</div>
						</label>
					);
				} ) }
			</div>
		);
	};

	const isRequired = required ? 'srfm-required' : '';

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
