/**
 * BillingCyclesControl component for managing subscription billing cycles.
 *
 * @param {Object}   props                  - Component props.
 * @param {Object}   props.subscriptionPlan - Current subscription plan object.
 * @param {Function} props.setAttributes    - Function to update block attributes.
 * @return {JSX.Element} The BillingCyclesControl component.
 */

// Import required dependencies
import { __ } from '@wordpress/i18n';
import { SelectControl } from '@wordpress/components';
import SRFMTextControl from '@Components/text-control';

const BillingCyclesControl = ( { subscriptionPlan, setAttributes } ) => {
	const handleSelectionChange = ( value ) => {
		setAttributes( {
			subscriptionPlan: {
				...( subscriptionPlan || {} ),
				billingCycles: 'custom' === value ? '' : value,
			},
		} );
	};

	const handleCustomValueChange = ( value ) => {
		const numValue = parseInt( value );

		if ( parseInt( value ) < 1 ) {
			value = 1;
		}

		// Validate: must be a number and at least 1
		if ( isNaN( numValue ) || numValue < 1 ) {
			return;
		}

		// Limit to max 100
		const validValue = Math.min( numValue, 100 );

		setAttributes( {
			subscriptionPlan: {
				...( subscriptionPlan || {} ),
				billingCycles: validValue,
			},
		} );
	};

	const isCustomIfBlank = '' === subscriptionPlan?.billingCycles;
	const cycleIsNotThree =
		typeof subscriptionPlan?.billingCycles === 'number' &&
		subscriptionPlan?.billingCycles !== 3;
	const isCustom = isCustomIfBlank || cycleIsNotThree;
	const showInput =
		[ 2, 3, 4, 5 ].includes( Number( subscriptionPlan?.billingCycles ) ) ||
		isCustom;

	const defaultOptions = [
		{
			label: __( '2 Payments', 'sureforms' ),
			value: 2,
		},
		{
			label: __( '3 Payments', 'sureforms' ),
			value: 3,
		},
		{
			label: __( '4 Payments', 'sureforms' ),
			value: 4,
		},
		{
			label: __( '5 Payments', 'sureforms' ),
			value: 5,
		},
		{
			label: __( 'Custom', 'sureforms' ),
			value: 'custom',
		},
		{
			label: __( 'Never', 'sureforms' ),
			value: 'ongoing',
		},
	];

	return (
		<>
			<SelectControl
				label={ __( 'Stop Subscription After', 'sureforms' ) }
				value={ isCustom ? 'custom' : subscriptionPlan?.billingCycles }
				options={ defaultOptions }
				onChange={ handleSelectionChange }
				help={ __(
					'Choose when to automatically stop the subscription',
					'sureforms'
				) }
			/>
			{ showInput && (
				<SRFMTextControl
					label={ __( 'Number of Payments', 'sureforms' ) }
					type="number"
					value={ subscriptionPlan?.billingCycles || null }
					data={ {
						value: subscriptionPlan?.billingCycles || null,
						label: 'billingCycles',
					} }
					onChange={ handleCustomValueChange }
					min={ 1 }
					step={ 1 }
					max={ 100 }
					help={ __(
						'Enter a number between 1 to 100',
						'sureforms'
					) }
				/>
			) }
		</>
	);
};

export default BillingCyclesControl;
