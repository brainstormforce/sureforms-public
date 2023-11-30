function initializeDateTime() {
	const dateTimeElement = document.getElementsByClassName(
		'srfm-input-date-container'
	);

	if ( dateTimeElement ) {
		for ( let i = 0; i < dateTimeElement.length; i++ ) {
			const blockID = dateTimeElement[ i ].id.split( '-' )[ 4 ];
			const dateInput = document.getElementById(
				`srfm-input-date-${ blockID }`
			);
			const timeInput = document.getElementById(
				`srfm-input-time-${ blockID }`
			);

			const fullDateTimeInput = document.getElementById(
				`srfm-full-date-time-${ blockID }`
			);

			const updateFullDateTime = () => {
				let date = '';
				if ( dateInput ) {
					date = dateInput.value
						.trim()
						.split( /[\/-]/ )
						.reverse()
						.join( '-' );
				}
				let time = '';
				if ( timeInput ) {
					time = timeInput.value.trim();
				}
				const dateTimeParts = [ date, time ];

				const fullDateTime = dateTimeParts
					.filter( ( part ) => part !== '' )
					.join( ', ' );

				fullDateTimeInput.value = fullDateTime;
			};

			if ( dateInput ) {
				dateInput.addEventListener( 'change', updateFullDateTime );
			}
			if ( timeInput ) {
				timeInput.addEventListener( 'change', updateFullDateTime );
			}
		}
	}

	const datePickerContainers = document.getElementsByClassName(
		'srfm-classic-date-time-container'
	);
	if ( datePickerContainers ) {
		flatpickr( '.srfm-input-time', {
			enableTime: true,
			noCalendar: true,
			dateFormat: 'H:i',
		} );

		for ( const datePickerContainer of datePickerContainers ) {
			const resultInput = datePickerContainer.querySelector(
				'.srfm-classic-date-time-result'
			);
			const blockId = datePickerContainer.getAttribute( 'block-id' );
			const minMaxHolder = datePickerContainer.querySelector(
				'.srfm-min-max-holder'
			);
			const maxDate = minMaxHolder.getAttribute( 'max' );
			const minDate = minMaxHolder.getAttribute( 'min' );
			flatpickr( `.srfm-input-date-time-${ blockId }`, {
				enableTime: true,
				dateFormat: 'Y-m-d H:i',
				minDate,
				maxDate,
			} );
			flatpickr( `.srfm-input-date-${ blockId }`, {
				enableTime: false,
				dateFormat: 'Y-m-d',
				minDate,
				maxDate,
			} );

			datePickerContainer.querySelector(
				'.srfm-input-data-time'
			).onchange = function ( e ) {
				const formattedDate = e.target.value.replaceAll( '/', '-' );
				resultInput.value = formattedDate;
			};
		}
	}
}

document.addEventListener( 'DOMContentLoaded', initializeDateTime );
