import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Button } from '@bsf/force-ui';
import { useUpdateEntriesReadStatus } from '../hooks/useEntriesQuery';

/**
 * SubmissionInfoSection Component
 * Displays submission metadata like entry ID, form name, etc.
 *
 * @param {Object} props
 * @param {Object} props.entryData - The entry data object
 */
const SubmissionInfoSection = ( { entryData } ) => {
	const [ status, setStatus ] = useState( entryData?.status || 'read' );
	const updateReadStatusMutation = useUpdateEntriesReadStatus();

	// Mock data for now - replace with actual entry data structure
	const infoFields = [
		{ id: 'entry', label: __( 'Entry:', 'sureforms' ), value: '#7' },
		{ id: 'form-name', label: __( 'Form Name:', 'sureforms' ), value: 'Basic Contact From' },
		{ id: 'user-ip', label: __( 'User IP:', 'sureforms' ), value: '103.180.47.90' },
		{ id: 'url', label: __( 'URL:', 'sureforms' ), value: 'https//:www.areallyreallylongurlexample.com/premium/join/' },
		{ id: 'browser', label: __( 'Browser:', 'sureforms' ), value: 'Test' },
		{ id: 'type', label: __( 'Type:', 'sureforms' ), value: 'Completed' },
		{ id: 'user', label: __( 'User:', 'sureforms' ), value: 'Aaditya' },
		{ id: 'status', label: __( 'Status:', 'sureforms' ), value: status.toLowerCase() },
		{ id: 'submitted-on', label: __( 'Submitted On:', 'sureforms' ), value: '2023-03-09 15:29:23' },
	];

	const handleMarkAsUnread = () => {
		console.log( 'Mark as Unread clicked', entryData );
		if ( entryData?.ID ) {
			updateReadStatusMutation.mutate(
				{
					entry_ids: [ entryData.ID ],
					action: 'unread',
				},
				{
					onSuccess: () => {
						setStatus( 'unread' );
					},
				}
			);
		}
	};

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
						<div className="flex-1 flex items-center justify-between">
							<span className="text-sm font-normal text-text-secondary capitalize">
								{ field.value }
							</span>
							{ field.id === 'status' && field.value === 'read' && (
								<Button
									variant="link"
									size="xs"
									onClick={ handleMarkAsUnread }
									disabled={ updateReadStatusMutation.isLoading }
									className="text-link-primary hover:text-link-primary-hover ml-2"
								>
									{ updateReadStatusMutation.isLoading
										? __( 'Updating…', 'sureforms' )
										: __( 'Mark as Unread', 'sureforms' ) }
								</Button>
							) }
						</div>
					</div>
				) ) }
			</div>
		</div>
	);
};

export default SubmissionInfoSection;
