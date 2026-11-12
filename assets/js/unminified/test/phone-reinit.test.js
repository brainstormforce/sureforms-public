/* eslint-env jest */
window.srfm_submit = { is_rtl: false, messages: {} };
window.srfm = { toggleErrorState: () => {} };
window.intlTelInput = require( '../../minified/deps/intl/intTelInputWithUtils.min.js' );
require( '../blocks/phone.js' );

const renderForm = () => {
	document.body.innerHTML = `
		<form form-id="1">
			<div class="srfm-block srfm-block-single srfm-phone-block">
				<input type="tel" class="srfm-input-phone" name="srfm-phone-1"
					default-country="gb" data-required="true" />
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

describe( 'phone field re-initialisation', () => {
	beforeEach( renderForm );

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
} );
