import {
	useParams,
	Link,
	useNavigate,
	useLocation,
} from '@tanstack/react-router';
import { __, sprintf } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { useMemo, useEffect, useState } from '@wordpress/element';
import { Button, Text } from '@bsf/force-ui';
import {
	useEntryDetail,
	useUpdateEntriesReadStatus,
} from '../hooks/useEntriesQuery';
import EntryDataSection from '../components/EntryDataSection';
import SubmissionInfoSection from '../components/SubmissionInfoSection';
import NotesSection from '../components/NotesSection';
import EntryLogsSection from '../components/EntryLogsSection';
import EntryDetailSkeleton from '../components/EntryDetailSkeleton';
import ConfirmationDialog from '../components/ConfirmationDialog';
import { ArrowLeft } from 'lucide-react';
import UpgradeTooltip from '../components/UpgradeTooltip';
import { transformEntryDetail } from '../utils/entryHelpers';

/**
 * Button to send entry details via email
 *
 * @param {Object}   props                 - Component props
 * @param {Function} props.handleSendEmail - Function to handle sending email
 * @param {boolean}  props.isDisabled      - Whether the button is disabled
 * @return {JSX.Element} SendDetailsButton component
 */
const SendDetailsButton = ( { handleSendEmail, isDisabled = true } ) => {
	// If component is available (pro version), render without tooltip
	if ( ! isDisabled ) {
		return (
			<Button variant="primary" size="md" onClick={ handleSendEmail }>
				{ __( 'Resend Notification', 'sureforms' ) }
			</Button>
		);
	}

	// If component is not available (free version), render with upgrade tooltip
	return (
		<UpgradeTooltip
			heading={ __( 'Unlock Resend Email Notification', 'sureforms' ) }
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
				{ __( 'Resend Notification', 'sureforms' ) }
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

	// State for confirmation dialog
	const [ confirmationDialog, setConfirmationDialog ] = useState( {
		open: false,
		title: '',
		description: '',
		confirmLabel: '',
		onConfirm: null,
		isLoading: false,
		destructive: true,
	} );

	// State for resend notification modal
	const [ openResendNotificationModal, setOpenResendNotificationModal ] =
		useState( false );

	// Fetch entry details
	const { data: rawEntryData, isLoading } = useEntryDetail( id );

	// Transform entry data
	const entryData = useMemo( () => {
		return transformEntryDetail( rawEntryData );
	}, [ rawEntryData ] );

	// Get ResendNotificationModal component from filter (pro feature)
	const ResendNotificationModal = applyFilters(
		'srfm-pro.entry-details.render-resend-notification-modal'
	);

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
					navigate( {
						to: '/entry/$id',
						params: { id },
						search: newSearch,
						replace: true,
					} );
				},
			}
		);
	}, [ location?.search, id, updateReadStatusMutation, navigate ] );

	const handleEditEntry = () => {
		// TODO: Implement edit entry functionality
		console.log( 'Edit entry clicked' );
	};

	const handleSendEmail = () => {
		if ( ! ResendNotificationModal ) {
			return;
		}

		setOpenResendNotificationModal( true );
	};

	/**
	 * Handler function for triggering confirmation dialogs from child components
	 *
	 * @param {Object}   config              - Configuration object
	 * @param {string}   config.title        - Dialog title
	 * @param {string}   config.description  - Dialog description
	 * @param {string}   config.confirmLabel - Confirm button label
	 * @param {Function} config.onConfirm    - Function to call on confirm
	 * @param {boolean}  config.isLoading    - Whether action is loading
	 * @param {boolean}  config.destructive  - Whether action is destructive
	 */
	const handleConfirmation = ( config ) => {
		setConfirmationDialog( {
			open: true,
			title: config.title,
			description: config.description,
			confirmLabel: config.confirmLabel,
			onConfirm: config.onConfirm,
			isLoading: config.isLoading || false,
			destructive: config.destructive !== false, // Default to true
		} );
	};

	if ( isLoading ) {
		return <EntryDetailSkeleton />;
	}

	return (
		<>
			<div className="p-8 bg-background-secondary min-h-screen space-y-6">
				{ /* Header */ }
				<div className="flex items-center gap-3 max-w-[1374px] mx-auto">
					<Button
						tag={ Link }
						to="/"
						variant="ghost"
						size="md"
						className="p-1"
						icon={ <ArrowLeft /> }
					/>
					<Text size={ 24 } color="primary" weight={ 600 }>
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
									<NotesSection entryId={ id } />
									<EntryLogsSection
										entryId={ id }
										onConfirmation={ handleConfirmation }
									/>
									{ /* Action buttons */ }
									<div className="ml-0.5">
										<SendDetailsButton
											handleSendEmail={ handleSendEmail }
											isDisabled={
												! ResendNotificationModal
											}
										/>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<ConfirmationDialog
				open={ confirmationDialog.open }
				setOpen={ ( open ) =>
					setConfirmationDialog( ( prev ) => ( { ...prev, open } ) )
				}
				onConfirm={ confirmationDialog.onConfirm }
				title={ confirmationDialog.title }
				description={ confirmationDialog.description }
				confirmLabel={ confirmationDialog.confirmLabel }
				isLoading={ confirmationDialog.isLoading }
				destructive={ confirmationDialog.destructive }
			/>

			{ /* Render ResendNotificationModal if available */ }
			{ !! ResendNotificationModal && entryData?.formId && (
				<ResendNotificationModal
					open={ openResendNotificationModal }
					setOpen={ setOpenResendNotificationModal }
					entryIds={ [ parseInt( id, 10 ) ] }
					formId={ entryData.formId }
				/>
			) }
		</>
	);
};

export default EntryDetailPage;
