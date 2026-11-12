/* eslint-env jest */
window.srfmStripe = { zeroDecimalCurrencies: [ 'JPY' ] };
require( '../stripe-payment.js' );

const { StripePayment } = window;

const renderBlock = ( attrs ) => {
	document.body.innerHTML = `
		<form data-srfm-instance="7">
			<div class="srfm-block srfm-payment-block" data-block-id="b1">
				<input class="srfm-payment-input" ${ attrs } />
			</div>
		</form>`;
	return document.querySelector( '.srfm-payment-input' );
};

const mountElements = ( amount ) => {
	const update = jest.fn();
	StripePayment.paymentElements[ '7-b1' ] = {
		elements: { update },
		amount,
	};
	return update;
};

describe( 'toMinorUnits', () => {
	it( 'rounds instead of truncating', () => {
		expect( StripePayment.toMinorUnits( 19.99, 'usd' ) ).toBe( 1999 );
		expect( StripePayment.toMinorUnits( 0.29, 'eur' ) ).toBe( 29 );
	} );

	it( 'leaves zero-decimal currencies as they are', () => {
		expect( StripePayment.toMinorUnits( 500, 'jpy' ) ).toBe( 500 );
	} );
} );

describe( 'syncElementsAmount', () => {
	it( 'gives Stripe the form amount, not a placeholder', () => {
		const input = renderBlock(
			'data-amount-type="fixed" data-fixed-amount="60" data-currency="eur"'
		);
		const update = mountElements( 12000 );

		StripePayment.syncElementsAmount( input );

		expect( update ).toHaveBeenCalledWith( { amount: 6000 } );
		expect( StripePayment.paymentElements[ '7-b1' ].amount ).toBe( 6000 );
	} );

	it( 'follows a variable amount and skips an unchanged one', () => {
		const input = renderBlock(
			'data-amount-type="variable" data-current-amount="110" data-currency="eur"'
		);
		const update = mountElements( 1 );

		StripePayment.syncElementsAmount( input );
		StripePayment.syncElementsAmount( input );

		expect( update ).toHaveBeenCalledTimes( 1 );
		expect( update ).toHaveBeenCalledWith( { amount: 11000 } );
	} );

	it( 'does nothing until the donor has picked an amount', () => {
		const input = renderBlock(
			'data-amount-type="variable" data-current-amount="" data-currency="eur"'
		);
		const update = mountElements( 1 );

		StripePayment.syncElementsAmount( input );

		expect( update ).not.toHaveBeenCalled();
	} );
} );

describe( 'initializePaymentElements', () => {
	it( 'mounts Elements with the form amount and the dashboard wallets', () => {
		document.body.innerHTML = `
			<form data-srfm-instance="7">
				<div class="srfm-block srfm-payment-block" data-block-id="b2">
					<input class="srfm-payment-input" data-stripe-key="pk_test" data-amount-type="fixed" data-fixed-amount="60" data-currency="eur" />
					<div class="srfm-stripe-payment-element"></div>
				</div>
			</form>`;
		window.srfmStripe.wallets = { applePay: 'auto', googlePay: 'never' };

		let elementsConfig;
		let createOptions;
		global.Stripe = jest.fn( () => ( {
			elements: ( config ) => {
				elementsConfig = config;
				return {
					create: ( _type, options ) => {
						createOptions = options;
						return { mount: jest.fn(), on: jest.fn() };
					},
				};
			},
		} ) );

		StripePayment.prototype.initializePaymentElements.call(
			{ setupPaymentElementEvents: jest.fn() },
			'7-b2',
			document.querySelector( '.srfm-payment-input' ),
			'one-time'
		);

		expect( elementsConfig.amount ).toBe( 6000 );
		expect( createOptions.wallets ).toEqual( {
			applePay: 'auto',
			googlePay: 'never',
		} );
		expect( StripePayment.paymentElements[ '7-b2' ].amount ).toBe( 6000 );
	} );

	// Stripe answers elements/sessions with 400 amount_too_small below its
	// minimum charge (about USD 0.50 after conversion), and the card field
	// never renders. A placeholder of 1 minor unit hid one-time variable and
	// calculated amounts in 2.13.1.
	it.each( [ '', '0' ] )(
		'mounts an unpicked amount (%p) with a placeholder Stripe accepts',
		( currentAmount ) => {
			document.body.innerHTML = `
				<form data-srfm-instance="7">
					<div class="srfm-block srfm-payment-block" data-block-id="b3">
						<input class="srfm-payment-input" data-stripe-key="pk_test" data-amount-type="variable" data-current-amount="${ currentAmount }" data-currency="cad" />
						<div class="srfm-stripe-payment-element"></div>
					</div>
				</form>`;

			let elementsConfig;
			global.Stripe = jest.fn( () => ( {
				elements: ( config ) => {
					elementsConfig = config;
					return {
						create: () => ( { mount: jest.fn(), on: jest.fn() } ),
					};
				},
			} ) );
			delete StripePayment.paymentElements[ '7-b3' ];
			delete StripePayment.stripeInstances[ '7-b3' ];

			StripePayment.prototype.initializePaymentElements.call(
				{ setupPaymentElementEvents: jest.fn() },
				'7-b3',
				document.querySelector( '.srfm-payment-input' ),
				'one-time'
			);

			expect( elementsConfig.amount ).toBe( 12000 );
		}
	);
} );

describe( 'srfmConfirmPayment', () => {
	it( 'brings the amount up to date before the wallet sheet opens', async () => {
		const input = renderBlock(
			'data-amount-type="variable" data-current-amount="110" data-currency="eur"'
		);
		const order = [];
		const elements = {
			update: jest.fn( () => order.push( 'update' ) ),
			submit: jest.fn( async () => {
				order.push( 'submit' );
				return { error: { message: 'stop' } };
			} ),
		};
		StripePayment.paymentElements[ '7-b1' ] = { elements, amount: 1 };

		await StripePayment.srfmConfirmPayment(
			'7-b1',
			StripePayment.paymentElements[ '7-b1' ],
			input.closest( 'form' )
		);

		expect( order ).toEqual( [ 'update', 'submit' ] );
		expect( elements.update ).toHaveBeenCalledWith( { amount: 11000 } );
	} );
} );
