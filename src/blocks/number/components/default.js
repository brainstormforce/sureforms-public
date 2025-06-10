import { RichText } from '@wordpress/block-editor';
import { decodeHtmlEntities } from '@Blocks/util';
import HelpText from '@Components/misc/HelpText';

export const NumberComponent = ( { attributes, blockID, setAttributes } ) => {
	const {
		label,
		placeholder,
		required,
		defaultValue,
		minValue,
		maxValue,
		help,
		prefix,
		suffix,
		readOnly,
	} = attributes;

	const isRequired = required ? ' srfm-required' : '';
	const slug = 'number';

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
			<div className={ `srfm-block-wrap${ readOnly ? ' srfm-read-only' : '' }` }>
				<div className="srfm-input-content">
					{ prefix && (
						<span className="srfm-number-prefix"> { prefix } </span>
					) }
					<input
						className={ `srfm-input-common srfm-input-${ slug }` }
						id={ `srfm-${ slug }-${ blockID }` }
						type={ 'text' }
						defaultValue={ defaultValue }
						value={ defaultValue }
						readOnly={ true }
						placeholder={ placeholder }
						required={ required }
						min={ minValue }
						max={ maxValue }
					/>
					{ suffix && (
						<span className="srfm-number-suffix"> { suffix } </span>
					) }
				</div>
			</div>
		</>
	);
};
