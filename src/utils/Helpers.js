import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { Toaster, ToastBar } from 'react-hot-toast';
import { store as editorStore } from '@wordpress/editor';
import { select } from '@wordpress/data';
import { cleanForSlug } from '@wordpress/url';
import clsx from 'clsx';
import { twMerge } from 'tailwind-merge';
import { format as format_date } from 'date-fns';
import { toast } from '@bsf/force-ui';

/**
 * Get Image Sizes and return an array of Size.
 *
 * @param {Object} sizes - The sizes object.
 * @return {Object} sizeArr - The sizeArr object.
 */
export function getImageSize( sizes ) {
	const sizeArr = [];
	for ( const size in sizes ) {
		if ( sizes.hasOwnProperty( size ) ) {
			const p = { value: size, label: size };
			sizeArr.push( p );
		}
	}
	return sizeArr;
}

export function getIdFromString( label ) {
	return label
		? label
			.toLowerCase()
			.replace( /[^a-zA-Z ]/g, '' )
			.replace( /\s+/g, '-' )
		: '';
}

export function getPanelIdFromRef( ref ) {
	if ( ref.current ) {
		const parentElement = ref.current.parentElement.closest(
			'.components-panel__body'
		);
		if (
			parentElement &&
			parentElement.querySelector( '.components-panel__body-title' )
		) {
			return getIdFromString(
				parentElement.querySelector( '.components-panel__body-title' )
					.textContent
			);
		}
	}
	return null;
}

export const srfmClassNames = ( classes ) =>
	classes.filter( Boolean ).join( ' ' );

export const srfmDeepClone = ( arrayOrObject ) =>
	JSON.parse( JSON.stringify( arrayOrObject ) );

export const handleAddNewPost = async (
	formData,
	templateName,
	templateMetas,
	isConversational = false,
	formType = ''
) => {
	if ( '1' !== srfm_admin.capability ) {
		console.error( 'User does not have permission to create posts' );
		return;
	}

	try {
		const response = await apiFetch( {
			path: 'sureforms/v1/create-new-form',
			method: 'POST',
			headers: {
				'Content-Type': 'text/html',
				'X-WP-Nonce': srfm_admin.template_picker_nonce,
			},
			data: {
				form_data: formData,
				template_name: templateName,
				template_metas: templateMetas,
				is_conversational: isConversational,
				form_type: formType,
			},
		} );

		if ( response.id ) {
			const postId = response.id;

			// Redirect to the newly created post
			window.location.href = `${ srfm_admin.site_url }/wp-admin/post.php?post=${ postId }&action=edit`;
		} else {
			console.error( 'Error creating sureforms_form:', response.message );
		}
	} catch ( error ) {
		console.log( error );
	}
};

export const initiateAuth = async () => {
	const response = await apiFetch( {
		path: '/sureforms/v1/initiate-auth',
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': srfm_admin.template_picker_nonce,
		},
		method: 'GET',
	} );

	if ( response?.success ) {
		window.location.href = response.data;
	}
};

export const randomNiceColor = () => {
	const randomInt = ( min, max ) => {
		return Math.floor( Math.random() * ( max - min + 1 ) ) + min;
	};

	const h = randomInt( 0, 360 );
	const s = randomInt( 42, 98 );
	const l = randomInt( 40, 90 );
	return `hsla(${ h },${ s }%,${ l }%,${ 0.2 })`;
};

export const generateDropDownOptions = (
	setInputData,
	optionsArray = [],
	arrayHeader = ''
) => {
	let data = optionsArray.map( ( [ key, val ] ) => {
		return {
			title: val,
			onClick: () => {
				setInputData( key );
			},
		};
	} );

	if ( 0 === data.length ) {
		data = [ { title: __( 'No tags available', 'sureforms' ) } ];
	}

	if ( 0 !== arrayHeader.length ) {
		data = [
			{
				title: arrayHeader,
				isDisabled: true,
				onclick: () => false,
			},
			...data,
		];
	}

	return data;
};

// Creates excerpt.
export function trimTextToWords( text, wordLimit, ending = '...' ) {
	// Split the text into words
	const words = text.split( /\s+/ );

	// If the text has fewer words than the limit, return it as is
	if ( words.length <= wordLimit ) {
		return text;
	}

	// Slice the array to the limit and join it back into a string and append the ending if there are more words than the limit
	return words.slice( 0, wordLimit ).join( ' ' ) + ending;
}

