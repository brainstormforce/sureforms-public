/**
 * BLOCK: Heading
 */

import UAGB_Block_Icons from '@Controls/block-icons';
import attributes from './attributes';
import Edit from './edit';
import './style.scss';
import { __ } from '@wordpress/i18n';
import { registerBlockType, createBlock } from '@wordpress/blocks';
import './format';
import colourNameToHex from '@Controls/changeColorNameToHex';
import PreviewImage from '@Controls/previewImage';
import { applyFilters } from '@wordpress/hooks';

import addCommonDataToSpectraBlocks from '@Controls/addCommonDataToSpectraBlocks';
let headingCommonData = {};
headingCommonData = applyFilters(
	'srfm/advanced-heading',
	addCommonDataToSpectraBlocks( headingCommonData )
);
registerBlockType( 'srfm/advanced-heading', {
	...headingCommonData,
	title: __( 'Heading', 'sureforms' ),
	description: __(
		'Add heading, sub heading and a separator using one block.',
		'sureforms'
	),
	icon: UAGB_Block_Icons.advanced_heading,
	keywords: [
		__( 'creative heading', 'sureforms' ),
		__( 'uag', 'sureforms' ),
		__( 'heading', 'sureforms' ),
	],
	supports: {
		anchor: true,
		html: false,
	},
	attributes,
	category: srfm_spec_blocks_info.category,
	edit: ( props ) =>
		props.attributes.isPreview ? (
			<PreviewImage image="advanced-heading" />
		) : (
			<Edit { ...props } />
		),
	save() {
		return null;
	},
	__experimentalLabel: ( atts ) =>
		applyFilters(
			'uag_loop_data_source_label',
			__( 'Heading', 'sureforms' ),
			atts
		),
	usesContext: [ 'postId', 'postType' ],
	transforms: {
		from: [
			{
				type: 'block',
				blocks: [ 'core/heading' ],
				transform: ( attribute ) => {
					return createBlock( 'srfm/advanced-heading', {
						headingTitle: attribute.content,
						headingAlign: attribute.textAlign,
						headingColor: colourNameToHex( attribute.textColor ),
						blockBackground: colourNameToHex(
							attribute.backgroundColor
						),
					} );
				},
			},
			{
				type: 'block',
				blocks: [ 'core/quote' ],
				transform: ( attribute ) => {
					return createBlock( 'srfm/advanced-heading', {
						headingTitle: attribute.value,
						headingDesc: attribute.citation,
						headingAlign: attribute.align,
						headingColor: colourNameToHex( attribute.textColor ),
						blockBackground: colourNameToHex(
							attribute.backgroundColor
						),
					} );
				},
			},
			{
				type: 'block',
				blocks: [ 'core/paragraph' ],
				transform: ( attribute ) => {
					return createBlock( 'srfm/advanced-heading', {
						headingTitle: attribute.content,
						headingAlign: attribute.align,
						headingColor: colourNameToHex( attribute.textColor ),
						blockBackground: colourNameToHex(
							attribute.backgroundColor
						),
					} );
				},
			},
			{
				type: 'block',
				blocks: [ 'core/list' ],
				transform: ( _attributes, childBlocks ) => {
					const newitems = [];
					childBlocks.forEach( ( item, i ) => {
						newitems.push( {
							text: childBlocks[ i ].attributes.content,
						} );
					} );

					return newitems.map( ( text ) =>
						createBlock( 'srfm/advanced-heading', {
							headingTitle: text.text,
							headingColor: colourNameToHex(
								_attributes.textColor
							),
							blockBackground: colourNameToHex(
								_attributes.backgroundColor
							),
						} )
					);
				},
			},
		],
		to: [
			{
				type: 'block',
				blocks: [ 'core/heading' ],
				transform: ( attribute ) => {
					return createBlock( 'core/heading', {
						content: attribute.headingTitle,
						align: attribute.headingAlign,
					} );
				},
			},
			{
				type: 'block',
				blocks: [ 'core/quote' ],
				transform: ( attribute ) => {
					return createBlock( 'core/quote', {
						value: attribute.headingTitle,
						citation: attribute.headingDesc,
					} );
				},
			},
			{
				type: 'block',
				blocks: [ 'core/paragraph' ],
				transform: ( attribute ) => {
					return createBlock( 'core/paragraph', {
						content: attribute.headingTitle,
					} );
				},
			},
		],
	},
} );
