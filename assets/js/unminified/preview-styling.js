/**
 * Preview Styling Handler
 *
 * Handles PostMessage styling updates from the block editor
 * for real-time preview in the iframe.
 *
 * @package SureForms
 * @since x.x.x
 */

( function() {
	'use strict';

	// Get container ID from localized data.
	var containerId = window.srfmPreviewStyling?.containerId;
	if ( ! containerId ) {
		return;
	}

	var container = document.querySelector( '.' + containerId );
	if ( ! container ) {
		return;
	}

	// Store original classes for reset functionality.
	var originalClasses = container.className;

	window.addEventListener( 'message', function( event ) {
		if ( ! event.data ) {
			return;
		}

		// Handle reset styling - clear inline styles to use original CSS from style block.
		if ( event.data.type === 'srfm-reset-styling' ) {
			container.removeAttribute( 'style' );
			container.className = originalClasses;

			// Reset button container styles.
			var submitContainer = container.querySelector( '.srfm-submit-container .wp-block-button' );
			if ( submitContainer ) {
				submitContainer.removeAttribute( 'style' );
				var btn = submitContainer.querySelector( 'button' );
				if ( btn ) {
					btn.removeAttribute( 'style' );
				}
			}
			return;
		}

		if ( event.data.type !== 'srfm-update-styling' ) {
			return;
		}

		var styling = event.data.styling;
		if ( ! styling ) {
			return;
		}

		// Apply color variables.
		if ( styling.primaryColor ) {
			container.style.setProperty( '--srfm-color-scheme-primary', styling.primaryColor );
			container.style.setProperty( '--srfm-quill-editor-color', styling.primaryColor );
			container.style.setProperty( '--srfm-color-input-border-hover', 'hsl(from ' + styling.primaryColor + ' h s l / 0.65)' );
			container.style.setProperty( '--srfm-color-input-border-focus-glow', 'hsl(from ' + styling.primaryColor + ' h s l / 0.15)' );
			container.style.setProperty( '--srfm-color-input-selected', 'hsl(from ' + styling.primaryColor + ' h s l / 0.1)' );
			container.style.setProperty( '--srfm-btn-color-hover', 'hsl(from ' + styling.primaryColor + ' h s l / 0.9)' );
			container.style.setProperty( '--srfm-btn-color-disabled', 'hsl(from ' + styling.primaryColor + ' h s l / 0.25)' );
		}

		if ( styling.textColor ) {
			container.style.setProperty( '--srfm-color-scheme-text', styling.textColor );
			container.style.setProperty( '--srfm-color-input-label', styling.textColor );
			container.style.setProperty( '--srfm-color-input-text', styling.textColor );
			container.style.setProperty( '--srfm-color-input-description', 'hsl(from ' + styling.textColor + ' h s l / 0.65)' );
			container.style.setProperty( '--srfm-color-input-placeholder', 'hsl(from ' + styling.textColor + ' h s l / 0.5)' );
			container.style.setProperty( '--srfm-color-input-prefix', 'hsl(from ' + styling.textColor + ' h s l / 0.65)' );
			container.style.setProperty( '--srfm-color-input-background', 'hsl(from ' + styling.textColor + ' h s l / 0.02)' );
			container.style.setProperty( '--srfm-color-input-background-hover', 'hsl(from ' + styling.textColor + ' h s l / 0.05)' );
			container.style.setProperty( '--srfm-color-input-background-disabled', 'hsl(from ' + styling.textColor + ' h s l / 0.07)' );
			container.style.setProperty( '--srfm-color-input-border', 'hsl(from ' + styling.textColor + ' h s l / 0.25)' );
			container.style.setProperty( '--srfm-color-input-border-disabled', 'hsl(from ' + styling.textColor + ' h s l / 0.15)' );
			container.style.setProperty( '--srfm-color-multi-choice-svg', 'hsl(from ' + styling.textColor + ' h s l / 0.7)' );
		}

		if ( styling.textOnPrimaryColor ) {
			container.style.setProperty( '--srfm-color-scheme-text-on-primary', styling.textOnPrimaryColor );
		}

		// Apply padding.
		var paddingUnit = styling.formPaddingUnit || 'px';
		if ( styling.formPaddingTop !== undefined ) {
			container.style.setProperty( '--srfm-form-padding-top', styling.formPaddingTop + paddingUnit );
		}
		if ( styling.formPaddingRight !== undefined ) {
			container.style.setProperty( '--srfm-form-padding-right', styling.formPaddingRight + paddingUnit );
		}
		if ( styling.formPaddingBottom !== undefined ) {
			container.style.setProperty( '--srfm-form-padding-bottom', styling.formPaddingBottom + paddingUnit );
		}
		if ( styling.formPaddingLeft !== undefined ) {
			container.style.setProperty( '--srfm-form-padding-left', styling.formPaddingLeft + paddingUnit );
		}

		// Apply border radius.
		var borderRadiusUnit = styling.formBorderRadiusUnit || 'px';
		if ( styling.formBorderRadiusTop !== undefined ) {
			container.style.setProperty( '--srfm-form-border-radius-top', styling.formBorderRadiusTop + borderRadiusUnit );
		}
		if ( styling.formBorderRadiusRight !== undefined ) {
			container.style.setProperty( '--srfm-form-border-radius-right', styling.formBorderRadiusRight + borderRadiusUnit );
		}
		if ( styling.formBorderRadiusBottom !== undefined ) {
			container.style.setProperty( '--srfm-form-border-radius-bottom', styling.formBorderRadiusBottom + borderRadiusUnit );
		}
		if ( styling.formBorderRadiusLeft !== undefined ) {
			container.style.setProperty( '--srfm-form-border-radius-left', styling.formBorderRadiusLeft + borderRadiusUnit );
		}

		// Apply background - remove all background classes first.
		container.classList.remove( 'srfm-bg-color', 'srfm-bg-gradient', 'srfm-bg-image' );

		if ( styling.bgType === 'color' ) {
			container.classList.add( 'srfm-bg-color' );
			if ( styling.bgColor ) {
				container.style.setProperty( '--srfm-bg-color', styling.bgColor );
			}
		} else if ( styling.bgType === 'gradient' ) {
			container.classList.add( 'srfm-bg-gradient' );
			if ( styling.bgGradient ) {
				container.style.setProperty( '--srfm-bg-gradient', styling.bgGradient );
			}
		} else if ( styling.bgType === 'image' ) {
			container.classList.add( 'srfm-bg-image' );
			if ( styling.bgImage ) {
				container.style.setProperty( '--srfm-bg-image', 'url(' + styling.bgImage + ')' );
			}
			if ( styling.bgImagePosition ) {
				var posX = ( styling.bgImagePosition.x || 0.5 ) * 100;
				var posY = ( styling.bgImagePosition.y || 0.5 ) * 100;
				container.style.setProperty( '--srfm-bg-position', posX + '% ' + posY + '%' );
			}
			if ( styling.bgImageSize ) {
				container.style.setProperty( '--srfm-bg-size', styling.bgImageSize );
			}
			if ( styling.bgImageRepeat ) {
				container.style.setProperty( '--srfm-bg-repeat', styling.bgImageRepeat );
			}
			if ( styling.bgImageAttachment ) {
				container.style.setProperty( '--srfm-bg-attachment', styling.bgImageAttachment );
			}
		}

		// Apply field size.
		if ( styling.fieldSize ) {
			var sizeVars = {
				small: {
					'--srfm-input-font-size': '14px',
					'--srfm-input-padding-top': '8px',
					'--srfm-input-padding-bottom': '8px',
					'--srfm-input-padding-left': '12px',
					'--srfm-input-padding-right': '12px',
					'--srfm-input-gap': '16px',
				},
				medium: {
					'--srfm-input-font-size': '16px',
					'--srfm-input-padding-top': '10px',
					'--srfm-input-padding-bottom': '10px',
					'--srfm-input-padding-left': '14px',
					'--srfm-input-padding-right': '14px',
					'--srfm-input-gap': '20px',
				},
				large: {
					'--srfm-input-font-size': '18px',
					'--srfm-input-padding-top': '14px',
					'--srfm-input-padding-bottom': '14px',
					'--srfm-input-padding-left': '16px',
					'--srfm-input-padding-right': '16px',
					'--srfm-input-gap': '24px',
				},
			};
			var vars = sizeVars[ styling.fieldSize ];
			if ( vars ) {
				for ( var key in vars ) {
					container.style.setProperty( key, vars[ key ] );
				}
			}
		}

		// Apply button alignment.
		if ( styling.buttonAlignment ) {
			var submitContainer = container.querySelector( '.srfm-submit-container .wp-block-button' );
			if ( submitContainer ) {
				submitContainer.style.textAlign = styling.buttonAlignment === 'full' ? 'center' : styling.buttonAlignment;
				var btn = submitContainer.querySelector( 'button' );
				if ( btn ) {
					btn.style.width = styling.buttonAlignment === 'full' ? '100%' : '';
				}
			}
		}

		// Allow Pro to extend styling via custom event.
		var customEvent = new CustomEvent( 'srfm-preview-styling-update', { detail: styling } );
		document.dispatchEvent( customEvent );
	} );
}() );