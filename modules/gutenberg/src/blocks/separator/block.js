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
const separatorCommonData = applyFilters(
	'sureforms/separator',
	addCommonDataToSpectraBlocks( {} )
);
registerBlockType( 'sureforms/separator', {
	...separatorCommonData,
	apiVersion: 2,
	title: __( 'Separator', 'sureforms' ),
	description: __(
		'Add a modern separator to divide your page content with icon/text.',
		'sureforms'
	),
	icon: UAGB_Block_Icons.separator,
	category: uagb_blocks_info.category,
	keywords: [ __( 'divider', 'sureforms' ), __( 'separator', 'sureforms' ) ],
	attributes,
	edit: ( props ) =>
		props.attributes.isPreview ? (
			<PreviewImage image="separator" />
		) : (
			<Edit { ...props } />
		),
	save() {
		return null;
	},
} );
