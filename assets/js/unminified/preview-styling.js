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
		if ( event.origin !== window.location.origin ) {
			return;
		}

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
			const primaryHsl = 'hsl(from ' + styling.primaryColor + ' h s l / ';
			const primaryColorStyles = [
				{
					cssVar: '--srfm-color-scheme-primary',
					value: styling.primaryColor,
				},
				{
					cssVar: '--srfm-quill-editor-color',
					value: styling.primaryColor,
				},
				{
					cssVar: '--srfm-color-input-border-hover',
					value: primaryHsl + '0.65)',
				},
				{
					cssVar: '--srfm-color-input-border-focus-glow',
					value: primaryHsl + '0.15)',
				},
				{
					cssVar: '--srfm-color-input-selected',
					value: primaryHsl + '0.1)',
				},
				{
					cssVar: '--srfm-btn-color-hover',
					value: primaryHsl + '0.9)',
				},
				{
					cssVar: '--srfm-btn-color-disabled',
					value: primaryHsl + '0.25)',
				},
			];
			primaryColorStyles.forEach( ( { cssVar, value } ) => {
				container.style.setProperty( cssVar, value );
			} );
		}

		if ( styling.textColor ) {
			const textHsl = 'hsl(from ' + styling.textColor + ' h s l / ';
			const textColorStyles = [
				{
					cssVar: '--srfm-color-scheme-text',
					value: styling.textColor,
				},
				{
					cssVar: '--srfm-color-input-label',
					value: styling.textColor,
				},
				{ cssVar: '--srfm-color-input-text', value: styling.textColor },
				{
					cssVar: '--srfm-color-input-description',
					value: textHsl + '0.65)',
				},
				{
					cssVar: '--srfm-color-input-placeholder',
					value: textHsl + '0.5)',
				},
				{
					cssVar: '--srfm-color-input-prefix',
					value: textHsl + '0.65)',
				},
				{
					cssVar: '--srfm-color-input-background',
					value: textHsl + '0.02)',
				},
				{
					cssVar: '--srfm-color-input-background-hover',
					value: textHsl + '0.05)',
				},
				{
					cssVar: '--srfm-color-input-background-disabled',
					value: textHsl + '0.07)',
				},
				{
					cssVar: '--srfm-color-input-border',
					value: textHsl + '0.25)',
				},
				{
					cssVar: '--srfm-color-input-border-disabled',
					value: textHsl + '0.15)',
				},
				{
					cssVar: '--srfm-color-multi-choice-svg',
					value: textHsl + '0.7)',
				},
			];
			textColorStyles.forEach( ( { cssVar, value } ) => {
				container.style.setProperty( cssVar, value );
			} );
		}

		if ( styling.textOnPrimaryColor ) {
			container.style.setProperty(
				'--srfm-color-scheme-text-on-primary',
				styling.textOnPrimaryColor
			);
		}

		// Apply padding and border radius.
		const paddingUnit = styling.formPaddingUnit || 'px';
		const borderRadiusUnit = styling.formBorderRadiusUnit || 'px';

		const spacingStyles = [
			{
				value: styling.formPaddingTop,
				unit: paddingUnit,
				cssVar: '--srfm-form-padding-top',
			},
			{
				value: styling.formPaddingRight,
				unit: paddingUnit,
				cssVar: '--srfm-form-padding-right',
			},
			{
				value: styling.formPaddingBottom,
				unit: paddingUnit,
				cssVar: '--srfm-form-padding-bottom',
			},
			{
				value: styling.formPaddingLeft,
				unit: paddingUnit,
				cssVar: '--srfm-form-padding-left',
			},
			{
				value: styling.formBorderRadiusTop,
				unit: borderRadiusUnit,
				cssVar: '--srfm-form-border-radius-top',
			},
			{
				value: styling.formBorderRadiusRight,
				unit: borderRadiusUnit,
				cssVar: '--srfm-form-border-radius-right',
			},
			{
				value: styling.formBorderRadiusBottom,
				unit: borderRadiusUnit,
				cssVar: '--srfm-form-border-radius-bottom',
			},
			{
				value: styling.formBorderRadiusLeft,
				unit: borderRadiusUnit,
				cssVar: '--srfm-form-border-radius-left',
			},
		];
		spacingStyles.forEach( ( { value, unit, cssVar } ) => {
			if ( value !== undefined ) {
				container.style.setProperty( cssVar, value + unit );
			}
		} );

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
				// Validate URL protocol and strip double-quotes to prevent CSS injection.
				const bgImageUrl = styling.bgImage.replace( /"/g, '' );
				if (
					bgImageUrl.startsWith( 'https://' ) ||
					bgImageUrl.startsWith( 'http://' )
				) {
					container.style.setProperty(
						'--srfm-bg-image',
						'url("' + bgImageUrl + '")'
					);
				}
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
					if ( key.startsWith( '--' ) ) {
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
