import { useState, useRef } from '@wordpress/element';
import SmartTagList from '@Components/misc/SmartTagList';
import { __ } from '@wordpress/i18n';
import ReactQuill, { Quill } from 'react-quill';
import EditorToolbar, {
	formats,
	modules,
} from './email-settings/EditorToolbar';
import { ChevronDownIcon } from 'lucide-react';
import { Label, Tabs, TextArea } from '@bsf/force-ui';
import { applyFilters } from '@wordpress/hooks';

const Editor = ( { handleContentChange, content, allData = false } ) => {
	const quillRef = useRef( null );
	const textAreaRef = useRef( null );

	const editorTabs = [
		{
			name: 'srfm-editor-visual',
			title: __( 'Visual', 'sureforms' ),
			value: 'visual',
			className: 'srfm-editor-visual',
		},
		{
			name: 'srfm-editor-html',
			title: __( 'HTML', 'sureforms' ),
			value: 'html',
			className: 'srfm-editor-html',
		},
	];
	const activeTabRef = useRef( 'srfm-editor-visual' );

	const [ activeTab, setActiveTab ] = useState( 'visual' );

	const visualEditorInsertText = ( text ) => {
		const quillInstance = quillRef.current.getEditor();
		let { index } = quillInstance.getSelection( true ); // Get the current cursor position.

		if ( ! index ) {
			/**
			 * If we are here, then it could mean editor is not in focus i.e. User did not click anywhere on the editor.
			 * So, in this scenario, add the dynamic text at the end of the content.
			 */
			const length = quillInstance.getLength();
			quillInstance.setSelection( length, 0 );

			index = length - 1;
		}

		quillInstance.insertText( index, text );
	};

	const insertSmartTag = ( tag ) => {
		if ( 'srfm-editor-visual' === activeTabRef.current ) {
			visualEditorInsertText( tag );
		} else {
			const textAreaInstance = textAreaRef.current;

			// Get the cursor position.
			const startPosition = textAreaInstance.selectionStart;
			const endPosition = textAreaInstance.selectionEnd;

			if ( ! startPosition && ! endPosition ) {
				/**
				 * If we are here, then it could mean editor is not in focus i.e. User did not click anywhere on the editor.
				 * So, in this scenario, add the dynamic text at the end of the content.
				 */
				return handleContentChange( content + tag );
			}

			// Get the current text value.
			const textValue = textAreaInstance.value;

			// Insert the text at the cursor position.
			textAreaInstance.value =
				textValue.substring( 0, startPosition ) +
				tag +
				textValue.substring( endPosition );

			// Update the cursor position to be after the inserted text.
			textAreaInstance.selectionStart = textAreaInstance.selectionEnd =
				startPosition + tag.length;

			// Update the state.
			handleContentChange( textAreaInstance.value );
		}
	};

	const genericSmartTags = window.srfm_block_data?.smart_tags_array
		? Object.entries( window.srfm_block_data.smart_tags_array )
		: [];
	const formSmartTags = window.sureforms?.formSpecificSmartTags ?? [];
	let formSmartTagsAllData = {};
	if ( allData ) {
		formSmartTagsAllData = [
			...formSmartTags,
			[ '{all_data}', __( 'All Data', 'sureforms' ) ],
		];
	}

	// Add inline style instead of classes.
	Quill.register( Quill.import( 'attributors/style/align' ), true );

	/**
	 * Custom Image Blot to add attributes to the image tag.
	 * This is required because the default image blot does not allow us to add attributes to the image tag.
	 *
	 * Blot - A piece of content that is inserted into the editor.
	 * Embed - A type of blot that represents an embedded object in the editor (For example, an image).
	 */
	const Embed = Quill.import( 'blots/embed' );
	class CustomImageBlot extends Embed {
		static create( value ) {
			const node = super.create();
			node.setAttribute( 'src', value.src );
			node.setAttribute( 'alt', '' );
			node.setAttribute( 'aria-hidden', 'true' );
			return node;
		}
		static value( node ) {
			return {
				src: node.getAttribute( 'src' ),
				alt: node.getAttribute( 'alt' ),
				'aria-hidden': node.getAttribute( 'aria-hidden' ),
			};
		}
	}
	CustomImageBlot.blotName = 'image';
	CustomImageBlot.tagName = 'img';
	Quill.register( CustomImageBlot );

	const formConfirmationSmartTags = applyFilters( 'srfm.formSettings.formConfirmationSmartTags', [
		{
			tags: allData
				? formSmartTagsAllData
				: formSmartTags,
			label: __( 'Form input tags', 'sureforms' ),
		},
		{
			tags: genericSmartTags,
			label: __( 'Generic tags', 'sureforms' ),
		},
	] );

	return (
		<>
			<Tabs activeItem={ activeTab }>
				<div className="flex items-center justify-between mb-1.5">
					<Label>{ __( 'Confirmation Message thissss', 'sureforms' ) }</Label>
					<div className="flex items-center gap-2 min-w-fit">
						<Tabs.Group
							variant="rounded"
							size="xs"
							onChange={ ( { value: { slug } } ) => {
								setActiveTab( slug );
								activeTabRef.current = editorTabs.find(
									( tab ) => tab.value === slug
								)?.name;
							} }
						>
							{ editorTabs.map( ( tab ) => (
								<Tabs.Tab
									className="text-sm font-medium"
									key={ tab.value }
									text={ tab.title }
									slug={ tab.value }
								/>
							) ) }
						</Tabs.Group>
						<SmartTagList
							tagFor="formSettings.quillEditor"
							icon={ <ChevronDownIcon /> }
							label={ __( 'Add Shortcode', 'sureforms' ) }
							// tagsArray={ [
							// 	{
							// 		tags: allData
							// 			? formSmartTagsAllData
							// 			: formSmartTags,
							// 		label: __( 'Form input tags', 'sureforms' ),
							// 	},
							// 	{
							// 		tags: genericSmartTags,
							// 		label: __( 'Generic tags', 'sureforms' ),
							// 	},
							// ] }
							tagsArray={ formConfirmationSmartTags }
							setTargetData={ insertSmartTag }
							dropdownPlacement="bottom-end"
						/>
					</div>
				</div>
				<Tabs.Panel slug="visual">
					<div className="">
						<EditorToolbar />
						<ReactQuill
							ref={ quillRef }
							formats={ formats }
							modules={ modules }
							value={ content }
							onChange={ ( newContent ) => {
								handleContentChange( newContent );
							} }
							className="[&>div]:border [&>div]:border-field-border [&>div]:border-solid [&>div]:rounded-b-lg [&_.ql-editor]:min-h-[18.75rem]"
						/>
					</div>
				</Tabs.Panel>
				<Tabs.Panel slug="html">
					<TextArea
						id="srfm-editor-html"
						ref={ textAreaRef }
						onChange={ ( value ) => handleContentChange( value ) }
						className="w-full min-h-[18.75rem] border border-field-border border-solid rounded-lg transition"
						value={ content }
						size="md"
					></TextArea>
				</Tabs.Panel>
			</Tabs>
		</>
	);
};

export default Editor;
