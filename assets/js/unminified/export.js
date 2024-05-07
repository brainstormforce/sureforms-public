/* eslint-disable no-undef */
function exportForm( postId ) {
	const xhr = new XMLHttpRequest();
	xhr.open( 'POST', srfm_export.ajaxurl, true );
	xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );
	xhr.onload = function () {
		if ( xhr.status >= 200 && xhr.status < 400 ) {
			// The server response is the JSON data
			const jsonData = JSON.parse( xhr.responseText );
			// Create a download link for the JSON data
			const downloadLink = document.createElement( 'a' );
			downloadLink.href =
				'data:application/json,' +
				encodeURIComponent( JSON.stringify( jsonData ) );
			downloadLink.download = 'sureforms-export-form.json';
			// Trigger the download
			downloadLink.click();
		} else {
			console.log( 'Server Error!' );
		}
	};
	xhr.onerror = function () {
		console.log( 'Connection Error!' );
	};
	xhr.send(
		`action=export_form&post_id=${ postId }&nonce=${ srfm_export.srfm_export_nonce }`
	);
}

function bulkExport() {
	const applyBtn = document.querySelector( '#doaction' );
	const select = document.querySelector( '#bulk-action-selector-top' );
	if ( applyBtn && select ) {
		applyBtn.addEventListener( 'click', ( e ) => {
			if ( select.value !== 'export' ) {
				return;
			}
			e.preventDefault();
			const checkboxes = document.querySelectorAll(
				'#the-list input[type=checkbox]'
			);
			const postIds = [];
			checkboxes.forEach( ( checkbox ) => {
				if ( checkbox.checked ) {
					postIds.push( checkbox.value );
				}
			} );
			if ( postIds.length > 0 ) {
				exportForm( postIds );
			}
		} );
	}
}

let data;
// eslint-disable-next-line no-unused-vars
function handleFileChange( event ) {
	const file = event.target.files[ 0 ];
	const reader = new FileReader();
	reader.onload = ( e ) => {
		const currData = JSON.parse( e.target.result );
		data = currData;
		const impSubmitBtn = document.querySelector( '#import-form-submit' );
		if ( impSubmitBtn ) {
			impSubmitBtn.removeAttribute( 'disabled' );
		}
	};
	reader.readAsText( file );
}

const handleImportForm = () => {
	if ( ! data ) {
		return;
	}
	const site_url = srfm_export.site_url;
	const endpoint = srfm_export.srfm_import_endpoint;
	const path = site_url + endpoint;
	fetch( path, {
		method: 'POST',
		body: JSON.stringify( data ),
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': srfm_export.import_form_nonce,
		},
	} )
		.then( ( response ) => {
			console.log( { response } );
			if ( ! response.ok ) {
				throw new Error( `HTTP error! Status: ${ response.status }` );
			}
			const importError = document.querySelector( '#srfm-import-error' );
			if ( importError ) {
				importError.style.display = 'none';
			}
			window.location.reload();
			return response;
		} )
		.catch( ( e ) => {
			const importError = document.querySelector( '#srfm-import-error' );
			if ( importError ) {
				importError.style.display = 'block';
			}
			console.log( e );
		} );
};

function importForm() {
	const importBtn = document.querySelector( '.srfm-import-btn' );
	const importContainer = document.querySelector( '.srfm-import-wrap' );
	const impSubmitBtn = document.querySelector( '#import-form-submit' );
	if ( importBtn ) {
		importBtn.addEventListener( 'click', () => {
			if ( importContainer ) {
				importContainer.classList.toggle( 'srfm-show' );
			}
		} );
	}
	if ( impSubmitBtn ) {
		impSubmitBtn.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			handleImportForm();
		} );
	}
}

function appendImportBtn() {
	const element = document.querySelector( '.wrap .page-title-action' );
	if ( element ) {
		const newElement = document.createElement( 'button' );
		newElement.className =
			'button button-secondary button-large srfm-import-btn';
		newElement.textContent = 'Import Form';
		element.parentNode.insertBefore( newElement, element.nextSibling );
		newElement.style.color = '#D54407';
		newElement.style.background = '#fff';
		newElement.style.borderColor = '#D54407';
		newElement.style.boxShadow = 'none';
	}
}
document.addEventListener( 'DOMContentLoaded', function () {
	appendImportBtn();
	importForm();
	bulkExport();
} );
