function initializeRatings() {

	const ratingBlock = document.querySelectorAll('.srfm-rating-block');

	if( ratingBlock ) {
		ratingBlock.forEach(element => {
			const singeSelector = element.querySelectorAll('.srfm-icon');

			if( singeSelector ) {
				singeSelector.forEach(single => {
					single.addEventListener( 'click', function(e) {
						handleSingleRating(e,'click');
					} );
					single.addEventListener( 'mouseover', function(e) {
						handleSingleRating(e,'hover');
					} );

					single.addEventListener( 'mouseout', function(e) {
						handleSingleRating(e,'mouse-out');
					} );
				});
			}
		});
	}
}

function handleSingleRating(e, type) {
	const stars = e.currentTarget.closest( 'ul' ).children;
	const selectedValue = parseInt(e.currentTarget.getAttribute('data-value'));
	const ratingValue = e.currentTarget.closest('.srfm-block').querySelector('.srfm-input-rating');

	if( selectedValue && selectedValue ) {
		for ( let i = 0; i < stars.length; i++ ) {
			stars[ i ].classList.remove('srfm-fill');
		}
		for ( let j = 0; j < selectedValue; j++ ) {
			stars[ j ].classList.add('srfm-fill');
		}

		if( 'click' === type ) {
			if( ratingValue ) {
				ratingValue.setAttribute('value', selectedValue );
			}
		}

		if( 'mouse-out' === type ) {
			if( ratingValue && stars ) {
				for ( let k = 0; k < stars.length; k++ ) {
					stars[ k ].classList.remove('srfm-fill');
				}

				if( ratingValue.getAttribute('value') ) {
					for ( let l = 0; l < ratingValue.getAttribute('value'); l++ ) {
						stars[ l ].classList.add('srfm-fill');
					}
				}
			}
		}
	}
}
document.addEventListener( 'DOMContentLoaded', initializeRatings );
