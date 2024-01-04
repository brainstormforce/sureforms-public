import {
	useRef,
	useMemo,
	useState,
	useEffect,
	useCallback,
} from '@wordpress/element';
import JoditEditor from 'jodit-react';
// import ReactQuill, { Quill } from 'react-quill';
import { BaseControl, TabPanel } from '@wordpress/components';

const Editor = ( { handleEmailBodyContent, content } ) => {
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
		],
	];

	const moduleVisual = {
		toolbar: TOOLBAR_OPTIONS_VISUAL,
	};

	const onSelect = () => {};

	var TOOLBAR_OPTIONS_TEXT = [
		[ 'bold', 'italic', 'underline', 'strike' ], // toggled buttons
		// [ 'blockquote', 'code-block' ],

		// [ { header: 1 }, { header: 2 } ], // custom button values
		// [ { list: 'ordered' }, { list: 'bullet' } ],
		// [ { script: 'sub' }, { script: 'super' } ], // superscript/subscript
		// [ { indent: '-1' }, { indent: '+1' } ], // outdent/indent
		// [ { direction: 'rtl' } ], // text direction

		// [ { size: [ 'small', false, 'large', 'huge' ] } ], // custom dropdown
		// [ { header: [ 1, 2, 3, 4, 5, 6, false ] } ],

		// [ { color: [] }, { background: [] } ], // dropdown with defaults from theme
		// [ { font: [] } ],
		// [ { align: [] } ],

		// [ 'clean' ], // remove formatting button
	];

	const editorTextWrap = useCallback( ( wrapper ) => {
		if ( wrapper == null ) return;
		const textArea = document.querySelector( '#srfm-editor-text' );
		editorTextWrap.current.append( textArea );
		wrapper.innerHTML = '';

		// const q =
		new Quill( textArea, {
			theme: 'snow',
			modules: { toolbar: TOOLBAR_OPTIONS_TEXT },
		} );
	}, [] );

	// useEffect( () => {
	// 	new Quill( '#srfm-editor-text', {
	// 		modules: {
	// 			toolbar: toolbarOptions,
	// 		},
	// 		theme: 'snow',
	// 	} );

	// 	return () => {
	// 		editorTextWrap;
	// 	};
	// }, [] );

	return (
		<TabPanel
			// activeClass="active-recaptcha"
			onSelect={ ( tab ) => onSelect( tab ) }
			tabs={ [
				{
					name: 'srfm-editor-visual',
					title: 'Visual',
					className: 'srfm-editor-visual',
				},
				{
					name: 'srfm-editor-text',
					title: 'Text',
					className: 'srfm-editor-text',
				},
			] }
		>
			{ ( tab ) => {
				switch ( tab.title ) {
					case 'Visual':
						return (
							<ReactQuill
								modules={ moduleVisual }
								value={ content }
								onChange={ ( newContent ) => {
									handleEmailBodyContent( newContent );
								} }
							/>
						);
					case 'Text':
						return (
							<div ref={ editorTextWrap }>
								<textarea
									id="srfm-editor-text"
									onChange={ ( newContent ) => {
										handleEmailBodyContent( newContent );
									} }
									className="srfm-editor-textarea"
								>
									{ content }
								</textarea>
							</div>
						);
					default:
						return null;
				}
			} }
		</TabPanel>
	);
};

export default Editor;