const pushSmartTagToArray = (
	blocks,
	blockSlugs,
	tagsArray,
	uniqueSlugs = [],
	allowedBlocks = []
) => {
	if ( Array.isArray( blocks ) && 0 === blocks.length ) {
		return;
	}

	blocks.forEach( ( block ) => {
		const isInnerBlock =
			Array.isArray( block?.innerBlocks ) &&
			0 !== block?.innerBlocks.length;

		if ( isInnerBlock ) {
			// If is inner block, process inner block recursively.
			return pushSmartTagToArray(
				block.innerBlocks,
				blockSlugs,
				tagsArray,
				uniqueSlugs,
				allowedBlocks
			);
		}

		const isAllowedBlock = !! allowedBlocks.length
			? allowedBlocks.includes( block?.name )
			: true;

		if ( ! isAllowedBlock ) {
			return;
		}

		// Verify if `block.attributes.block_id` is defined and not empty.
		if ( ! block?.attributes?.block_id ) {
			return;
		}

		const fieldSlug = blockSlugs[ block.attributes.block_id ];

		if ( 'undefined' === typeof fieldSlug || ! fieldSlug ) {
			// If we are here, then field is invalid and we don't need to process it.
			return;
		}

		if ( uniqueSlugs.includes( fieldSlug ) ) {
			return;
		}

		/**
		 * Compose field tag and label.
		 */
		const fieldTag = '{form:' + fieldSlug + '}';
		let fieldLabel = trimTextToWords( block.attributes.label, 5 ); // Limit the label to 5 words, maximum. It does not affect the slug.

		if ( 'srfm/gdpr' === block?.name ) {
			// If we have GDPR field, lets add a GDPR prefix to make it clear to the users.
			fieldLabel = `[GDPR] ${ fieldLabel }`;
		}

		tagsArray.push( [ fieldTag, fieldLabel ] );
		uniqueSlugs.push( fieldSlug );
	} );
};

export const withoutSlugBlocks = [
	'srfm/inline-button',
	'srfm/hidden',
	'srfm/page-break',
	'srfm/separator',
	'srfm/advanced-heading',
	'srfm/image',
	'srfm/icon',
];

export const setFormSpecificSmartTags = ( updateBlockAttributes ) => {
	const { getBlocks } = select( editorStore );
	let savedBlocks = getBlocks();
	const blockSlugs = prepareBlockSlugs( updateBlockAttributes, savedBlocks );

	if ( ! Object.keys( blockSlugs )?.length ) {
		return;
	}

	const formSmartTags = [];
	const formEmailSmartTags = [];
	const formSmartTagsUniqueSlugs = [];
	const formEmailSmartTagsUniqueSlugs = [];

	if ( typeof window.sureforms === 'undefined' ) {
		window.sureforms = {};
	}

	window.sureforms.formSpecificSmartTags = formSmartTags;
	window.sureforms.formSpecificEmailSmartTags = formEmailSmartTags;

	if ( ! savedBlocks?.length ) {
		return;
	}

	savedBlocks = savedBlocks.filter(
		( savedBlock ) => ! withoutSlugBlocks.includes( savedBlock?.name )
	);

	pushSmartTagToArray(
		savedBlocks,
		blockSlugs,
		formSmartTags,
		formSmartTagsUniqueSlugs
	);
	pushSmartTagToArray(
		savedBlocks,
		blockSlugs,
		formEmailSmartTags,
		formEmailSmartTagsUniqueSlugs,
		[ 'srfm/email' ]
	);

	window.sureforms.formSpecificSmartTags = formSmartTags;
	window.sureforms.formSpecificEmailSmartTags = formEmailSmartTags;
};

/**
 * A function to check if an object is not empty.
 *
 * @function
 *
 * @param {Object} obj - The object to check.
 *
 * @return {boolean} Returns true if the object is not empty, otherwise returns false.
 */
export const isObjectNotEmpty = ( obj ) => {
	return (
		obj &&
		Object.keys( obj ).length > 0 &&
		Object.getPrototypeOf( obj ) === Object.prototype
	);
};

/**
 * Formats a number to display in a human-readable format.
 *
 * @param {number} num - The number to format.
 * @return {string} The formatted number.
 */
export const formatNumber = ( num ) => {
	if ( ! num ) {
		return '0';
	}
	const thresholds = [
		{ magnitude: 1e12, suffix: 'T' },
		{ magnitude: 1e9, suffix: 'B' },
		{ magnitude: 1e6, suffix: 'M' },
		{ magnitude: 1e3, suffix: 'K' },
		{ magnitude: 1, suffix: '' },
	];

	const { magnitude, suffix } = thresholds.find(
		( { magnitude: magnitudeValue } ) => num >= magnitudeValue
	);

	const formattedNum = ( num / magnitude ).toFixed( 1 ).replace( /\.0$/, '' );

	return num < 1000
		? num.toString()
		: formattedNum + suffix + ( num % magnitude > 0 ? '+' : '' );
};

