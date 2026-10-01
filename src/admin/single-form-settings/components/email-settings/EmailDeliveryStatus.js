import { __ } from '@wordpress/i18n';
import { Alert, Button, Container, Text } from '@bsf/force-ui';
import { AlertTriangle, CircleCheck } from 'lucide-react';

// States in which a mailer is known to carry wp_mail().
const OK_STATES = [ 'ready', 'other_mailer' ];

/**
 * One-line email delivery status above the notification list.
 *
 * Tells the owner whether the notifications configured here will be handed to
 * a real email service. Informational, so it stays under Distraction Free; the
 * only link is to SureMail itself when SureMail has a next step to offer.
 *
 * @return {JSX.Element|null} The status, or null when none was localised.
 */
const EmailDeliveryStatus = () => {
	const status =
		typeof srfm_block_data === 'undefined'
			? null
			: srfm_block_data?.email_delivery;

	if ( ! status?.state ) {
		return null;
	}

	const ok = OK_STATES.includes( status.state );
	const actionUrl = [
		'open_suremail',
		'view_logs',
		'verify_surecontact',
	].includes( status.action?.type )
		? status.action.url
		: '';
	// Verifying is the fix, but SureMail's logs still show the exact reason.
	const logsUrl =
		'verify_surecontact' === status.action?.type ? status.links?.logs : '';

	let message = status.message || '';
	if ( 'ready' === status.state && 'suremail' === status.mailer?.slug ) {
		message +=
			' ' +
			__(
				'SureMail sends from the address set on its connection, so the From address set in these notifications may be replaced.',
				'sureforms'
			);
	} else if ( 'not_configured' === status.state ) {
		message +=
			' ' +
			__(
				'You can set it up from the SureForms dashboard.',
				'sureforms'
			);
	}

	return (
		<Alert
			// Spaced off the notification table below, which it otherwise touches.
			className="mb-3"
			variant={ ok ? 'info' : 'warning' }
			title={ status.title }
			icon={ ok ? <CircleCheck /> : <AlertTriangle /> }
			content={
				<Container direction="column" gap="xs">
					<Text size={ 13 }>{ message }</Text>
					{ !! actionUrl && (
						<Button
							variant="link"
							size="xs"
							tag="a"
							href={ actionUrl }
							target="_blank"
							rel="noopener noreferrer"
							className="self-start [&>span]:px-0"
						>
							{ status.action.label }
						</Button>
					) }
					{ !! logsUrl && (
						<Button
							variant="link"
							size="xs"
							tag="a"
							href={ logsUrl }
							target="_blank"
							rel="noopener noreferrer"
							className="self-start [&>span]:px-0"
						>
							{ __( 'View SureMail logs', 'sureforms' ) }
						</Button>
					) }
				</Container>
			}
		/>
	);
};

export default EmailDeliveryStatus;
