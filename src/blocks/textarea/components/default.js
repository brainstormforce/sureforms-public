import { RichText } from '@wordpress/block-editor';
import { decodeHtmlEntities } from '@Blocks/util';
import HelpText from '@Components/misc/HelpText';
import ReactQuill from 'react-quill';
import { QuillToolbar, modules, formats } from './utils';
import { useRef } from '@wordpress/element';

export const TextareaComponent = ( { attributes, blockID, setAttributes } ) => {
	const {
		label,
		placeholder,
		required,
		maxLength,
		defaultValue,
		rows,
		cols,
		help,
		isRichText,
		readOnly,
	} = attributes;
	const isRequired = required ? ' srfm-required' : '';
	const slug = 'textarea';

	const uniqueIDRef = useRef(
		Math.random().toString( 36 ).substring( 2, 8 )
	);
	const quillId = `quill-id-${ uniqueIDRef.current }`;

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
			<div
				className={ `srfm-block-wrap${
					isRichText ? ' srfm-richtext' : ''
				}${ readOnly ? ' srfm-read-only' : '' }` }
			>
				{ isRichText ? (
					<div className="srfm-textarea-quill">
						<QuillToolbar id={ quillId } />
						<ReactQuill
							formats={ formats }
							value={ defaultValue }
							modules={ modules( quillId ) }
						/>
					</div>
				) : (
					<textarea
						required={ required }
						label={ label }
						placeholder={ placeholder }
						value={ defaultValue }
						rows={ rows }
						cols={ cols }
						maxLength={ maxLength }
						className={ `srfm-input-common srfm-input-${ slug }` }
					></textarea>
				) }
			</div>
		</>
	);
};
