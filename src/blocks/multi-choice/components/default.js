import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { RichText } from '@wordpress/block-editor';
import { decodeHtmlEntities } from '@Blocks/util';
import parse from 'html-react-parser';
import svgIcons from '@Svg/svgs.json';
import HelpText from '@Components/misc/HelpText';

export const MultiChoiceComponent = ( {
	attributes,
	blockID,
	isSelected,
	addOption,
	changeOption,
	deleteOption,
	setAttributes,
} ) => {
	const { label, required, options, choiceWidth, help, singleSelection } =
		attributes;
	const isRequired = required ? ' srfm-required' : '';
	const slug = 'multi-choice';
	const defaultChoiceWidth = '100';
	const choiceWidthString = choiceWidth
		? String( choiceWidth )
		: defaultChoiceWidth;
	const choiceWidthClass = choiceWidth
		? choiceWidthString.replace( '.', '-' )
		: defaultChoiceWidth;

	const editView = options.map( ( option, index ) => {
		return (
			<div key={ index } className={ `srfm-multi-choice-single` }>
				<label htmlFor={ option.optionTitle }></label>
				<input
					className="srfm-input-common"
					aria-label={ option.optionTitle }
					onChange={ ( e ) =>
						changeOption(
							{
								optionTitle: e.target.value,
							},
							index
						)
					}
					type="textarea"
					value={ option.optionTitle }
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
		const selectionSvg = singleSelection
			? parse( svgIcons[ 'circle-unchecked' ] )
			: parse( svgIcons[ 'square-unchecked' ] );
		return (
			<div
				className={ `srfm-block-wrap srfm-choice-width-${ choiceWidthClass }` }
			>
				{ options.map( ( option, key ) => {
					return (
						<label key={ key } className="srfm-multi-choice-single">
							<div className="srfm-block-content-wrap">
								<span
									className={ `srfm-icon srfm-${ slug }-icon` }
								>
									{ selectionSvg }
								</span>
								<p>{ option.optionTitle }</p>
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
				onChange={ ( value ) => {
					setAttributes( { label: decodeHtmlEntities( value ) } );
				} }
				className={ `srfm-block-label${ isRequired }` }
				multiline={ false }
				id={ blockID }
				allowedFormats={ [] }
			/>
			<HelpText
				help={ help }
				setAttributes={ setAttributes }
				block_id={ blockID }
			/>
			{ isSelected && (
				<>
					<div
						className={ `srfm-block-wrap srfm-choice-width-${ choiceWidthClass }` }
					>
						{ editView }
						<div className={ `srfm-${ slug }-add-option-wrapper` }>
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
