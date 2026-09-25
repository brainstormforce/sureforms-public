import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { applyFilters } from '@wordpress/hooks';
import { Button, Input, Loader, Select, Switch, toast } from '@bsf/force-ui';
import ContentSection from '../components/ContentSection';
import LoadingSkeleton from '@Admin/components/LoadingSkeleton';

const days = [
	{ label: __( 'Monday', 'sureforms' ), value: 'Monday' },
	{ label: __( 'Tuesday', 'sureforms' ), value: 'Tuesday' },
	{ label: __( 'Wednesday', 'sureforms' ), value: 'Wednesday' },
	{ label: __( 'Thursday', 'sureforms' ), value: 'Thursday' },
	{ label: __( 'Friday', 'sureforms' ), value: 'Friday' },
	{ label: __( 'Saturday', 'sureforms' ), value: 'Saturday' },
	{ label: __( 'Sunday', 'sureforms' ), value: 'Sunday' },
];

/**
 * Email Summaries settings section.
 *
 * @param {Object}   props
 * @param {Object}   props.emailTabOptions      - Email summary settings.
 * @param {Function} props.updateGlobalSettings - Settings update handler.
 */
const EmailSummariesContent = ( { emailTabOptions, updateGlobalSettings } ) => {
	const [ sendingTestEmail, setSendingTestEmail ] = useState( false );

	const getReportsLabel = ( value ) => {
		const selectedDay = days.find( ( day ) => day.value === value );
		return selectedDay ? selectedDay.label : '';
	};

	return (
		<>
			<Switch
				label={ {
					heading: __( 'Enable email summaries', 'sureforms' ),
					description: __(
						'Receive a scheduled digest of form submissions straight to your inbox.',
						'sureforms'
					),
				} }
				value={ emailTabOptions.srfm_email_summary }
				onChange={ ( value ) =>
					updateGlobalSettings(
						'srfm_email_summary',
						value,
						'email-settings'
					)
				}
			/>
			{ emailTabOptions.srfm_email_summary && (
				<>
					<div className="flex items-end gap-2">
						<div className="flex-1">
							<Input
								size="md"
								label={ __( 'Send Email To', 'sureforms' ) }
								type="email"
								value={ emailTabOptions.srfm_email_sent_to }
								onChange={ ( value ) =>
									updateGlobalSettings(
										'srfm_email_sent_to',
										value,
										'email-settings'
									)
								}
								required
								autoComplete="off"
							/>
						</div>
						<Button
							variant="outline"
							size="md"
							icon={ sendingTestEmail && <Loader /> }
							iconPosition="left"
							onClick={ async () => {
								if ( sendingTestEmail ) {
									return;
								}

								setSendingTestEmail( true );
								try {
									await apiFetch( {
										path: '/sureforms/v1/send-test-email-summary',
										method: 'POST',
										data: {
											srfm_email_sent_to:
												emailTabOptions.srfm_email_sent_to,
										},
									} ).then( ( response ) => {
										setSendingTestEmail( false );
										toast.success( response?.data );
									} );
								} catch ( error ) {
									setSendingTestEmail( false );
									toast.error( error?.data );
									console.error(
										'Error Sending Test Email Summary:',
										error
									);
								}
							} }
							className="bg-background-secondary"
						>
							{ __( 'Test Email', 'sureforms' ) }
						</Button>
					</div>
					<Select
						value={ getReportsLabel(
							emailTabOptions.srfm_schedule_report
						) }
						onChange={ ( value ) =>
							updateGlobalSettings(
								'srfm_schedule_report',
								value,
								'email-settings'
							)
						}
					>
						<Select.Button
							type="button"
							label={ __( 'Schedule Reports', 'sureforms' ) }
						/>
						<Select.Portal id="srfm-settings-container">
							<Select.Options>
								{ days.map( ( day ) => (
									<Select.Option
										key={ day.value }
										value={ day.value }
									>
										{ day.label }
									</Select.Option>
								) ) }
							</Select.Options>
						</Select.Portal>
					</Select>
				</>
			) }
		</>
	);
};

/**
 * IP Logging settings section.
 *
 * @param {Object}   props
 * @param {Object}   props.generalTabOptions    - General settings.
 * @param {Function} props.updateGlobalSettings - Settings update handler.
 */
