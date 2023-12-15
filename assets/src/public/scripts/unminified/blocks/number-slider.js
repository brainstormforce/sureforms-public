function initializeNumberSlider() {
	const sliderElement = document.querySelectorAll('.srfm-input-number-slider');

	if( sliderElement ) {
		sliderElement.forEach(element => {
			element.addEventListener('input', function(e) {
				this.parentNode.style.setProperty('--value',this.value);
				this.parentNode.style.setProperty('--text-value', JSON.stringify(this.value));
				element.setAttribute('value', this.value);
			});
		});
	}

}
document.addEventListener( 'DOMContentLoaded', initializeNumberSlider );
