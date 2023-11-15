/**
 * BLOCK: simple-block
 *
 * Registering a basic block with Gutenberg.
 * Simple block, renders and saves the same content without any interactivity.
 */

import CF_Block_Icons from '../../block-icons';
import edit from './edit';
import './style.scss';

const { __ } = wp.i18n;

const { registerBlockType } = wp.blocks;

if ( 'checkout' === cf_blocks_info.step_type && cf_blocks_info.is_woo_active ) {
	registerBlockType( 'wcfb/checkout-form', {
		title: __( 'Checkout Form', 'cartflows' ),
		description: __( 'CartFlows Checkout Block', 'cartflows' ),
		icon: CF_Block_Icons.checkout_form,
		category: cf_blocks_info.category,
		keywords: [
			__( 'cartflows', 'cartflows' ),
			__( 'checkout form', 'cartflows' ),
			__( 'cf', 'cartflows' ),
		],
		edit,
		example: {},
		save() {
			return null;
		},
	} );
}

wp.hooks.applyFilters( 'editor.registerBlockType', 'wcfp/cfp-filter-blocks' );
