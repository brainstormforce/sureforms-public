import { __ } from '@wordpress/i18n';
import { useEffect, useContext } from '@wordpress/element';
import { FormRestrictionContext } from './context';
import {
	Container,
	Input,
	Title,
	Switch,
	Label,
	TextArea,
} from '@bsf/force-ui';
import TabContentWrapper from '@Components/tab-content-wrapper';
import DatePickerModal from '@Components/force-ui-components/DatePickerModal';
import TimePicker from '@Components/force-ui-components/TimePicker';

const FormRestriction = () => {
	const { updateMeta, preserveMetaData, editMeta } = useContext(
		FormRestrictionContext
	);

	useEffect( () => {
		editMeta();
	}, [] );

	return (
			<TabContentWrapper title={ __( 'General Settings', 'sureforms' ) }>
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
						message: __(
							'Set the maximum number of entries allowed for this form. Once the limit is reached, the form will automatically be disabled.',
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
										value = parseInt( value );
										if ( value < 0 ) {
											value = 1;
										}
										updateMeta( 'maxEntries', value );
									} }
								/>
							</div>
						</div>

						<div className="flex gap-2 w-1/2">
							<div className="w-2/3">
								<DatePickerModal
									label={ __( 'Date & Time', 'sureforms' ) }
									date={ preserveMetaData?.date }
									onDateChange={ ( formattedDate ) => {
										updateMeta( 'date', formattedDate );
									} }
									topValue={ '2.5rem' }
								/>
							</div>

							{ preserveMetaData?.date && (
								<div className="w-1/3">
									<TimePicker
										hours={ preserveMetaData?.hours ?? '1' }
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
							<Label className="mb-1">
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
										"Oops! This form is now closed as we've received all the entries. Stay tuned for more!",
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
		</TabContentWrapper>
	);
};

export default FormRestriction;
