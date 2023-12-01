function initializeMultichoice() {
	const multiChoices = document.getElementsByClassName( 'srfm-multi-choice' );

	if ( multiChoices ) {
		const selectedOptions = new Map();
		for ( let i = 0; i < multiChoices.length; i++ ) {
			multiChoices[ i ].addEventListener( 'click', ( e ) => {
				const clickArr = e.target.id.split( '-' );
				const clickedId = clickArr[ 3 ];
				const selectedInd = Number( clickArr[ 4 ] );

				const sureformsMultiChoiceLabel =
					document.getElementsByClassName(
						`srfm-multi-choice-label-${ clickedId }`
					);

				if (
					'buttons' ===
					document.getElementById(
						`srfm-multi-choice-style-${ clickedId }`
					).value
				) {
					const selectContainer = document.getElementById(
						`srfm-multi-choice-container-${ clickedId }`
					);
					selectContainer.classList.add( 'sf--focus' );
					const singleSelection = document.getElementById(
						`srfm-multi-choice-selection-${ clickedId }`
					).value;
					if (
						singleSelection &&
						sureformsMultiChoiceLabel.length !== 0
					) {
						// Reset background color and text color for all labels
						for (
							let j = 0;
							j < sureformsMultiChoiceLabel.length;
							j++
						) {
							sureformsMultiChoiceLabel[
								j
							].style.backgroundColor = 'white';
							sureformsMultiChoiceLabel[ j ].style.color =
								'black';
						}
						// Set background color and text color for the selected label
						sureformsMultiChoiceLabel[
							selectedInd
						].style.backgroundColor = 'black';
						sureformsMultiChoiceLabel[ selectedInd ].style.color =
							'white';
					} else if ( sureformsMultiChoiceLabel.length !== 0 ) {
						const backgroundColor =
							sureformsMultiChoiceLabel[ selectedInd ].style
								.backgroundColor;
						const color =
							sureformsMultiChoiceLabel[ selectedInd ].style
								.color;
						if (
							backgroundColor === 'black' &&
							color === 'white'
						) {
							sureformsMultiChoiceLabel[
								selectedInd
							].style.backgroundColor = 'white';
							sureformsMultiChoiceLabel[
								selectedInd
							].style.color = 'black';
						} else {
							sureformsMultiChoiceLabel[
								selectedInd
							].style.backgroundColor = 'black';
							sureformsMultiChoiceLabel[
								selectedInd
							].style.color = 'white';
						}
					}
				}
				const singleSelection = document.getElementById(
					`srfm-multi-choice-selection-${ clickedId }`
				).value;
				const selectedValue = document.getElementById(
					`srfm-multi-choice-option-${ clickedId }-${ selectedInd }`
				).innerText;
				if ( ! selectedOptions.has( clickedId ) ) {
					selectedOptions.set( clickedId, [] );
				}
				const curr_block = selectedOptions.get( clickedId );
				if ( singleSelection ) {
					if ( ! curr_block.includes( selectedInd + 1 ) ) {
						selectedOptions.set( clickedId, [ selectedValue ] );
					} else {
						selectedOptions.set( clickedId, [] );
					}
				} else if ( curr_block.includes( selectedValue ) ) {
					const index = curr_block.indexOf( selectedValue );
					if ( index !== -1 ) {
						curr_block.splice( index, 1 );
					}
				} else {
					curr_block.push( selectedValue );
				}

				const multiChoiceValueField = document.getElementsByClassName(
					`srfm-multi-choice-${ clickedId }`
				);
				multiChoiceValueField[ 0 ].value = selectedOptions
					.get( clickedId )
					.join( ', ' );
			} );
		}
	}
	const multichoiceOptions = document.getElementsByClassName(
		'srfm-classic-multi-choice'
	);
	if ( window.innerWidth > 630 ) {
		for ( let x = 0; x < multichoiceOptions.length - 1; x++ ) {
			const optionHeight = multichoiceOptions[ x ].offsetHeight;
			const adjacentOptionHeight =
				multichoiceOptions[ x + 1 ].offsetHeight;
			if ( optionHeight > adjacentOptionHeight ) {
				multichoiceOptions[ x ].style.height = optionHeight + 'px';
				multichoiceOptions[ x + 1 ].style.height = optionHeight + 'px';
			} else {
				multichoiceOptions[ x ].style.height =
					adjacentOptionHeight + 'px';
				multichoiceOptions[ x + 1 ].style.height =
					adjacentOptionHeight + 'px';
			}
			x++;
		}
	}
}
document.addEventListener( 'DOMContentLoaded', initializeMultichoice );
