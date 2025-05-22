import { applyFilters } from '@wordpress/hooks';
import { cn, generateDropDownOptions } from '@Utils/Helpers';
import { DropdownMenu, Button, Label } from '@bsf/force-ui';
import { EllipsisVerticalIcon } from 'lucide-react';

export default function SmartTagList( {
	icon,
	label,
	tagFor,
	tagsArray,
	setTargetData,
	className,
	triggerSize = 'sm',
	triggerVariant = 'outline',
	triggerClassName,
	dropdownPlacement = 'bottom-start',
} ) {
	const controls = [];

	applyFilters( 'srfm.smartTagList.tagsArray', tagsArray, tagFor ).forEach(
		( tagsArrayItem ) => {
			controls.push(
				generateDropDownOptions(
					setTargetData,
					tagsArrayItem.tags,
					tagsArrayItem.label
				)
			);
		}
	);

	return (
		<DropdownMenu placement={ dropdownPlacement } className="min-w-fit">
			<DropdownMenu.Trigger>
				<Button
					variant={ triggerVariant }
					size={ triggerSize }
					icon={ icon ?? <EllipsisVerticalIcon /> }
					iconPosition="right"
					className={ cn( 'min-w-fit [&_svg]:shrink-0', triggerClassName ) }
				>
					{ !! label && label }
				</Button>
			</DropdownMenu.Trigger>
			<DropdownMenu.Portal id="srfm-dialog-root">
				<DropdownMenu.ContentWrapper>
					<DropdownMenu.Content className={ cn( 'w-60 max-h-80 overflow-y-auto', className ) }>
						<DropdownMenu.List>
							{ controls.map( ( section, sectionIndx ) =>
								section.map( ( control, indx ) =>
									indx === 0 ? (
										<Label
											key={ `${ sectionIndx }-${ indx }-control-${ control?.title }` }
											variant="help"
											size="xs"
											className="p-2 text-text-primary font-medium text-sm"
											tabIndex={ -1 }
										>
											{ control?.title }
										</Label>
									) : (
										<DropdownMenu.Item
											key={ `${ sectionIndx }-${ indx }-control-${ control?.title }` }
											className="text-sm font-normal text-text-secondary hover:bg-background-secondary hover:text-text-primary focus:bg-background-secondary focus:text-text-primary"
											{ ...( typeof control?.onClick ===
											'function'
												? { onClick: control?.onClick }
												: {} ) }
										>
											{ control?.title }
										</DropdownMenu.Item>
									)
								)
							) }
						</DropdownMenu.List>
					</DropdownMenu.Content>
				</DropdownMenu.ContentWrapper>
			</DropdownMenu.Portal>
		</DropdownMenu>
	);
}
