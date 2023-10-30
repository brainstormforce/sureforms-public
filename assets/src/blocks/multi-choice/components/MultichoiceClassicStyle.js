import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export const MultichoiceClassicStyle = ( { attributes, blockID, isSelected,addOption,changeOption } ) => {
	const { label, required, options, single_selection } = attributes;

	const editView = options.map( ( option, index ) => {
		return (
			<div key={ index } className="uagb-form-radio-option">
				<input
					type="radio"
					name={ `radio-${ blockID }` }
					value={ option.optiontitle }
					id={ option.optiontitle }
				/>
				<label // eslint-disable-line jsx-a11y/label-has-associated-control
					htmlFor={ option.optiontitle }
				></label>
				<input
					className="uagb-inner-input-view"
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
					className="uagb-form-radio-option-delete"
					icon="trash"
					label="Remove"
					onClick={ () => deleteOption( index ) }
				/>
			</div>
		);
	} );

	const OriginalView = ()=>{
		return (
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
										{ option.optiontitle }
									</article>
								</div>
							</div>
						</label>
					);
				} ) }
			</div>
		)
	}

	return (
		<>
			<label className="sf-classic-label-text" htmlFor="text">
				{ label }
				{ required && label && (
					<span className="text-red-500"> *</span>
				) }
			</label>
			{ isSelected && (
					<>
						<div className="uagb-forms-radio-controls">
							{ editView }
							<div>
								<Button isSecondary onClick={ addOption }>
									{ __( ' + Add Option ', 'ultimate-addons-for-gutenberg' ) }
								</Button>
							</div>
						</div>
					</>
				) }

				{ ! isSelected && <OriginalView  /> }
		</>
	);
};
