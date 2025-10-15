import { useParams, Link } from '@tanstack/react-router';
import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Button, Text } from '@bsf/force-ui';
import { useEntryDetail } from '../hooks/useEntriesQuery';
import EntryDataSection from '../components/EntryDataSection';
import SubmissionInfoSection from '../components/SubmissionInfoSection';
import NotesSection from '../components/NotesSection';
import EntryLogsSection from '../components/EntryLogsSection';
import { ArrowLeft } from 'lucide-react';
import UpgradeTooltip from '../components/UpgradeTooltip';

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
	const [ isLoading ] = useState( false );

	// Fetch entry details
	const { data: entryData, isLoading: isEntryLoading } = useEntryDetail( id );

	const handleEditEntry = () => {
		// TODO: Implement edit entry functionality
		console.log( 'Edit entry clicked' );
	};

	const handleSendEmail = () => {
		// TODO: Implement send email functionality
		console.log( 'Send email clicked' );
	};

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
				<div className="bg-white rounded-xl border-0.5 border-solid border-border-subtle shadow-sm p-6 space-y-6">
					{ isLoading || isEntryLoading ? (
						<div className="flex items-center justify-center py-12">
							<div className="animate-spin rounded-full h-8 w-8 border-b-2 border-brand-primary-600" />
						</div>
					) : (
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
					) }
				</div>
			</div>
		</div>
	);
};

export default EntryDetailPage;
