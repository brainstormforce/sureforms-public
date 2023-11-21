export const getBorderAttributes = ( prefix, defaultArgs = {} ) => {
	const defaults = {
		// Width
		borderTopWidth: '',
		borderRightWidth: '',
		borderBottomWidth: '',
		borderLeftWidth: '',
		borderTopWidthTablet: '',
		borderRightWidthTablet: '',
		borderBottomWidthTablet: '',
		borderLeftWidthTablet: '',
		borderTopWidthMobile: '',
		borderRightWidthMobile: '',
		borderBottomWidthMobile: '',
		borderLeftWidthMobile: '',
		// Radius
		borderTopLeftRadius: '',
		borderTopRightRadius: '',
		borderBottomRightRadius: '',
		borderBottomLeftRadius: '',
		borderTopLeftRadiusTablet: '',
		borderTopRightRadiusTablet: '',
		borderBottomRightRadiusTablet: '',
		borderBottomLeftRadiusTablet: '',
		borderTopLeftRadiusMobile: '',
		borderTopRightRadiusMobile: '',
		borderBottomRightRadiusMobile: '',
		borderBottomLeftRadiusMobile: '',
		// unit
		borderRadiusUnit: 'px',
		borderRadiusUnitTablet: 'px',
		borderRadiusUnitMobile: 'px',
		// common
		borderStyle: '',
		borderColor: '',
		borderHColor: '',
		...defaultArgs,
	};
	const attributes = {};
	const devices = [
		{ devicePrefix: '', copyPastePrefix: '' },
		{ devicePrefix: 'Tablet', copyPastePrefix: '-tablet' },
		{ devicePrefix: 'Mobile', copyPastePrefix: '-mobile' },
	];

	devices.forEach( ( item ) => {
		// border width
		attributes[ prefix + 'BorderTopWidth' + item.devicePrefix ] = {
			type: 'number',
			default: defaults[ `borderTopWidth${ item.devicePrefix }` ],
		};
		attributes[ prefix + 'BorderLeftWidth' + item.devicePrefix ] = {
			type: 'number',
			default: defaults[ `borderLeftWidth${ item.devicePrefix }` ],
		};
		attributes[ prefix + 'BorderRightWidth' + item.devicePrefix ] = {
			type: 'number',
			default: defaults[ `borderRightWidth${ item.devicePrefix }` ],
		};
		attributes[ prefix + 'BorderBottomWidth' + item.devicePrefix ] = {
			type: 'number',
			default: defaults[ `borderBottomWidth${ item.devicePrefix }` ],
		};

		// border radius
		attributes[ prefix + 'BorderTopLeftRadius' + item.devicePrefix ] = {
			type: 'number',
			default: defaults[ `borderTopLeftRadius${ item.devicePrefix }` ],
		};
		attributes[ prefix + 'BorderTopRightRadius' + item.devicePrefix ] = {
			type: 'number',
			default: defaults[ `borderTopRightRadius${ item.devicePrefix }` ],
		};
		attributes[ prefix + 'BorderBottomLeftRadius' + item.devicePrefix ] = {
			type: 'number',
			default: defaults[ `borderBottomLeftRadius${ item.devicePrefix }` ],
		};
		attributes[ prefix + 'BorderBottomRightRadius' + item.devicePrefix ] = {
			type: 'number',
			default:
				defaults[ `borderBottomRightRadius${ item.devicePrefix }` ],
		};

		// radius unit
		attributes[ prefix + 'BorderRadiusUnit' + item.devicePrefix ] = {
			type: 'string',
			default: defaults[ `borderRadiusUnit${ item.devicePrefix }` ],
		};
	} );

	attributes[ prefix + 'BorderLink' ] = {
		type: 'boolean',
		default: true,
	};

	attributes[ prefix + 'BorderRadiusLink' ] = {
		type: 'boolean',
		default: true,
	};

	attributes[ prefix + 'BorderStyle' ] = {
		type: 'string',
		default: defaults.borderStyle,
	};

	attributes[ prefix + 'BorderColor' ] = {
		type: 'string',
		default: defaults.borderColor,
	};

	attributes[ prefix + 'BorderHColor' ] = {
		type: 'string',
		default: defaults.borderHColor,
	};

	return attributes;
};

export const migrateBorderAttributes = (
	prefix,
	borderWidth,
	borderRadius,
	color = {},
	hoverColor = {},
	borderStyle = {},
	setAttributes,
	attributes = {}
) => {
	if ( ! isNaN( borderWidth.value ) ) {
		if ( '' === attributes[ prefix + 'BorderTopWidth' ] ) {
			setAttributes( {
				[ prefix + 'BorderTopWidth' ]: borderWidth.value,
			} );
		}
		if ( '' === attributes[ prefix + 'BorderLeftWidth' ] ) {
			setAttributes( {
				[ prefix + 'BorderLeftWidth' ]: borderWidth.value,
			} );
		}
		if ( '' === attributes[ prefix + 'BorderRightWidth' ] ) {
			setAttributes( {
				[ prefix + 'BorderRightWidth' ]: borderWidth.value,
			} );
		}
		if ( '' === attributes[ prefix + 'BorderBottomWidth' ] ) {
			setAttributes( {
				[ prefix + 'BorderBottomWidth' ]: borderWidth.value,
			} );
		}
		// reset
		attributes[ borderWidth.label ] = '';
	}

	if ( ! isNaN( borderRadius.value ) ) {
		if ( '' === attributes[ prefix + 'BorderTopLeftRadius' ] ) {
			setAttributes( {
				[ prefix + 'BorderTopLeftRadius' ]: borderRadius.value,
			} );
		}
		if ( '' === attributes[ prefix + 'BorderTopRightRadius' ] ) {
			setAttributes( {
				[ prefix + 'BorderTopRightRadius' ]: borderRadius.value,
			} );
		}
		if ( '' === attributes[ prefix + 'BorderBottomLeftRadius' ] ) {
			setAttributes( {
				[ prefix + 'BorderBottomLeftRadius' ]: borderRadius.value,
			} );
		}
		if ( '' === attributes[ prefix + 'BorderBottomRightRadius' ] ) {
			setAttributes( {
				[ prefix + 'BorderBottomRightRadius' ]: borderRadius.value,
			} );
		}
		// reset
		attributes[ borderRadius.label ] = '';
	}

	if ( color.value ) {
		if ( '' === attributes[ prefix + 'BorderColor' ] ) {
			setAttributes( { [ prefix + 'BorderColor' ]: color.value } );
		}
		// reset
		attributes[ color.label ] = '';
	}

	if ( hoverColor.value ) {
		if ( '' === attributes[ prefix + 'BorderHColor' ] ) {
			setAttributes( { [ prefix + 'BorderHColor' ]: hoverColor.value } );
		}
		// reset
		attributes[ hoverColor.label ] = '';
	}

	if ( borderStyle.value ) {
		if ( '' === attributes[ prefix + 'BorderStyle' ] ) {
			setAttributes( { [ prefix + 'BorderStyle' ]: borderStyle.value } );
		}
		// reset
		attributes[ borderStyle.label ] = '';
	}
	return attributes;
};
