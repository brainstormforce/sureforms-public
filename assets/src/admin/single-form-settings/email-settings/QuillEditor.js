import { useCallback } from '@wordpress/element';
import ReactQuill from 'react-quill';
import EditorToolbar, { modules, formats } from './EditorToolbar';
import { TabPanel } from '@wordpress/components';

const Editor = ( {
	handleEmailBodyContent,
	content,
	formData,
	setFormData,
} ) => {
	const TOOLBAR_OPTIONS_VISUAL = [
		[
			{ header: [ 1, 2, 3, 4, 5, 6, false ] },
			'bold',
			'italic',
			{ list: 'ordered' },
			{ list: 'bullet' },
			'link',
			'blockquote',
			{ align: [] },
			'underline',
			'strike',
			{ color: [] },
			{ background: [] },
			'clean',
			{ indent: '-1' },
			{ indent: '+1' },
			'code',
		],
	];

	const moduleVisual = {
		toolbar: TOOLBAR_OPTIONS_VISUAL,
	};

	const onSelect = () => {};

	var TOOLBAR_OPTIONS_TEXT = [
		[
			'bold',
			'italic',
			'link',
			'underline',
			{ list: 'ordered' },
			{ list: 'bullet' },
			'code',
		],
	];

	const editorTextWrapRef = useCallback( ( wrapper ) => {
		if ( wrapper == null ) return;
		const textArea = document.querySelector( '#srfm-editor-html' );

		new Quill( textArea, {
			theme: 'snow',
			modules: { toolbar: TOOLBAR_OPTIONS_TEXT },
		} );
	}, [] );

	return (
		<TabPanel
			activeClass="srfm-active-editor"
			onSelect={ ( tab ) => onSelect( tab ) }
			tabs={ [
				{
					name: 'srfm-editor-visual',
					title: 'Visual',
					className: 'srfm-editor-visual',
				},
				{
					name: 'srfm-editor-html',
					title: 'HTML',
					className: 'srfm-editor-html',
				},
			] }
		>
			{ ( tab ) => {
				switch ( tab.title ) {
					case 'Visual':
						return (
							<div className="srfm-editor-visual">
								<EditorToolbar />
								<ReactQuill
									formats={ formats }
									modules={ modules }
									value={ content }
									onChange={ ( newContent ) => {
										handleEmailBodyContent( newContent );
									} }
								/>
							</div>
						);
					case 'HTML':
						return (
							<textarea
								id="srfm-editor-html"
								onChange={ ( e ) =>
									setFormData( {
										...formData,
										email_body: e.target.value,
									} )
								}
								className="srfm-editor-textarea"
							>
								{ content }
							</textarea>
						);
					default:
						return null;
				}
			} }
		</TabPanel>
	);
};

export default Editor;
