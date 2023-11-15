/**
 * BLOCK: UAGB - Separator
 */
import UAGB_Block_Icons from '@Controls/block-icons';
import './style.scss';
import attributes from './attributes';
import Edit from './edit';
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import PreviewImage from '@Controls/previewImage';
import { applyFilters } from '@wordpress/hooks';
import addCommonDataToSpectraBlocks from '@Controls/addCommonDataToSpectraBlocks';
const separatorCommonData = applyFilters( 'srfm/separator', addCommonDataToSpectraBlocks( {} ) );
registerBlockType( 'srfm/separator', {
	...separatorCommonData,
	apiVersion: 2,
	title: __( 'Separator', 'ultimate-addons-for-gutenberg' ),
	description: __(
		'Add a modern separator to divide your page content with icon/text.',
		'ultimate-addons-for-gutenberg'
	),
	icon: '',
	category: '',
	keywords: [ __( 'divider', 'ultimate-addons-for-gutenberg' ), __( 'separator', 'ultimate-addons-for-gutenberg' ) ],
	attributes,
	edit: ( props ) => ( props.attributes.isPreview ? <PreviewImage image="separator" /> : <Edit { ...props } /> ),
	save() {
		return null;
	},
} );