export const SRFMToaster = ( {
	containerClassName,
	containerStyle,
	position = 'top-right',
} ) => {
	return (
		<Toaster
			containerClassName={ containerClassName }
			position={ position }
			containerStyle={ containerStyle }
		>
			{ ( t ) => (
				<ToastBar
					toast={ t }
					style={ {
						...t.style,
						animation: t.visible
							? 'slide-in-left 0.5s ease'
							: 'slide-out-right 0.5s ease',
					} }
				/>
			) }
		</Toaster>
	);
};

// Using for the icon picker component.
export const uagbClassNames = ( classes ) =>
	classes.filter( Boolean ).join( ' ' );

export const addQueryParam = ( url, paramValue, paramKey = 'utm_medium' ) => {
	try {
		const urlObj = new URL( url );
		urlObj.searchParams.set( paramKey, paramValue );
		return urlObj.toString();
	} catch ( error ) {
		console.error( 'Invalid URL:', error );
		return url; // Return the original URL in case of error
	}
};

/**
 * Utility function to merge Tailwind CSS and conditional class names.
 *
 * @param {...any} args
 * @return {string} - The concatenated class string.
 */
export const cn = ( ...args ) => twMerge( clsx( ...args ) );

/**
 * Formats a given date string based on the provided options.
 *
 * @param {string}  dateString       - The date string to format.
 * @param {Object}  options          - Formatting options to customize the output.
 * @param {boolean} [options.day]    - Whether to include the day in the output.
 * @param {boolean} [options.month]  - Whether to include the month in the output.
 * @param {boolean} [options.year]   - Whether to include the year in the output.
 * @param {boolean} [options.hour]   - Whether to include the hour in the output.
 * @param {boolean} [options.minute] - Whether to include the minute in the output.
 * @param {boolean} [options.hour12] - Whether to use a 12-hour clock format.
 * @return {string} - The formatted date string or a fallback if the input is invalid.
 */
export const formatDate = ( dateString, options = {} ) => {
	if ( ! dateString || isNaN( new Date( dateString ).getTime() ) ) {
		return __( 'No Date', 'sureforms' );
	}

	const optionMap = {
		day: '2-digit',
		month: 'short',
		year: 'numeric',
		hour: '2-digit',
		minute: '2-digit',
		hour12: true, // Note: hour12 is a boolean directly
	};

	const formattingOptions = Object.keys( optionMap ).reduce( ( acc, key ) => {
		if ( options[ key ] === true ) {
			acc[ key ] = optionMap[ key ];
		} else if ( options[ key ] === false ) {
		} else if ( options[ key ] !== undefined ) {
			acc[ key ] = options[ key ];
		}
		return acc;
	}, {} );

	return new Intl.DateTimeFormat( 'en-US', formattingOptions ).format(
		new Date( dateString )
	);
};

/**
 *
 * @return {string} - The formatted date string.
 */
export const getDatePlaceholder = () => {
	const currentDate = new Date();
	const pastDate = new Date();
	pastDate.setDate( currentDate.getDate() - 30 ); // Set to 30 days ago

	const formattedPastDate = formatDate( pastDate, 'MM/dd/yyyy' );
	const formattedCurrentDate = formatDate( currentDate, 'MM/dd/yyyy' );

	return `${ formattedPastDate } - ${ formattedCurrentDate }`;
};

/**
 * Formats a given date string based on the provided options.
 * If no options are provided, it defaults to 'yyyy-MM-dd' format.
 *
 * @param {string|Date} date                      - The date string or Date object to format.
 * @param {string}      [dateFormat='yyyy-MM-dd'] - The date format string for `date-fns`.
 * @return {string} - The formatted date string or a fallback if the input is invalid.
 */
export const format = ( date, dateFormat = 'yyyy-MM-dd' ) => {
	try {
		if ( ! date || isNaN( new Date( date ).getTime() ) ) {
			throw new Error( __( 'Invalid Date', 'sureforms' ) );
		}
		return format_date( new Date( date ), dateFormat );
	} catch ( error ) {
		return __( 'No Date', 'sureforms' );
	}
};

/**
 * Returns selected date in string format
 *
 * @param {*} selectedDates
 * @return {string} - Formatted string.
 */
