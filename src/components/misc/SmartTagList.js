import { DropdownMenu } from '@wordpress/components';
import { generateDropDownOptions } from '@Utils/Helpers';

export default function SmartTagList( {
	icon,
	label,
	text,
	cssClass,
	tagsArray,
	setTargetData,
} ) {
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
			icon={ icon }
			className={ cssClass }
			label={ label }
			text={ text }
			controls={ controls }
		/>
	);
}
