import { RichText } from '@wordpress/block-editor';
import parse from 'html-react-parser';
import svgIcons from '@Svg/svgs.json';

export const DateTimeComponent = ( { attributes, setAttributes, blockID } ) => {
	const { label, required, fieldType } = attributes;
	const isRequired = required ? ' srfm-required' : '';
	const slug = 'datepicker';
	const calender = parse( svgIcons.calender );
	const clock = parse( svgIcons.clock );

	return (
		<>
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => setAttributes( { label: value } ) }
				className={ `srfm-block-label ${ isRequired }` }
				multiline={ false }
				id={ blockID }
			/>
			<div className="srfm-block-wrap srfm-with-icon">
				{ 'time' === fieldType ? (
					<span
						className={ `srfm-icon srfm-${ slug }-icon srfm-input-icon` }
					>
						{ clock }
					</span>
				) : (
					<span
						className={ `srfm-icon srfm-${ slug }-icon srfm-input-icon` }
					>
						{ calender }
					</span>
				) }
				<input
					id={ `srfm-${ slug }-${ blockID }` }
					type="text"
					className={ `srfm-input-common srfm-input-${ slug }` }
					required={ required }
				/>
			</div>
		</>
	);
};
