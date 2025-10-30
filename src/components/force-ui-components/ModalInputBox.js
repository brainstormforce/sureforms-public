import SmartTagList from '@Components/misc/SmartTagList';
import { Label, Input, Container, Tooltip } from '@bsf/force-ui';
import { cn } from '@Utils/Helpers';
import { Info } from 'lucide-react';
import { __ } from '@wordpress/i18n';

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
	labelWithTooltip = false,
	labelWithInfoTooltip = false,
} ) => {
	const renderLabelWithTooltip = () => (
		<Tooltip
			content={ helpText }
			title={ label }
			placement="left"
			triggers={ [ 'hover' ] }
			variant="light"
			tooltipPortalId="srfm-settings-container"
			className="rounded-md border border-solid border-border-subtle shadow-sm"
		>
			<Label
				htmlFor={ id }
				size="sm"
				className="font-medium border-b border-t-0 border-l-0 border-r-0 border-dotted border-text-primary cursor-help inline-block"
			>
				{ label }
			</Label>
		</Tooltip>
	);

	const renderInfoWithTooltip = () => (
		<Label className="text-base">
			{ label }
			<Tooltip
				tooltipPortalId="srfm-settings-container"
				arrow
				content={
					<span>
						{ __(
							'Enter one or more emails in formats like email@example.com or John Doe <john@example.com>, separated by commas.',
							'sureforms'
						) }
					</span>
				}
				placement="top"
				triggers={ [ 'hover', 'focus' ] }
				variant="dark"
				open={true}
			>
				<Info className="size-4 !text-icon-secondary" />
			</Tooltip>
		</Label>
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
							! labelWithTooltip
								? labelWithInfoTooltip
									? renderInfoWithTooltip()
									: label
								: renderLabelWithTooltip()
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

			{ helpText && ! labelWithTooltip && (
				<Label size="sm" variant="help" className="font-normal">
					{ helpText }
				</Label>
			) }
		</Container>
	);
};

export default ModalInputBox;
