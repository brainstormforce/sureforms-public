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
			borderOnActive
			label={ {
				heading: label,
			} }
			// There are some style issues existing in the radio button component of the force-ui package.
			// This is a temporary fix to override the styles.
			buttonWrapperClasses={ cn(
				'py-2 pl-2.5 pr-10 max-w-fit min-w-full',
				checked && 'outline-focus',
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
