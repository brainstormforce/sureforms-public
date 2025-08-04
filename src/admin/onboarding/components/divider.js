import { cn } from '@Utils/Helpers';

const Divider = ( { className } ) => {
	return (
		<hr
			className={ cn(
				'w-full border-t border-b-0 border-x-0 border-solid border-border-subtle',
				className
			) }
		/>
	);
};

export default Divider;
