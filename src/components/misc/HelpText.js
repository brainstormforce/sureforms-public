import { RichText } from '@wordpress/block-editor';
import { decodeHtmlEntities } from '@Blocks/util';

const HelpText = ( { help, setAttributes, block_id } ) => {
	return help !== '' ? (
		<RichText
			tagName="label"
			value={ help }
			onChange={ ( value ) => {
				setAttributes( { help: decodeHtmlEntities( value ) } );
			} }
			className="srfm-description"
			multiline={ false }
			id={ block_id }
			allowedFormats={ [] }
		/>
	) : null;
};

export default HelpText;
