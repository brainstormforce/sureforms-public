import { Button as FUIButton } from '@bsf/force-ui';
import { forwardRef } from '@wordpress/element';

/**
 * Button Component
 *
 * A wrapper around Force UI Button with customizable variant and onClick handler.
 *
 * @param {Object}   props         - Component props.
 * @param {string}   props.text    - Button text.
 * @param {string}   props.variant - Button variant (primary|secondary|tertiary).
 * @param {Function} props.onClick - Click handler function.
 * @return {JSX.Element} Button component.
 */
const Button = ( { text, variant, onClick } ) => {
	const handleOnClick = ( event ) => {
		if ( !! onClick && typeof onClick === 'function' ) {
			onClick( event );
		}
	};

	return (
		<FUIButton
			variant={ variant || 'primary' }
			size="sm"
			onClick={ handleOnClick }
		>
			{ text }
		</FUIButton>
	);
};

export default forwardRef( Button );