const IPLoggingContent = ( { generalTabOptions, updateGlobalSettings } ) => {
	return (
		<>
			<Switch
				label={ {
					heading: __( 'Enable IP logging', 'sureforms' ),
					description: __(
						"If this option is turned on, the user's IP address will be saved with the form data",
						'sureforms'
					),
				} }
				value={ generalTabOptions.srfm_ip_log }
				onChange={ ( value ) =>
					updateGlobalSettings(
						'srfm_ip_log',
						value,
						'general-settings'
					)
				}
			/>
		</>
	);
};

/**
 * Admin Notification settings section.
 *
 * @param {Object}   props
 * @param {Object}   props.generalTabOptions    - General settings.
 * @param {Function} props.updateGlobalSettings - Settings update handler.
 * @param {boolean}  props.showLearnTip         - Whether to show the Learn section guidance tooltip.
 */
const AdminNotificationContent = ( {
	generalTabOptions,
	updateGlobalSettings,
	showLearnTip,
} ) => {
	return (
		<div className="relative">
			<Switch
				label={ {
					heading: __( 'Enable Admin Notification', 'sureforms' ),
					description: __(
						'Admin notifications keep you informed about new form entries since your last visit.',
						'sureforms'
					),
				} }
				value={ generalTabOptions.srfm_admin_notification }
				onChange={ ( value ) =>
					updateGlobalSettings(
						'srfm_admin_notification',
						value,
						'general-settings'
					)
				}
			/>
			{ showLearnTip && (
				<div className="absolute top-full left-1/3 -translate-x-1/2 mt-2 z-[999999] pointer-events-none">
					<div className="absolute -top-[5px] left-1/2 -translate-x-1/2 w-2.5 h-2.5 bg-[#1e1e1e] rotate-45" />
					<div className="bg-[#1e1e1e] text-white text-sm px-3 py-1.5 rounded-md shadow-md whitespace-nowrap">
						{ __(
							'Turn on Admin Notification from here.',
							'sureforms'
						) }
					</div>
				</div>
			) }
		</div>
	);
};

/**
 * Form Views & Conversion tracking settings section.
 *
 * @param {Object}   props
 * @param {Object}   props.generalTabOptions    - General settings.
 * @param {Function} props.updateGlobalSettings - Settings update handler.
 */
const FormViewsTrackingContent = ( {
	generalTabOptions,
	updateGlobalSettings,
} ) => {
	return (
		<Switch
			label={ {
				heading: __(
					'Show views and conversion rate',
					'sureforms'
				),
				description: (
					// One flex item, not two. force-ui's Label carries `flex
					// items-center gap-0.5` in its base classes, and the className
					// Switch passes it has no display utility to override that --
					// so two sibling spans become flex items side by side, not
					// stacked, however they are displayed themselves. The wrapper
					// is the single item; its children stack as ordinary blocks.
					<span className="block">
						<span className="block mt-1">
							{ __(
								'Views and submissions by anyone who can edit the site are normally left out, so testing your own forms does not change these figures.',
								'sureforms'
							) }
						</span>
						<span className="block mt-1">
							<a
								href="https://sureforms.com/docs/show-views-and-conversion-rate/"
								target="_blank"
								rel="noopener noreferrer"
								className="text-field-helper"
							>
								{ __( 'Learn More', 'sureforms' ) }
							</a>
						</span>
					</span>
				),
			} }
			value={ generalTabOptions.srfm_form_views_tracking }
			onChange={ ( value ) =>
				updateGlobalSettings(
					'srfm_form_views_tracking',
					value,
					'general-settings'
				)
			}
		/>
	);
};

/**
 * Usage Tracking / Analytics settings section.
 *
 * @param {Object}   props
 * @param {Object}   props.generalTabOptions    - General settings.
 * @param {Function} props.updateGlobalSettings - Settings update handler.
 */
const UsageTrackingContent = ( {
	generalTabOptions,
	updateGlobalSettings,
} ) => {
	return (
		<Switch
			label={ {
				heading: __( 'Help shape the future of SureForms', 'sureforms' ),
				description: (
					<>
						<p>
							{ __(
								'Collect non-sensitive information from your website, such as the PHP version and features used, to help us fix bugs faster, make smarter decisions, and build features that actually matter to you. ',
								'sureforms'
							) }
							<a
								href="https://sureforms.com/share-usage-data/"
								target="_blank"
								rel="noopener noreferrer"
								className="text-field-helper"
							>
								{ __( 'Learn More', 'sureforms' ) }
							</a>
						</p>
					</>
				),
			} }
			value={ generalTabOptions.srfm_bsf_analytics }
			onChange={ ( value ) =>
				updateGlobalSettings(
					'srfm_bsf_analytics',
					value,
					'general-settings'
				)
			}
		/>
	);
};

