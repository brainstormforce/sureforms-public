function initializeDateTime() {

	const datePickerContainers = document.querySelectorAll('.srfm-datepicker-block');
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

		datePickerContainers.forEach(element => {
			const selector = element.querySelector('.srfm-input-datepicker');

			selector.onchange = function ( e ) {
				const formattedDate = e.target.value.replaceAll( '/', '-' );
				selector.setAttribute('value', formattedDate);
			};

		});
	}


	var headers = new Headers();
headers.append("X-CSCAPI-KEY", "API_KEY");

var requestOptions = {
method: 'GET',
headers: headers,
redirect: 'follow'
};

fetch("https://api.countrystatecity.in/v1/states", requestOptions)
.then(response => response.text())
.then(result => console.log(result))
.catch(error => console.log('error', error));
}

document.addEventListener( 'DOMContentLoaded', initializeDateTime );
