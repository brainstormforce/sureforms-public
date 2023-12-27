import { RichText } from '@wordpress/block-editor';
import parse from 'html-react-parser';
import svgIcons from '@Svg/svgs.json';

export const RatingComponent = ( { attributes, setAttributes, blockID } ) => {
	const { required, label, showNumbers, iconShape, maxValue } = attributes;
	const isRequired = required ? ' srfm-required' : '';

	const starIcon = parse( svgIcons.star );
	const heartIcon = parse( svgIcons.heart );
	const smileyIcon = parse( svgIcons.email );

	let svg = '';
	const slug = 'rating';
	switch ( iconShape ) {
		case 'star':
			svg = <span className="srfm-icon srfm-star-icon">{ starIcon }</span>;
			break;
		case 'heart':
			svg = <span className="srfm-icon srfm-heart-icon">{ heartIcon }</span>;
			break;
		case 'smiley':
			svg = (
				<span className="srfm-icon srfm-smiley-icon">{ smileyIcon }</span>
			);
			break;
		default:
			svg = <span className="srfm-icon srfm-star-icon">{ starIcon }</span>;
			break;
	}

	const ratingItems = [];
	for ( let i = 0; i < maxValue; i++ ) {
		ratingItems.push(
			<li key={ i }>
				{ svg }
				<span className={ `srfm-${ slug }-number` }>
					{ showNumbers ? i + 1 : '' }
				</span>
			</li>
		);
	}

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
			<ul>{ ratingItems }</ul>
		</>
	);
};
