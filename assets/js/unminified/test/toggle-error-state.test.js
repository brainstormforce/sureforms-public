/* eslint-env jest */
require( '../frontend.js' );

const renderField = ( describedBy = '' ) => {
	document.body.innerHTML = `
		<div class="srfm-block">
			<input id="field" data-srfm-error-target ${
	describedBy ? `aria-describedby="${ describedBy }"` : ''
} />
			<div id="srfm-description-b1">Help</div>
			<div class="srfm-error-message" data-srfm-id="srfm-error-b1">Required</div>
		</div>`;
	return {
		container: document.querySelector( '.srfm-block' ),
		input: document.getElementById( 'field' ),
		error: document.querySelector( '.srfm-error-message' ),
	};
};

describe( 'toggleErrorState', () => {
	it( 'links the error message only while the error is shown', () => {
		const { container, input, error } = renderField();

		window.srfm.toggleErrorState( container, true );
		expect( error.id ).toBe( 'srfm-error-b1' );
		expect( input.getAttribute( 'aria-describedby' ) ).toBe(
			'srfm-error-b1'
		);

		window.srfm.toggleErrorState( container, false );
		expect( error.hasAttribute( 'id' ) ).toBe( false );
		expect( input.hasAttribute( 'aria-describedby' ) ).toBe( false );
	} );

	it( 'keeps the help text reference', () => {
		const { container, input } = renderField( 'srfm-description-b1' );

		window.srfm.toggleErrorState( container, true );
		expect( input.getAttribute( 'aria-describedby' ) ).toBe(
			'srfm-error-b1 srfm-description-b1'
		);

		window.srfm.toggleErrorState( container, false );
		expect( input.getAttribute( 'aria-describedby' ) ).toBe(
			'srfm-description-b1'
		);
	} );
	it( 'keeps a shared error id linked while another message still shows it', () => {
		document.body.innerHTML = `
			<div class="srfm-block wrap">
				<div class="srfm-email-block">
					<input id="main" data-srfm-error-target />
					<div class="srfm-error-message" data-srfm-id="srfm-error-e1">Main</div>
				</div>
				<div class="srfm-email-confirm-block">
					<input id="confirm" data-srfm-error-target />
					<div class="srfm-error-message" data-srfm-id="srfm-error-e1">Confirm</div>
				</div>
			</div>`;
		const wrap = document.querySelector( '.wrap' );
		const confirmBlock = document.querySelector(
			'.srfm-email-confirm-block'
		);
		const confirmInput = document.getElementById( 'confirm' );

		window.srfm.toggleErrorState( wrap, true );
		window.srfm.toggleErrorState( confirmBlock, true );
		window.srfm.toggleErrorState( wrap, false );

		expect( confirmInput.getAttribute( 'aria-describedby' ) ).toBe(
			'srfm-error-e1'
		);
		expect( document.getElementById( 'srfm-error-e1' ).textContent ).toBe(
			'Confirm'
		);
	} );
} );
