/**
 * Preview Styling Handler
 *
 * Handles PostMessage styling updates from the block editor
 * for real-time preview in the iframe.
 *
 * Uses a dynamic <style> tag to override the server-rendered CSS variables.
 * This avoids issues with container.style.setProperty() silently failing
 * for certain CSS custom properties (e.g. --srfm-color-input-label,
 * --srfm-color-input-text, --srfm-color-input-description) when the same
 * properties are defined in a <style> tag nested inside the container.
 *
 * @package
 * @since x.x.x
 */

( function () {
	'use strict';

	// Get container ID from localized data and validate it as a safe CSS class name.
	const containerId = window.srfmPreviewStyling?.containerId;
	if ( ! containerId || ! /^[a-zA-Z0-9_-]+$/.test( containerId ) ) {
		return;
	}

	const container = document.querySelector( '.' + containerId );
	if ( ! container ) {
		return;
	}

	// Store original classes for reset functionality.
	const originalClasses = container.className;

	// Create a dynamic <style> element for CSS variable overrides.
	// Placed inside the container after the server-rendered <style> so it wins
	// in the cascade with the same selector specificity (later declaration wins).
	const overrideStyle = document.createElement( 'style' );
	overrideStyle.id = 'srfm-preview-override-style';
	container.appendChild( overrideStyle );

	/**
	 * Build CSS text for all variable overrides and apply via the dynamic <style> tag.
	 *
	 * @param {Object} cssVars Key-value pairs of CSS variable names to values.
	 * @since x.x.x
	 */
	function applyCssVarOverrides( cssVars ) {
		let cssText = '.' + containerId + ' {\n';
		for ( const key in cssVars ) {
			if ( cssVars[ key ] !== undefined && cssVars[ key ] !== null ) {
				cssText += '\t' + key + ': ' + cssVars[ key ] + ';\n';
			}
		}
		cssText += '}\n';
		overrideStyle.textContent = cssText;
	}

	window.addEventListener( 'message', function ( event ) {
		if ( event.origin !== window.location.origin ) {
			return;
		}

		if ( ! event.data ) {
			return;
		}

		// Handle reset styling - clear overrides to use original CSS from style block.
		if ( event.data.type === 'srfm-reset-styling' ) {
			overrideStyle.textContent = '';
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

		// Collect all CSS variable overrides into a single object,
		// then apply them all at once via the dynamic <style> tag.
		const cssVars = {};

		// Primary color variables.
		if ( styling.primaryColor ) {
			const primaryHsl = 'hsl(from ' + styling.primaryColor + ' h s l / ';
			cssVars[ '--srfm-color-scheme-primary' ] = styling.primaryColor;
			cssVars[ '--srfm-quill-editor-color' ] = styling.primaryColor;
			cssVars[ '--srfm-color-input-border-hover' ] = primaryHsl + '0.65)';
			cssVars[ '--srfm-color-input-border-focus-glow' ] =
				primaryHsl + '0.15)';
			cssVars[ '--srfm-color-input-selected' ] = primaryHsl + '0.1)';
			cssVars[ '--srfm-btn-color-hover' ] = primaryHsl + '0.9)';
			cssVars[ '--srfm-btn-color-disabled' ] = primaryHsl + '0.25)';
		}

		// Text color variables.
		if ( styling.textColor ) {
			const textHsl = 'hsl(from ' + styling.textColor + ' h s l / ';
			cssVars[ '--srfm-color-scheme-text' ] = styling.textColor;
			cssVars[ '--srfm-color-input-label' ] = styling.textColor;
			cssVars[ '--srfm-color-input-text' ] = styling.textColor;
			cssVars[ '--srfm-color-input-description' ] = textHsl + '0.65)';
			cssVars[ '--srfm-color-input-placeholder' ] = textHsl + '0.5)';
			cssVars[ '--srfm-color-input-prefix' ] = textHsl + '0.65)';
			cssVars[ '--srfm-color-input-background' ] = textHsl + '0.02)';
			cssVars[ '--srfm-color-input-background-hover' ] =
				textHsl + '0.05)';
			cssVars[ '--srfm-color-input-background-disabled' ] =
				textHsl + '0.07)';
			cssVars[ '--srfm-color-input-border' ] = textHsl + '0.25)';
			cssVars[ '--srfm-color-input-border-disabled' ] = textHsl + '0.15)';
			cssVars[ '--srfm-color-multi-choice-svg' ] = textHsl + '0.7)';
		}

		// Text on primary color.
		if ( styling.textOnPrimaryColor ) {
			cssVars[ '--srfm-color-scheme-text-on-primary' ] =
				styling.textOnPrimaryColor;
		}

		// Padding and border radius.
		const paddingUnit = styling.formPaddingUnit || 'px';
		const borderRadiusUnit = styling.formBorderRadiusUnit || 'px';

		if ( styling.formPaddingTop !== undefined ) {
			cssVars[ '--srfm-form-padding-top' ] =
				styling.formPaddingTop + paddingUnit;
		}
		if ( styling.formPaddingRight !== undefined ) {
			cssVars[ '--srfm-form-padding-right' ] =
				styling.formPaddingRight + paddingUnit;
		}
		if ( styling.formPaddingBottom !== undefined ) {
			cssVars[ '--srfm-form-padding-bottom' ] =
				styling.formPaddingBottom + paddingUnit;
		}
		if ( styling.formPaddingLeft !== undefined ) {
			cssVars[ '--srfm-form-padding-left' ] =
				styling.formPaddingLeft + paddingUnit;
		}
		if ( styling.formBorderRadiusTop !== undefined ) {
			cssVars[ '--srfm-form-border-radius-top' ] =
				styling.formBorderRadiusTop + borderRadiusUnit;
		}
		if ( styling.formBorderRadiusRight !== undefined ) {
			cssVars[ '--srfm-form-border-radius-right' ] =
				styling.formBorderRadiusRight + borderRadiusUnit;
		}
		if ( styling.formBorderRadiusBottom !== undefined ) {
			cssVars[ '--srfm-form-border-radius-bottom' ] =
				styling.formBorderRadiusBottom + borderRadiusUnit;
		}
		if ( styling.formBorderRadiusLeft !== undefined ) {
			cssVars[ '--srfm-form-border-radius-left' ] =
				styling.formBorderRadiusLeft + borderRadiusUnit;
		}

		// Background.
		container.classList.remove(
			'srfm-bg-color',
			'srfm-bg-gradient',
			'srfm-bg-image'
		);

		if ( styling.bgType === 'color' ) {
			container.classList.add( 'srfm-bg-color' );
			if ( styling.bgColor ) {
				cssVars[ '--srfm-bg-color' ] = styling.bgColor;
			}
		} else if ( styling.bgType === 'gradient' ) {
			container.classList.add( 'srfm-bg-gradient' );
			if ( styling.bgGradient ) {
				cssVars[ '--srfm-bg-gradient' ] = styling.bgGradient;
			}
		} else if ( styling.bgType === 'image' ) {
			container.classList.add( 'srfm-bg-image' );
			if ( styling.bgImage ) {
				// Validate URL protocol and strip double-quotes to prevent CSS injection.
				const bgImageUrl = styling.bgImage.replace( /"/g, '' );
				if (
					bgImageUrl.startsWith( 'https://' ) ||
					bgImageUrl.startsWith( 'http://' )
				) {
					cssVars[ '--srfm-bg-image' ] = 'url("' + bgImageUrl + '")';
				}
			}
			if ( styling.bgImagePosition ) {
				const posX = ( styling.bgImagePosition.x ?? 0.5 ) * 100;
				const posY = ( styling.bgImagePosition.y ?? 0.5 ) * 100;
				cssVars[ '--srfm-bg-position' ] = posX + '% ' + posY + '%';
			}
			if ( styling.bgImageSize ) {
				cssVars[ '--srfm-bg-size' ] = styling.bgImageSize;
			}
			if ( styling.bgImageRepeat ) {
				cssVars[ '--srfm-bg-repeat' ] = styling.bgImageRepeat;
			}
			if ( styling.bgImageAttachment ) {
				cssVars[ '--srfm-bg-attachment' ] = styling.bgImageAttachment;
			}
		}

		// Field spacing.
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
					if ( key.startsWith( '--' ) ) {
						cssVars[ key ] = finalSize[ key ];
					}
				}
			}
		}

		// Apply all CSS variable overrides at once via the dynamic <style> tag.
		applyCssVarOverrides( cssVars );

		// Button alignment uses direct inline styles on the button element itself
		// (not a CSS variable on the container), so this stays as style manipulation.
		if ( styling.buttonAlignment ) {
			const submitContainer = container.querySelector(
				'.srfm-submit-container .wp-block-button'
			);
			if ( submitContainer ) {
				submitContainer.style.textAlign =
					styling.buttonAlignment === 'justify'
						? 'center'
						: styling.buttonAlignment;
				const btn = submitContainer.querySelector( 'button' );
				if ( btn ) {
					btn.style.width =
						styling.buttonAlignment === 'justify' ? '100%' : '';
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
