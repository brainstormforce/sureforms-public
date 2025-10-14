import { Button } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import { ArrowLeft, ArrowRight } from 'lucide-react';

/**
 * EntryLogsSection Component
 * Displays entry logs and activities
 *
 * @param {Object} props
 * @param {Object} props.entryData - The entry data object
 */
const EntryLogsSection = ( { entryData } ) => {
	// Mock data for now - replace with actual logs data
	const logs = [
		{
			id: 1,
			timestamp: '2023-03-09 15:29:24',
			action: 'EmailNotification (Email Sending Initiated)',
			details: 'Email Notification broadcasted to support@sureforms.com. Subject: Test the form',
		},
		{
			id: 2,
			timestamp: '2023-03-09 15:29:24',
			action: 'EmailNotification (Email Sending Initiated)',
			details: 'Email Notification broadcasted to support@sureforms.com. Subject: Test the form',
		},
	];

	return (
		<div className="bg-background-primary border-0.5 border-solid border-border-subtle rounded-lg shadow-sm">
			<div className="pb-0 px-4 pt-4">
				<h3 className="text-base font-semibold text-text-primary">
					{ __( 'Entry Logs', 'sureforms' ) }
				</h3>
			</div>
			<div className="p-4 space-y-1 relative before:content-[''] before:block before:absolute before:inset-3 before:bg-background-secondary before:rounded-lg">
				{ logs.map( ( log ) => (
					<div
						key={ log.id }
						className="bg-background-primary rounded-md p-3 relative shadow-sm"
					>
						<div className="flex items-start gap-4">
							<div className="flex-1 space-y-2">
								<div className="text-sm font-semibold text-text-primary">
									{ log.action } at { log.timestamp }
								</div>
								<div className="text-sm font-normal text-text-primary">
									{ log.details }
								</div>
							</div>
						</div>
					</div>
				) ) }
			</div>
			{ /* Pagination */ }
			<div className="w-full flex items-center justify-end space-x-2 pb-3 pr-3">
				<Button variant="ghost" size="xs" icon={ <ArrowLeft /> } iconPosition="left">
					{ __( 'Previous', 'sureforms' ) }
				</Button>
				<Button variant="ghost" size="xs" icon={ <ArrowRight /> } iconPosition="right">
					{ __( 'Next', 'sureforms' ) }
				</Button>
			</div>
		</div>
	);
};

export default EntryLogsSection;
