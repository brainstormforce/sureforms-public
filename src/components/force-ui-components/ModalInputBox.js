import SmartTagList from '@Components/misc/SmartTagList';
import { Label, Input, Container, Tooltip } from '@bsf/force-ui';
import { cn } from '@Utils/Helpers';

const ModalInputBox = ( {
	label,
	id,
	value,
	onChange,
	required = false,
	smartTagList,
	tagFor,
	setTargetData,
	showSmartTagList = true,
	helpText,
	padding = '',
	smartTagWrapperClasses = 'mt-6',
	inputProps = {},
	placeholder = '',
	isNativeIntegrations = false,
} ) => {
	const nativeIntegrationLabel = () => (
		<Tooltip
			content={ helpText }
			title={ label }
			placement="left"
			triggers={ [ 'hover' ] }
			variant="light"
			tooltipPortalId="srfm-settings-container"
			className="rounded-md border border-solid border-border-subtle shadow-sm"
		>
			<Label htmlFor={ id } size="sm" className="font-medium border-b border-t-0 border-l-0 border-r-0 border-dotted border-text-primary cursor-help inline-block">
				{ label }
			</Label>
		</Tooltip>
	);

	return (
		<Container
			direction="column"
			className={ cn( 'w-full gap-2', padding ) }
		>
			<Container align="center" className="gap-1.5">
				<div className="flex-1 space-y-1.5">
					<Input
						aria-label={ label }
						type="text"
						id={ id }
						onChange={ ( val ) => onChange( val ) }
						value={ value }
						label={
							! isNativeIntegrations
								? label
								: nativeIntegrationLabel()
						}
						required={ required }
						placeholder={ placeholder }
						size="md"
						className="[&>input]:text-field-input"
						{ ...inputProps }
					/>
				</div>

				{ showSmartTagList && (
					<div className={ cn( smartTagWrapperClasses ) }>
						<SmartTagList
							className="shrink-0"
							tagFor={ tagFor }
							tagsArray={ smartTagList }
							setTargetData={ setTargetData }
							triggerSize="md"
						/>
					</div>
				) }
			</Container>

			{ helpText && ! isNativeIntegrations && (
				<Label size="sm" variant="help" className="font-normal">
					{ helpText }
				</Label>
			) }
		</Container>
	);
};

export default ModalInputBox;
