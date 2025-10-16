import { __ } from '@wordpress/i18n';
import { SquarePen } from 'lucide-react';
import { Button } from '@bsf/force-ui';
import UpgradeTooltip from '@Admin/entries/components/UpgradeTooltip';

const EditEntryButton = ( { onEdit } ) => {
	return (
		<UpgradeTooltip
			heading={ __( 'Unlock Edit Form Entires', 'sureforms' ) }
			content={ __(
				'With the SureForms Starter plan, you can easily edit your entries to suit your needs.',
				'sureforms'
			) }
			placement="top"
			utmMedium="edit_entry"
		>
			<Button
				variant="outline"
				size="xs"
				icon={ <SquarePen className="w-4 h-4" /> }
				iconPosition="left"
				onClick={ onEdit }
				disabled
			>
				{ __( 'Edit Entries', 'sureforms' ) }
			</Button>
		</UpgradeTooltip>
	);
};

/**
 * Render field value - handles both regular and repeater fields
 *
 * @param {*} value - Field value (can be string, array for repeater, etc.)
 * @return {string} Rendered value
 */
const renderFieldValue = ( value ) => {
	// Handle repeater fields (array of objects)
	if ( Array.isArray( value ) ) {
		// For repeater fields, show a count or summary
		return `${ value.length } ${ value.length === 1 ? __( 'item', 'sureforms' ) : __( 'items', 'sureforms' ) }`;
	}

	// Handle regular fields
	return value || '-';
};

/**
 * EntryDataSection Component
 * Displays the form fields and their values for an entry
 *
 * @param {Object}   props
 * @param {Object}   props.entryData - The entry data object
 * @param {Function} props.onEdit    - Handler for edit action
 */
const EntryDataSection = ( { entryData, onEdit } ) => {
	// Extract and transform form data fields from entry data
	const fields = ( entryData?.formData || [] ).map( ( field ) => ( {
		label: field.label,
		value: renderFieldValue( field.value ),
	} ) );

	return (
		<div className="bg-background-primary border-0.5 border-solid border-border-subtle rounded-lg shadow-sm">
			<div className="pb-0 px-4 pt-4">
				<div className="flex items-center justify-between">
					<h3 className="text-base font-semibold text-text-primary">
						{ __( 'Entry Data', 'sureforms' ) }
					</h3>
					<EditEntryButton onEdit={ onEdit } />
				</div>
			</div>
			<div className="p-4 space-y-1 relative before:content-[''] before:block before:absolute before:inset-3 before:bg-background-secondary before:rounded-lg">
				{ fields.map( ( field, index ) => (
					<div
						key={ index }
						className="p-3 relative bg-background-primary rounded-md shadow-sm"
					>
						<div className="flex gap-4">
							<div className="w-40 flex-shrink-0">
								<span className="text-sm font-semibold text-text-primary">
									{ field.label }
								</span>
							</div>
							<div className="flex-1">
								<span className="text-sm font-medium text-text-secondary">
									{ field.value }
								</span>
							</div>
						</div>
					</div>
				) ) }
			</div>
		</div>
	);
};

export default EntryDataSection;
