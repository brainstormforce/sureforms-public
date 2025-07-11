import SmartTagList from '@Components/misc/SmartTagList';
import { Label, Input, Container } from '@bsf/force-ui';
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
	placeholder = '',
} ) => {
	return (
		<Container
			direction="column"
			className={ cn( 'w-full gap-2', padding ) }
		>
			<Container align="center" className="gap-1.5">
				<div className="flex-1">
					<Input
						aria-label={ label }
						type="text"
						id={ id }
						onChange={ ( val ) => onChange( val ) }
						value={ value }
						label={ label }
						required={ required }
						placeholder={ placeholder }
						size="md"
						className="[&>input]:text-field-input"
					/>
				</div>
				{ showSmartTagList && (
					<div className="mt-6">
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
			{ helpText && (
				<Label size="sm" variant="help" className="font-normal">
					{ helpText }
				</Label>
			) }
		</Container>
	);
};

export default ModalInputBox;
