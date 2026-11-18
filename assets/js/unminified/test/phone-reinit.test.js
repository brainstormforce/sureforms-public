/* eslint-env jest */
window.srfm_submit = {
	is_rtl: false,
	messages: { srfm_valid_phone_number: 'Invalid phone number' },
};
window.srfm = { toggleErrorState: jest.fn() };
// jsdom has no layout, so no scrollIntoView (used when the dropdown opens).
window.HTMLElement.prototype.scrollIntoView = () => {};
window.intlTelInput = require( '../../minified/deps/intl/intTelInputWithUtils.min.js' );
require( '../blocks/phone.js' );

const renderForm = ( value = '', extraAttrs = '' ) => {
	document.body.innerHTML = `
		<form form-id="1">
			<div class="srfm-block srfm-block-single srfm-phone-block">
				<input type="tel" class="srfm-input-phone" name="srfm-phone-1"
					default-country="gb" data-required="true" value="${ value }" ${ extraAttrs } />
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
const phoneInput = () => document.querySelector( '.srfm-input-phone' );

// Let pending promise callbacks (the geo fetch chain) run.
const flush = async () => {
	for ( let i = 0; i < 5; i++ ) {
		await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
	}
};

// Pick a country the way a visitor does: click it in the dropdown list.
const pickCountry = ( iso2 ) => {
	// intl-tel-input binds the list's click handler only while it is open.
	document.querySelector( '.iti__selected-country' ).click();
	document
		.querySelector( `.iti__country[data-country-code="${ iso2 }"]` )
		.click();
};

beforeEach( () => {
	window.srfm.toggleErrorState.mockClear();
	window.sessionStorage.clear();
	delete window.srfm_phone_data;
} );

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

	it( 'keeps a country picked from the dropdown with nothing typed', () => {
		window.srfmInitializePhoneField();
		pickCountry( 'fr' );
		expect( selectedCountry() ).toBe( 'fr' );

		window.srfmInitializePhoneField();
		expect( selectedCountry() ).toBe( 'fr' );
	} );

	it( 'does not keep a country nobody picked on an empty field', () => {
		// Same as the repeater template input: re-initialised on every
		// page-wide init, but never touched. New rows are cloned from it.
		window.srfmInitializePhoneField();
		window.srfmInitializePhoneField();
		window.srfmInitializePhoneField();
		expect( phoneInput().dataset.srfmSelectedCountry ).toBeUndefined();
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

		// One input event runs one SureForms handler, not one per init.
		window.srfm.toggleErrorState.mockClear();
		type( '7911123456' );
		expect( window.srfm.toggleErrorState ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'keeps an existing phone error on re-init', () => {
		window.srfmInitializePhoneField();
		type( '123' );
		expect( block().classList.contains( 'srfm-phone-error' ) ).toBe( true );

		window.srfmInitializePhoneField();
		expect( block().classList.contains( 'srfm-phone-error' ) ).toBe( true );
	} );

	it( 'does not clear a shown error on re-init when the number is valid', () => {
		window.srfmInitializePhoneField();
		type( '123' );
		// Change the value without an input event, so only init sees it.
		phoneInput().value = '7911123456';
		window.srfm.toggleErrorState.mockClear();

		window.srfmInitializePhoneField();
		expect( window.srfm.toggleErrorState ).not.toHaveBeenCalledWith(
			block(),
			false
		);
		expect( block().classList.contains( 'srfm-phone-error' ) ).toBe( true );
		expect( hiddenValue() ).toBe( '+447911123456' );
	} );
} );

describe( 'phone field with auto-country', () => {
	let geoRequests;

	beforeEach( () => {
		renderForm( '', 'data-auto-country="true"' );
		window.srfm_phone_data = { geo_endpoint: '/geo' };
		geoRequests = [];
		global.fetch = jest.fn(
			() => new Promise( ( resolve ) => geoRequests.push( resolve ) )
		);
	} );

	afterEach( () => {
		delete global.fetch;
	} );

	const answer = ( request, country ) =>
		request( {
			ok: true,
			json: () => Promise.resolve( { detected: true, country } ),
		} );

	it( 'applies the server country when re-init happens before the fetch resolves', async () => {
		window.srfmInitializePhoneField();
		// Pro's repeater init, then the 100ms srfm_form_before_submission re-init.
		window.srfmInitializePhoneField();

		geoRequests.forEach( ( request ) => answer( request, 'nz' ) );
		await flush();

		expect( selectedCountry() ).toBe( 'nz' );
	} );

	it( 'does not treat an auto-country as the visitor\'s choice', async () => {
		window.srfmInitializePhoneField();
		// Resolves after init attached its listeners, so countrychange is seen.
		answer( geoRequests[ 0 ], 'nz' );
		await flush();
		expect( selectedCountry() ).toBe( 'nz' );

		window.srfmInitializePhoneField();
		expect( phoneInput().dataset.srfmCountryChosen ).toBeUndefined();
		expect( phoneInput().dataset.srfmSelectedCountry ).toBeUndefined();
		// Auto-country ran again, from the session cache this time.
		expect( selectedCountry() ).toBe( 'nz' );
	} );

	it( 'keeps an auto-detected country once a number is typed against it', async () => {
		window.srfmInitializePhoneField();
		answer( geoRequests[ 0 ], 'fr' );
		await flush();
		type( '612345678' );
		expect( hiddenValue() ).toBe( '+33612345678' );

		// Auto-country won't override a typed number, so without keeping the
		// country here the field would fall back to the gb default.
		window.srfmInitializePhoneField();
		expect( selectedCountry() ).toBe( 'fr' );
		expect( hiddenValue() ).toBe( '+33612345678' );
	} );

	it( 'ignores a fetch that resolves after its instance was replaced', async () => {
		window.srfmInitializePhoneField();
		window.srfmInitializePhoneField();
		expect( geoRequests ).toHaveLength( 2 );

		answer( geoRequests[ 1 ], 'nz' );
		await flush();
		expect( selectedCountry() ).toBe( 'nz' );

		const countryChange = jest.fn();
		phoneInput().addEventListener( 'countrychange', countryChange );
		answer( geoRequests[ 0 ], 'fr' );
		await flush();

		expect( countryChange ).not.toHaveBeenCalled();
		expect( selectedCountry() ).toBe( 'nz' );
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

	it( 'does not show the invalid default error before the visitor acts', () => {
		renderForm( '123' );
		window.srfmInitializePhoneField();

		// Shown on blur or submit by validation.js, with the right message.
		expect( window.srfm.toggleErrorState ).not.toHaveBeenCalled();
		expect(
			block().querySelector( '.srfm-error-message' ).textContent
		).toBe( 'Invalid phone number' );
	} );
} );
