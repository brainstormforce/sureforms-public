import { useMemo, useState, useRef, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Modal, Button } from '@wordpress/components';
import svgIcons from '@Image/single-form-logo.json';
import parse from 'html-react-parser';

/**
 * BulkInserterPopup component for bulk editing options in a modal.
 *
 * @param {Object}   props               - Component props.
 * @param {Array}    props.options       - The list of options where each option is an object.
 * @param {string}   props.titleKey      - The key to use to extract titles from each option object.
 * @param {Function} props.closeModal    - A function to close the modal.
 * @param {Function} props.insertOptions - A function to handle the insertion of new or updated options.
 *
 * @return {JSX.Element} The BulkInserterPopup modal component.
 */
export const BulkInserterPopup = ( props ) => {
	const { options, titleKey, closeModal, insertOptions } = props;

	// Memoize optionText to avoid recalculating the initial textarea content on each render.
	const optionText = useMemo(
		() =>
			options
				.map( ( option ) => option?.[ titleKey ]?.trim() )
				.join( '\n' ),
		[ options, titleKey ]
	);

	// Memoize modal icons for performance optimization.
	const modalIcon = useMemo( () => parse( svgIcons.modalLogo ), [] );
	const wpXIcon = useMemo( () => parse( svgIcons.wpXIcon ), [] );

	// Local state to manage the textarea value and modal body styling.
	const [ tempOptions, setOptions ] = useState( optionText );
	const [ bodyStyle, setBodyStyle ] = useState( {} );
	const modalHeaderRef = useRef( null );

	/**
	 * Calculate the header height and update body padding dynamically.
	 */
	useEffect( () => {
		// Set padding-top for the modal body based on the header height plus some extra space (24px).
		if ( modalHeaderRef?.current ) {
			const headerHeight = modalHeaderRef.current.offsetHeight;
			setBodyStyle( { paddingTop: `${ headerHeight + 24 }px` } );
		}
	}, [] );

	/**
	 * Handle the bulk insert operation to process the textarea input, find duplicates, and prepare the final options list.
	 */
	const handleBulkInsert = () => {
		// Create a duplicate array of options to avoid mutating the original array during iteration.
		const duplicateOptions = [ ...options ];

		// Split the textarea value by newlines and process each item.
		const newOptions = tempOptions.split( '\n' ).reduce( ( acc, item ) => {
			// Trim white spaces from each item.
			item = item.trim();

			// Skip empty items.
			if ( item === '' ) {
				return acc;
			}

			// Find if the item already exists in the options array by checking the titleKey field.
			const existingOptionIndex = duplicateOptions.findIndex(
				( option ) => option?.[ titleKey ]?.trim() === item
			);

			if ( existingOptionIndex > -1 ) {
				// If the option exists, clone the option and push it to the accumulator.
				const existingOption = {
					...duplicateOptions[ existingOptionIndex ],
				};
				acc.push( { ...existingOption } );
				// Remove the matched item from duplicateOptions to prevent future matches.
				duplicateOptions.splice( existingOptionIndex, 1 );
			} else {
				// If the option doesn't exist, create a new option with the provided title.
				acc.push( { [ titleKey ]: item } );
			}

			return acc;
		}, [] );

		// Pass the new options back to the parent component through insertOptions prop.
		insertOptions( newOptions );
	};

	return (
		<Modal
			onRequestClose={ closeModal }
			title={ __( 'Bulk Add', 'sureforms' ) }
			className="srfm-bulk-edit-modal"
			isFullScreen={ false }
		>
			<div className="srfm-modal-header" ref={ modalHeaderRef }>
				<div className="srfm-modal-header-content">
					<div className="srfm-modal-logo">{ modalIcon }</div>
					<div className="srfm-modal-title">
						<h1>{ __( 'Bulk Add Options', 'sureforms' ) }</h1>
					</div>
				</div>
				<div
					className="srfm-modal-header-close-icon"
					onClick={ closeModal }
				>
					{ wpXIcon }
				</div>
			</div>
			<div className="srfm-modal-body" style={ bodyStyle }>
				<div className="srfm-modal-body-content">
					<p className="srfm-modal-help-text">
						{ __(
							'Enter each option on a new line.',
							'sureforms'
						) }
					</p>
					<textarea
						onChange={ ( e ) => setOptions( e.target.value ) }
						value={ tempOptions }
						className="srfm-bulk-edit-textarea"
					></textarea>
					<div className="srfm-body-footer">
						<Button
							isSecondary
							onClick={ closeModal }
							className="srfm-cancel-button"
						>
							{ __( 'Cancel', 'sureforms' ) }
						</Button>
						<Button
							isPrimary
							onClick={ handleBulkInsert }
							className="srfm-insert-button"
						>
							{ __( 'Insert Options', 'sureforms' ) }
						</Button>
					</div>
				</div>
			</div>
		</Modal>
	);
};

/**
 * BulkInserterWithButton component renders a button that triggers a modal for bulk inserting options.
 *
 * @param {Object}   props               - Component props.
 * @param {Array}    props.options       - The list of options where each option is an object.
 * @param {string}   props.titleKey      - The key to use to extract titles from each option object.
 * @param {Function} props.insertOptions - A function to handle the insertion of new or updated options.
 *
 * @return {JSX.Element} The BulkInserterWithButton component.
 */
export const BulkInserterWithButton = ( props ) => {
	const { options, titleKey, insertOptions } = props;

	// State to manage the visibility of the modal.
	const [ isModalOpen, setIsModalOpen ] = useState( false );

	/**
	 * Closes the modal when called.
	 */
	const closeModal = () => setIsModalOpen( false );

	// JSX structure for the button and conditional modal rendering.
	const bulkEdit = (
		<>
			<Button
				className="sureforms-add-bulk-option-button"
				variant="secondary"
				onClick={ () => {
					setIsModalOpen( true );
				} }
			>
				{ __( 'Bulk Add', 'sureforms' ) }
			</Button>

			{ isModalOpen && (
				<BulkInserterPopup
					closeModal={ closeModal }
					titleKey={ titleKey }
					options={ options }
					insertOptions={ ( newOptions ) => {
						// Handle the new options and close the modal after insertion.
						insertOptions( newOptions, closeModal );
					} }
				/>
			) }
		</>
	);

	return bulkEdit;
};
