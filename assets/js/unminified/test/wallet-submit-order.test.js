/* eslint-env jest */
/**
 * Safari opens Apple Pay only while the submit click is fresh. The Stripe
 * wallet submit has to start in the click itself, before field validation or
 * any other awaited work, or the sheet is refused on slow servers.
 */

const order = [];

jest.mock( '../validation', () => ( {
	...jest.requireActual( '../validation' ),
	fieldValidation: jest.fn( async () => {
		order.push( 'fieldValidation' );
		// Stop the submission here: the test only cares what ran first.
		return { validateResult: true };
	} ),
} ) );

require( '../form-submit.js' );

const FORM = `
	<div class="srfm-form-container">
		<form class="srfm-form" form-id="1" ajaxurl="/wp-admin/admin-ajax.php">
			<div class="srfm-loader"></div>
			<button type="submit" id="srfm-submit-btn">Submit</button>
		</form>
	</div>`;

describe( 'form submission with a Stripe payment block', () => {
	beforeEach( () => {
		order.length = 0;
		document.body.innerHTML = FORM;
		document.dispatchEvent( new Event( 'DOMContentLoaded' ) );
	} );

	afterEach( () => {
		delete window.StripePayment;
	} );

	it( 'starts the wallet submit before field validation runs', async () => {
		const startWalletSubmit = jest.fn( () => order.push( 'wallet' ) );
		window.StripePayment = { startWalletSubmit };
		const form = document.querySelector( '.srfm-form' );

		form.dispatchEvent(
			new Event( 'submit', { bubbles: true, cancelable: true } )
		);

		// Synchronously, inside the event dispatch: an await before it would
		// keep the order below but lose the click.
		expect( startWalletSubmit ).toHaveBeenCalledWith( form );

		await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
		expect( order ).toEqual( [ 'wallet', 'fieldValidation' ] );
	} );

	it( 'submits normally when Stripe is not loaded on the page', async () => {
		const form = document.querySelector( '.srfm-form' );

		form.dispatchEvent(
			new Event( 'submit', { bubbles: true, cancelable: true } )
		);
		await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );

		expect( order ).toEqual( [ 'fieldValidation' ] );
	} );

	it( 'shares the payment block check with Stripe', () => {
		expect( typeof window.srfmValidatePaymentBlock ).toBe( 'function' );
	} );
} );
