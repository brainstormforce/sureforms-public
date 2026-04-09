import { RichText } from '@wordpress/block-editor';
import { decodeHtmlEntities } from '@Blocks/util';
import HelpText from '@Components/misc/HelpText';
import ReactQuill from 'react-quill';
import { QuillToolbar, formats } from './utils';
import { useRef, useState, useCallback, useMemo } from '@wordpress/element';

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

	const toolbarRef = useRef( null );
	const [ isToolbarReady, setIsToolbarReady ] = useState( false );
	const handleToolbarRef = useCallback( ( node ) => {
		toolbarRef.current = node;
		setIsToolbarReady( !! node );
	}, [] );
	const quillModules = useMemo( () => ( {
		toolbar: {
			container: toolbarRef.current,
		},
	} ), [ isToolbarReady ] );

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
				}${ readOnly && defaultValue ? ' srfm-read-only' : '' }` }
			>
				{ isRichText ? (
					<div className="srfm-textarea-quill">
						<QuillToolbar ref={ handleToolbarRef } />
						{ isToolbarReady && (
							<ReactQuill
								formats={ formats }
								value={ defaultValue }
								modules={ quillModules }
							/>
						) }
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
