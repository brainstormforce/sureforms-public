import { useParams, Link } from '@tanstack/react-router';
import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Button } from '@bsf/force-ui';
import { useEntryDetail } from '../hooks/useEntriesQuery';
import EntryDataSection from '../components/EntryDataSection';
import SubmissionInfoSection from '../components/SubmissionInfoSection';
import NotesSection from '../components/NotesSection';
import EntryLogsSection from '../components/EntryLogsSection';

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
		<div className="p-8 bg-background-secondary min-h-screen">
			<div className="max-w-[1374px] mx-auto">
				<div className="bg-white rounded-xl border-0.5 border-solid border-border-subtle shadow-sm p-6 space-y-6">
					{ /* Header */ }
					<div className="flex items-center gap-4 pb-4">
						<Link
							to="/"
							className="inline-flex items-center gap-2 text-text-secondary hover:text-text-primary transition-colors"
						>
							<svg
								className="w-5 h-5"
								fill="none"
								stroke="currentColor"
								viewBox="0 0 24 24"
							>
								<path
									strokeLinecap="round"
									strokeLinejoin="round"
									strokeWidth={ 2 }
									d="M10 19l-7-7m0 0l7-7m-7 7h18"
								/>
							</svg>
							{ __( 'Back to Entries', 'sureforms' ) }
						</Link>
						<h1 className="text-2xl font-semibold text-text-primary">
							{ sprintf(
								// translators: %s is the entry ID
								__( 'Entry #%s', 'sureforms' ),
								id
							) }
						</h1>
					</div>

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
										<Button
											variant="primary"
											size="md"
											onClick={ handleSendEmail }
										>
											{ __( 'Send Details via Email', 'sureforms' ) }
										</Button>
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
