import { __ } from '@wordpress/i18n';
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
	const updateReadStatusMutation = useUpdateEntriesReadStatus();

	// Build info fields from actual entry data
	const infoFields = [
		{
			id: 'entry',
			label: __( 'Entry:', 'sureforms' ),
			value: entryData?.id ? `#${ entryData.id }` : '-',
		},
		{
			id: 'form-name',
			label: __( 'Form Name:', 'sureforms' ),
			value: entryData?.formName || '-',
		},
		{
			id: 'user-ip',
			label: __( 'User IP:', 'sureforms' ),
			value: entryData?.submissionInfo?.userIp || '-',
		},
		{
			id: 'url',
			label: __( 'URL:', 'sureforms' ),
			value: entryData?.formPermalink || '-',
			render: ( val ) => (
				<Button
					variant="link"
					tag="a"
					href={ val }
					className="no-underline hover:underline"
					target="_blank"
					rel="noopener noreferrer"
				>
					{ val }
				</Button>
			),
		},
		{
			id: 'browser',
			label: __( 'Browser:', 'sureforms' ),
			value: entryData?.submissionInfo?.browserName || '-',
		},
		{
			id: 'device',
			label: __( 'Device:', 'sureforms' ),
			value: entryData?.submissionInfo?.deviceName || '-',
		},
		{
			id: 'user',
			label: __( 'User:', 'sureforms' ),
			value: entryData?.user?.displayName || '-',
			render: ( val ) =>
				entryData?.user?.profileUrl ? (
					<Button
						variant="link"
						tag="a"
						href={ entryData.user.profileUrl }
						target="_blank"
						className="no-underline hover:underline"
						rel="noopener noreferrer"
					>
						{ val }
					</Button>
				) : (
					val
				),
		},
		{
			id: 'status',
			label: __( 'Status:', 'sureforms' ),
			value: entryData.status.toLowerCase(),
			render: ( val ) => val.charAt( 0 ).toUpperCase() + val.slice( 1 ),
		},
		{
			id: 'created_at',
			label: __( 'Submitted On:', 'sureforms' ),
			value: entryData?.formattedDateTime || '-',
		},
	];

	const handleMarkAsUnread = () => {
		if ( entryData?.id ) {
			updateReadStatusMutation.mutate(
				{
					entry_ids: [ entryData.id ],
					action: 'unread',
				},
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
							<span className="text-sm font-normal text-text-secondary">
								{ typeof field?.render === 'function'
									? field.render( field.value )
									: field.value }
							</span>
							{ field.id === 'status' &&
								field.value === 'read' && (
								<Button
									variant="link"
									size="xs"
									onClick={ handleMarkAsUnread }
									disabled={
										updateReadStatusMutation.isLoading
									}
									className="text-link-primary hover:text-link-primary-hover ml-2"
								>
									{ updateReadStatusMutation.isLoading
										? __( 'Updating…', 'sureforms' )
										: __(
											'Mark as Unread',
											'sureforms'
											  ) }
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