export const getSelectedDate = ( selectedDates ) => {
	if ( ! selectedDates.from ) {
		return '';
	}
	if ( ! selectedDates.to ) {
		return `${ format( selectedDates.from, 'MM/dd/yyyy' ) }`;
	}
	return `${ format( selectedDates.from, 'MM/dd/yyyy' ) } - ${ format(
		selectedDates.to,
		'MM/dd/yyyy'
	) }`;
};

/**
 *
 * @param {Object} dates - The date object.
 * @return {string} - The formatted date string.
 */
export const getLastNDays = ( dates ) => {
	const { from, to } = dates;

	if ( ! from || ! to ) {
		const currentDate = new Date();
		const pastDate = new Date();
		pastDate.setDate( currentDate.getDate() - 30 );

		return {
			from: pastDate,
			to: currentDate,
		};
	}

	return {
		from: new Date( from ),
		to: new Date( to ),
	};
};

const generateSlug = ( label, existingSlugs ) => {
	const baseSlug = cleanForSlug( label );

	let slug = baseSlug;
	let counter = 1;

	while ( existingSlugs.has( slug ) ) {
		slug = `${ baseSlug }-${ counter }`;
		counter++;
	}

	return slug;
};

const prepareBlockSlugs = ( updateBlockAttributes, srfmBlocks ) => {
	const blockSlugs = {};
	const existingSlugs = new Set();

	const processBlocks = ( blocks ) => {
		for ( const block of blocks ) {
			let { slug, label, block_id } = block.attributes;

			if ( ! slug ) {
				slug = generateSlug( label, existingSlugs );

				// Update the block attributes with the generated slug.
				updateBlockAttributes( block.clientId, { slug } );
			}

			blockSlugs[ block_id ] = slug;
			existingSlugs.add( slug );

			if (
				Array.isArray( block.innerBlocks ) &&
				block.innerBlocks.length > 0
			) {
				processBlocks( block.innerBlocks );
			}
		}
	};

	processBlocks( srfmBlocks );

	return blockSlugs;
};

/**
 * Check if the given element is a valid React element.
 *
 * @param {Object} element - The element to check.
 * @return {boolean} Returns true if the element is a valid React element, otherwise returns false.
 */
export const isValidReactElement = ( element ) => {
	if ( ! element || typeof element !== 'object' ) {
		return false;
	}
	return Symbol.for( 'react.element' ) === element.$$typeof;
};

// Add the CSS properties to the root element.
export const addStyleInRoot = ( root, cssProperties ) => {
	if ( Object.keys( cssProperties ).length > 0 ) {
		for ( const [ key, objValue ] of Object.entries( cssProperties ) ) {
			root.style.setProperty( key, objValue );
		}
	}
};

// Constants for plugin action types
export const PLUGIN_ACTIONS = {
	ACTIVATE: 'sureforms_recommended_plugin_activate',
	INSTALL: 'sureforms_recommended_plugin_install',
};

// Get plugin button text
export const getPluginStatusText = ( plugin_data ) => {
	const statusTextMap = {
		Installed: srfm_admin.plugin_activate_text,
		Install: __( 'Install & Activate', 'sureforms' ),
		Activated: srfm_admin.plugin_activated_text,
	};

	return statusTextMap[ plugin_data.status ] || plugin_data.status;
};

// Get action type based on plugin status
export const getAction = ( status ) => {
	return status === 'Installed'
		? PLUGIN_ACTIONS.ACTIVATE
		: status === 'Activated'
			? ''
			: PLUGIN_ACTIONS.INSTALL;
};

// Helper function for API requests
export const performApiAction = async ( {
	url,
	formData,
	successCallback,
	errorCallback,
} ) => {
	try {
		const response = await apiFetch( {
			url,
			method: 'POST',
			body: formData,
		} );

		if ( response.success ) {
			successCallback( response );
		} else {
			errorCallback( response );
		}
	} catch ( error ) {
		console.error( 'API Error:', error );
		toast.error(
			__( 'An error occurred. Please try again later.', 'sureforms' ),
			{
				duration: 5000,
			}
		);
	}
};

