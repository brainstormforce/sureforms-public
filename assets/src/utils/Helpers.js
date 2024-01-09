/**
 * Get Image Sizes and return an array of Size.
 *
 * @param {Object} sizes - The sizes object.
 * @return {Object} sizeArr - The sizeArr object.
 */

import apiFetch from '@wordpress/api-fetch';

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

export const handleAddNewPost = async ( formData, templateName ) => {
	if ( '1' !== sureforms_admin.capability ) {
		console.error( 'User does not have permission to create posts' );
		return;
	}

	try {
		const response = await apiFetch( {
			path: 'sureforms/v1/create-new-form',
			method: 'POST',
			headers: {
				'Content-Type': 'text/html',
			},
			data: { form_data: formData, template_name: templateName },
		} );

		if ( response.id ) {
			const postId = response.id;

			// Redirect to the newly created post
			window.location.href = `${ sureforms_admin.site_url }/wp-admin/post.php?post=${ postId }&action=edit`;
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

export const generateSmartTagsDropDown = ( setInputData, inputData, props ) => {
	const smartTagList = sfBlockData.smart_tags_array;
	if ( ! smartTagList ) {
		return;
	}
	const entries = Object.entries( smartTagList );
	const data = entries.map( ( [ key, val ] ) => {
		return {
			title: val,
			onClick: () => {
				props?.onChange( inputData + key );
				setInputData( inputData + key );
			},
		};
	} );

	return data;
};
