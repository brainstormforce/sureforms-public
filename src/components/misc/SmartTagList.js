import { DropdownMenu } from '@wordpress/components';
import { generateDropDownOptions } from '@Utils/Helpers';
import svgIcons from '@Image/single-form-logo.json';
import parse from 'html-react-parser';
import { __ } from '@wordpress/i18n';

export default function SmartTagList( {
	icon,
	label,
	text,
	cssClass,
	tagsArray,
	setTargetData,
} ) {
	const verticalDotIcon = parse( svgIcons.verticalDot );
	const controls = [];
	tagsArray.map( ( tagsArrayItem ) => (
		controls.push( generateDropDownOptions(
			setTargetData,
			tagsArrayItem.tags,
			tagsArrayItem.label
		) )
	) );

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
