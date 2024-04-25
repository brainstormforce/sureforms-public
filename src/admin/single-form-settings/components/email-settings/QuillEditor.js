import ReactQuill, { Quill } from 'react-quill';
import EditorToolbar, { modules, formats } from './EditorToolbar';
import { TabPanel, DropdownMenu } from '@wordpress/components';
import { generateDropDownOptions } from '@Utils/Helpers';
import svgIcons from '@Image/single-form-logo.json';
import parse from 'html-react-parser';
import { useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const Editor = ( {
	handleContentChange,
	content,
	formData,
	setFormData,
} ) => {
	const dropdownIcon = parse( svgIcons.downArrow );
	const quillRef = useRef( null );
	const insertTextAtEnd = ( text ) => {
		const quillInstance = quillRef.current.getEditor();
		const length = quillInstance.getLength();
		quillInstance.insertText( length - 1, text );
	};

	const genericSmartTags = window.srfm_block_data?.smart_tags_array ? Object.entries( window.srfm_block_data.smart_tags_array ) : [];
	const formSmartTags = window.sureforms?.formSpecificSmartTags ?? [];

	const onSelect = () => { };
	// Add inline style instead of classes.
	Quill.register( Quill.import( 'attributors/style/align' ), true );

	return (
		<>
			<DropdownMenu
				icon={ dropdownIcon }
				className="srfm-editor-dropdown srfm-smart-tag-dropdown"
				label={ __( 'Select Shortcodes', 'sureforms' ) }
				text={ __( 'Add Shortcodes', 'sureforms' ) }
				controls={
					[
						generateDropDownOptions(
							setFormData,
							formData,
							insertTextAtEnd,
							formSmartTags,
							__( 'Form input tags', 'sureforms' )
						),
						generateDropDownOptions(
							setFormData,
							formData,
							insertTextAtEnd,
							genericSmartTags,
							__( 'Generic tags', 'sureforms' )
						),
					]
				}
			/>
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
								>
									{ content }
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
