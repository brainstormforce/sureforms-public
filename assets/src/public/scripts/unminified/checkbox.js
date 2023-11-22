
function initializeCheckbox(){
    const checkboxContainers = document.querySelectorAll(
        '.srfm-checkbox-container'
    );
    
    if ( checkboxContainers ) {
        for ( let i = 0; i < checkboxContainers.length; i++ ) {
            {
                const formElement = checkboxContainers[ i ].closest( 'form' );
                // eslint-disable-next-line no-undef
                const computedStyle = getComputedStyle( formElement );
                const primaryColor = computedStyle.getPropertyValue(
                    '--srfm-primary-color'
                );
                const checkboxInputs = checkboxContainers[ i ].querySelectorAll(
                    '.srfm-classic-checkbox-input'
                );
                checkboxInputs.forEach( ( checkboxInput ) => {
                    if ( '' === primaryColor ) {
                        checkboxInput.classList.add(
                            '!srfm-text-[#0084C7]',
                            'focus:!srfm-ring-[#0084C7]',
                            'checked:!srfm-bg-[#0084C7]',
                            'checked:!srfm-border-none'
                        );
                    } else {
                        checkboxInput.classList.add(
                            '!srfm-text-srfm_primary_color',
                            'focus:!srfm-ring-srfm_primary_color',
                            'checked:!srfm-bg-srfm_primary_color',
                            'checked:!srfm-border-none'
                        );
                    }
                } );
            }
        }
    }
}
document.addEventListener('DOMContentLoaded', initializeCheckbox);
