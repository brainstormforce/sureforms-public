function initializeRatings() {
	// Initialize single ratings
	const singleRatingIcons = document.querySelectorAll(
		'.srfm-rating-icon svg'
	);

	if ( singleRatingIcons ) {
		singleRatingIcons.forEach( ( icon ) => {
			icon.addEventListener( 'click', handleSingleRatingClick );
		} );
	}

	// Initialize classic ratings
	const classicRatingContainers = document.getElementsByClassName(
		'srfm-classic-rating-container'
	);

	for ( const classicRating of classicRatingContainers ) {
		const icons = classicRating.querySelectorAll(
			'.srfm-classic-event [data-te-rating-icon-ref]'
		);
		const ratingResult = classicRating.querySelector(
			'.srfm-rating-field-result'
		);

		icons.forEach( ( el ) => {
			el.addEventListener( 'onSelect.te.rating', ( e ) => {
				const ratingValue = e.value;
				ratingResult.value = ratingValue;
			} );
		} );
	}
}

function handleSingleRatingClick( e ) {
	const onStar = parseInt(
		e.target.closest( '.srfm-rating-icon' ).getAttribute( 'data-value' )
	);
	const stars = e.target.closest( '.srfm-rating-icon-wrapper' ).children;

	const container = e.target.closest( '.srfm-classic-rating-container' );
	const resultField = container.querySelector( '.srfm-rating-field-result' );

	resultField.setAttribute( 'value', onStar );

	for ( let i = 0; i < stars.length; i++ ) {
		stars[ i ]
			.querySelector( 'svg' )
			.classList.remove( 'srfm-fill-current' );
	}

	for ( let j = 0; j < onStar; j++ ) {
		stars[ j ].querySelector( 'svg' ).classList.add( 'srfm-fill-current' );
	}

	// Rating Field theme JS code

	const ratingElements =
		document.getElementsByClassName( 'srfm-rating-field' );

	if ( ratingElements ) {
		const randomIds = document.getElementsByClassName(
			'srfm-rating-random-id'
		);
		const inputLabels = [];

		for ( let i = 0; i < randomIds.length; i++ ) {
			const inputLabel = document.getElementsByClassName(
				'srfm-rating-' + randomIds[ i ].value
			);
			if ( inputLabel.length > 0 ) {
				inputLabels.push( inputLabel );
			}
		}
		const selectedRatingIndex = new Map();
		for ( let x = 0; x < inputLabels.length; x++ ) {
			selectedRatingIndex.set( inputLabels[ x ][ 0 ].className, -1 );
		}

		for ( let i = 0; i < ratingElements.length; i++ ) {
			ratingElements[ i ].addEventListener( 'click', ( ev ) => {
				const clickArr = ev.target.id.split( '-' );
				const clickedStarId = clickArr[ 2 ];
				const clickIndexId = Number( clickArr[ 3 ] );
				const selectedBlock = `srfm-rating-${ clickedStarId }`;
				const isSelected = ev.target;
				const label =
					ratingElements[ i ].nextElementSibling.querySelector(
						'label'
					);
				const colorDataValue = label.getAttribute( 'color-data' );
				const iconColor = document.querySelector(
					`.srfm-rating-icon-color-${ clickedStarId }`
				).value;

				if ( colorDataValue === iconColor ) {
					isSelected.value = '';
				} else {
					isSelected.value = clickIndexId + 1;
				}
				if (
					selectedRatingIndex.get( selectedBlock ) === clickIndexId
				) {
					selectedRatingIndex.set( selectedBlock, -1 );
				} else {
					selectedRatingIndex.set( selectedBlock, clickIndexId );
				}
				for ( let j = 0; j < inputLabels.length; j++ ) {
					for ( let k = 0; k < inputLabels[ j ].length; k++ ) {
						const hasClassName = inputLabels[ j ][
							k
						].classList.contains(
							`srfm-rating-${ clickedStarId }`
						);

						if (
							k <=
								parseInt(
									selectedRatingIndex.get( selectedBlock )
								) &&
							inputLabels[ j ][ k ] &&
							hasClassName
						) {
							inputLabels[ j ][ k ].style.color = iconColor;
							inputLabels[ j ][ k ].setAttribute(
								'color-data',
								iconColor
							);
						} else if ( inputLabels[ j ][ k ] && hasClassName ) {
							inputLabels[ j ][ k ].style.color = '#ddd';
							inputLabels[ j ][ k ].setAttribute(
								'color-data',
								'#ddd'
							);
						}
						if (
							k ===
								parseInt(
									selectedRatingIndex.get( selectedBlock )
								) &&
							hasClassName
						) {
							inputLabels[ j ][ k ].style.fontSize = '30px';
						} else {
							inputLabels[ j ][ k ].style.fontSize = '25px';
						}
					}
				}
			} );

			ratingElements[ i ].setAttribute( 'hidden', 'true' );
		}
	}
}

document.addEventListener( 'DOMContentLoaded', initializeRatings );
