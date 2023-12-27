function initializeUploadField() {
	const uploadFieldsContainer =
		document.querySelectorAll( '.srfm-upload-block' );

	const documentIcon =
		"<svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' strokeWidth={1.5} stroke='currentColor' dataSlot='icon' className='w-6 h-6'><path strokeLinecap='round' strokeLinejoin='round' d='M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z' /></svg>";
	const deleteIcon =
		"<svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='currentColor' data-slot='icon' class='w-6 h-6'><path stroke-linecap='round' stroke-linejoin='round' d='m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0' /></svg>";

	if ( uploadFieldsContainer ) {
		uploadFieldsContainer.forEach( ( element ) => {
			const fileSizeField = element.querySelector( '.srfm-upload-size' );
			const uploadInput = element.querySelector( '.srfm-input-upload' );

			if ( uploadInput && fileSizeField ) {
				uploadInput.addEventListener( 'change', ( e ) => {
					const file = e?.target?.files[ 0 ]
						? e.target.files[ 0 ]
						: '';
					const maxFileSize =
						parseInt( fileSizeField.value ) * 1024 * 1024;

					if ( file ) {
						if ( file.size > maxFileSize ) {
						} else {
							const fileName =
								file.name.length > 20
									? file.name.substring( 0, 17 ) +
									  '...' +
									  file.name.split( '.' ).pop()
									: file.name;

							/* eslint-disable no-undef */
							const reader = new FileReader();
							reader.onload = function ( event ) {
								const imgSrc = event.target.result;
								const imageFormats = [
									'image/jpeg',
									'image/png',
									'image/gif',
									'image/bmp',
									'image/tiff',
									'image/webp',
									'image/svg+xml',
									'image/heif',
									'image/x-icon',
								];
								const icon = imageFormats.includes( file.type )
									? `<img class="srfm-upload-data-image" src="${ imgSrc }"/>`
									: `${ documentIcon }`;

								const uploadResultContainer =
									element.querySelector(
										'.srfm-upload-data'
									);

								uploadResultContainer.innerHTML = `
								<div class="srfm-upload-data-left">
									${ icon }
									<div class="srfm-upload-data-details">
										<div class="srfm-upload-data-filename">${ fileName }</div>
										<div class="srfm-upload-data-size"> ${ ( file.size / 1000000 ).toFixed(
		2
	) }MB</div>
									</div>
								</div>
								<div class="srfm-upload-data-right">
									${ deleteIcon }
								</div>`;
								if ( uploadResultContainer ) {
									uploadResultContainer.classList.add(
										'active'
									);
								}
								const resetButton =
									uploadResultContainer.querySelector(
										'.srfm-upload-data-delete'
									);
								if ( resetButton ) {
									resetButton.addEventListener(
										'click',
										() => {
											uploadInput.value = '';

											if ( uploadResultContainer ) {
												uploadResultContainer.classList.remove(
													'active'
												);
											}
										}
									);
								}
							};
							reader.readAsDataURL( file );
						}
					}
				} );
			}
		} );
	}
}

document.addEventListener( 'DOMContentLoaded', initializeUploadField );
