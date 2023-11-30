function initializeUploadField() {
	const uploadFieldsContainer = document.getElementsByClassName(
		'srfm-upload-container'
	);

	if ( uploadFieldsContainer ) {
		for ( const uploadField of uploadFieldsContainer ) {
			const fileSizeField =
				uploadField.querySelector( '.srfm-upload-size' );
			const uploadInput =
				uploadField.querySelector( '.srfm-upload-field' );

			if ( uploadInput ) {
				uploadInput.addEventListener( 'change', ( e ) => {
					const id = e.target.id.split( '-' )[ 2 ];
					const file = e.target.files[ 0 ];
					const isError = uploadField.querySelector(
						'.srfm-error-message'
					);
					if ( isError ) {
						isError.style.display = 'none';
					}
					const maxFileSize =
						parseInt( fileSizeField.value ) * 1024 * 1024;
					if ( file ) {
						if ( file.size > maxFileSize ) {
							e.target.value = '';
							uploadField
								.querySelector(
									`#srfm-upload-field-error-${ id }`
								)
								.removeAttribute( 'hidden' );
						} else {
							uploadField
								.querySelector(
									`#srfm-upload-field-error-${ id }`
								)
								.setAttribute( 'hidden', true );
							const fileName =
								file.name.length > 20
									? file.name.substring( 0, 17 ) +
									  '...' +
									  file.name.split( '.' ).pop()
									: file.name;
							const isClassic = uploadField.classList.contains(
								'srfm-classic-inputs-holder'
							);
							if ( ! isClassic ) {
								uploadField.querySelector(
									`#srfm-upload-title-${ id }`
								).innerHTML =
									`<div class="srfm-text-primary" style="display:flex; gap:0.4rem; align-items:center">
                                <i class="fa-solid fa-file-lines srfm-text-primary"></i> ` +
									fileName +
									' ' +
									( file.size / 1000000 ).toFixed( 2 ) +
									`MB <i class="fa-sharp fa-solid fa-trash-can srfm-text-primary" id="srfm-reset-upload-field" style="cursor:pointer"></i></div>`;
							}
							if ( isClassic ) {
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
									const icon = imageFormats.includes(
										file.type
									)
										? `<img class="srfm-rounded-md" src="${ imgSrc }" height="50px" width="50px"/>`
										: '<div style="font-size:35px" class="srfm-text-gray-300"><i class="fa-solid fa-file-lines"></i></div>';
									const uploadResultContainer =
										uploadField.querySelector(
											`#srfm-upload-field-result-${ id }`
										);
									uploadResultContainer.innerHTML = `<div class="srfm-text-primary srfm-w-full srfm-flex srfm-gap-2 srfm-p-[10px]">
                                        ${ icon }
                                        <div class="srfm-w-full srfm-flex srfm-justify-between">
                                            <div>
                                                <div class="srfm-text-base">${ fileName }</div>
                                                <div class="srfm-text-sm srfm-text-gray-500"> ${ (
		file.size / 1000000
	).toFixed( 2 ) }MB</div>
                                            </div>
                                            <div>
                                                  <i class="fa-sharp fa-solid fa-trash-can srfm-text-gray-400" id="srfm-reset-upload-field" style="cursor:pointer"></i>
                                            </div>
                                        </div>
                                    </div>`;
									if ( uploadResultContainer ) {
										uploadResultContainer.style.display =
											'flex';
									}
									const resetButton =
										uploadField.querySelector(
											'#srfm-reset-upload-field'
										);
									if ( resetButton ) {
										resetButton.addEventListener(
											'click',
											() => {
												uploadInput.value = '';

												const resultElement =
													uploadField.querySelector(
														`#srfm-upload-field-result-${ id }`
													);

												if ( resultElement ) {
													resultElement.style.display =
														'none';
												}
											}
										);
									}
								};
								reader.readAsDataURL( file );
							}
						}
					}
				} );
			}
		}
	}
}

document.addEventListener( 'DOMContentLoaded', initializeUploadField );
