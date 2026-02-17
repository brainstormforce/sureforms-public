/**
 * Elementor Styling Preview
 *
 * Handles live preview of styling changes in the Elementor editor.
 * Listens for control value changes and applies CSS variables directly to the DOM.
 *
 * @package sureforms
 * @since x.x.x
 */

( function () {
	'use strict';

	/**
	 * Resolve a global color key to its actual color value.
	 *
	 * When a user selects a global color in Elementor, the value is stored as
	 * a reference like 'globals/colors?id=primary'. This function resolves
	 * that reference to the actual color value.
	 *
	 * @param {string} globalKey The global color key (e.g., 'globals/colors?id=primary').
	 * @return {string|null} The resolved color value or null if not found.
	 */
	function resolveGlobalColor( globalKey ) {
		if ( ! globalKey || typeof globalKey !== 'string' ) {
			return null;
		}

		// Parse the global key to extract the color ID.
		// Format: 'globals/colors?id=primary' -> id: 'primary'
		const match = globalKey.match( /id=([^&]+)/ );
		if ( ! match ) {
			return null;
		}

		const colorId = match[ 1 ];

		try {
			// Method 1: Get from CSS variable (most reliable).
			// Elementor outputs global colors as CSS variables: --e-global-color-{id}
			const cssVarName = '--e-global-color-' + colorId;
			const cssValue = getComputedStyle( document.documentElement )
				.getPropertyValue( cssVarName )
				.trim();
			if ( cssValue ) {
				return cssValue;
			}

			// Method 2: Try to get from $e.data cache (fallback).
			if (
				typeof $e !== 'undefined' &&
				$e.data &&
				$e.components &&
				$e.components.get
			) {
				const globalsComponent = $e.components.get( 'globals' );
				if ( globalsComponent ) {
					const data = $e.data.getCache(
						globalsComponent,
						'globals/colors',
						{ id: colorId }
					);
					if ( data && data.value ) {
						return data.value;
					}
				}
			}
		} catch ( e ) {
			// Silently fail and return null.
		}

		return null;
	}

	window.addEventListener( 'elementor/frontend/init', function () {
		if (
			typeof elementorFrontend === 'undefined' ||
			! elementorFrontend.hooks
		) {
			return;
		}

		elementorFrontend.hooks.addAction(
			'frontend/element_ready/sureforms_form.default',
			function ( $scope ) {
				const container = $scope
					.find( '.srfm-form-container' )
					.get( 0 );
				if ( ! container ) {
					return;
				}

				// Store original classes for reset functionality.
				const originalClasses = container.className;

				// Listen for control changes in editor.
				if (
					typeof elementor !== 'undefined' &&
					elementor.channels &&
					elementor.channels.editor
				) {
					const widgetId = $scope.data( 'id' );

					// Regular control change listener.
					elementor.channels.editor.on(
						'change',
						function ( controlView ) {
							if (
								! controlView.container ||
								! controlView.container.id
							) {
								return;
							}

							if ( controlView.container.id !== widgetId ) {
								return;
							}

							const name = controlView.model.get( 'name' );
							let value =
								controlView.container.settings.get( name );

							// Check for global color value and resolve it.
							const globalKey =
								controlView.container.globals.get( name );
							if ( globalKey ) {
								value = resolveGlobalColor( globalKey );
							}

							applyStyleUpdate(
								container,
								name,
								value,
								originalClasses
							);
						}
					);

					// Listen for global color changes.
					// When a global color is selected, Elementor updates the globals model
					// instead of firing the regular change event.
					const widgetContainer = elementor.getContainer
						? elementor.getContainer( widgetId )
						: null;

					if ( widgetContainer && widgetContainer.globals ) {
						widgetContainer.globals.on(
							'change',
							function ( model ) {
								// Get all changed attributes.
								const changed = model.changed;
								for ( const name in changed ) {
									const globalKey = changed[ name ];
									let value = null;

									if ( globalKey ) {
										// Global color selected - resolve it.
										value = resolveGlobalColor( globalKey );
									} else {
										// Global color cleared - get direct value.
										value =
											widgetContainer.settings.get(
												name
											);
									}

									applyStyleUpdate(
										container,
										name,
										value,
										originalClasses
									);
								}
							}
						);
					}
				}
			}
		);
	} );

	/**
	 * Apply style update based on control name and value.
	 *
	 * @param {HTMLElement} container      The form container element.
	 * @param {string}      name           The control name.
	 * @param {string}      value          The control value.
	 * @param {string}      originalClasses Original container classes for reset.
	 */
	function applyStyleUpdate( container, name, value, originalClasses ) {
		// Handle inheritStyling toggle - reset all styles when enabled.
		if ( name === 'inheritStyling' ) {
			if ( value === 'yes' ) {
				resetAllStyles( container, originalClasses );
			}
			dispatchUpdateEvent( name, value, container );
			return;
		}

		// Handle empty/default values - reset to original.
		if ( value === '' || value === 'default' ) {
			resetControl( container, name, originalClasses );
			dispatchUpdateEvent( name, value, container );
			return;
		}

		const styleMap = {
			primaryColor: function ( val ) {
				container.style.setProperty(
					'--srfm-color-scheme-primary',
					val
				);
				container.style.setProperty( '--srfm-quill-editor-color', val );
				container.style.setProperty(
					'--srfm-color-input-border-hover',
					'hsl(from ' + val + ' h s l / 0.65)'
				);
				container.style.setProperty(
					'--srfm-color-input-border-focus-glow',
					'hsl(from ' + val + ' h s l / 0.15)'
				);
				container.style.setProperty(
					'--srfm-color-input-selected',
					'hsl(from ' + val + ' h s l / 0.1)'
				);
				container.style.setProperty(
					'--srfm-btn-color-hover',
					'hsl(from ' + val + ' h s l / 0.9)'
				);
				container.style.setProperty(
					'--srfm-btn-color-disabled',
					'hsl(from ' + val + ' h s l / 0.25)'
				);
			},
			textColor: function ( val ) {
				container.style.setProperty( '--srfm-color-scheme-text', val );
				container.style.setProperty( '--srfm-color-input-label', val );
				container.style.setProperty( '--srfm-color-input-text', val );
				container.style.setProperty(
					'--srfm-color-input-description',
					'hsl(from ' + val + ' h s l / 0.65)'
				);
				container.style.setProperty(
					'--srfm-color-input-placeholder',
					'hsl(from ' + val + ' h s l / 0.5)'
				);
				container.style.setProperty(
					'--srfm-color-input-prefix',
					'hsl(from ' + val + ' h s l / 0.65)'
				);
				container.style.setProperty(
					'--srfm-color-input-background',
					'hsl(from ' + val + ' h s l / 0.02)'
				);
				container.style.setProperty(
					'--srfm-color-input-background-hover',
					'hsl(from ' + val + ' h s l / 0.05)'
				);
				container.style.setProperty(
					'--srfm-color-input-background-disabled',
					'hsl(from ' + val + ' h s l / 0.07)'
				);
				container.style.setProperty(
					'--srfm-color-input-border',
					'hsl(from ' + val + ' h s l / 0.25)'
				);
				container.style.setProperty(
					'--srfm-color-input-border-disabled',
					'hsl(from ' + val + ' h s l / 0.15)'
				);
				container.style.setProperty(
					'--srfm-color-multi-choice-svg',
					'hsl(from ' + val + ' h s l / 0.7)'
				);
			},
			textOnPrimaryColor: function ( val ) {
				container.style.setProperty(
					'--srfm-color-scheme-text-on-primary',
					val
				);
			},
			fieldSpacing: function ( val ) {
				const fieldSpacingVars =
					window.srfmElementorStyling?.fieldSpacingVars;
				if ( fieldSpacingVars ) {
					// Merge base (small) with size-specific overrides.
					const baseSize = fieldSpacingVars.small || {};
					const overrideSize = fieldSpacingVars[ val ] || {};
					const finalSize = Object.assign(
						{},
						baseSize,
						overrideSize
					);

					for ( const key in finalSize ) {
						container.style.setProperty( key, finalSize[ key ] );
					}
				}
			},
			buttonAlignment: function ( val ) {
				const submitContainer = container.querySelector(
					'.srfm-submit-container .wp-block-button'
				);
				if ( submitContainer ) {
					submitContainer.style.textAlign =
						val === 'justify' ? 'center' : val;
					const btn = submitContainer.querySelector( 'button' );
					if ( btn ) {
						btn.style.width = val === 'justify' ? '100%' : '';
					}
				}
			},
			// Form Padding (DIMENSIONS control).
			formPadding: function ( val ) {
				if ( typeof val !== 'object' ) {
					return;
				}
				const unit = val.unit || 'px';
				if ( val.top !== '' && val.top !== undefined ) {
					container.style.setProperty(
						'--srfm-form-padding-top',
						val.top + unit
					);
				}
				if ( val.right !== '' && val.right !== undefined ) {
					container.style.setProperty(
						'--srfm-form-padding-right',
						val.right + unit
					);
				}
				if ( val.bottom !== '' && val.bottom !== undefined ) {
					container.style.setProperty(
						'--srfm-form-padding-bottom',
						val.bottom + unit
					);
				}
				if ( val.left !== '' && val.left !== undefined ) {
					container.style.setProperty(
						'--srfm-form-padding-left',
						val.left + unit
					);
				}
			},
			// Form Border Radius (DIMENSIONS control).
			formBorderRadius: function ( val ) {
				if ( typeof val !== 'object' ) {
					return;
				}
				const unit = val.unit || 'px';
				if ( val.top !== '' && val.top !== undefined ) {
					container.style.setProperty(
						'--srfm-form-border-radius-top',
						val.top + unit
					);
				}
				if ( val.right !== '' && val.right !== undefined ) {
					container.style.setProperty(
						'--srfm-form-border-radius-right',
						val.right + unit
					);
				}
				if ( val.bottom !== '' && val.bottom !== undefined ) {
					container.style.setProperty(
						'--srfm-form-border-radius-bottom',
						val.bottom + unit
					);
				}
				if ( val.left !== '' && val.left !== undefined ) {
					container.style.setProperty(
						'--srfm-form-border-radius-left',
						val.left + unit
					);
				}
			},
			// Background.
			bgType: function ( val ) {
				// Store the type for use by other background handlers.
				container.dataset.bgType = val;
				applyBackground( container );
			},
			bgColor: function ( val ) {
				container.dataset.bgColor = val;
				applyBackground( container );
			},
			bgGradient: function ( val ) {
				container.dataset.bgGradient = val;
				applyBackground( container );
			},
			bgImage: function ( val ) {
				// val is an object with url property from Elementor.
				const url = typeof val === 'object' ? val.url : val;
				container.dataset.bgImage = url || '';
				applyBackground( container );
			},
			bgImageSize: function ( val ) {
				container.style.setProperty( '--srfm-bg-size', val );
			},
			bgImagePosition: function ( val ) {
				container.style.setProperty( '--srfm-bg-position', val );
			},
			bgImageRepeat: function ( val ) {
				container.style.setProperty( '--srfm-bg-repeat', val );
			},
			bgImageAttachment: function ( val ) {
				container.style.setProperty( '--srfm-bg-attachment', val );
			},
		};

		if ( styleMap[ name ] ) {
			styleMap[ name ]( value );
		}

		// Dispatch event for Pro to extend.
		dispatchUpdateEvent( name, value, container );
	}

	/**
	 * Apply background based on type.
	 *
	 * @param {HTMLElement} container The form container element.
	 */
	function applyBackground( container ) {
		const bgType = container.dataset.bgType || 'color';
		const bgColor = container.dataset.bgColor || '#FFFFFF';
		const bgGradient =
			container.dataset.bgGradient ||
			'linear-gradient(90deg, #FFC9B2 0%, #C7CBFF 100%)';
		const bgImage = container.dataset.bgImage || '';

		// Reset background.
		container.style.removeProperty( 'background' );
		container.style.removeProperty( 'background-color' );
		container.style.removeProperty( 'background-image' );

		if ( bgType === 'color' ) {
			container.style.backgroundColor = bgColor;
		} else if ( bgType === 'gradient' ) {
			container.style.background = bgGradient;
		} else if ( bgType === 'image' && bgImage ) {
			container.style.backgroundImage = `url(${ bgImage })`;
		}
	}

	/**
	 * Reset a control to its original state.
	 *
	 * @param {HTMLElement} container      The form container element.
	 * @param {string}      name           The control name.
	 * @param {string}      originalClasses Original container classes.
	 */
	function resetControl( container, name, originalClasses ) {
		const resetMap = {
			primaryColor: function () {
				container.style.removeProperty( '--srfm-color-scheme-primary' );
				container.style.removeProperty( '--srfm-quill-editor-color' );
				container.style.removeProperty(
					'--srfm-color-input-border-hover'
				);
				container.style.removeProperty(
					'--srfm-color-input-border-focus-glow'
				);
				container.style.removeProperty(
					'--srfm-color-input-selected'
				);
				container.style.removeProperty( '--srfm-btn-color-hover' );
				container.style.removeProperty( '--srfm-btn-color-disabled' );
			},
			textColor: function () {
				container.style.removeProperty( '--srfm-color-scheme-text' );
				container.style.removeProperty( '--srfm-color-input-label' );
				container.style.removeProperty( '--srfm-color-input-text' );
				container.style.removeProperty(
					'--srfm-color-input-description'
				);
				container.style.removeProperty(
					'--srfm-color-input-placeholder'
				);
				container.style.removeProperty( '--srfm-color-input-prefix' );
				container.style.removeProperty(
					'--srfm-color-input-background'
				);
				container.style.removeProperty(
					'--srfm-color-input-background-hover'
				);
				container.style.removeProperty(
					'--srfm-color-input-background-disabled'
				);
				container.style.removeProperty( '--srfm-color-input-border' );
				container.style.removeProperty(
					'--srfm-color-input-border-disabled'
				);
				container.style.removeProperty(
					'--srfm-color-multi-choice-svg'
				);
			},
			textOnPrimaryColor: function () {
				container.style.removeProperty(
					'--srfm-color-scheme-text-on-primary'
				);
			},
			fieldSpacing: function () {
				// Reset field spacing by removing all related CSS variables.
				const fieldSpacingVars =
					window.srfmElementorStyling?.fieldSpacingVars;
				if ( fieldSpacingVars && fieldSpacingVars.small ) {
					for ( const key in fieldSpacingVars.small ) {
						container.style.removeProperty( key );
					}
				}
			},
			buttonAlignment: function () {
				const submitContainer = container.querySelector(
					'.srfm-submit-container .wp-block-button'
				);
				if ( submitContainer ) {
					submitContainer.style.removeProperty( 'text-align' );
					const btn = submitContainer.querySelector( 'button' );
					if ( btn ) {
						btn.style.removeProperty( 'width' );
					}
				}
			},
			// Form Padding (DIMENSIONS control).
			formPadding: function () {
				container.style.removeProperty( '--srfm-form-padding-top' );
				container.style.removeProperty( '--srfm-form-padding-right' );
				container.style.removeProperty( '--srfm-form-padding-bottom' );
				container.style.removeProperty( '--srfm-form-padding-left' );
			},
			// Form Border Radius (DIMENSIONS control).
			formBorderRadius: function () {
				container.style.removeProperty(
					'--srfm-form-border-radius-top'
				);
				container.style.removeProperty(
					'--srfm-form-border-radius-right'
				);
				container.style.removeProperty(
					'--srfm-form-border-radius-bottom'
				);
				container.style.removeProperty(
					'--srfm-form-border-radius-left'
				);
			},
			// Background.
			bgType: function () {
				delete container.dataset.bgType;
				container.style.removeProperty( 'background' );
				container.style.removeProperty( 'background-color' );
				container.style.removeProperty( 'background-image' );
			},
			bgColor: function () {
				delete container.dataset.bgColor;
				container.style.removeProperty( 'background-color' );
			},
			bgGradient: function () {
				delete container.dataset.bgGradient;
				container.style.removeProperty( 'background' );
			},
			bgImage: function () {
				delete container.dataset.bgImage;
				container.style.removeProperty( 'background-image' );
			},
			bgImageSize: function () {
				container.style.removeProperty( '--srfm-bg-size' );
			},
			bgImagePosition: function () {
				container.style.removeProperty( '--srfm-bg-position' );
			},
			bgImageRepeat: function () {
				container.style.removeProperty( '--srfm-bg-repeat' );
			},
			bgImageAttachment: function () {
				container.style.removeProperty( '--srfm-bg-attachment' );
			},
		};

		if ( resetMap[ name ] ) {
			resetMap[ name ]();
		}
	}

	/**
	 * Reset all styles when inheritStyling is enabled.
	 *
	 * @param {HTMLElement} container       The form container element.
	 * @param {string}      originalClasses Original container classes.
	 */
	function resetAllStyles( container, originalClasses ) {
		// Reset all free plugin controls.
		const controlsToReset = [
			'primaryColor',
			'textColor',
			'textOnPrimaryColor',
			'fieldSpacing',
			'buttonAlignment',
			// Form Padding (DIMENSIONS control).
			'formPadding',
			// Form Border Radius (DIMENSIONS control).
			'formBorderRadius',
			// Background.
			'bgType',
			'bgColor',
			'bgGradient',
			'bgImage',
			'bgImageSize',
			'bgImagePosition',
			'bgImageRepeat',
			'bgImageAttachment',
		];

		controlsToReset.forEach( function ( controlName ) {
			resetControl( container, controlName, originalClasses );
		} );

		// Reset container classes to original.
		container.className = originalClasses;
	}

	/**
	 * Dispatch event for Pro and other extensions to listen to.
	 *
	 * @param {string}      name      The control name.
	 * @param {string}      value     The control value.
	 * @param {HTMLElement} container The form container element.
	 */
	function dispatchUpdateEvent( name, value, container ) {
		document.dispatchEvent(
			new CustomEvent( 'srfm-elementor-styling-update', {
				detail: { name, value, container },
			} )
		);
	}
}() );
