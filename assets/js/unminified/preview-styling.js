/**
 * Preview Styling Handler
 *
 * Handles PostMessage styling updates from the block editor
 * for real-time preview in the iframe.
 *
 * @package
 * @since x.x.x
 */

import {
	PRIMARY_COLOR_MAP,
	TEXT_COLOR_MAP,
	SIMPLE_CSS_MAP,
	DIMENSIONS_CSS_MAP,
	applyColorMap,
	applyDimensionsFromStyling,
} from '@Utils/styling-utils';

( function () {
	'use strict';

	// Get container ID from localized data.
	const containerId = window.srfmPreviewStyling?.containerId;
	if ( ! containerId ) {
		return;
	}

	const container = document.querySelector( '.' + containerId );
	if ( ! container ) {
		return;
	}

	// Store original classes for reset functionality.
	const originalClasses = container.className;

	window.addEventListener( 'message', function ( event ) {
		if ( ! event.data ) {
			return;
		}

		// Handle reset styling - clear inline styles to use original CSS from style block.
		if ( event.data.type === 'srfm-reset-styling' ) {
			container.removeAttribute( 'style' );
			container.className = originalClasses;

			// Reset button container styles.
			const resetSubmitContainer = container.querySelector(
				'.srfm-submit-container .wp-block-button'
			);
			if ( resetSubmitContainer ) {
				resetSubmitContainer.removeAttribute( 'style' );
				const resetBtn = resetSubmitContainer.querySelector( 'button' );
				if ( resetBtn ) {
					resetBtn.removeAttribute( 'style' );
				}
			}
			return;
		}

		if ( event.data.type !== 'srfm-update-styling' ) {
			return;
		}

		const styling = event.data.styling;
		if ( ! styling ) {
			return;
		}

		// Apply color variables using shared utility functions.
		if ( styling.primaryColor ) {
			applyColorMap( container, PRIMARY_COLOR_MAP, styling.primaryColor );
		}

		if ( styling.textColor ) {
			applyColorMap( container, TEXT_COLOR_MAP, styling.textColor );
		}

		// Apply simple CSS variables.
		// Note: `bgImagePosition` is intentionally included in SIMPLE_CSS_MAP so it
		// can be applied as a plain string value in non-Gutenberg contexts (e.g. Elementor).
		// In Gutenberg, `bgImagePosition` is an {x, y} object, so this loop will
		// temporarily set --srfm-bg-position to "[object Object]". The explicit
		// bgImagePosition handler below (inside the bgType === 'image' branch)
		// immediately overwrites it with the correctly formatted "X% Y%" string.
		for ( const controlName in SIMPLE_CSS_MAP ) {
			if (
				Object.hasOwn( SIMPLE_CSS_MAP, controlName ) &&
				styling[ controlName ]
			) {
				container.style.setProperty(
					SIMPLE_CSS_MAP[ controlName ],
					styling[ controlName ]
				);
			}
		}

		// Apply dimensions from styling object.
		for ( const controlName in DIMENSIONS_CSS_MAP ) {
			if ( Object.hasOwn( DIMENSIONS_CSS_MAP, controlName ) ) {
				const prefix = DIMENSIONS_CSS_MAP[ controlName ];
				const unitKey = controlName + 'Unit';
				const unit = styling[ unitKey ] || 'px';
				applyDimensionsFromStyling(
					container,
					prefix,
					styling,
					controlName,
					unit
				);
			}
		}

		// Apply background - remove all background classes first.
		container.classList.remove(
			'srfm-bg-color',
			'srfm-bg-gradient',
			'srfm-bg-image'
		);

		if ( styling.bgType === 'color' ) {
			container.classList.add( 'srfm-bg-color' );
			if ( styling.bgColor ) {
				container.style.setProperty(
					'--srfm-bg-color',
					styling.bgColor
				);
			}
		} else if ( styling.bgType === 'gradient' ) {
			container.classList.add( 'srfm-bg-gradient' );
			if ( styling.bgGradient ) {
				container.style.setProperty(
					'--srfm-bg-gradient',
					styling.bgGradient
				);
			}
		} else if ( styling.bgType === 'image' ) {
			container.classList.add( 'srfm-bg-image' );
			if ( styling.bgImage ) {
				container.style.setProperty(
					'--srfm-bg-image',
					'url("' + styling.bgImage + '")'
				);
			} else {
				container.style.removeProperty( '--srfm-bg-image' );
			}
			if ( styling.bgImagePosition ) {
				const posX = ( styling.bgImagePosition.x || 0.5 ) * 100;
				const posY = ( styling.bgImagePosition.y || 0.5 ) * 100;
				container.style.setProperty(
					'--srfm-bg-position',
					posX + '% ' + posY + '%'
				);
			}
		}

		// Apply field spacing - uses CSS variables from Helper::get_css_vars() via localized data.
		if ( styling.fieldSpacing ) {
			const fieldSpacingVars =
				window.srfmPreviewStyling?.fieldSpacingVars;
			if ( fieldSpacingVars ) {
				// Merge base (small) with size-specific overrides, same approach as StyleSettings.js.
				const baseSize = fieldSpacingVars.small || {};
				const overrideSize =
					fieldSpacingVars[ styling.fieldSpacing ] || {};
				const finalSize = Object.assign( {}, baseSize, overrideSize );

				for ( const key in finalSize ) {
					if ( Object.hasOwn( finalSize, key ) ) {
						container.style.setProperty( key, finalSize[ key ] );
					}
				}
			}
		}

		// Apply button alignment.
		if ( styling.buttonAlignment ) {
			const submitContainer = container.querySelector(
				'.srfm-submit-container .wp-block-button'
			);
			if ( submitContainer ) {
				submitContainer.style.textAlign =
					styling.buttonAlignment === 'full'
						? 'center'
						: styling.buttonAlignment;
				const btn = submitContainer.querySelector( 'button' );
				if ( btn ) {
					btn.style.width =
						styling.buttonAlignment === 'full' ? '100%' : '';
				}
			}
		}

		// Allow Pro to extend styling via custom event.
		const customEvent = new CustomEvent( 'srfm-preview-styling-update', {
			detail: {
				styling,
				container,
			},
		} );
		document.dispatchEvent( customEvent );
	} );
}() );
