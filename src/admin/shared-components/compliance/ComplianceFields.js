import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { Alert, Input, Switch, Label, Container, Text } from '@bsf/force-ui';
import { AlertTriangle } from 'lucide-react';

// States in which a mailer is known to be carrying wp_mail(). Anything else
// means a failed notification is likely, and on a form that stores no entries
// that failure loses the submission.
const EMAIL_DELIVERY_OK_STATES = [ 'ready', 'other_mailer' ];

/**
 * Email delivery status localised for this screen: srfm_block_data in the form
 * editor, srfm_admin on the global settings page.
 *
 * @return {Object|null} Status, or null when not provided.
 */
const getEmailDeliveryStatus = () =>
	window?.srfm_block_data?.email_delivery ||
	window?.srfm_admin?.email_delivery ||
	null;

/**
 * Debounced Number Input for Compliance
 * Maintains local state to prevent focus loss on parent re-renders.
 * Only syncs to parent on blur.
 * @param {Object}   root0
 * @param {*}        root0.value
 * @param {Function} root0.onChange
 * @param {string}   root0.context
 */
const DebouncedDaysInput = ( { value, onChange, context, ...props } ) => {
	const [ localValue, setLocalValue ] = useState( value );

	// Sync local state when external value changes.
	useEffect( () => {
		setLocalValue( value );
	}, [ value ] );

	const handleBlur = () => {
		let parsedValue = parseInt( localValue, 10 );

		// Ensure value is at least 1.
		if ( isNaN( parsedValue ) || parsedValue < 1 ) {
			parsedValue = 1;
			setLocalValue( context === 'form' ? '1' : 1 );
		}

		// Form context stores as string, global stores as number.
		const finalValue =
			context === 'form' ? parsedValue.toString() : parsedValue;
		onChange( finalValue );
	};

	return (
		<Input
			type="number"
			value={ localValue }
			onChange={ setLocalValue }
			onBlur={ handleBlur }
			{ ...props }
		/>
	);
};

/**
 * Shared Compliance Fields Component
 *
 * Context-aware component for rendering compliance settings (GDPR, auto-delete, etc.)
 * Can be used in both form-level and global settings contexts.
 *
 * @param {Object}   props
 * @param {string}   props.context  - 'form' | 'global' - determines data handling context
 * @param {Object}   props.values   - Current compliance values { gdpr, do_not_store_entries, auto_delete_entries, auto_delete_days }
 * @param {Function} props.onChange - Handler: (fieldKey, value) => void
 */
const ComplianceFields = ( { context, values, onChange } ) => {
	// Define the compliance switches configuration.
	// Conditional visibility based on current values.
	const switches = [
		{
			id: 'gdpr',
			label: {
				heading: __( 'Enable GDPR Compliance', 'sureforms' ),
				description: __(
					'When enabled this form will not store User IP, Browser Name and the Device Name in the Entries.',
					'sureforms'
				),
			},
		},
		{
			id: 'do_not_store_entries',
			label: {
				heading: __(
					'Never store entry data after form submission',
					'sureforms'
				),
				description: __(
					'When enabled this form will never store Entries.',
					'sureforms'
				),
			},
			condition: values?.gdpr,
		},
		{
			id: 'auto_delete_entries',
			label: {
				heading: __( 'Automatically delete entries', 'sureforms' ),
				description: __(
					'When enabled this form will automatically delete entries after a certain period of time.',
					'sureforms'
				),
			},
			condition: values?.gdpr && ! values?.do_not_store_entries,
		},
	];

	// With entries never stored, the notification email is the only copy of each
	// submission. Say so, and say it louder when email delivery is not set up.
	const neverStores = !! ( values?.gdpr && values?.do_not_store_entries );
	const emailDelivery = neverStores ? getEmailDeliveryStatus() : null;
	const emailAtRisk =
		!! emailDelivery?.state &&
		! EMAIL_DELIVERY_OK_STATES.includes( emailDelivery.state );

	// Determine if auto_delete_days input should be shown.
	const showAutoDeleteDays =
		values?.auto_delete_entries &&
		! values?.do_not_store_entries &&
		values?.gdpr;

	// On the global settings page this is the default for new forms, not one
	// form, so the wording says so.
	const isGlobal = 'global' === context;
	const deliveryTitle = emailDelivery?.title || '';
	let neverStoreWarning;
	if ( emailAtRisk ) {
		neverStoreWarning = isGlobal
			? sprintf(
				/* translators: %s: current email delivery status, e.g. "Email delivery is not set up". */
				__(
					'New forms will not store entries by default, so if a notification email is not sent, that submission is gone. Email delivery on this site needs attention first: %s.',
					'sureforms'
				),
				deliveryTitle
			  )
			: sprintf(
				/* translators: %s: current email delivery status, e.g. "Email delivery is not set up". */
				__(
					'This form will not store entries, so if a notification email is not sent, that submission is gone. Email delivery on this site needs attention first: %s.',
					'sureforms'
				),
				deliveryTitle
			  );
	} else {
		neverStoreWarning = isGlobal
			? __(
				'New forms will not store entries by default. If a notification email ever fails to send, that submission cannot be recovered.',
				'sureforms'
			  )
			: __(
				'This form will not store entries. If a notification email ever fails to send, that submission cannot be recovered.',
				'sureforms'
			  );
	}

	return (
		<Container direction="column" className="gap-6">
			{ switches.map(
				( { id, label, condition = true } ) =>
					condition && (
						<Switch
							key={ id }
							label={ label }
							value={ values?.[ id ] ?? false }
							onChange={ ( val ) => onChange( id, val ) }
						/>
					)
			) }
			{ neverStores && (
				<Alert
					variant={ emailAtRisk ? 'warning' : 'info' }
					title={
						emailAtRisk
							? __(
								'Submissions may be lost if an email fails',
								'sureforms'
							  )
							: __(
								'Email is the only copy of each submission',
								'sureforms'
							  )
					}
					content={
						<Text size={ 13 }>
							{ neverStoreWarning }
						</Text>
					}
					icon={ <AlertTriangle /> }
				/>
			) }
			{ showAutoDeleteDays && (
				<Container direction="column" className="gap-1.5">
					<DebouncedDaysInput
						aria-label={ __(
							'Entries older than the selected days will be deleted.',
							'sureforms'
						) }
						size="md"
						value={
							values?.auto_delete_days ??
							( context === 'form' ? '30' : 30 )
						}
						label={ __( 'Entries Time Period', 'sureforms' ) }
						context={ context }
						onChange={ ( value ) =>
							onChange( 'auto_delete_days', value )
						}
					/>
					<Container gap="0" align="center">
						<Label tag="p" size="sm" variant="help">
							{ __(
								'Entries older than the days set will be deleted automatically.',
								'sureforms'
							) }
						</Label>
					</Container>
				</Container>
			) }
		</Container>
	);
};

export default ComplianceFields;
