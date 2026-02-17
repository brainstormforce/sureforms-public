/**
 * Elementor Styling Preview
 *
 * Handles live preview of styling changes in the Elementor editor.
 * Listens for control value changes and applies CSS variables directly to the DOM.
 *
 * @package
 * @since x.x.x
 */

( function () {
	'use strict';

	/**
	 * Simple CSS variable mappings: controlName → cssVariable
	 * For controls that just set a single CSS variable.
	 */
	const SIMPLE_CSS_MAP = {
		textOnPrimaryColor: '--srfm-color-scheme-text-on-primary',
		bgImageSize: '--srfm-bg-size',
		bgImagePosition: '--srfm-bg-position',
		bgImageRepeat: '--srfm-bg-repeat',
		bgImageAttachment: '--srfm-bg-attachment',
	};

	/**
	 * DIMENSIONS control mappings: controlName → cssVarPrefix
	 * These controls have top/right/bottom/left values.
	 */
	const DIMENSIONS_CSS_MAP = {
		formPadding: '--srfm-form-padding',
		formBorderRadius: '--srfm-form-border-radius',
	};

	/**
	 * Dimension sides for padding/border-radius controls.
	 */
	const SIDES = [ 'top', 'right', 'bottom', 'left' ];

	/**
	 * CSS variables set by primaryColor control.
	 * Maps cssVariable → opacity (null = use raw value, number = HSL alpha).
	 */
	const PRIMARY_COLOR_MAP = {
		'--srfm-color-scheme-primary': null,
		'--srfm-quill-editor-color': null,
		'--srfm-color-input-border-hover': 0.65,
		'--srfm-color-input-border-focus-glow': 0.15,
		'--srfm-color-input-selected': 0.1,
		'--srfm-btn-color-hover': 0.9,
		'--srfm-btn-color-disabled': 0.25,
	};

	/**
	 * CSS variables set by textColor control.
	 * Maps cssVariable → opacity (null = use raw value, number = HSL alpha).
	 */
	const TEXT_COLOR_MAP = {
		'--srfm-color-scheme-text': null,
		'--srfm-color-input-label': null,
		'--srfm-color-input-text': null,
		'--srfm-color-input-description': 0.65,
		'--srfm-color-input-placeholder': 0.5,
		'--srfm-color-input-prefix': 0.65,
		'--srfm-color-input-background': 0.02,
		'--srfm-color-input-background-hover': 0.05,
		'--srfm-color-input-background-disabled': 0.07,
		'--srfm-color-input-border': 0.25,
		'--srfm-color-input-border-disabled': 0.15,
		'--srfm-color-multi-choice-svg': 0.7,
	};

	/**
	 * Background-related controls that use dataset storage.
	 */
	const BACKGROUND_CONTROLS = [
		'bgType',
		'bgColor',
		'bgGradient',
		'bgImage',
	];

	/**
	 * All free plugin control names for reset functionality.
	 */
	const ALL_FREE_CONTROLS = [
		'primaryColor',
		'textColor',
		'fieldSpacing',
		'buttonAlignment',
		...Object.keys( SIMPLE_CSS_MAP ),
		...Object.keys( DIMENSIONS_CSS_MAP ),
		...BACKGROUND_CONTROLS,
	];

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

	/**
	 * Apply DIMENSIONS control value (top/right/bottom/left).
	 *
	 * @param {HTMLElement} container The form container element.
	 * @param {string}      prefix    The CSS variable prefix.
	 * @param {Object}      val       The dimensions value object.
	 */
	function applyDimensions( container, prefix, val ) {
		if ( typeof val !== 'object' ) {
			return;
		}
		const unit = val.unit || 'px';

		SIDES.forEach( function ( side ) {
			if ( val[ side ] !== '' && val[ side ] !== undefined ) {
				container.style.setProperty(
					prefix + '-' + side,
					val[ side ] + unit
				);
			}
		} );
	}

	/**
	 * Reset DIMENSIONS control (remove all sides).
	 *
	 * @param {HTMLElement} container The form container element.
	 * @param {string}      prefix    The CSS variable prefix.
	 */
	function resetDimensions( container, prefix ) {
		SIDES.forEach( function ( side ) {
			container.style.removeProperty( prefix + '-' + side );
		} );
	}

	/**
	 * Apply color with HSL transformations based on a color map.
	 *
	 * @param {HTMLElement} container The form container element.
	 * @param {Object}      colorMap  Map of cssVariable → opacity (null = raw, number = HSL alpha).
	 * @param {string}      val       The color value.
	 */
	function applyColorMap( container, colorMap, val ) {
		for ( const cssVar in colorMap ) {
			if ( ! Object.hasOwn( colorMap, cssVar ) ) {
				continue;
			}
			const opacity = colorMap[ cssVar ];
			if ( opacity === null ) {
				container.style.setProperty( cssVar, val );
			} else {
				container.style.setProperty(
					cssVar,
					'hsl(from ' + val + ' h s l / ' + opacity + ')'
				);
			}
		}
	}

	/**
	 * Reset color map CSS variables.
	 *
	 * @param {HTMLElement} container The form container element.
	 * @param {Object}      colorMap  Map of cssVariable → opacity.
	 */
	function resetColorMap( container, colorMap ) {
		for ( const cssVar in colorMap ) {
			if ( Object.hasOwn( colorMap, cssVar ) ) {
				container.style.removeProperty( cssVar );
			}
		}
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
	 * Apply a style update for a control.
	 *
	 * @param {HTMLElement} container The form container element.
	 * @param {string}      name      The control name.
	 * @param {*}           value     The control value.
	 */
	function applyStyle( container, name, value ) {
		// Handle simple CSS variable mappings.
		if ( SIMPLE_CSS_MAP[ name ] ) {
			container.style.setProperty( SIMPLE_CSS_MAP[ name ], value );
			return;
		}

		// Handle DIMENSIONS controls.
		if ( DIMENSIONS_CSS_MAP[ name ] ) {
			applyDimensions( container, DIMENSIONS_CSS_MAP[ name ], value );
			return;
		}

		// Handle special controls.
		switch ( name ) {
			case 'primaryColor':
				applyColorMap( container, PRIMARY_COLOR_MAP, value );
				break;

			case 'textColor':
				applyColorMap( container, TEXT_COLOR_MAP, value );
				break;

			case 'fieldSpacing': {
				const fieldSpacingVars =
					window.srfmElementorStyling?.fieldSpacingVars;
				if ( fieldSpacingVars ) {
					// Merge base (small) with size-specific overrides.
					const baseSize = fieldSpacingVars.small || {};
					const overrideSize = fieldSpacingVars[ value ] || {};
					const finalSize = Object.assign(
						{},
						baseSize,
						overrideSize
					);

					for ( const key in finalSize ) {
						if ( Object.hasOwn( finalSize, key ) ) {
							container.style.setProperty(
								key,
								finalSize[ key ]
							);
						}
					}
				}
				break;
			}

			case 'buttonAlignment': {
				const submitContainer = container.querySelector(
					'.srfm-submit-container .wp-block-button'
				);
				if ( submitContainer ) {
					submitContainer.style.textAlign =
						value === 'justify' ? 'center' : value;
					const btn = submitContainer.querySelector( 'button' );
					if ( btn ) {
						btn.style.width = value === 'justify' ? '100%' : '';
					}
				}
				break;
			}

			// Background controls - store in dataset and apply.
			case 'bgType':
				container.dataset.bgType = value;
				applyBackground( container );
				break;

			case 'bgColor':
				container.dataset.bgColor = value;
				applyBackground( container );
				break;

			case 'bgGradient':
				container.dataset.bgGradient = value;
				applyBackground( container );
				break;

			case 'bgImage': {
				// val is an object with url property from Elementor.
				const url = typeof value === 'object' ? value.url : value;
				container.dataset.bgImage = url || '';
				applyBackground( container );
				break;
			}
		}
	}

	/**
	 * Reset a control to its original state.
	 *
	 * @param {HTMLElement} container The form container element.
	 * @param {string}      name      The control name.
	 */
	function resetControl( container, name ) {
		// Handle simple CSS variable mappings.
		if ( SIMPLE_CSS_MAP[ name ] ) {
			container.style.removeProperty( SIMPLE_CSS_MAP[ name ] );
			return;
		}

		// Handle DIMENSIONS controls.
		if ( DIMENSIONS_CSS_MAP[ name ] ) {
			resetDimensions( container, DIMENSIONS_CSS_MAP[ name ] );
			return;
		}

		// Handle special controls.
		switch ( name ) {
			case 'primaryColor':
				resetColorMap( container, PRIMARY_COLOR_MAP );
				break;

			case 'textColor':
				resetColorMap( container, TEXT_COLOR_MAP );
				break;

			case 'fieldSpacing': {
				// Reset field spacing by removing all related CSS variables.
				const fieldSpacingVars =
					window.srfmElementorStyling?.fieldSpacingVars;
				if ( fieldSpacingVars && fieldSpacingVars.small ) {
					for ( const key in fieldSpacingVars.small ) {
						if ( Object.hasOwn( fieldSpacingVars.small, key ) ) {
							container.style.removeProperty( key );
						}
					}
				}
				break;
			}

			case 'buttonAlignment': {
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
				break;
			}

			// Background controls.
			case 'bgType':
				delete container.dataset.bgType;
				container.style.removeProperty( 'background' );
				container.style.removeProperty( 'background-color' );
				container.style.removeProperty( 'background-image' );
				break;

			case 'bgColor':
				delete container.dataset.bgColor;
				container.style.removeProperty( 'background-color' );
				break;

			case 'bgGradient':
				delete container.dataset.bgGradient;
				container.style.removeProperty( 'background' );
				break;

			case 'bgImage':
				delete container.dataset.bgImage;
				container.style.removeProperty( 'background-image' );
				break;
		}
	}

	/**
	 * Reset all styles when inheritStyling is enabled.
	 *
	 * @param {HTMLElement} container       The form container element.
	 * @param {string}      originalClasses Original container classes.
	 */
	function resetAllStyles( container, originalClasses ) {
		ALL_FREE_CONTROLS.forEach( function ( controlName ) {
			resetControl( container, controlName );
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

	/**
	 * Apply style update based on control name and value.
	 *
	 * @param {HTMLElement} container       The form container element.
	 * @param {string}      name            The control name.
	 * @param {string}      value           The control value.
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
			resetControl( container, name );
			dispatchUpdateEvent( name, value, container );
			return;
		}

		// Apply the style update.
		applyStyle( container, name, value );

		// Dispatch event for Pro to extend.
		dispatchUpdateEvent( name, value, container );
	}

	// Main initialization.
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
									if ( ! Object.hasOwn( changed, name ) ) {
										continue;
									}
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
}() );
