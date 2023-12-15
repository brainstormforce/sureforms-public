function initializeMultichoice() {
	const multiChoices = document.querySelectorAll( '.srfm-multi-choice-block' );
	if ( multiChoices ) {
		multiChoices.forEach( ( single ) => {
			const choices = single.querySelectorAll(
				'.srfm-input-multi-choice-single'
			);
			let savedValues = [];
			let getValue = '';
			choices.forEach( ( element ) => {
				element.addEventListener( 'click', ( e ) => {
						getValue = e.target
							.closest( 'label' )
							.querySelector( 'p' ).innerText;
						if ( getValue ) {
							// For Radio Mode / single select.
							if (
								single.classList.contains( 'srfm-radio-mode' )
							) {
								if ( e.target.checked ) {
									single
										.querySelector(
											'.srfm-input-multi-choice-hidden'
										)
										.setAttribute( 'value', getValue );
								}
							}
							// For checkbox mode / multi select.
							if (
								single.classList.contains(
									'srfm-checkbox-mode'
								)
							) {
								if ( e.target.checked ) {
									savedValues = [ ...savedValues, getValue ];
									single
										.querySelector(
											'.srfm-input-multi-choice-hidden'
										)
										.setAttribute( 'value', savedValues );
								} else {
									arr = savedValues.filter(
										( item ) => item !== getValue
									);
									savedValues = arr;
									single
										.querySelector(
											'.srfm-input-multi-choice-hidden'
										)
										.setAttribute( 'value', savedValues );
								}
							}
						}
				} );
			} );
		} );
	}
}
document.addEventListener( 'DOMContentLoaded', initializeMultichoice );
