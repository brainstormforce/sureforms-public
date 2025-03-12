import SmartTagList from '@Components/misc/SmartTagList';
import svgIcons from '@Image/single-form-logo.json';
import { TabPanel } from '@wordpress/components';
import { useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import parse from 'html-react-parser';
import ReactQuill, { Quill } from 'react-quill';
import EditorToolbar, {
	formats,
	modules,
} from './email-settings/EditorToolbar';

const Editor = ( {
	handleContentChange,
	content,
	allData = false,
} ) => {
	const dropdownIcon = parse( svgIcons.downArrow );

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
		formSmartTagsAllData = [ ...formSmartTags, [ '{all_data}', __( 'All Data', 'sureforms' ) ] ];
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

	return (
		<>
			<SmartTagList
				tagFor="formSettings.quillEditor"
				icon={ dropdownIcon }
				text={ __( 'Add Shortcode', 'sureforms' ) }
				cssClass={ 'srfm-editor-dropdown' }
				tagsArray={
					[
						{
							tags: allData ? formSmartTagsAllData : formSmartTags,
							label: __( 'Form input tags', 'sureforms' ),
						},
						{
							tags: genericSmartTags,
							label: __( 'Generic tags', 'sureforms' ),
						},
					]

				}
				setTargetData={ insertSmartTag }
			/>
			<TabPanel
				activeClass="srfm-active-editor"
				onSelect={ ( tabName ) => ( activeTabRef.current = tabName ) }
				tabs={ editorTabs }
				initialTabName={ activeTabRef.current }
			>
				{ ( tab ) => {
					switch ( tab.value ) {
						case 'visual':
							return (
								<div className="srfm-editor-visual">
									<EditorToolbar />
									<ReactQuill
										ref={ quillRef }
										formats={ formats }
										modules={ modules }
										value={ content }
										onChange={ ( newContent ) => {
											handleContentChange( newContent );
										} }
									/>
								</div>
							);
						case 'html':
							return (
								<textarea
									id="srfm-editor-html"
									ref={ textAreaRef }
									onChange={ ( e ) =>
										handleContentChange( e.target.value )
									}
									className="srfm-editor-textarea"
									value={ content }
								></textarea>
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
