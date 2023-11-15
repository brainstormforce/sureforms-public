/**
 * BLOCK: Multi Buttons
 */

import CF_Block_Icons from '../../block-icons';
import attributes from './attributes';
import edit from './edit';
import save from './save';
import deprecated from './deprecated';
import './style.scss';
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';

if ( 'landing' === cf_blocks_info.step_type ) {
	registerBlockType( 'wcfb/next-step-button', {
		title: __( 'Next Step Button', 'cartflows' ),
		description: __( 'CartFlows Next Step Button Block.', 'cartflows' ),
		icon: CF_Block_Icons.next_step,
		category: cf_blocks_info.category,
		keywords: [
			__( 'cartflows', 'cartflows' ),
			__( 'next step button', 'cartflows' ),
			__( 'cf', 'cartflows' ),
		],
		supports: {
			anchor: true,
		},
		attributes,
		example: {},
		edit,
		save,
		deprecated,
	} );
}
