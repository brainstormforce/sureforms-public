function initializeTextarea() {
	const textAreaContainer = Array.from(
		document.getElementsByClassName( 'srfm-textarea-block' )
	);
	if ( textAreaContainer ) {
		for ( const areaInput of textAreaContainer ) {
			const areaField = areaInput.querySelector( 'textarea' );
			if ( areaField ) {
				areaField.addEventListener( 'input', function () {
					const textAreaValue = areaField.value;
					const maxLength = areaField.getAttribute( 'maxLength' );
					if ( maxLength !== '' ) {
						const counterDiv =
							areaInput.querySelector( '.srfm-text-counter' );
						if ( counterDiv ) {
							const remainingLength =
								maxLength - textAreaValue.length;
							counterDiv.innerText =
								remainingLength + '/' + maxLength;
						}
					}
				} );

				/**
				 * Initializes the Quill editor for a textarea if it is marked as a rich text field.
				 *
				 * This function checks if the given textarea has the `data-is-richtext` attribute set to "true".
				 * If the attribute is set to "true", it will call the `addQuillEditor` function to add a Quill editor
				 * instance to the corresponding textarea.
				 *
				 * @param {HTMLElement} areaField - The textarea element to be enhanced with the Quill editor if applicable.
				 */
				const isRichText = areaField.getAttribute( 'data-is-richtext' );

				// Check if the textarea is marked as a rich text field
				if ( isRichText === 'true' ) {
					// Initialize Quill editor on the textarea
					addQuillEditor( areaField );
					// Handle the screen readraccessibility of the Quill editor for screen reader.
					handleQuillEditorA11Y( areaField );

					const quillEditorContainer =
						areaField?.parentElement?.querySelector(
							'.quill-editor-container'
						);
					const quillEditor =
						quillEditorContainer?.querySelector( '.ql-editor' );
					quillEditor?.addEventListener( 'focus', function () {
						// Add focus class to the textarea block wrapper.
						quillEditorContainer.parentElement.classList.add(
							'srfm-quill-editor-focused'
						);
					} );
					quillEditor?.addEventListener( 'click', function () {
						// Add focus class to the textarea block wrapper.
						quillEditorContainer.parentElement.classList.add(
							'srfm-quill-editor-focused'
						);
					} );
					quillEditor?.addEventListener( 'blur', function () {
						// Remove focus class from the textarea block wrapper.
						quillEditorContainer.parentElement.classList.remove(
							'srfm-quill-editor-focused'
						);
					} );
				}
			}
		}
	}
}

/**
 * Adds a Quill editor to a given textarea field with inline styles for alignment and direction.
 *
 * @param {HTMLElement} areaField - The textarea element that will be enhanced by Quill editor.
 */
