import { Container, Button, Loader } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import { ChevronRight } from 'lucide-react';
import { useState } from '@wordpress/element';

/**
 * Button type configuration map
 *
 * @type {Object}
 */
const BUTTON_TYPES = {
	back: {
		key: 'back',
		defaultProps: {
			type: 'button',
			variant: 'outline',
			icon: <ChevronRight className="rotate-180" />,
			text: __( 'Back', 'sureforms' ),
		},
		loaderProps: {
			variant: 'primary',
		},
	},
	continue: {
		key: 'continue',
		defaultProps: {
			type: 'button',
			variant: 'primary',
			icon: <ChevronRight />,
			iconPosition: 'right',
			text: __( 'Continue Setup', 'sureforms' ),
		},
		loaderProps: {
			variant: 'secondary',
		},
	},
	skip: {
		key: 'skip',
		defaultProps: {
			type: 'button',
			className: 'text-text-tertiary',
			size: 'md',
			variant: 'ghost',
			icon: null,
			text: __( 'Skip', 'sureforms' ),
		},
		loaderProps: {
			variant: 'primary',
		},
	},
};

/**
 * A reusable navigation buttons component using the prop getter pattern
 * for maximum flexibility and customization.
 *
 * @param {Object} props                  - Component props
 * @param {Object} props.backProps        - Custom props for the back button
 * @param {Object} props.continueProps    - Custom props for the continue button
 * @param {Object} props.skipProps        - Custom props for the skip button
 * @param {Object} props.containerProps   - Props for the main container
 * @param {Object} props.buttonGroupProps - Props for the continue and skip button container
 * @return {JSX.Element|null} The navigation buttons component or null if no buttons
 */
const NavigationButtons = ( {
	backProps = {},
	continueProps = {},
	skipProps = {},
	containerProps = {},
	buttonGroupProps = {},
} ) => {
	const [ isLoading, setIsLoading ] = useState( {
		back: false,
		continue: false,
		skip: false,
	} );

	/**
	 * Updates the loading state for a specific button
	 *
	 * @param {string}  key   - The button key to update
	 * @param {boolean} value - The loading state value
	 */
	const handleSetIsLoading = ( key, value ) => {
		setIsLoading( ( prevState ) => ( {
			...prevState,
			[ key ]: value,
		} ) );
	};

	/**
	 * Creates action handler with loading state management
	 *
	 * @param {string}   actionType     - The type of action (back, continue, skip)
	 * @param {Function} actionCallback - The callback function to execute
	 * @return {Function} The action handler with loading state management
	 */
	const createActionHandler =
		( actionType, actionCallback ) =>
		async ( ...args ) => {
			if ( typeof actionCallback !== 'function' ) {
				return;
			}
			if ( isLoading[ actionType ] ) {
				return;
			}
			try {
				handleSetIsLoading( actionType, true );
				await actionCallback( ...args );
			} catch ( error ) {
				// Silent error handling - could be enhanced with a toast notification
			} finally {
				handleSetIsLoading( actionType, false );
			}
		};

	/**
	 * Generic button prop getter
	 *
	 * @param {string} buttonType - The type of button (back, continue, skip)
	 * @param {Object} props      - The props for this button type
	 * @return {Object|null} The final props for the button or null if not needed
	 */
	const getButtonProps = ( buttonType, props = {} ) => {
		const { onClick, disabled, text, icon, ...restProps } = props;

		const config = BUTTON_TYPES[ buttonType ];

		// Check if the button should be shown
		const hasOnClick = typeof onClick === 'function';
		if ( ! hasOnClick ) {
			return null;
		}

		// Create the click handler
		const handleClick = createActionHandler( buttonType, onClick );

		// Handle loading state for the icon
		const buttonIcon = isLoading[ buttonType ] ? (
			<Loader variant={ config.loaderProps.variant } />
		) : (
			icon || config.defaultProps.icon
		);

		// Build the final props
		return {
			...config.defaultProps,
			...restProps,
			onClick: handleClick,
			disabled,
			icon: buttonIcon,
			children: text || config.defaultProps.text,
		};
	};

	// Create final props for all buttons
	const finalBackButtonProps = getButtonProps( 'back', backProps );
	const finalContinueButtonProps = getButtonProps(
		'continue',
		continueProps
	);
	const finalSkipButtonProps = getButtonProps( 'skip', skipProps );

	// Return early if no buttons to render
	if (
		! finalBackButtonProps &&
		! finalContinueButtonProps &&
		! finalSkipButtonProps
	) {
		return null;
	}

	return (
		<Container justify="between" { ...containerProps }>
			{ finalBackButtonProps && <Button { ...finalBackButtonProps } /> }

			<Container className="gap-3" { ...buttonGroupProps }>
				{ finalSkipButtonProps && (
					<Button { ...finalSkipButtonProps } />
				) }
				{ finalContinueButtonProps && (
					<Button { ...finalContinueButtonProps } />
				) }
			</Container>
		</Container>
	);
};

export default NavigationButtons;
