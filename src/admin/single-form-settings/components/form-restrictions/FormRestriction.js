import { __ } from '@wordpress/i18n';
import {
	useContext,
	createInterpolateElement,
	isValidElement,
} from '@wordpress/element';
import { FormRestrictionContext } from './context';
import {
	Container,
	Input,
	Title,
	Switch,
	Label,
	TextArea,
	Tooltip,
} from '@bsf/force-ui';
import { Info } from 'lucide-react';
import TabContentWrapper from '@Components/tab-content-wrapper';
import DatePickerModal from '@Components/force-ui-components/DatePickerModal';
import TimePicker from '@Components/force-ui-components/TimePicker';
import { applyFilters } from '@wordpress/hooks';

const FormRestriction = () => {
	const { updateMeta, preserveMetaData } = useContext(
		FormRestrictionContext
	);

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
						heading: __( 'Maximum Number of Entries', 'sureforms' ),
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
						<div className="flex gap-2 w-full">
							<div className="w-full">
								<Input
									size="md"
									type="number"
									className="w-full"
									value={ preserveMetaData?.maxEntries ?? 0 }
									label={ __(
										'Maximum Entries',
										'sureforms'
									) }
									onChange={ ( value ) => {
										let parsedValue = parseInt( value, 10 );
										if (
											isNaN( parsedValue ) ||
											parsedValue < 0
										) {
											parsedValue = 0;
										}
										updateMeta( 'maxEntries', parsedValue );
									} }
								/>
							</div>
						</div>

						<div className="flex gap-2 w-1/2">
							<div className="w-2/3">
								<DatePickerModal
									label={
										<>
											{ __( 'Date & Time', 'sureforms' ) }
											<Tooltip
												tooltipPortalId="srfm-form-restriction-panel"
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
										</>
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
											preserveMetaData?.minutes ?? '00'
										}
										meridiem={
											preserveMetaData?.meridiem ?? 'AM'
										}
										onChange={ updateMeta }
									/>
								</div>
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
										"This form is now closed as we've received all the entries.",
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
	);
};

export default FormRestriction;
