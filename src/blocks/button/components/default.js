import { RichText } from '@wordpress/block-editor';
import { decodeHtmlEntities } from '@Blocks/util';

export const InputComponent = ( { attributes, blockID, setAttributes } ) => {
	const { label, placeholder, required, defaultValue } = attributes;

	const isRequired = required ? ' srfm-required' : '';
	const slug = 'input';

	return (
		<>
			<div
				className="srfm-block-label"
				style={ {
					height: '1em',
				} }
			/>
			<button
				style={ {
					backgroundColor: '#0E4372',
					color: 'white',
					padding: '10px 20px',
					border: 'none',
					borderRadius: '5px',
				} }
				id={ `srfm-${ slug }-confirm-${ blockID }` }
				type="text"
				className={ `srfm-input-common srfm-input-${ slug }` }
				placeholder={ placeholder }
				required={ required }
			>
				<RichText
					tagName="label"
					value={ 'SUBMIT' }
					onChange={ ( value ) => {
						setAttributes( { label: decodeHtmlEntities( value ) } );
					} }
					// className={ `srfm-block-label${ isRequired }` }
					multiline={ false }
					id={ blockID }
					allowedFormats={ [] }
				/>
			</button>
		</>
	);
};
