function initializeDateTime() {
	const datePickerContainers = document.querySelectorAll(
		'.srfm-datepicker-block'
	);
	if ( datePickerContainers ) {
		datePickerContainers.forEach( ( element ) => {
			const selector = element.querySelector( '.srfm-input-datepicker' );
			const id = selector.getAttribute( 'id' );
			const maxDate = selector.getAttribute( 'max' );
			const minDate = selector.getAttribute( 'min' );
			if (
				selector.classList.contains( 'srfm-input-datepicker-date-time' )
			) {
				const optionArr = {
					enableTime: true,
					dateFormat: 'Y-m-d H:i',
					// Only add minDate and maxDate if they are not empty
					...( minDate && { minDate } ),
					...( maxDate && { maxDate } ),
				};
				flatpickr( `#${ id }`, optionArr );
			} else if (
				selector.classList.contains( 'srfm-input-datepicker-date' )
			) {
				const optionArr = {
					enableTime: false,
					dateFormat: 'Y-m-d H:i',
					// Only add minDate and maxDate if they are not empty
					...( minDate && { minDate } ),
					...( maxDate && { maxDate } ),
				};
				flatpickr( `#${ id }`, optionArr );
			} else {
				flatpickr( `#${ id }`, {
					enableTime: true,
					noCalendar: true,
					dateFormat: 'H:i',
				} );
			}
			if ( selector ) {
				selector.onchange = function ( e ) {
					const formattedDate = e.target.value.replaceAll( '/', '-' );
					selector.setAttribute( 'value', formattedDate );
				};
			}
		} );
	}
}

document.addEventListener( 'DOMContentLoaded', initializeDateTime );
