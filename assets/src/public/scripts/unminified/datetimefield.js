
function initializeDateTime(){
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
        flatpickr( '.srfm-input-date-time', {
            enableTime: true,
            dateFormat: 'Y-m-d H:i',
        } );
    
        flatpickr( '.srfm-input-date' );
    
        flatpickr( '.srfm-input-time', {
            enableTime: true,
            noCalendar: true,
            dateFormat: 'H:i',
        } );
    
        for ( const datePickerContainer of datePickerContainers ) {
            const resultInput = datePickerContainer.querySelector(
                '.srfm-classic-date-time-result'
            );
    
            datePickerContainer.querySelector( '.srfm-input-data-time' ).onchange =
                function ( e ) {
                    formattedDate = e.target.value.replaceAll( '/', '-' );
                    resultInput.value = formattedDate;
                };
        }
    }
}

document.addEventListener('DOMContentLoaded', initializeDateTime);