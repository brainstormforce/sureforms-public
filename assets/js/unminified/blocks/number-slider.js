function initializeNumberSlider() {
	const sliderElement = document.querySelectorAll(
		'.srfm-input-number-slider'
	);
	if ( sliderElement ) {
		sliderElement.forEach( ( element ) => {
			element.addEventListener( 'input', function ( e ) {
				const target = e.currentTarget;
				const min = target.getAttribute( 'min' );
				const max = target.getAttribute( 'max' );
				const value = target.value;
				const parent = target
					.closest( '.srfm-block-wrap' )
					.querySelector( '.srfm-number-slider-wrap' );
				if ( parent && min && max ) {
					/* eslint-disable no-mixed-operators */
					const currentValue =
						( 100 / ( parseInt( max ) - parseInt( min ) ) ) *
							parseInt( value ) -
						( 100 / ( parseInt( max ) - parseInt( min ) ) ) *
							parseInt( min );
					/* eslint-enable no-mixed-operators */
					parent.querySelector(
						'.srfm-number-slider-sign span'
					).textContent = value;
					parent.style.setProperty( '--value', currentValue + '%' );
					target.setAttribute( 'value', value );
				}
			} );
		} );
	}
}
document.addEventListener( 'DOMContentLoaded', initializeNumberSlider );
