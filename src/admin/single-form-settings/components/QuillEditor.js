import ReactQuill, { Quill } from 'react-quill';
import EditorToolbar, { modules, formats } from './email-settings/EditorToolbar';
import { TabPanel } from '@wordpress/components';
import { generateDropDownOptions } from '@Utils/Helpers';
import SmartTagList from '@Components/misc/SmartTagList';
import svgIcons from '@Image/single-form-logo.json';
import parse from 'html-react-parser';
import { __ } from '@wordpress/i18n';
import { useRef } from '@wordpress/element';

const Editor = ( {
	handleContentChange,
	content,
} ) => {
	const dropdownIcon = parse( svgIcons.downArrow );
	const quillRef = useRef( null );
	const editorTabs = [
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
	];
	const activeTabRef = useRef( 'srfm-editor-visual' );
	const insertTextAtEnd = ( text ) => {
		const quillInstance = quillRef.current.getEditor();
		const length = quillInstance.getLength();
		quillInstance.insertText( length - 1, text );
	};

	const insertSmartTag = ( tag ) => {
		if ( 'srfm-editor-visual' === activeTabRef.current ) {
			insertTextAtEnd( tag );
		} else {
			handleContentChange( content + tag );
		}
	};

	const genericSmartTags = window.srfm_block_data?.smart_tags_array ? Object.entries( window.srfm_block_data.smart_tags_array ) : [];
	const formSmartTags = window.sureforms?.formSpecificSmartTags ?? [];

	// Add inline style instead of classes.
	Quill.register( Quill.import( 'attributors/style/align' ), true );

	return (
		<>
			<SmartTagList
				icon={ dropdownIcon }
				label={ __( 'Select Shortcodes', 'sureforms' ) }
				text={ __( 'Add Shortcode', 'sureforms' ) }
				cssClass={ 'srfm-editor-dropdown' }
				optionsCallback={ generateDropDownOptions }
				tagsArray={
					[
						{
							tags: formSmartTags,
							label: __( 'Form input tags', 'sureforms' ),
						},
						{
							tags: genericSmartTags,
							label: __( 'Form input tags', 'sureforms' ),
						},
					]

				}
				setTargetData={ insertSmartTag }

			/>
			<TabPanel
				activeClass="srfm-active-editor"
				onSelect={ ( tabName ) => activeTabRef.current = tabName }
				tabs={ editorTabs }
				initialTabName={ activeTabRef.current }
			>
				{ ( tab ) => {
					switch ( tab.title ) {
						case 'Visual':
							return (
								<div className="srfm-editor-visual">
									<EditorToolbar />
									<ReactQuill
										ref={ quillRef }
										formats={ formats }
										modules={ modules }
										value={ content }
										onChange={ ( newContent ) => {
											handleContentChange(
												newContent
											);
										} }
									/>
								</div>
							);
						case 'HTML':
							return (
								<textarea
									id="srfm-editor-html"
									onChange={ ( e ) =>
										handleContentChange( e.target.value )
									}
									className="srfm-editor-textarea"
									value={ content }
								>
								</textarea>
							);
						default:
							return null;
					}
				} }
			</TabPanel>
		</>
	);
};

export default Editor;
