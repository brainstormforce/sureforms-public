import { useEffect } from '@wordpress/element';
import { select } from '@wordpress/data';
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

const addInitialAttr = ( ChildComponent ) => {
	const WrappedComponent = ( props ) => {
		const {
			setAttributes,
			clientId,
			attributes: { block_id },
		} = props;

		useEffect( () => {
			const attributeObject = { block_id: clientId.substr( 0, 8 ) };
			const getAllBlocks = select( 'core/editor' )?.getBlocks();
			const { blockIds, clientIds } = getAllBlocks
				? getUniqId( getAllBlocks )
				: { blockIds: [], clientIds: [] };

			// const isDuplicate = checkDuplicate(
			// 	blockIds,
			// 	block_id,
			// 	clientIds.indexOf( clientId )
			// );

			// console.log( 'isDuplicate', {isDuplicate, block_id} );

			if (
				'not_set' === block_id ||
				'0' === block_id ||
				! block_id ||
				checkDuplicate(
					blockIds,
					block_id,
					clientIds.indexOf( clientId )
				)
			) {
				setAttributes( attributeObject );
			}
		}, [ clientId ] );

		return <ChildComponent { ...props } />;
	};
	return WrappedComponent;
};
export default addInitialAttr;
