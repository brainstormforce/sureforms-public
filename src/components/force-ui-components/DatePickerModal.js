import { __ } from '@wordpress/i18n';
import { useEffect, useRef, useState } from '@wordpress/element';
import { Input, DatePicker } from '@bsf/force-ui';

/**
 * 1Code has alerts. Press enter to view.
 * Custom DatePicker Modal Component
 * That opens on the click of an input field
 * @param {Object}   props              1Code has alerts. Press enter to view.
 * @param {string}   props.date         The date in a specific format (e.g., '2025.10.01').
 * @param {Function} props.onDateChange Callback function to handle date changes
 * @return {JSX.Element} Rendered DatePicker Modal
 */
const DatePickerModal = ( { label, date, onDateChange } ) => {
	const [ isOpen, setIsOpen ] = useState( false );
	const ref = useRef( null );

	useEffect( () => {
		const handleClickOutside = ( event ) => {
			if (
				isOpen &&
				ref.current &&
				! ref.current.contains( event.target )
			) {
				setIsOpen( false );
			}
		};
		document.addEventListener( 'mousedown', handleClickOutside );
		return () => {
			document.removeEventListener( 'mousedown', handleClickOutside );
		};
	}, [ isOpen ] );

	const getFormattedDate = ( dateStr ) => {
		if ( ! dateStr ) {
			return '';
		}
		const dateObj = new Date( dateStr );
		return dateObj.toLocaleDateString( undefined, {
			year: 'numeric',
			month: '2-digit',
			day: '2-digit',
		} );
	};

	return (
		<>
			<div className="relative w-full z-50" ref={ ref }>
				{ isOpen && (
					<div
						className="absolute right-0 bg-white shadow-lg rounded z-999999"
						style={ {
							top: '4.5rem',
						} }
					>
						<DatePicker
							selectionType="single"
							variant="normal"
							// selected={ date ?? '' }
							selected={ date ? new Date( date ) : null }
							cancelButtonText={
								// if the input is empty, show "Cancel" button
								date
									? __( 'Clear', 'sureforms' )
									: __( 'Cancel', 'sureforms' )
							}
							onApply={ ( value ) => {
								if ( value ) {
									const year = value.getFullYear();
									const month = String(
										value.getMonth() + 1
									).padStart( 2, '0' );
									const day = String(
										value.getDate()
									).padStart( 2, '0' );
									const formattedDate = `${ year }-${ month }-${ day }`;
									onDateChange( formattedDate );
								}
								setIsOpen( false );
							} }
							onCancel={ () => {
								onDateChange( '' );
								setIsOpen( false );
							} }
						/>
					</div>
				) }
			</div>
			<Input
				size="md"
				className="w-full cursor-pointer"
				// label={ __( 'Date', 'sureforms' ) }
                label={
                    label || __( 'Date', 'sureforms' )
                }
				value={ getFormattedDate( date ) }
				readOnly
				onClick={ () => setIsOpen( true ) }
				placeholder="dd/mm/yyyy"
			/>
		</>
	);
};

export default DatePickerModal;
