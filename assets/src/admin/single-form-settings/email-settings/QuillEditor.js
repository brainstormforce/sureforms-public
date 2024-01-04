import { useCallback } from '@wordpress/element';
import ReactQuill, { Quill } from 'react-quill';
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

	const onSelect = () => {};

	// Add inline style instead of classes.
	Quill.register( Quill.import( 'attributors/style/align' ), true );

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
