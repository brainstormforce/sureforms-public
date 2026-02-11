/**
 * Preview Styling Handler
 *
 * Handles PostMessage styling updates from the block editor
 * for real-time preview in the iframe.
 *
 * @package
 * @since x.x.x
 */

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

		// Apply color variables.
		if ( styling.primaryColor ) {
			container.style.setProperty(
				'--srfm-color-scheme-primary',
				styling.primaryColor
			);
			container.style.setProperty(
				'--srfm-quill-editor-color',
				styling.primaryColor
			);
			container.style.setProperty(
				'--srfm-color-input-border-hover',
				'hsl(from ' + styling.primaryColor + ' h s l / 0.65)'
			);
			container.style.setProperty(
				'--srfm-color-input-border-focus-glow',
				'hsl(from ' + styling.primaryColor + ' h s l / 0.15)'
			);
			container.style.setProperty(
				'--srfm-color-input-selected',
				'hsl(from ' + styling.primaryColor + ' h s l / 0.1)'
			);
			container.style.setProperty(
				'--srfm-btn-color-hover',
				'hsl(from ' + styling.primaryColor + ' h s l / 0.9)'
			);
			container.style.setProperty(
				'--srfm-btn-color-disabled',
				'hsl(from ' + styling.primaryColor + ' h s l / 0.25)'
			);
		}

		if ( styling.textColor ) {
			container.style.setProperty(
				'--srfm-color-scheme-text',
				styling.textColor
			);
			container.style.setProperty(
				'--srfm-color-input-label',
				styling.textColor
			);
			container.style.setProperty(
				'--srfm-color-input-text',
				styling.textColor
			);
			container.style.setProperty(
				'--srfm-color-input-description',
				'hsl(from ' + styling.textColor + ' h s l / 0.65)'
			);
			container.style.setProperty(
				'--srfm-color-input-placeholder',
				'hsl(from ' + styling.textColor + ' h s l / 0.5)'
			);
			container.style.setProperty(
				'--srfm-color-input-prefix',
				'hsl(from ' + styling.textColor + ' h s l / 0.65)'
			);
			container.style.setProperty(
				'--srfm-color-input-background',
				'hsl(from ' + styling.textColor + ' h s l / 0.02)'
			);
			container.style.setProperty(
				'--srfm-color-input-background-hover',
				'hsl(from ' + styling.textColor + ' h s l / 0.05)'
			);
			container.style.setProperty(
				'--srfm-color-input-background-disabled',
				'hsl(from ' + styling.textColor + ' h s l / 0.07)'
			);
			container.style.setProperty(
				'--srfm-color-input-border',
				'hsl(from ' + styling.textColor + ' h s l / 0.25)'
			);
			container.style.setProperty(
				'--srfm-color-input-border-disabled',
				'hsl(from ' + styling.textColor + ' h s l / 0.15)'
			);
			container.style.setProperty(
				'--srfm-color-multi-choice-svg',
				'hsl(from ' + styling.textColor + ' h s l / 0.7)'
			);
		}

		if ( styling.textOnPrimaryColor ) {
			container.style.setProperty(
				'--srfm-color-scheme-text-on-primary',
				styling.textOnPrimaryColor
			);
		}

		// Apply padding.
		const paddingUnit = styling.formPaddingUnit || 'px';
		if ( styling.formPaddingTop !== undefined ) {
			container.style.setProperty(
				'--srfm-form-padding-top',
				styling.formPaddingTop + paddingUnit
			);
		}
		if ( styling.formPaddingRight !== undefined ) {
			container.style.setProperty(
				'--srfm-form-padding-right',
				styling.formPaddingRight + paddingUnit
			);
		}
		if ( styling.formPaddingBottom !== undefined ) {
			container.style.setProperty(
				'--srfm-form-padding-bottom',
				styling.formPaddingBottom + paddingUnit
			);
		}
		if ( styling.formPaddingLeft !== undefined ) {
			container.style.setProperty(
				'--srfm-form-padding-left',
				styling.formPaddingLeft + paddingUnit
			);
		}

		// Apply border radius.
		const borderRadiusUnit = styling.formBorderRadiusUnit || 'px';
		if ( styling.formBorderRadiusTop !== undefined ) {
			container.style.setProperty(
				'--srfm-form-border-radius-top',
				styling.formBorderRadiusTop + borderRadiusUnit
			);
		}
		if ( styling.formBorderRadiusRight !== undefined ) {
			container.style.setProperty(
				'--srfm-form-border-radius-right',
				styling.formBorderRadiusRight + borderRadiusUnit
			);
		}
		if ( styling.formBorderRadiusBottom !== undefined ) {
			container.style.setProperty(
				'--srfm-form-border-radius-bottom',
				styling.formBorderRadiusBottom + borderRadiusUnit
			);
		}
		if ( styling.formBorderRadiusLeft !== undefined ) {
			container.style.setProperty(
				'--srfm-form-border-radius-left',
				styling.formBorderRadiusLeft + borderRadiusUnit
			);
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
			if ( styling.bgImageSize ) {
				container.style.setProperty(
					'--srfm-bg-size',
					styling.bgImageSize
				);
			}
			if ( styling.bgImageRepeat ) {
				container.style.setProperty(
					'--srfm-bg-repeat',
					styling.bgImageRepeat
				);
			}
			if ( styling.bgImageAttachment ) {
				container.style.setProperty(
					'--srfm-bg-attachment',
					styling.bgImageAttachment
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
					container.style.setProperty( key, finalSize[ key ] );
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
