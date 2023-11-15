/**
 * BLOCK: order-detail-form
 *
 * Registering a basic block with Gutenberg.
 * Simple block, renders and saves the same content without any interactivity.
 */

import CF_Block_Icons from '../../block-icons';
import './style.scss';
// import './editor.scss';
import edit from './edit';

const { __ } = wp.i18n;

const { registerBlockType } = wp.blocks;

if ( 'thankyou' === cf_blocks_info.step_type && cf_blocks_info.is_woo_active ) {
	registerBlockType( 'wcfb/order-detail-form', {
		title: __( 'Order Detail Form', 'cartflows' ),
		description: __( 'CartFlows Order Detail Form Block', 'cartflows' ),
		icon: CF_Block_Icons.order_detail,
		category: cf_blocks_info.category,
		keywords: [
			__( 'cartflows', 'cartflows' ),
			__( 'order detail form', 'cartflows' ),
			__( 'cf', 'cartflows' ),
		],
		edit,
		example: {},
		save() {
			return null;
		},
	} );
}
