/**
 * WordPress dependencies
 */
import icon from '@Image/Logo.js';

/**
 * Internal dependencies
 */
import edit from './edit';
import save from './save';
import metadata from '@IncBlocks/sform/block.json';
import { FieldsPreview } from '../FieldsPreview.jsx';
import addCommonData from '@Controls/addCommonData';
import { applyFilters } from '@wordpress/hooks';

// To remove the preview in the site editor.
let formCommonData = {};
formCommonData = applyFilters( 'srfm/form', addCommonData( formCommonData ) );

const fieldName = srfm_fields_preview.sureforms_preview;

export { metadata };
export const settings = {
	...formCommonData,
	icon,
	edit: ( props ) =>
		props.attributes.isPreview ? (
			<FieldsPreview fieldName={ fieldName } />
		) : (
			edit( props )
		),
	save,
};
