import { DropdownMenu } from '@wordpress/components';
import { applyFilters } from '@wordpress/hooks';
import { generateDropDownOptions } from '@Utils/Helpers';
import svgIcons from '@Image/single-form-logo.json';
import parse from 'html-react-parser';
import { __ } from '@wordpress/i18n';

export default function SmartTagList( {
	icon,
	label,
	text,
	tagFor,
	cssClass,
	tagsArray,
	setTargetData,
} ) {
	const verticalDotIcon = parse( svgIcons.verticalDot );
	const controls = [];

	applyFilters( 'srfm.smartTagList.tagsArray', tagsArray, tagFor )
	.forEach( ( tagsArrayItem ) => {
		controls.push(
			generateDropDownOptions(
				setTargetData,
				tagsArrayItem.tags,
				tagsArrayItem.label
			)
		)
	} );

	return (
		<DropdownMenu
			icon={ icon ?? verticalDotIcon }
			className={ cssClass ?? 'srfm-scroll-dropdown' }
			label={ label ?? __( 'Select Shortcodes', 'sureforms' ) }
			text={ text }
			controls={ controls }
		/>
	);
}
