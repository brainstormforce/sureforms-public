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

				// Adding quill editor to the textarea.
				// check attribute data-is-richtext="true"' available and should be true.
				const isRichText = areaField.getAttribute( 'data-is-richtext' );
				if ( isRichText === 'true' ) {
					const getQuillId = areaField.getAttribute( 'id' );

					// Import the Style Attributors
					const AlignStyle = Quill.import(
						'attributors/style/align'
					); // Import align style
					const DirectionStyle = Quill.import(
						'attributors/style/direction'
					); // Import direction style

					// Register the Attributors for Inline Styles
					Quill.register( AlignStyle, true ); // Register align style
					Quill.register( DirectionStyle, true ); // Register direction style

					const quillEditor = new Quill( `#quill-${ getQuillId }`, {
						theme: 'snow',
						modules: {
							toolbar: {
								container: [
									[ { header: [ 1, 2, 3, 4, 5, 6, false ] } ],
									[ 'bold', 'italic', 'underline', 'strike' ],
									[ { list: 'ordered' }, { list: 'bullet' } ],
									[ 'blockquote' ],
									[ { align: [] } ],
									[ { color: [] }, { background: [] } ],
									[ 'clean' ], // Remove formatting button
									[ 'link', 'image' ],
								],
							},
						},
					} );

					// Retrieve content as HTML
					quillEditor.on(
						'text-change',
						function ( delta, oldDelta, source ) {
							const content = quillEditor.root.innerHTML;
							console.log( 'Text change:', {
								delta,
								oldDelta,
								source,
								content,
							} );

							// Update the textarea with the new content.
							areaField.value = content;
						}
					);

					quillEditor.clipboard.dangerouslyPasteHTML(
						'<p>Hello, Quill!</p>'
					);
				}
			}
		}
	}
}
document.addEventListener( 'DOMContentLoaded', initializeTextarea );
