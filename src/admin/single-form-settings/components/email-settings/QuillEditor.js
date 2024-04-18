import ReactQuill, { Quill } from 'react-quill';
import EditorToolbar, { modules, formats } from './EditorToolbar';
import { TabPanel, DropdownMenu } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { generateDropDownOptions } from '@Utils/Helpers';
import svgIcons from '@Image/single-form-logo.json';
import parse from 'html-react-parser';
import { useRef } from '@wordpress/element';

const Editor = ( {
	handleEmailBodyContent,
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

	const savedBlocks = useSelect( ( select ) =>
		select( editorStore ).getBlocks()
	);

	const excludedBlocks = [
		'srfm/inline-button',
		'srfm/hidden',
		'srfm/page-break',
		'srfm/separator',
		'srfm/advanced-heading',
		'srfm/image',
		'srfm/icon',
	];
	const formSmartTags = [];

	savedBlocks.map( ( savedBlock ) =>
		! excludedBlocks.includes( savedBlock.name ) &&
		undefined !== savedBlock.attributes.slug &&
		undefined !== savedBlock.attributes.label &&
		formSmartTags.push( [
			'{form:' + savedBlock.attributes.slug + '}',
			savedBlock.attributes.label,
		] )
	);

	const genericSmartTags = window.srfm_block_data?.smart_tags_array ? Object.entries( window.srfm_block_data.smart_tags_array ) : [];

	const onSelect = () => { };
	// Add inline style instead of classes.
	Quill.register( Quill.import( 'attributors/style/align' ), true );

	return (
		<>
			<DropdownMenu
				icon={ dropdownIcon }
				className="srfm-editor-dropdown srfm-smart-tag-dropdown"
				label="Select Shortcodes"
				text="Add Shortcodes"
				controls={
					[
						generateDropDownOptions(
							setFormData,
							formData,
							insertTextAtEnd,
							genericSmartTags,
							'Generic tags'
						),
						generateDropDownOptions(
							setFormData,
							formData,
							insertTextAtEnd,
							formSmartTags,
							'Form input tags'
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
		</>
	);
};

export default Editor;
