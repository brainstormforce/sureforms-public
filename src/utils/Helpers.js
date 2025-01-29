/**
 * Get Image Sizes and return an array of Size.
 *
 * @param {Object} sizes - The sizes object.
 * @return {Object} sizeArr - The sizeArr object.
 */

import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { Toaster, ToastBar } from 'react-hot-toast';

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
	templateMetas
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

export const generateSmartTagsDropDown = ( setInputData ) => {
	const smartTagList = srfm_block_data.smart_tags_array;
	if ( ! smartTagList ) {
		return;
	}
	const entries = Object.entries( smartTagList );
	return generateDropDownOptions( setInputData, entries );
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

export async function getServerGeneratedBlockSlugs( formID, content ) {
	const option = {
		path: `/sureforms/v1/generate-block-slugs`,
		method: 'POST',
		data: {
			formID,
			content,
		},
	};

	return await apiFetch( option );
}

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

		const fieldSlug = blockSlugs[ block.attributes.block_id ];

		if ( 'undefined' === typeof fieldSlug ) {
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

export const setFormSpecificSmartTags = ( savedBlocks, blockSlugs ) => {
	if ( ! Object.keys( blockSlugs )?.length ) {
		return;
	}

	const excludedBlocks = [
		'srfm/inline-button',
		'srfm/page-break',
		'srfm/separator',
		'srfm/advanced-heading',
		'srfm/image',
		'srfm/icon',
	];

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
		( savedBlock ) => ! excludedBlocks.includes( savedBlock?.name )
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

// Add the CSS properties to the root element.
export const addStyleInRoot = ( root, cssProperties ) => {
	if ( Object.keys( cssProperties ).length > 0 ) {
		for ( const [ key, objValue ] of Object.entries( cssProperties ) ) {
			root.style.setProperty( key, objValue );
		}
	}
};
