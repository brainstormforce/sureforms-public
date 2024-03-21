import ReactQuill, { Quill } from 'react-quill';
import EditorToolbar, { modules, formats } from './EditorToolbar';
import { TabPanel } from '@wordpress/components';

const Editor = ( {
	handleEmailBodyContent,
	content,
	formData,
	setFormData,
} ) => {
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
