/* eslint-env jest */
window.srfm_submit = { is_rtl: false, messages: {} };
window.srfm = { toggleErrorState: () => {} };
window.intlTelInput = require( '../../minified/deps/intl/intTelInputWithUtils.min.js' );
require( '../blocks/phone.js' );

const renderForm = ( value = '' ) => {
	document.body.innerHTML = `
		<form form-id="1">
			<div class="srfm-block srfm-block-single srfm-phone-block">
				<input type="tel" class="srfm-input-phone" name="srfm-phone-1"
					default-country="gb" data-required="true" value="${ value }" />
				<div class="srfm-error-message"></div>
			</div>
		</form>`;
};

const hiddenValue = () =>
	document.querySelector( '.srfm-input-phone' ).nextElementSibling.value;

const type = ( value ) => {
	const input = document.querySelector( '.srfm-input-phone' );
	input.value = value;
	input.dispatchEvent( new Event( 'input' ) );
};

const block = () => document.querySelector( '.srfm-phone-block' );

const selectedCountry = () =>
	window.intlTelInput
		.getInstance( document.querySelector( '.srfm-input-phone' ) )
		.getSelectedCountryData().iso2;

describe( 'phone field re-initialisation', () => {
	beforeEach( () => renderForm() );

	it( 'keeps an entered valid number in the hidden input', () => {
		window.srfmInitializePhoneField();
		type( '7911123456' );
		expect( hiddenValue() ).toBe( '+447911123456' );

		// A repeater row add re-initialises every phone field on the page.
		window.srfmInitializePhoneField();
		expect( hiddenValue() ).toBe( '+447911123456' );
	} );

	it( 'keeps an entered invalid number in the hidden input', () => {
		window.srfmInitializePhoneField();
		type( '123' );

		window.srfmInitializePhoneField();
		expect( hiddenValue() ).toBe( '123' );
	} );

	it( 'leaves the hidden input empty when nothing was entered', () => {
		window.srfmInitializePhoneField();
		window.srfmInitializePhoneField();
		expect( hiddenValue() ).toBe( '' );
	} );

	it( 'keeps the country the visitor selected', () => {
		window.srfmInitializePhoneField();
		window.intlTelInput
			.getInstance( document.querySelector( '.srfm-input-phone' ) )
			.setCountry( 'fr' );
		type( '612345678' );
		expect( hiddenValue() ).toBe( '+33612345678' );

		window.srfmInitializePhoneField();
		expect( selectedCountry() ).toBe( 'fr' );
		expect( hiddenValue() ).toBe( '+33612345678' );
	} );

	it( 'does not stack listeners or instances across re-inits', () => {
		// The registry is global, so compare against the count after one init.
		const instanceCount = () =>
			Object.keys( window.intlTelInput.instances ).length;
		window.srfmInitializePhoneField();
		const afterFirstInit = instanceCount();
		window.srfmInitializePhoneField();
		window.srfmInitializePhoneField();
		expect( document.querySelectorAll( '.iti' ) ).toHaveLength( 1 );
		expect( instanceCount() ).toBe( afterFirstInit );
	} );

	it( 'keeps an existing phone error on re-init', () => {
		window.srfmInitializePhoneField();
		type( '123' );
		expect( block().classList.contains( 'srfm-phone-error' ) ).toBe( true );

		window.srfmInitializePhoneField();
		expect( block().classList.contains( 'srfm-phone-error' ) ).toBe( true );
	} );
} );

describe( 'phone field with a default value', () => {
	it( 'fills the hidden input from a valid default', () => {
		renderForm( '7911123456' );
		window.srfmInitializePhoneField();
		expect( hiddenValue() ).toBe( '+447911123456' );
		expect( block().classList.contains( 'srfm-phone-error' ) ).toBe( false );
	} );

	it( 'flags an invalid default at init', () => {
		renderForm( '123' );
		window.srfmInitializePhoneField();
		expect( hiddenValue() ).toBe( '123' );
		expect( block().classList.contains( 'srfm-phone-error' ) ).toBe( true );
	} );
} );
