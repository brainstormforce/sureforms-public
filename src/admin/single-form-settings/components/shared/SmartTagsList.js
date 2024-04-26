import { DropdownMenu } from '@wordpress/components';

export default function SmartTagList( {
	icon,
	label,
	text,
	cssClass,
	optionsCallback,
	tagsArray,
	setTargetData,
} ) {
	const controls = [];
	tagsArray.map( ( tagsArrayItem ) => (
		controls.push( optionsCallback(
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