// Function to activate a plugin
export function activatePlugin( { plugin, event } ) {
	const formData = new window.FormData();
	formData.append( 'action', PLUGIN_ACTIONS.ACTIVATE );
	formData.append(
		'security',
		srfm_admin.sfPluginManagerNonce ?? srfm_admin.sf_plugin_manager_nonce
	);
	formData.append( 'init', plugin.path );
	formData.append( 'slug', plugin.slug );

	event.target.innerText = srfm_admin.plugin_activating_text;

	performApiAction( {
		url: srfm_admin.ajax_url,
		formData,
		successCallback: () => {
			if ( srfm_admin?.current_screen_id === 'sureforms_menu' ) {
				event.target.style.color = '#16A34A';
			}
			event.target.innerText = srfm_admin.plugin_activated_text;
			if ( plugin?.redirection ) {
				window.location = plugin.redirection;
			}
		},
		errorCallback: () => {
			toast.error(
				__(
					'Plugin activation failed, Please try again later.',
					'sureforms'
				),
				{
					duration: 5000,
				}
			);
			event.target.innerText = srfm_admin.plugin_activate_text;
		},
	} );
}

export function handlePluginActionTrigger( { plugin, event } ) {
	const action = getAction( plugin.status );
	if ( ! action ) {
		return;
	}

	const formData = new window.FormData();

	if ( action === PLUGIN_ACTIONS.INSTALL ) {
		formData.append( 'action', PLUGIN_ACTIONS.INSTALL );
		formData.append( '_ajax_nonce', srfm_admin.plugin_installer_nonce );
		formData.append( 'slug', plugin.slug );

		event.target.innerText = srfm_admin.plugin_installing_text;

		performApiAction( {
			url: srfm_admin.ajax_url,
			formData,
			successCallback: () => {
				event.target.innerText = srfm_admin.plugin_installed_text;
				activatePlugin( { plugin, event } );
			},
			errorCallback: () => {
				event.target.innerText = __( 'Install', 'sureforms' );
				alert(
					__(
						'Plugin Installation failed, Please try again later.',
						'sureforms'
					)
				);
			},
		} );
	} else if ( action === PLUGIN_ACTIONS.ACTIVATE ) {
		activatePlugin( { plugin, event } );
	}
}

/**
 * Sets a cookie with the specified name, value, and expiration days.
 *
 * @param {string} name  - The name of the cookie.
 * @param {string} value - The value of the cookie.
 * @param {number} days  - The number of days until the cookie expires.
 */
export function setCookie( name, value, days ) {
	const date = new Date();
	const additionalDuration = days * 24 * 60 * 60 * 1000;
	date.setTime( date.getTime() + additionalDuration );
	document.cookie = `${ name }=${ value }; expires=${ date.toUTCString() }; path=/`;
}

/**
 * Retrieves the value of a cookie by its name.
 *
 * @param {string} name - The name of the cookie to retrieve.
 * @return {string|null} - The value of the cookie, or null if not found.
 */
export function getCookie( name ) {
	const value = `; ${ document.cookie }`;
	const parts = value.split( `; ${ name }=` );
	return parts.length === 2 ? parts.pop().split( ';' ).shift() : null;
}

// Remove the CSS properties from the root element.
export const removeStylesFromRoot = ( root, cssProperties ) => {
	if ( Object.keys( cssProperties ).length > 0 ) {
		for ( const [ key ] of Object.entries( cssProperties ) ) {
			root.style.removeProperty( key );
		}
	}
};

// Get the advanced gradient css styles.
export const getGradientCSS = (
	type = 'linear',
	color1 = '#FFC9B2',
	color2 = '#C7CBFF',
	loc1 = 0,
	loc2 = 100,
	angle = 90
) => {
	if ( type === 'radial' ) {
		return `radial-gradient(${ color1 } ${ loc1 }%, ${ color2 } ${ loc2 }% )`;
	}
	return `linear-gradient( ${ angle }deg, ${ color1 } ${ loc1 }%, ${ color2 } ${ loc2 }%)`;
};

/**
 * Sets default values for form attributes in the post meta object.
 *
 * This helper function iterates over the provided `formAttributes` object and checks
 * if each attribute key is present in the `postMeta` object. If a key is missing,
 * it assigns the corresponding default value from `formAttributes` to `postMeta`.
 *
 * @param {Object} formAttributes - An object containing form attribute definitions,
 *                                where each key maps to an object that includes a `default` property.
 * @param {Object} postMeta       - The metadata object to be updated with default values
 *                                for any missing attributes.
 */
export const setDefaultFormAttributes = ( formAttributes, postMeta ) => {
	if ( ! formAttributes ) {
		return;
	}
	Object.keys( formAttributes ).forEach( ( key ) => {
		if ( ! ( key in postMeta ) ) {
			postMeta[ key ] = formAttributes[ key ].default;
		}
	} );
};
