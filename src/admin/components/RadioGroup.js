import { RadioButton } from '@bsf/force-ui';
import { Children } from '@wordpress/element';
import { cn } from '@Utils/Helpers';

const RadioGroup = ( { className, cols, children } ) => {
	return (
		<RadioButton.Group
			columns={ cols ?? Children.count( children ) }
			className={ cn( 'gap-3 max-w-fit', className ) }
			size="sm"
		>
			{ children }
		</RadioButton.Group>
	);
};

const RadioGroupOption = ( { label, value, checked, onChange, className } ) => {
	return (
		<RadioButton.Button
			borderOn
			label={ {
				heading: label,
			} }
			buttonWrapperClasses={ cn(
				'py-2 pl-2.5 pr-10 max-w-fit hover:ring-1 ring-1 ring-transparent hover:ring-focus',
				checked && 'ring-focus',
				className
			) }
			onChange={ onChange }
			value={ value }
			checked={ checked }
		/>
	);
};

// Update display name.
RadioGroupOption.displayName = 'RadioGroup.Option';

// Assign RadioGroupOption as a child component.
RadioGroup.Option = RadioGroupOption;

export default RadioGroup;
