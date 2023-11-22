
function initializeNumberSlider(){
    const sliderElement = document.getElementsByClassName(
        'srfm-number-slider-input'
    );
    
    if ( sliderElement ) {
        for ( let i = 0; i < sliderElement.length; i++ ) {
            const blockID = sliderElement[ i ].id.split( '-' )[ 3 ];
            const sliderInput = document.getElementById(
                `srfm-number-slider-${ blockID }`
            );
            if ( sliderInput ) {
                sliderInput.addEventListener( 'input', ( e ) => {
                    const slideValue = e.target.value;
                    document.getElementById(
                        `srfm-number-slider-value-${ blockID }`
                    ).innerText = slideValue;
                } );
            }
        }
    }

    const numberSliderContainer = document.getElementsByClassName(
        'srfm-classic-number-slider'
    );
    
    if ( numberSliderContainer ) {
        // Bg Init
        const bgInit = ( element, val = 0, min = 0, max = 255, color ) => {
            color = color === '' ? '#0284c7' : color;
            // Background Change
            const valBg = ( ( val - min ) / ( max - min ) ) * 100;
            element.style.background = `linear-gradient(to right, ${ color } 0%, ${ color } ${ valBg }%, #fff ${ valBg }%, white 100%)`;
        };
    
        // Pre Init
        const preInit = ( sliderContainer, primaryColor ) => {
            const rangeSliders =
                sliderContainer.querySelector( '.srfm-range-slider' );
            const val = Number( rangeSliders.value );
            const min = Number( rangeSliders.getAttribute( 'min' ) );
            const max = Number( rangeSliders.getAttribute( 'max' ) );
            bgInit( rangeSliders, val, min, max, primaryColor );
        };
    
        // Toggle Class
        const init = ( sliderContainer, primaryColor ) => {
            // Slider Range Change or Input
            const rangeSliders =
                sliderContainer.querySelector( '.srfm-range-slider' );
            const numberInput = sliderContainer.querySelector(
                '.srfm-number-input-slider'
            );
            if ( rangeSliders ) {
                rangeSliders.addEventListener( 'input', function ( e ) {
                    // Prevent Default
                    e.preventDefault();
                    e.stopPropagation();
                    // Background Change
                    const val = Number( rangeSliders.value );
                    const min = Number( rangeSliders.getAttribute( 'min' ) );
                    const max = Number( rangeSliders.getAttribute( 'max' ) );
                    bgInit( rangeSliders, val, min, max, primaryColor );
    
                    // Assign value to slider input
                    numberInput.value = rangeSliders.value;
                } );
            }
            // Input Slider Input
            if ( numberInput ) {
                numberInput.addEventListener( 'input', function ( event ) {
                    // Prevent Default
                    event.preventDefault();
                    event.stopPropagation();
    
                    // Background Change
                    const thisInput = this;
                    let inputVal = Number( thisInput.value );
                    const inputMin = Number( thisInput.getAttribute( 'min' ) );
                    const inputMax = Number( thisInput.getAttribute( 'max' ) );
    
                    // Max Validation
                    if ( inputVal > inputMax ) {
                        inputVal = inputMax;
                        thisInput.value = inputVal;
                    }
    
                    // Min Validation
                    if ( inputVal < inputMin ) {
                        inputVal = inputMin;
                        thisInput.value = inputVal;
                    }
    
                    // Background Change
                    const sliderEle = rangeSliders;
                    bgInit( sliderEle, inputVal, inputMin, inputMax, primaryColor );
    
                    // Assign value to slider range.
                    sliderEle.value = inputVal;
                } );
            }
        };
        for ( const numberSlider of numberSliderContainer ) {
            const formElement = numberSlider.closest( 'form' );
            // eslint-disable-next-line no-undef
            const computedStyle = getComputedStyle( formElement );
            const primaryColor = computedStyle.getPropertyValue(
                '--srfm-primary-color'
            );
            preInit( numberSlider, primaryColor );
            init( numberSlider, primaryColor );
        }
    }
}
document.addEventListener('DOMContentLoaded', initializeNumberSlider);