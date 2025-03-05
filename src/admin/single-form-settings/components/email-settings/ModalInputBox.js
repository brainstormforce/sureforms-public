import SmartTagList from '@Components/misc/SmartTagList';
import { Label, Input } from '@bsf/force-ui';

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
} ) => {
	return (
		<div className="flex flex-col w-full h-auto gap-2">
			<div className="flex w-full items-center gap-2">
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
					/>
				</div>
				{ showSmartTagList && (
					<div className="mt-6">
						<SmartTagList
							className="shrink-0"
							tagFor={ tagFor }
							tagsArray={ smartTagList }
							setTargetData={ setTargetData }
						/>
					</div>
				) }
			</div>
			{ helpText && (
				<Label size="xs" variant="help" className="italic">
					{ helpText }
				</Label>
			) }
		</div>
	);
};

export default ModalInputBox;
