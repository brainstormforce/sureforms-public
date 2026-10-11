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
} );
