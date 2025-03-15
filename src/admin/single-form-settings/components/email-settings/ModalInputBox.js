import SmartTagList from '@Components/misc/SmartTagList';
import { Label, Input } from '@bsf/force-ui';
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
} ) => {
	return (
		<div className={ cn( 'flex flex-col w-full h-auto gap-2', padding ) }>
			<div className="flex w-full items-center gap-1.5">
				<div className="flex-1">
					<Input
						aria-label={ label }
						type="text"
						className="w-full flex-1"
						id={ id }
						onChange={ ( val ) => onChange( val ) }
						value={ value }
						label={ label }
						required={ required }
						size="md"
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
			</div>
			{ helpText && (
				<Label size="sm" variant="help" className="font-normal">
					{ helpText }
				</Label>
			) }
		</div>
	);
};

export default ModalInputBox;
