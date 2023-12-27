function initializeDateTime() {
	const datePickerContainers = document.querySelectorAll(
		'.srfm-datepicker-block'
	);
	if ( datePickerContainers ) {
		flatpickr( '.srfm-input-datepicker-date-time', {
			enableTime: true,
			dateFormat: 'Y-m-d H:i',
		} );

		flatpickr( '.srfm-input-datepicker-date' );

		flatpickr( '.srfm-input-datepicker-time', {
			enableTime: true,
			noCalendar: true,
			dateFormat: 'H:i',
		} );

		datePickerContainers.forEach( ( element ) => {
			const selector = element.querySelector( '.srfm-input-datepicker' );

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
