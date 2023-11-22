
function initializeTextarea(){
    const textAreaContainer = Array.from(
        document.getElementsByClassName( 'srfm-textarea-container' )
    );
    if ( textAreaContainer ) {
        for ( const areaInput of textAreaContainer ) {
            const areaField = areaInput.querySelector( 'textarea' );
            if ( areaField ) {
                areaField.addEventListener( 'input', function () {
                    const textAreaValue = areaField.value;
                    const maxLength = areaField.getAttribute( 'maxLength' );
                    if ( maxLength !== '' ) {
                        const counterDiv = areaInput.querySelector(
                            '.srfm-text-area-counter'
                        );
                        const remainingLength = maxLength - textAreaValue.length;
                        counterDiv.innerText = remainingLength + '/' + maxLength;
                    }
                } );
            }
        }
    }
}
document.addEventListener('DOMContentLoaded', initializeTextarea);