/**
 * Debug logging settings section.
 *
 * @param {Object}   props
 * @param {Object}   props.generalTabOptions    - General settings.
 * @param {Function} props.updateGlobalSettings - Settings update handler.
 * @param {Object}   props.logMeta              - Server-reported log size and expiry.
 * @param {Function} props.setLogMeta           - Updates the reported log status.
 */
// Mirrors Client_Logger::MAX_FILE_SIZE. Once the file is full it stops accepting
// lines rather than evicting the repro someone is trying to capture, so the UI has
// to say so — silently dropping new entries is the one bad outcome here.
const MAX_LOG_SIZE = 1048576;

const LogsContent = ( {
	generalTabOptions,
	updateGlobalSettings,
	logMeta,
	setLogMeta,
} ) => {
	const [ clearing, setClearing ] = useState( false );
	const [ confirmingClear, setConfirmingClear ] = useState( false );

	const nonce = window?.srfm_admin?.client_logs_nonce ?? '';
	const ajaxUrl = window?.srfm_admin?.ajax_url ?? '';
	const downloadUrl = `${ ajaxUrl }?action=srfm_download_logs&_wpnonce=${ nonce }`;

	const formatSize = ( bytes ) => {
		if ( bytes < 1024 ) {
			return `${ bytes } B`;
		}
		if ( bytes < 1048576 ) {
			return `${ Math.round( bytes / 1024 ) } KB`;
		}
		return `${ ( bytes / 1048576 ).toFixed( 1 ) } MB`;
	};

	const handleClear = async () => {
		if ( clearing ) {
			return;
		}

		// Two-step rather than a native confirm(): this deletes the only evidence
		// anyone has during an active investigation, and a blocking dialog in
		// wp-admin is worse than an inline second click.
		if ( ! confirmingClear ) {
			setConfirmingClear( true );
			return;
		}

		setConfirmingClear( false );
		setClearing( true );
		try {
			const response = await fetch(
				`${ ajaxUrl }?action=srfm_clear_logs&_wpnonce=${ nonce }`,
				{ method: 'POST', credentials: 'same-origin' }
			);

			// fetch only rejects on a network failure, so a 403 from a stale nonce
			// or a 500 resolves normally. Without this the toast said the log was
			// cleared and the size was zeroed -- hiding the row -- while the file
			// was still on disk.
			if ( ! response.ok ) {
				throw new Error( `HTTP ${ response.status }` );
			}

			setLogMeta( { ...logMeta, size: 0 } );
			toast.success( __( 'Log cleared.', 'sureforms' ) );
		} catch ( error ) {
			toast.error( __( 'Could not clear the log.', 'sureforms' ) );
		} finally {
			setClearing( false );
		}
	};

	return (
		<>
			<Switch
				label={ {
					heading: __( 'Enable logs', 'sureforms' ),
					description: __(
						'Records form submission failures reported by the browser, so you can send the log to support instead of reading the console. Nothing is written while your forms are working, and submitted values are never stored.',
						'sureforms'
					),
				} }
				value={ generalTabOptions.srfm_enable_logs }
				onChange={ ( value ) =>
					updateGlobalSettings(
						'srfm_enable_logs',
						value,
						'general-settings'
					)
				}
			/>
			{ generalTabOptions.srfm_enable_logs && (
				<div className="flex items-center gap-3">
					<Button
						variant="outline"
						size="md"
						tag="a"
						href={ downloadUrl }
						className="bg-background-secondary no-underline hover:no-underline"
					>
						{ __( 'Download log', 'sureforms' ) }
					</Button>
					<Button
						variant="ghost"
						size="md"
						onClick={ handleClear }
						disabled={ clearing }
						aria-busy={ clearing }
						icon={ clearing && <Loader /> }
						iconPosition="left"
						{ ...( confirmingClear && {
							destructive: true,
						} ) }
					>
						{ confirmingClear
							? __( 'Confirm delete', 'sureforms' )
							: __( 'Clear', 'sureforms' ) }
					</Button>
					{ confirmingClear && ! clearing && (
						<Button
							variant="ghost"
							size="md"
							onClick={ () => setConfirmingClear( false ) }
						>
							{ __( 'Cancel', 'sureforms' ) }
						</Button>
					) }
					{ logMeta?.size >= MAX_LOG_SIZE && (
						<span className="text-sm text-support-error">
							{ __(
								'Log is full — download and clear it to keep recording.',
								'sureforms'
							) }
						</span>
					) }
					{ logMeta?.size > 0 && logMeta.size < MAX_LOG_SIZE && (
						<span className="text-sm text-text-secondary">
							{ formatSize( logMeta.size ) }
						</span>
					) }
				</div>
			) }
		</>
	);
};

