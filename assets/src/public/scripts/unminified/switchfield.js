
function initializeSwitchField(){
    //classic field JS
    const toggleSwitchesContainer = document.getElementsByClassName(
        'srfm-classic-switch-container'
    );
    
    if ( toggleSwitchesContainer ) {
        for ( let i = 0; i < toggleSwitchesContainer.length; i++ ) {
            const toggleSwitch =
                toggleSwitchesContainer[ i ].querySelector( '.srfm-switch' );
            const toggleLabel =
                toggleSwitchesContainer[ i ].querySelector( '.srfm-switch-label' );
            const toggleSwitchCurrentId = toggleSwitch.getAttribute( 'id' );
            if ( toggleSwitch && toggleLabel ) {
                toggleSwitch.id = toggleSwitchCurrentId + i;
                toggleLabel.htmlFor = toggleSwitchCurrentId + i;
            }
            if ( toggleSwitch ) {
                toggleSwitch.addEventListener( 'click', () => {
                    const formElement = toggleSwitch.closest( 'form' );
                    // eslint-disable-next-line no-undef
    
                    if (
                        toggleSwitch.classList.contains(
                            'srfm-classic-switch-input'
                        )
                    ) {
                        const computedStyle = getComputedStyle( formElement );
                        const primaryColor = computedStyle.getPropertyValue(
                            '--srfm-primary-color'
                        );
                        const currentValue = toggleSwitch.value;
    
                        toggleSwitch.value =
                            currentValue === 'true' ? 'false' : 'true';
                        const switchBackground = toggleSwitchesContainer[
                            i
                        ].querySelector( '.srfm-switch-background' );
                        const switchToggle = toggleSwitchesContainer[
                            i
                        ].querySelector( '.srfm-switch-toggle' );
                        const switchTickIcon = toggleSwitchesContainer[
                            i
                        ].querySelector( '.srfm-classic-toggle-icon' );
    
                        if ( toggleSwitch.value === 'true' ) {
                            if ( switchBackground && switchTickIcon ) {
                                switchBackground.style.backgroundColor =
                                    primaryColor !== ''
                                        ? 'var(--srfm-primary-color)'
                                        : '#0284c7';
                                switchTickIcon.style.fill =
                                    primaryColor !== ''
                                        ? 'var(--srfm-primary-color)'
                                        : '#0284c7';
                            }
                            if (
                                switchBackground
                                    .querySelector(
                                        '.srfm-classic-toggle-icon-container'
                                    )
                                    .classList.contains( '!srfm-opacity-0' )
                            ) {
                                switchBackground
                                    .querySelector(
                                        '.srfm-classic-toggle-icon-container'
                                    )
                                    .classList.remove( '!srfm-opacity-0' );
                                switchBackground
                                    .querySelector(
                                        '.srfm-classic-toggle-icon-container'
                                    )
                                    .classList.add( '!srfm-opacity-100' );
                                if ( switchToggle ) {
                                    switchToggle.style.left = '24px';
                                }
                                if ( toggleSwitch ) {
                                    toggleSwitch.value = 'true';
                                }
                            } else {
                                if ( switchBackground ) {
                                    switchBackground.style.backgroundColor =
                                        '#dcdcdc';
                                    switchBackground
                                        .querySelector(
                                            '.srfm-classic-toggle-icon-container'
                                        )
                                        .classList.add( '!srfm-opacity-0' );
                                    switchBackground
                                        .querySelector(
                                            '.srfm-classic-toggle-icon-container'
                                        )
                                        .classList.remove( '!srfm-opacity-100' );
                                }
                                if ( switchToggle ) {
                                    switchToggle.style.left = '0';
                                }
                                if ( toggleSwitch ) {
                                    toggleSwitch.value = 'false';
                                }
                            }
                        } else {
                            // eslint-disable-next-line no-lonely-if
                            if (
                                switchBackground
                                    .querySelector(
                                        '.srfm-classic-toggle-icon-container'
                                    )
                                    .classList.contains( '!srfm-opacity-100' )
                            ) {
                                switchBackground
                                    .querySelector(
                                        '.srfm-classic-toggle-icon-container'
                                    )
                                    .classList.remove( '!srfm-opacity-100' );
                                switchBackground
                                    .querySelector(
                                        '.srfm-classic-toggle-icon-container'
                                    )
                                    .classList.add( '!srfm-opacity-0' );
                                if ( switchToggle ) {
                                    switchToggle.style.left = '0';
                                }
                                if ( switchBackground ) {
                                    switchBackground.style.backgroundColor =
                                        '#dcdcdc';
                                }
                                if ( toggleSwitch ) {
                                    toggleSwitch.value = 'false';
                                }
                            } else {
                                switchBackground
                                    .querySelector(
                                        '.srfm-classic-toggle-icon-container'
                                    )
                                    .classList.remove( '!srfm-opacity-0' );
                                switchBackground
                                    .querySelector(
                                        '.srfm-classic-toggle-icon-container'
                                    )
                                    .classList.add( '!srfm-opacity-100' );
                                if ( switchToggle ) {
                                    switchToggle.style.left = '24px';
                                }
                                if ( switchBackground && switchTickIcon ) {
                                    switchBackground.style.backgroundColor =
                                        primaryColor !== ''
                                            ? 'var(--srfm-primary-color)'
                                            : '#0284c7';
                                    switchTickIcon.style.fill =
                                        primaryColor !== ''
                                            ? 'var(--srfm-primary-color)'
                                            : '#0284c7';
                                }
                                if ( toggleSwitch ) {
                                    toggleSwitch.value = 'true';
                                }
                            }
                        }
                    } else {
                        const currentValue = toggleSwitch.value;
                        toggleSwitch.value =
                            currentValue === 'true' ? 'false' : 'true';
                        const switchBackground = toggleSwitchesContainer[
                            i
                        ].querySelector( '.srfm-switch-background' );
                        const switchToggle = toggleSwitchesContainer[
                            i
                        ].querySelector( '.srfm-switch-toggle' );
                        if ( toggleSwitch.value === 'true' ) {
                            if ( switchBackground && switchToggle ) {
                                switchBackground.style.backgroundColor = '#007CBA';
                                switchToggle.style.left = '27px';
                            }
                        } else if ( switchBackground && switchToggle ) {
                            switchBackground[ i ].style.backgroundColor = '#dcdcdc';
                            switchToggle.style.left = '2px';
                        }
                    }
                } );
            }
        }
    }

    //default field JS
    const toggleSwitches = document.getElementsByClassName( 'srfm-default-switch' );

    if ( toggleSwitches ) {
        for ( let i = 0; i < toggleSwitches.length; i++ ) {
            toggleSwitches[ i ].addEventListener( 'click', () => {
                const currentValue = toggleSwitches[ i ].value;
                toggleSwitches[ i ].value =
                    currentValue === 'true' ? 'false' : 'true';
                const switchBackground = document.getElementsByClassName(
                    'srfm-switch-background'
                );
                const switchToggle =
                    document.getElementsByClassName( 'srfm-switch-toggle' );
                if ( toggleSwitches[ i ].value === 'true' ) {
                    switchBackground[ i ].style.backgroundColor = '#007CBA';
                    switchToggle[ i ].style.left = '27px';
                } else {
                    switchBackground[ i ].style.backgroundColor = '#dcdcdc';
                    switchToggle[ i ].style.left = '2px';
                }
            } );
        }
    }
}

document.addEventListener('DOMContentLoaded', initializeSwitchField);