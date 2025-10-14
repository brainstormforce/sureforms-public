import { __ } from '@wordpress/i18n';

/**
 * SubmissionInfoSection Component
 * Displays submission metadata like entry ID, form name, etc.
 *
 * @param {Object} props
 * @param {Object} props.entryData - The entry data object
 */
const SubmissionInfoSection = ( { entryData } ) => {
	// Mock data for now - replace with actual entry data structure
	const infoFields = [
		{ label: __( 'Entry:', 'sureforms' ), value: '#7' },
		{ label: __( 'Form Name:', 'sureforms' ), value: 'Basic Contact From' },
		{ label: __( 'User IP:', 'sureforms' ), value: '103.180.47.90' },
		{ label: __( 'URL:', 'sureforms' ), value: 'https//:www.areallyreallylongurlexample.com/premium/join/' },
		{ label: __( 'Browser:', 'sureforms' ), value: 'Test' },
		{ label: __( 'Type:', 'sureforms' ), value: 'Completed' },
		{ label: __( 'User:', 'sureforms' ), value: 'Aaditya' },
		{ label: __( 'Status:', 'sureforms' ), value: 'Read' },
		{ label: __( 'Submitted On:', 'sureforms' ), value: '2023-03-09 15:29:23' },
	];

	return (
		<div className="bg-background-primary border-0.5 border-solid border-border-subtle rounded-lg shadow-sm">
			<div className="pb-0 px-4 pt-4">
				<h3 className="text-sm font-semibold text-text-primary">
					{ __( 'Submission Info', 'sureforms' ) }
				</h3>
			</div>
			<div className="p-4 space-y-1 relative before:content-[''] before:block before:absolute before:inset-3 before:bg-background-secondary before:rounded-lg">
				{ infoFields.map( ( field, index ) => (
					<div
						key={ index }
						className="flex items-center py-3 px-3 relative bg-background-primary rounded-md shadow-sm"
					>
						<div className="w-32 flex-shrink-0">
							<span className="text-sm font-medium text-text-primary">
								{ field.label }
							</span>
						</div>
						<div className="flex-1">
							<span className="text-sm font-normal text-text-secondary">
								{ field.value }
							</span>
						</div>
					</div>
				) ) }
			</div>
		</div>
	);
};

export default SubmissionInfoSection;
