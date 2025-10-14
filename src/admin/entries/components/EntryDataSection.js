import { __ } from '@wordpress/i18n';
import { Edit3 } from 'lucide-react';
import { Button } from '@bsf/force-ui';

/**
 * EntryDataSection Component
 * Displays the form fields and their values for an entry
 *
 * @param {Object}   props
 * @param {Object}   props.entryData - The entry data object
 * @param {Function} props.onEdit    - Handler for edit action
 */
const EntryDataSection = ( { entryData, onEdit } ) => {
	// Mock data for now - replace with actual entry data structure
	const fields = [
		{ label: __( 'First Name:', 'sureforms' ), value: 'Aaditya Sharma' },
		{ label: __( 'Email:', 'sureforms' ), value: 'ads@gmail.com' },
		{ label: __( 'Gender:', 'sureforms' ), value: 'Male' },
		{ label: __( 'Phone Number:', 'sureforms' ), value: '+91-8006572823' },
		{
			label: __( 'Message:', 'sureforms' ),
			value: 'Suspendisse egestas amet in varius commodo in in morbi. Quis viverra in quis vitae aliquam viverra tellus phasellus. Diam netus imperdiet risus amet turpis massa cras donec morbi. Viverra ac egestas mollis.',
		},
	];

	return (
		<div className="bg-background-primary border-0.5 border-solid border-border-subtle rounded-lg shadow-sm">
			<div className="pb-0 px-4 pt-4">
				<div className="flex items-center justify-between">
					<h3 className="text-base font-semibold text-text-primary">
						{ __( 'Entry Data', 'sureforms' ) }
					</h3>
					<Button
						variant="outline"
						size="xs"
						icon={ <Edit3 className="w-4 h-4" /> }
						iconPosition="left"
						onClick={ onEdit }
					>
						{ __( 'Edit Entries', 'sureforms' ) }
					</Button>
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
