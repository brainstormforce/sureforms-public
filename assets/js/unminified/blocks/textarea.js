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
				console.log("isRichText:2 ", isRichText);

				// const quill = new Quill('#editor', {
				// 	theme: 'snow'
				//   });
				if( isRichText === 'true' ) {
					const quillEditor = new Quill("#quill-srfm-textarea-f72a6a0f-lbl-VGV4dGFyZWE", {
						theme: 'snow'
					});
				}
			}
		}
	}
}
document.addEventListener( 'DOMContentLoaded', initializeTextarea );
