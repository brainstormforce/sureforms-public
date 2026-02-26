/**
 * BLOCK: Image
 */

import Edit from './edit';
import attributes from './attributes';
import UAGB_Block_Icons from '@Controls/block-icons';
import { __ } from '@wordpress/i18n';
import './style.scss';
import { registerBlockType, createBlock } from '@wordpress/blocks';
import PreviewImage from '@Controls/previewImage';
import { applyFilters } from '@wordpress/hooks';
import addCommonDataToSpectraBlocks from '@Controls/addCommonDataToSpectraBlocks';
let imageCommonData = {};
imageCommonData = applyFilters(
	'srfm/image',
	addCommonDataToSpectraBlocks( imageCommonData )
);
registerBlockType( 'srfm/image', {
	...imageCommonData,
	title: __( 'Image', 'sureforms' ),
	description: __(
		'Add images on your webpage with multiple customization options.',
		'sureforms'
	),
	icon: UAGB_Block_Icons.image,
	keywords: [
		__( 'image', 'sureforms' ),
		__( 'advance image', 'sureforms' ),
		__( 'caption', 'sureforms' ),
		__( 'overlay image', 'sureforms' ),
	],
	supports: {
		anchor: true,
		color: {
			__experimentalDuotone: 'img',
			text: false,
			background: false,
		},
		align: true,
		html: false,
	},

	attributes,
	category: srfm_spec_blocks_info.category,
	edit: ( props ) =>
		props.attributes.isPreview ? (
			<PreviewImage image="image" />
		) : (
			<Edit { ...props } />
		),
	save() {
		return null;
	},
	__experimentalLabel: ( atts ) =>
		applyFilters(
			'uag_loop_data_source_label',
			__( 'Image', 'sureforms' ),
			atts
		),
	usesContext: [ 'postId', 'postType' ],
	transforms: {
		from: [
			{
				type: 'block',
				blocks: [ 'core/image' ],
				transform: ( { url, sizeSlug } ) => {
					return createBlock( 'srfm/image', {
						url,
						sizeSlug,
					} );
				},
			},
			{
				type: 'block',
				blocks: [ 'core/post-featured-image' ],
				transform: ( { sizeSlug } ) => {
					return createBlock( 'srfm/image', {
						useDynamicData: true,
						dynamicContentType: 'featured-image',
						sizeSlug,
					} );
				},
			},
		],
		to: [
			{
				type: 'block',
				blocks: [ 'core/image' ],
				transform: ( { url, sizeSlug } ) => {
					return createBlock( 'core/image', {
						url,
						sizeSlug,
					} );
				},
			},
		],
	},
} );