function addQuillEditor( areaField ) {
	// Get the ID of the textarea element to use for Quill editor container
	const getQuillId = areaField.getAttribute( 'id' );

	// Import the Style Attributors for inline styling (align and direction)
	const AlignStyle = Quill.import( 'attributors/style/align' ); // Import align style attributor
	const DirectionStyle = Quill.import( 'attributors/style/direction' ); // Import direction style attributor
	const icons = Quill.import( 'ui/icons' ); // Import Quill icons for toolbar
	icons.undo =
		'<svg viewBox="0 0 18 18"><polygon class="ql-fill ql-stroke" points="6 10 4 12 2 10 6 10" /><path class="ql-stroke" d="M8.09,13.91A4.6,4.6,0,0,0,9,14,5,5,0,1,0,4,9" /></svg>';
	icons.redo =
		'<svg viewBox="0 0 18 18"><polygon class="ql-fill ql-stroke" points="12 10 14 12 16 10 12 10" /><path class="ql-stroke" d="M9.91,13.91A4.6,4.6,0,0,1,9,14a5,5,0,1,1,5-5"/></svg>';

	// Register the imported Attributors for inline styles
	Quill.register( AlignStyle, true ); // Register align style
	Quill.register( DirectionStyle, true ); // Register direction style

	const quillId = `quill-${ getQuillId }`;
	const colorsArray = [
		'#000000',
		'#e60000',
		'#ff9900',
		'#ffff00',
		'#008a00',
		'#0066cc',
		'#9933ff',
		'#ffffff',
		'#facccc',
		'#ffebcc',
		'#ffffcc',
		'#cce8cc',
		'#cce0f5',
		'#ebd6ff',
		'#bbbbbb',
		'#f06666',
		'#ffc266',
		'#ffff66',
		'#66b966',
		'#66a3e0',
		'#c285ff',
		'#888888',
		'#a10000',
		'#b26b00',
		'#b2b200',
		'#006100',
		'#0047b2',
		'#6b24b2',
		'#444444',
		'#5c0000',
		'#663d00',
		'#666600',
		'#003700',
		'#002966',
		'#3d1466',
	];

	// Initialize Quill editor with a toolbar configuration
	const quillEditor = new Quill( `#${ quillId }`, {
		theme: 'snow', // Use the 'snow' theme for a sleek look
		modules: {
			toolbar: {
				container: [
					[ { header: [ 1, 2, 3, 4, 5, 6, false ] } ], // Header levels
					[ 'bold', 'italic', 'underline', 'strike' ], // Text formatting
					[ { list: 'ordered' }, { list: 'bullet' } ], // Ordered and bullet lists
					[ 'blockquote' ], // Blockquote
					[ { align: [] } ], // Alignment (registered as inline style)
					[
						{ color: colorsArray }, // Text color options.
						{ background: colorsArray }, // Background color options.
					], // Color and background
					[ 'link' ], // Add links and images
					[ 'clean' ], // Remove formatting
					[ 'undo', 'redo' ], // Undo and redo actions
				],
				handlers: {
					undo() {
						this.quill.history.undo(); // Undo action
					},
					redo() {
						this.quill.history.redo(); // Redo action
					},
				},
			},
			keyboard: {
				bindings: {
					tab: true, // Allow tab key for exiting the editor.
				},
			},
			history: {
				delay: 1000, // Delay for history actions
				maxStack: 100, // Maximum number of history actions to keep
				userOnly: true, // Only user-initiated changes are recorded
			},
		},
	} );

	// Add Quill editor instance to the global window.srfm object for future reference.
	window?.addGlobalSrfmObject( getQuillId, quillEditor );

	// Set default content from the textarea to the Quill editor
	const quillDefaultContent = areaField.value;
	quillEditor.clipboard.dangerouslyPasteHTML( quillDefaultContent ); // Populate Quill with initial content

	quillEditor.blur(); // Ensure the editor is not focused initially.

	// Listen for changes in the Quill editor and update the corresponding textarea
	quillEditor.on( 'text-change', function () {
		const updatedContent = quillEditor.root.innerHTML;
		const lengthOfContent = quillEditor.getLength();

		areaField.value = lengthOfContent > 1 ? updatedContent : ''; // Update the textarea with the Quill content

		// Due to we are adding the Quill editor to the textarea, we need to trigger the input event
		// to ensure the textarea's value is updated in the DOM.
		areaField.dispatchEvent( new Event( 'input' ) );
	} );
}

/**
 * Function to handle a11y for the rich text editor.
 *
 * It is responsible to add the aria-labelledby attribute to the Quill editor container.
 * This will ensure the label is read by the screen reader.
 *
 * @param {HTMLElement} areaField - The textarea element.
 */
function handleQuillEditorA11Y( areaField ) {
	const block = areaField.closest( '.srfm-textarea-block' );
	// If block is not found, return early.
	if ( ! block ) {
		return;
	}
	const quillEditorContainer = block.querySelector(
		'.quill-editor-container'
	);
	const qlContainer = block.querySelector( '.ql-container' );
	const label = block.querySelector( 'label' );
	const labelID = label?.getAttribute( 'id' );
	qlContainer?.setAttribute( 'aria-labelledby', labelID );
	quillEditorContainer?.setAttribute( 'aria-labelledby', labelID );
}

document.addEventListener( 'DOMContentLoaded', initializeTextarea );
