/* eslint-env jest */
require( '../frontend.js' );
require( '../../stripe-payment.js' );

// A translated option: the label shows the translation, data-option-text and
// the submitted value keep the source title.
const renderBlock = ( value ) => {
	document.body.innerHTML = `
		<div class="srfm-multi-choice-block">
			<div class="srfm-multi-choice-single">
				<input class="srfm-input-multi-choice-single" option-value="149" />
				<div class="srfm-option-container">
					<label data-option-text="Early Bird">Frühbucher</label>
				</div>
			</div>
			<input class="srfm-input-multi-choice-hidden" value="${ value }" />
		</div>`;
	const label = document.querySelector( 'label' );
	// jsdom has no layout, so innerText is not implemented.
	Object.defineProperty( label, 'innerText', { value: label.textContent } );
	return {
		block: document.querySelector( '.srfm-multi-choice-block' ),
		hidden: document.querySelector( '.srfm-input-multi-choice-hidden' ),
	};
};

describe( 'getMultiChoiceAmount', () => {
	it( 'matches the submitted value, not the translated label', () => {
		const { block, hidden } = renderBlock( 'Early Bird' );

		expect(
			window.srfmPaymentUtility.getMultiChoiceAmount( block, hidden )
		).toBe( 149 );
	} );

	it( 'ignores a value that only matches the visible label', () => {
		const { block, hidden } = renderBlock( 'Frühbucher' );

		expect(
			window.srfmPaymentUtility.getMultiChoiceAmount( block, hidden )
		).toBe( 0 );
	} );
} );
