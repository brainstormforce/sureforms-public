import { RichText } from '@wordpress/block-editor';
import { decodeHtmlEntities } from '@Blocks/util';
import parse from 'html-react-parser';
import svgIcons from '@Svg/svgs.json';
import HelpText from '@Components/misc/HelpText';
import renderSVG from '@Components/icon-picker/renderIcon.js';

export const MultiChoiceComponent = ( {
	attributes,
	blockID,
	setAttributes,
} ) => {
	const {
		label,
		required,
		options,
		choiceWidth,
		help,
		singleSelection,
		verticalLayout,
		optionType,
	} = attributes;
	const isRequired = required ? ' srfm-required' : '';
	const slug = 'multi-choice';
	const defaultChoiceWidth = '100';
	const choiceWidthString = choiceWidth
		? String( choiceWidth )
		: defaultChoiceWidth;
	const choiceWidthClass = choiceWidth
		? choiceWidthString.replace( '.', '-' )
		: defaultChoiceWidth;

	const View = () => {
		const selectionSvg = singleSelection
			? parse( svgIcons[ 'circle-unchecked' ] )
			: parse( svgIcons[ 'square-unchecked' ] );
		return (
			<div
				className={ `srfm-block-wrap srfm-choice-width-${ choiceWidthClass }${
					verticalLayout ? ' srfm-vertical-layout' : ''
				}` }
			>
				{ options.map( ( option, key ) => {
					return (
						<label key={ key } className="srfm-multi-choice-single">
							<div className="srfm-block-content-wrap">
								<div className="srfm-option-container">
									{ optionType === 'icon' && option.icon && (
										<span className="srfm-option-icon">
											{ ' ' }
											{ renderSVG( option.icon ) }{ ' ' }
										</span>
									) }
									{ optionType === 'image' &&
										option.image && (
										<span className="srfm-option-image">
											<img
												src={ option.image }
												alt={ option.optionTitle }
											/>
										</span>
									) }
									<p>{ option.optionTitle }</p>
								</div>
								<div className="srfm-icon-container">
									<span
										className={ `srfm-icon srfm-${ slug }-icon` }
									>
										{ selectionSvg }
									</span>
								</div>
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
			<View />
		</>
	);
};
