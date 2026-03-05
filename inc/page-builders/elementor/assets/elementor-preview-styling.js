/**
 * Elementor Styling Preview
 *
 * Handles live preview of styling changes in the Elementor editor.
 * Listens for control value changes and applies CSS variables directly to the DOM.
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
	resetColorMap,
	applyDimensions,
	resetDimensions,
	buildGradientCSS,
	initGradientFromSettings,
} from '@Utils/styling-utils';

( function () {
	'use strict';

	/**
	 * Background-related controls that use dataset storage.
	 */
	const BACKGROUND_CONTROLS = [ 'bgType', 'bgColor', 'bgImage' ];

	/**
	 * Gradient group control field names.
	 * These come from Elementor's Group_Control_Background with name 'bgGradient'.
	 * Position control is hidden - radial gradients use 'center center' position.
	 */
	const GRADIENT_CONTROLS = [
		'bgGradient_color',
		'bgGradient_color_b',
		'bgGradient_gradient_type',
		'bgGradient_gradient_angle',
		'bgGradient_color_stop',
		'bgGradient_color_b_stop',
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
		...GRADIENT_CONTROLS,
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
	 * Apply background based on type.
	 *
	 * @param {HTMLElement} container The form container element.
	 */
	function applyBackground( container ) {
		const bgType = container.dataset.bgType || 'color';
		const bgColor = container.dataset.bgColor || '#FFFFFF';
		const bgImage = container.dataset.bgImage || '';

		// Reset background.
		container.style.removeProperty( 'background' );
		container.style.removeProperty( 'background-color' );
		container.style.removeProperty( 'background-image' );

		if ( bgType === 'color' ) {
			container.style.backgroundColor = bgColor;
		} else if ( bgType === 'gradient' ) {
			const gradient = buildGradientCSS( container, 'bgGradient' );
			if ( gradient ) {
				container.style.background = gradient;
			}
		} else if ( bgType === 'image' && bgImage ) {
			// Validate URL protocol to prevent CSS injection.
			if (
				bgImage.startsWith( 'https://' ) ||
				bgImage.startsWith( 'http://' )
			) {
				container.style.backgroundImage = `url(${ bgImage })`;
			}
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
						if ( Object.hasOwn( finalSize, key ) && key.startsWith( '--' ) ) {
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

			// Gradient group control fields - store in dataset and rebuild gradient.
			case 'bgGradient_color':
				container.dataset.bgGradientColor = value;
				applyBackground( container );
				break;

			case 'bgGradient_color_b':
				container.dataset.bgGradientColorB = value;
				applyBackground( container );
				break;

			case 'bgGradient_gradient_type':
				container.dataset.bgGradientType = value;
				applyBackground( container );
				break;

			case 'bgGradient_gradient_angle': {
				// Elementor slider returns object with size and unit.
				const angleSize =
					typeof value === 'object' ? value.size : value;
				const angleUnit =
					typeof value === 'object' ? value.unit : 'deg';
				container.dataset.bgGradientAngle = angleSize;
				container.dataset.bgGradientAngleUnit = angleUnit;
				applyBackground( container );
				break;
			}

			case 'bgGradient_color_stop': {
				// Elementor slider returns object with size and unit.
				const stopSize = typeof value === 'object' ? value.size : value;
				const stopUnit = typeof value === 'object' ? value.unit : '%';
				container.dataset.bgGradientColorStop = stopSize;
				container.dataset.bgGradientColorStopUnit = stopUnit;
				applyBackground( container );
				break;
			}

			case 'bgGradient_color_b_stop': {
				// Elementor slider returns object with size and unit.
				const stopBSize =
					typeof value === 'object' ? value.size : value;
				const stopBUnit = typeof value === 'object' ? value.unit : '%';
				container.dataset.bgGradientColorBStop = stopBSize;
				container.dataset.bgGradientColorBStopUnit = stopBUnit;
				applyBackground( container );
				break;
			}

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

			// Gradient group control fields.
			case 'bgGradient_color':
				delete container.dataset.bgGradientColor;
				container.style.removeProperty( 'background' );
				break;

			case 'bgGradient_color_b':
				delete container.dataset.bgGradientColorB;
				container.style.removeProperty( 'background' );
				break;

			case 'bgGradient_gradient_type':
				delete container.dataset.bgGradientType;
				container.style.removeProperty( 'background' );
				break;

			case 'bgGradient_gradient_angle':
				delete container.dataset.bgGradientAngle;
				delete container.dataset.bgGradientAngleUnit;
				container.style.removeProperty( 'background' );
				break;

			case 'bgGradient_color_stop':
				delete container.dataset.bgGradientColorStop;
				delete container.dataset.bgGradientColorStopUnit;
				container.style.removeProperty( 'background' );
				break;

			case 'bgGradient_color_b_stop':
				delete container.dataset.bgGradientColorBStop;
				delete container.dataset.bgGradientColorBStopUnit;
				container.style.removeProperty( 'background' );
				break;

			case 'bgImage':
				delete container.dataset.bgImage;
				container.style.removeProperty( 'background-image' );
				break;
		}
	}

	/**
	 * Reset all styles when formTheme is set to inherit.
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
	 * @param {string}      name           The control name.
	 * @param {string}      value          The control value.
	 * @param {HTMLElement} container      The form container element.
	 * @param {Object}      widgetSettings The Elementor widget settings object (optional).
	 */
	function dispatchUpdateEvent(
		name,
		value,
		container,
		widgetSettings = null
	) {
		document.dispatchEvent(
			new CustomEvent( 'srfm-elementor-styling-update', {
				detail: { name, value, container, widgetSettings },
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
	 * @param {Object}      widgetSettings  The Elementor widget settings object (optional).
	 */
	function applyStyleUpdate(
		container,
		name,
		value,
		originalClasses,
		widgetSettings = null
	) {
		// Handle formTheme change - reset all styles when set to inherit.
		if ( name === 'formTheme' ) {
			if ( value === 'inherit' ) {
				resetAllStyles( container, originalClasses );
			}
			dispatchUpdateEvent( name, value, container, widgetSettings );
			return;
		}

		// Handle empty/default values - reset to original.
		if ( value === '' || value === 'default' ) {
			resetControl( container, name );
			dispatchUpdateEvent( name, value, container, widgetSettings );
			return;
		}

		// Apply the style update.
		applyStyle( container, name, value );

		// Dispatch event for Pro to extend.
		dispatchUpdateEvent( name, value, container, widgetSettings );
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

					// Initialize dataset from current widget settings.
					// This ensures values like bgType are available before any change events.
					const widgetContainer = elementor.getContainer
						? elementor.getContainer( widgetId )
						: null;

					if ( widgetContainer && widgetContainer.settings ) {
						const settings = widgetContainer.settings;

						// Initialize bgType from current settings.
						const bgType = settings.get( 'bgType' );
						if ( bgType ) {
							container.dataset.bgType = bgType;
						}

						// Initialize gradient values if bgType is gradient.
						if ( bgType === 'gradient' ) {
							initGradientFromSettings(
								container,
								settings,
								'bgGradient',
								'bgGradient_'
							);
						}

						// Dispatch init event for Pro plugin to initialize its controls.
						document.dispatchEvent(
							new CustomEvent( 'srfm-elementor-styling-init', {
								detail: { container, widgetSettings: settings },
							} )
						);
					}

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

							// Initialize gradient values when any gradient control changes.
							// This ensures colors are in dataset when location/angle sliders change.
							if ( name.startsWith( 'bgGradient_' ) ) {
								initGradientFromSettings(
									container,
									controlView.container.settings,
									'bgGradient',
									'bgGradient_'
								);
							}

							applyStyleUpdate(
								container,
								name,
								value,
								originalClasses,
								controlView.container.settings
							);
						}
					);

					// Listen for global color changes.
					// When a global color is selected, Elementor updates the globals model
					// instead of firing the regular change event.
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
										originalClasses,
										widgetContainer.settings
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
