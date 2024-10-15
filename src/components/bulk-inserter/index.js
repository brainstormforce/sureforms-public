import { useState, useMemo, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Modal, Button } from '@wordpress/components';
import svgIcons from '@Image/single-form-logo.json';
import parse from 'html-react-parser';

export const BulkInserterPopup = ( props ) => {
	const { options, titleKey, closeModal, insertOptions } = props;

	// Memoize optionText to avoid recalculating on each render
	const optionText = useMemo(
		() =>
			options
				.map( ( option ) => option?.[ titleKey ]?.trim() )
				.join( '\n' ),
		[ options ]
	);

	// Modal icon memoized for performance.
	const modalIcon = useMemo( () => parse( svgIcons.modalLogo ), [] );
	const wpXIcon = useMemo( () => parse( svgIcons.wpXIcon ), [] );

	// Set initial textarea value to optionText and provide setOptions function for updates
	const [ tempOptions, setOptions ] = useState( optionText );
	const [ bodyStyle, setBodyStyle ] = useState( {} );
	const modalHeaderRef = useRef( null );

	useEffect( () => {
		// Get modalHeaderRef outer height and set it as padding top and add 24px in that padding and add top padding to the body.
		if ( modalHeaderRef?.current ) {
			const headerHeight = modalHeaderRef.current.offsetHeight;
			setBodyStyle( { paddingTop: `${ headerHeight + 24 }px` } );
		}
	}, [] );

	const handleBulkInsert = () => {
		// Keep only those which option.title is repeating in option.title = { // option all properties }
		const duplicateOptions = [ ...options ];

		const newOptions = tempOptions.split( '\n' ).reduce( ( acc, item ) => {
			item = item.trim();

			// Skip empty items
			if ( item === '' ) {
				return acc;
			}

			// Check if item exists in the duplicateOptions if then get the object and its index and remove it from duplicateOptions
			const existingOptionIndex = duplicateOptions.findIndex(
				( option ) => option?.[ titleKey ]?.trim() === item
			);

			if ( existingOptionIndex > -1 ) {
				const existingOption = {
					...duplicateOptions[ existingOptionIndex ],
				};
				acc.push( { ...existingOption } );
				// Remove the object from duplicateOptions
				duplicateOptions.splice( existingOptionIndex, 1 );
			} else {
				// If item doesn't exist in options, add as a new entry
				acc.push( { [ titleKey ]: item } );
			}

			return acc;
		}, [] );

		// Handle the newOptions result (e.g., send to parent component or update state)
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
						<Button isSecondary onClick={ closeModal }>
							{ __( 'Cancel', 'sureforms' ) }
						</Button>
						<Button isPrimary onClick={ handleBulkInsert }>
							{ __( 'Insert Options', 'sureforms' ) }
						</Button>
					</div>
				</div>
			</div>
		</Modal>
	);
};

export const BulkInserterWithButton = ( props ) => {
	const { options, titleKey, insertOptions } = props;
	const [ isModalOpen, setIsModalOpen ] = useState( false );
	const closeModal = () => setIsModalOpen( false );

	const bulkEdit = (
		<>
			<Button
				className="sureforms-add-bulk-option-button"
				variant="secondary"
				onClick={ () => {
					setIsModalOpen( true );
				} }
			>
				{ __( 'Bulk Edit', 'sureforms' ) }
			</Button>
			{ isModalOpen && (
				<BulkInserterPopup
					closeModal={ closeModal }
					titleKey={ titleKey }
					options={ options }
					insertOptions={ ( newOptions ) => {
						insertOptions( newOptions, closeModal );
					} }
				/>
			) }
		</>
	);

	return bulkEdit;
};
