import { useEffect } from '@wordpress/element';
import { select, dispatch } from '@wordpress/data';
import { withoutSlugBlocks } from '@Utils/Helpers';

const getUniqId = ( blocks ) =>
	blocks.reduce(
		( result, block ) => {
			if (
				block?.attributes?.block_id &&
				block.name.includes( 'srfm' )
			) {
				result.blockIds.push( block.attributes.block_id );
				result.clientIds.push( block.clientId );
			}

			if ( block.innerBlocks ) {
				const { blockIds, clientIds } = getUniqId( block.innerBlocks );
				result.blockIds = [ ...result.blockIds, ...blockIds ];
				result.clientIds = [ ...result.clientIds, ...clientIds ];
			}

			return result;
		},
		{ blockIds: [], clientIds: [] }
	);

const checkDuplicate = ( blockIds, block_id, currentIndex ) => {
	const getFiltered = blockIds.filter( ( el ) => el === block_id );
	return (
		getFiltered.length > 1 &&
		currentIndex === blockIds.lastIndexOf( block_id )
	);
};

const copyConditionalLogic = ( originalBlockId, newBlockId ) => {
	const { getEditedPostAttribute } = select( 'core/editor' );
	const { editPost } = dispatch( 'core/editor' );
	const postMeta = getEditedPostAttribute( 'meta' );
	const conditionalLogicData = postMeta?._srfm_conditional_logic;

	if ( ! conditionalLogicData || ! Array.isArray( conditionalLogicData ) ) {
		return;
	}

	let originalLogic = null;
	for ( let i = 0; i < conditionalLogicData.length; i++ ) {
		if ( conditionalLogicData[ i ][ originalBlockId ] ) {
			originalLogic = conditionalLogicData[ i ][ originalBlockId ];
			break;
		}
	}

	if ( originalLogic ) {
		const newConditionalLogicData = [ ...conditionalLogicData ];
		newConditionalLogicData.push( { [ newBlockId ]: { ...originalLogic } } );

		editPost( {
			meta: {
				_srfm_conditional_logic: newConditionalLogicData,
				meta_modified: new Date().toISOString(),
			},
		} );
	}
};

const addInitialAttr = ( ChildComponent ) => {
	const WrappedComponent = ( props ) => {
		const {
			setAttributes,
			clientId,
			name,
			attributes: { block_id },
			attributes,
		} = props;

		useEffect( () => {
			const newBlockId = clientId.substr( 0, 8 );
			const attributeObject = { block_id: newBlockId };
			const getAllBlocks = select( 'core/editor' )?.getBlocks();
			const { blockIds, clientIds } = getAllBlocks
				? getUniqId( getAllBlocks )
				: { blockIds: [], clientIds: [] };

			const isDuplicate = checkDuplicate(
				blockIds,
				block_id,
				clientIds.indexOf( clientId )
			);

			if ( isDuplicate ) {
				if (
					! withoutSlugBlocks.includes( name ) &&
					attributes?.slug
				) {
					attributeObject.slug = '';
				}

				copyConditionalLogic( block_id, newBlockId );
			}

			if (
				'not_set' === block_id ||
				'0' === block_id ||
				! block_id ||
				isDuplicate
			) {
				setAttributes( attributeObject );
			}
		}, [ clientId ] );

		return <ChildComponent { ...props } />;
	};
	return WrappedComponent;
};
export default addInitialAttr;
