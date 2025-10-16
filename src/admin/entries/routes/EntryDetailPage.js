import { useParams, Link, useNavigate, useLocation } from '@tanstack/react-router';
import { __, sprintf } from '@wordpress/i18n';
import { useMemo, useEffect } from '@wordpress/element';
import { Button, Text } from '@bsf/force-ui';
import { useEntryDetail, useUpdateEntriesReadStatus } from '../hooks/useEntriesQuery';
import EntryDataSection from '../components/EntryDataSection';
import SubmissionInfoSection from '../components/SubmissionInfoSection';
import NotesSection from '../components/NotesSection';
import EntryLogsSection from '../components/EntryLogsSection';
import EntryDetailSkeleton from '../components/EntryDetailSkeleton';
import { ArrowLeft } from 'lucide-react';
import UpgradeTooltip from '../components/UpgradeTooltip';
import { transformEntryDetail } from '../utils/entryHelpers';

/**
 * Button to send entry details via email
 *
 * @param {Object}   props                 - Component props
 * @param {Function} props.handleSendEmail - Function to handle sending email
 * @return {JSX.Element} SendDetailsButton component
 */

const SendDetailsButton = ( { handleSendEmail } ) => {
	return (
		<UpgradeTooltip
			heading={ __(
				'Unlock Resend Email Notification',
				'sureforms'
			) }
			content={ __(
				'With the SureForms Starter plan, you can effortlessly resend email notifications, ensuring your important updates reach their recipients with ease.',
				'sureforms'
			) }
			utmMedium="entry_resend_email_notification"
			placement="top"
		>
			<Button
				variant="primary"
				size="md"
				onClick={ handleSendEmail }
				disabled
			>
				{ __(
					'Send Details via Email',
					'sureforms'
				) }
			</Button>
		</UpgradeTooltip>
	);
};

/**
 * EntryDetailPage Component
 * Displays detailed view of a single entry
 */
const EntryDetailPage = () => {
	const { id } = useParams( { strict: false } );

	const location = useLocation( { from: '/entry/$id' } );
	const navigate = useNavigate();
	const { mutate: updateReadStatusMutation } = useUpdateEntriesReadStatus();

	// Fetch entry details
	const { data: rawEntryData, isLoading } = useEntryDetail( id );

	// Transform entry data
	const entryData = useMemo( () => {
		return transformEntryDetail( rawEntryData );
	}, [ rawEntryData ] );

	// Mark entry as read if "read" query param is present
	useEffect( () => {
		if ( ! location?.search?.read ) {
			return;
		}
		const newSearch = { ...location.search };
		delete newSearch.read;
		updateReadStatusMutation(
			{
				entry_ids: [ id ],
				action: 'read',
				skipToast: true,
			},
			{
				onSuccess: () => {
					navigate( { to: '/entry/$id', params: { id }, search: newSearch, replace: true } );
				},
			}
		);
	}, [ location?.search, id, updateReadStatusMutation, navigate ] );

	const handleEditEntry = () => {
		// TODO: Implement edit entry functionality
		console.log( 'Edit entry clicked' );
	};

	const handleSendEmail = () => {
		// TODO: Implement send email functionality
		console.log( 'Send email clicked' );
	};

	if ( isLoading ) {
		return <EntryDetailSkeleton />;
	}

	return (
		<div className="p-8 bg-background-secondary min-h-screen space-y-6">
			{ /* Header */ }
			<div className="flex items-center gap-3">
				<Button
					tag={ Link }
					to="/"
					variant="ghost"
					size="lg"
					className="p-2"
					icon={ <ArrowLeft /> }
				/>
				<Text size={ 36 } color="primary" weight={ 600 }>
					{ sprintf(
						// translators: %s is the entry ID
						__( 'Entry #%s', 'sureforms' ),
						id
					) }
				</Text>
			</div>
			<div className="max-w-[1374px] mx-auto">
				<div className="space-y-6">
					<div className="space-y-6">
						{ /* Main Content Grid */ }
						<div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
							{ /* Left Column */ }
							<div className="lg:col-span-2 space-y-6">
								<EntryDataSection
									entryData={ entryData }
									onEdit={ handleEditEntry }
								/>
								<SubmissionInfoSection
									entryData={ entryData }
								/>
							</div>

							{ /* Right Column */ }
							<div className="space-y-4">
								<NotesSection entryData={ entryData } />
								<EntryLogsSection entryData={ entryData } />
								{ /* Action buttons */ }
								<div className="ml-0.5">
									<SendDetailsButton
										handleSendEmail={
											handleSendEmail
										}
									/>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	);
};

export default EntryDetailPage;
