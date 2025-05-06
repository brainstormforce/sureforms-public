import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { Toaster, ToastBar } from 'react-hot-toast';
import { store as editorStore } from '@wordpress/editor';
import { select } from '@wordpress/data';
import { cleanForSlug } from '@wordpress/url';

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