const GeneralPage = ( {
	loading,
	generalTabOptions,
	emailTabOptions,
	updateGlobalSettings,
	logMeta,
	setLogMeta,
} ) => {
	// Detect if user arrived from the Learn section (email-notification lesson).
	const [ isLearnSource ] = useState(
		() =>
			new URLSearchParams( window.location.search ).get( 'source' ) ===
			'learn'
	);
	const [ showLearnTip, setShowLearnTip ] = useState( false );
	// Section keys an add-on asked to hide ('logs', 'analytics'), set through
	// the additionalSections slot. SureForms Pro's Distraction Free uses it.
	const [ hiddenSections, setHiddenSections ] = useState( [] );

	useEffect( () => {
		if ( ! isLearnSource ) {
			return;
		}
		const showTimer = setTimeout( () => setShowLearnTip( true ), 300 );
		const hideTimer = setTimeout( () => setShowLearnTip( false ), 5300 );
		return () => {
			clearTimeout( showTimer );
			clearTimeout( hideTimer );
		};
	}, [ isLearnSource ] );

	if ( loading ) {
		return (
			<div className="space-y-6">
				<LoadingSkeleton count={ 6 } className="h-6 rounded-sm" />
			</div>
		);
	}

	return (
		<div className="space-y-6">
			<ContentSection
				loading={ loading }
				title={ __( 'Email Summaries', 'sureforms' ) }
				content={
					<EmailSummariesContent
						emailTabOptions={ emailTabOptions }
						updateGlobalSettings={ updateGlobalSettings }
					/>
				}
			/>
			<ContentSection
				loading={ loading }
				title={ __( 'IP Logging', 'sureforms' ) }
				content={
					<IPLoggingContent
						generalTabOptions={ generalTabOptions }
						updateGlobalSettings={ updateGlobalSettings }
					/>
				}
			/>
			<ContentSection
				loading={ loading }
				title={ __( 'Admin Notification', 'sureforms' ) }
				content={
					<AdminNotificationContent
						generalTabOptions={ generalTabOptions }
						updateGlobalSettings={ updateGlobalSettings }
						showLearnTip={ showLearnTip }
					/>
				}
			/>
			<ContentSection
				loading={ loading }
				title={ __( 'Form Views & Conversion', 'sureforms' ) }
				content={
					<FormViewsTrackingContent
						generalTabOptions={ generalTabOptions }
						updateGlobalSettings={ updateGlobalSettings }
					/>
				}
			/>
			{ /* Extension slot for add-on sections, such as SureForms Pro's
			     Distraction Free. Renders nothing on its own.
			     setHiddenSections lets it hide the Logs and Analytics sections. */ }
			{ applyFilters(
				'srfm.settings.general.additionalSections',
				null,
				{ loading, setHiddenSections }
			) }
			{ ! hiddenSections.includes( 'logs' ) && (
				<ContentSection
					loading={ loading }
					title={ __( 'Logs', 'sureforms' ) }
					content={
						<LogsContent
							generalTabOptions={ generalTabOptions }
							updateGlobalSettings={ updateGlobalSettings }
							logMeta={ logMeta }
							setLogMeta={ setLogMeta }
						/>
					}
				/>
			) }
			{ /* Everything above is something SureForms does for this site.
			     Analytics is the one setting that sends anything outward, so it
			     sits last, behind a rule, rather than reading as one more form
			     setting. */ }
			{ ! hiddenSections.includes( 'analytics' ) && (
				<>
					<hr className="w-full m-0 border-0 border-t border-solid border-border-subtle" />
					<ContentSection
						loading={ loading }
						title={ __( 'Anonymous Analytics', 'sureforms' ) }
						content={
							<UsageTrackingContent
								generalTabOptions={ generalTabOptions }
								updateGlobalSettings={ updateGlobalSettings }
							/>
						}
					/>
				</>
			) }
		</div>
	);
};

export default GeneralPage;
