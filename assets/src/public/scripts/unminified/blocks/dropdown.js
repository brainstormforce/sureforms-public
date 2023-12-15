function initializeDropdown() {
    const dropDownSelector = document.querySelectorAll('.srfm-dropdown-common');

    dropDownSelector.forEach(element => {
        NiceSelect.bind(element);
    });
}
document.addEventListener( 'DOMContentLoaded', initializeDropdown );
