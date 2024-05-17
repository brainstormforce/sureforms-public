/**
 * Get Image Sizes and return an array of Size.
 *
 * @param {Object} sizes - The sizes object.
 * @return {Object} sizeArr - The sizeArr object.
 */

import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

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

export const setFormSpecificSmartTags = ( savedBlocks ) => {
	const excludedBlocks = [
		'srfm/inline-button',
		'srfm/hidden',
		'srfm/page-break',
		'srfm/separator',
		'srfm/advanced-heading',
		'srfm/image',
		'srfm/icon',
	];

	savedBlocks = savedBlocks.filter(
		( savedBlock ) => ! excludedBlocks.includes( savedBlock?.name )
	);

	const formSmartTags = [];
	const formEmailSmartTags = [];

	const pushSmartTagToArray = (
		blocks,
		tagsArray,
		uniqueSlugs = [],
		allowedBlocks = []
	) => {
		if ( Array.isArray( blocks ) && 0 === blocks.length ) {
			return;
		}

		blocks.forEach( ( block ) => {
			if (
				undefined === block?.attributes?.slug ||
				undefined === block?.attributes?.label ||
				'' === block?.attributes?.slug ||
				( 0 !== allowedBlocks.length &&
					! allowedBlocks.includes( block?.name ) )
			) {
				return;
			}

			if (
				Array.isArray( block?.innerBlocks ) &&
				0 !== block?.innerBlocks.length
			) {
				pushSmartTagToArray(
					block.innerBlocks,
					tagsArray,
					uniqueSlugs,
					allowedBlocks
				);
			} else {
				if ( uniqueSlugs.includes( block.attributes.slug ) ) {
					return;
				}
				tagsArray.push( [
					'{form:' + block.attributes.slug + '}',
					'srfm/gdpr' === block?.name
						? __( 'GDPR Agreement', 'sureforms' )
						: block.attributes.label,
				] );
				uniqueSlugs.push( block.attributes.slug );
			}
		} );
	};

	pushSmartTagToArray( savedBlocks, formSmartTags, [] );
	pushSmartTagToArray(
		savedBlocks,
		formEmailSmartTags,
		[],
		[ 'srfm/email' ]
	);

	if ( typeof window.sureforms === 'undefined' ) {
		window.sureforms = {};
	}

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

export const getRemaingCredits = () => {
	return (
		parseInt( srfm_admin.zip_ai_credit_details?.total ) -
		parseInt( srfm_admin.zip_ai_credit_details?.used )
	);
};
