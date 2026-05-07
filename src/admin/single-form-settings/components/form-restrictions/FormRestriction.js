import { __ } from '@wordpress/i18n';
import {
	useContext,
	createInterpolateElement,
	isValidElement,
	useMemo,
	useEffect,
} from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { FormRestrictionContext } from './context';
import {
	Container,
	Input,
	Title,
	Switch,
	Label,
	TextArea,
	Tooltip,
	toast,
} from '@bsf/force-ui';
import { Info } from 'lucide-react';
import TabContentWrapper from '@Components/tab-content-wrapper';
import DatePickerModal from '@Components/force-ui-components/DatePickerModal';
import TimePicker from '@Components/force-ui-components/TimePicker';
import { applyFilters } from '@wordpress/hooks';

const FormRestriction = ( { setHasValidationErrors } ) => {
	const { updateMeta, preserveMetaData } = useContext(
		FormRestrictionContext
	);

	// Read compliance meta directly — it lives in a separate post-meta key
	// (`_srfm_compliance`) outside FormRestrictionContext. When GDPR + Never
	// Store are both on, `Form_Submit` returns before inserting an entry
	// (inc/form-submit.php:538), so the entries-table-driven cap (and any
	// recurring extension that hooks `srfm_form_restriction_entries_count`)
	// has no row to count and silently does not enforce.
	const isNeverStoreEntriesEnabled = useSelect( ( select ) => {
		const meta = select( 'core/editor' ).getEditedPostAttribute( 'meta' );
		const compliance = Array.isArray( meta?._srfm_compliance )
			? meta._srfm_compliance[ 0 ]
			: null;
		return !! ( compliance?.gdpr && compliance?.do_not_store_entries );
	}, [] );

	// Validate that start date/time is before end date/time
	const dateTimeValidationError = useMemo( () => {
		// Only validate if scheduling is enabled and both dates are set
		if (
			! preserveMetaData?.schedulingStatus ||
			! preserveMetaData?.startDate ||
			! preserveMetaData?.date
		) {
			return null;
		}

		// Helper function to convert date string and time to Date object
		const createDateTime = ( dateStr, hours, minutes, meridiem ) => {
			if ( ! dateStr ) {
				return null;
			}

			// Parse the date string (assuming format: YYYY-MM-DD or MM/DD/YYYY)
			const date = new Date( dateStr );
			if ( isNaN( date.getTime() ) ) {
				return null;
			}

			// Convert 12-hour format to 24-hour format
			let hour24 = parseInt( hours, 10 );
			if ( meridiem === 'PM' && hour24 !== 12 ) {
				hour24 += 12;
			} else if ( meridiem === 'AM' && hour24 === 12 ) {
				hour24 = 0;
			}

			date.setHours( hour24, parseInt( minutes, 10 ), 0, 0 );
			return date;
		};

		const startDateTime = createDateTime(
			preserveMetaData.startDate,
			preserveMetaData.startHours ?? '12',
			preserveMetaData.startMinutes ?? '00',
			preserveMetaData.startMeridiem ?? 'AM'
		);

		const endDateTime = createDateTime(
			preserveMetaData.date,
			preserveMetaData.hours ?? '12',
			preserveMetaData.minutes ?? '00',
			preserveMetaData.meridiem ?? 'PM'
		);

		// Validate both dates were created successfully
		if ( ! startDateTime || ! endDateTime ) {
			return null;
		}

		// Check if start is before end
		if ( startDateTime >= endDateTime ) {
			return __(
				'The start date and time must be before the end date and time.',
				'sureforms'
			);
		}

		return null;
	}, [
		preserveMetaData?.schedulingStatus,
		preserveMetaData?.startDate,
		preserveMetaData?.startHours,
		preserveMetaData?.startMinutes,
		preserveMetaData?.startMeridiem,
		preserveMetaData?.date,
		preserveMetaData?.hours,
		preserveMetaData?.minutes,
		preserveMetaData?.meridiem,
	] );

	// Update parent component's validation state and show toast
	useEffect( () => {
		const hasError = !! dateTimeValidationError;

		// Update parent component's validation state
		if ( setHasValidationErrors ) {
			setHasValidationErrors( hasError );
		}

		// Show toast notification when validation error occurs
		if ( hasError ) {
			toast.error( dateTimeValidationError, {
				duration: 4000,
			} );
		}
	}, [ dateTimeValidationError, setHasValidationErrors ] );

	// Apply filter and sanitize for security
	let additionalSettings = applyFilters(
		'srfm_form_restriction_additional_settings',
		[]
	);

	// Lightweight security validation for admin context
	if ( ! Array.isArray( additionalSettings ) ) {
		additionalSettings = [];
	} else {
		additionalSettings = additionalSettings.filter( ( setting ) => {
			// Basic validation: must be object with valid component
			return (
				setting &&
				typeof setting === 'object' &&
				setting.component &&
				isValidElement( setting.component )
			);
		} );
	}

	return (
		<>
			<TabContentWrapper
				title={ __( 'Advanced Settings', 'sureforms' ) }
				showTitleHelpText={ true }
				titleHelpText={ __(
					'Set limits on how many times a form can be submitted and manage compliance options, including GDPR and data retention.',
					'sureforms'
				) }
			>
				<div id="srfm-form-restriction-panel" />
				<Title
					size="xs"
					className="mb-4"
					title={ __( 'Form Restriction', 'sureforms' ) }
				/>

				<Container direction="column" className="gap-6">
					<Switch
						size="sm"
						label={ {
							heading: __(
								'Maximum Number of Entries',
								'sureforms'
							),
							description: __(
								'Set the total number of submissions allowed for this form.',
								'sureforms'
							),
						} }
						checked={ preserveMetaData?.status ?? false }
						onChange={ ( checked ) => {
							updateMeta( 'status', checked );
						} }
					/>

					{ preserveMetaData?.status && (
						<>
							{ isNeverStoreEntriesEnabled && (
								<Container className="w-full p-3 gap-2 border border-solid border-alert-border-warning bg-alert-background-warning rounded-lg">
									<span className="size-5">
										<Info
											size={ 20 }
											className="text-yellow-600"
										/>
									</span>
									<span className="text-sm font-normal">
										{ __(
											'The entry cap relies on stored entries to count submissions. While Compliance Settings has "Never store entry data after form submission" enabled, this limit will not be enforced. Disable that option, or remove the entry limit, to use this feature.',
											'sureforms'
										) }
									</span>
								</Container>
							) }
							<div className="flex gap-2 w-full items-end">
								<div className="w-full">
									<Input
										size="md"
										type="number"
										className="w-full"
										value={
											preserveMetaData?.maxEntries ?? 0
										}
										label={ __(
											'Maximum Entries',
											'sureforms'
										) }
										onChange={ ( value ) => {
											let parsedValue = parseInt(
												value,
												10
											);
											if (
												isNaN( parsedValue ) ||
												parsedValue < 0
											) {
												parsedValue = 0;
											}
											updateMeta(
												'maxEntries',
												parsedValue
											);
										} }
									/>
								</div>
								{ /*
								  * Slot for extensions to render a control aligned
								  * to the right of the Maximum Entries input — used
								  * by SureForms Pro to expose a Total / Per Day /
								  * Per Week / Per Month / Per Year period selector.
								  *
								  * Filter contract: callbacks receive whatever the
								  * previous callback returned (initially `null`)
								  * and are expected to return a JSX node. To stay
								  * a good citizen alongside other extensions a
								  * callback should compose with the previous return
								  * rather than replacing it, e.g.:
								  *
								  *   addFilter(
								  *     'srfm_form_restriction_max_entries_after',
								  *     'my-plugin/extra',
								  *     ( prev ) => <>{ prev }<MyControl /></>
								  *   );
								  *
								  * Last-registered-callback-wins is acceptable when
								  * only one extension is active, which is the
								  * current single-vendor reality (SureForms Pro).
								  */ }
								{ applyFilters(
									'srfm_form_restriction_max_entries_after',
									null
								) }
							</div>

							<div>
								<Label className="mb-1.5">
									{ __(
										'Response Description After Maximum Entries',
										'sureforms'
									) }
								</Label>
								<TextArea
									size="md"
									className="w-full"
									value={
										preserveMetaData?.message ??
										__(
											'This form is now closed as the maximum number of entries has been received.',
											'sureforms'
										)
									}
									onChange={ ( value ) => {
										updateMeta( 'message', value );
									} }
								/>
							</div>
						</>
					) }
				</Container>

				{ additionalSettings.map( ( setting, index ) => {
					// Generate safe key - prefer setting.id, fallback to title-based or index
					const safeKey =
						setting.id ||
						( setting.title
							? `title-${ setting.title.replace(
								/[^a-zA-Z0-9]/g,
								'-'
							  ) }-${ index }`
							: `setting-${ index }` );

					// Basic title sanitization - remove HTML tags if present
					const safeTitle =
						setting.title && typeof setting.title === 'string'
							? setting.title.replace( /<[^>]*>/g, '' ).trim()
							: setting.title;

					return (
						<div key={ safeKey } className="mt-6">
							{ safeTitle && (
								<Title
									size="xs"
									className="mb-4"
									title={ safeTitle }
								/>
							) }
							{ setting.component }
						</div>
					);
				} ) }
			</TabContentWrapper>

			<TabContentWrapper className="!mt-0">
				<div id="srfm-form-scheduling-panel" />
				<Title
					size="xs"
					className="mb-4"
					title={ __( 'Form Scheduling', 'sureforms' ) }
				/>

				<Container direction="column" className="gap-6">
					<Switch
						size="sm"
						label={ {
							heading: __(
								'Enable Form Scheduling',
								'sureforms'
							),
							description: __(
								'Set a time period during which this form will be available for submissions.',
								'sureforms'
							),
						} }
						checked={ preserveMetaData?.schedulingStatus ?? false }
						onChange={ ( checked ) => {
							updateMeta( 'schedulingStatus', checked );
						} }
					/>

					{ preserveMetaData?.schedulingStatus && (
						<>
							{ dateTimeValidationError && (
								<Container className="w-full p-3 gap-2 border border-solid border-alert-border-danger bg-alert-background-danger rounded-lg">
									<span className="size-5">
										<Info
											size={ 20 }
											className="text-red-600"
										/>
									</span>
									<span className="text-sm font-normal">
										{ dateTimeValidationError }
									</span>
								</Container>
							) }

							<div className="flex gap-2 w-1/2">
								<div className="w-2/3">
									<DatePickerModal
										label={
											<Label className="mb-1.5 flex items-center gap-1">
												{ __(
													'Start Date & Time',
													'sureforms'
												) }
												<Tooltip
													tooltipPortalId="srfm-form-scheduling-panel"
													arrow
													interactive
													content={
														<span>
															{ createInterpolateElement(
																__(
																	"The Time Period setting works according to your WordPress site's time zone. <a>Click here</a> to open your WordPress General Settings, where you can check and update it.",
																	'sureforms'
																),
																{
																	a: (
																		<a
																			href={
																				srfm_admin?.general_settings_url ||
																				'/wp-admin/options-general.php'
																			}
																			target="_blank"
																			rel="noopener noreferrer"
																			className="tooltip-link text-link-inverse text-xs font-semibold no-underline hover:no-underline"
																		>
																			{ __(
																				'Click here',
																				'sureforms'
																			) }
																		</a>
																	),
																}
															) }
														</span>
													}
													placement="top"
													triggers={ [
														'hover',
														'focus',
													] }
													variant="dark"
												>
													<Info className="size-4 !text-icon-secondary" />
												</Tooltip>
											</Label>
										}
										date={ preserveMetaData?.startDate }
										onDateChange={ ( formattedDate ) => {
											updateMeta(
												'startDate',
												formattedDate
											);
										} }
									/>
								</div>

								{ preserveMetaData?.startDate && (
									<div className="w-1/3">
										<TimePicker
											hours={
												preserveMetaData?.startHours ??
												'12'
											}
											minutes={
												preserveMetaData?.startMinutes ??
												'00'
											}
											meridiem={
												preserveMetaData?.startMeridiem ??
												'AM'
											}
											onChange={ ( key, value ) => {
												if ( key === 'hours' ) {
													updateMeta(
														'startHours',
														value
													);
												} else if (
													key === 'minutes'
												) {
													updateMeta(
														'startMinutes',
														value
													);
												} else if (
													key === 'meridiem'
												) {
													updateMeta(
														'startMeridiem',
														value
													);
												}
											} }
										/>
									</div>
								) }
							</div>

							<div className="flex gap-2 w-1/2">
								<div className="w-2/3">
									<DatePickerModal
										label={
											<Label className="mb-1.5 flex items-center gap-1">
												{ __(
													'End Date & Time',
													'sureforms'
												) }
												<Tooltip
													tooltipPortalId="srfm-form-scheduling-panel"
													arrow
													interactive
													content={
														<span>
															{ createInterpolateElement(
																__(
																	"The Time Period setting works according to your WordPress site's time zone. <a>Click here</a> to open your WordPress General Settings, where you can check and update it.",
																	'sureforms'
																),
																{
																	a: (
																		<a
																			href={
																				srfm_admin?.general_settings_url ||
																				'/wp-admin/options-general.php'
																			}
																			target="_blank"
																			rel="noopener noreferrer"
																			className="tooltip-link text-link-inverse text-xs font-semibold no-underline hover:no-underline"
																		>
																			{ __(
																				'Click here',
																				'sureforms'
																			) }
																		</a>
																	),
																}
															) }
														</span>
													}
													placement="top"
													triggers={ [
														'hover',
														'focus',
													] }
													variant="dark"
												>
													<Info className="size-4 !text-icon-secondary" />
												</Tooltip>
											</Label>
										}
										date={ preserveMetaData?.date }
										onDateChange={ ( formattedDate ) => {
											updateMeta( 'date', formattedDate );
										} }
									/>
								</div>

								{ preserveMetaData?.date && (
									<div className="w-1/3">
										<TimePicker
											hours={
												preserveMetaData?.hours ?? '12'
											}
											minutes={
												preserveMetaData?.minutes ??
												'00'
											}
											meridiem={
												preserveMetaData?.meridiem ??
												'PM'
											}
											onChange={ updateMeta }
										/>
									</div>
								) }
							</div>

							<div>
								<Label className="mb-1.5">
									{ __(
										'Response Description Before Start Date',
										'sureforms'
									) }
								</Label>
								<TextArea
									size="md"
									className="w-full"
									value={
										preserveMetaData?.schedulingNotStartedMessage ??
										__(
											'This form is not yet available. Please check back after the scheduled start time.',
											'sureforms'
										)
									}
									onChange={ ( value ) => {
										updateMeta(
											'schedulingNotStartedMessage',
											value
										);
									} }
								/>
							</div>

							<div>
								<Label className="mb-1.5">
									{ __(
										'Response Description After End Date',
										'sureforms'
									) }
								</Label>
								<TextArea
									size="md"
									className="w-full"
									value={
										preserveMetaData?.schedulingEndedMessage ??
										__(
											'This form is no longer accepting submissions. The submission period has ended.',
											'sureforms'
										)
									}
									onChange={ ( value ) => {
										updateMeta(
											'schedulingEndedMessage',
											value
										);
									} }
								/>
							</div>
						</>
					) }
				</Container>
			</TabContentWrapper>
		</>
	);
};

export default FormRestriction;
