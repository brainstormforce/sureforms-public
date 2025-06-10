import { RichText } from '@wordpress/block-editor';
import { decodeHtmlEntities } from '@Blocks/util';
import HelpText from '@Components/misc/HelpText';

export const InputComponent = ( { attributes, blockID, setAttributes } ) => {
	const { label, placeholder, required, defaultValue, help, readOnly } = attributes;

	const isRequired = required ? ' srfm-required' : '';
	const slug = 'input';

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
				<input
					id={ `srfm-${ slug }-confirm-${ blockID }` }
					type="text"
					value={ defaultValue }
					className={ `srfm-input-common srfm-input-${ slug }` }
					placeholder={ placeholder }
					required={ required }
					readOnly={ readOnly }
				/>
			</div>
		</>
	);
};
