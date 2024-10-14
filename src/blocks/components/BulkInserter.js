import { useState, useMemo, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Modal, Button } from '@wordpress/components';
import svgIcons from '@Image/single-form-logo.json';
import parse from 'html-react-parser';

const BulkInserter = ( props ) => {
	const { options, closeModal, insertOptions } = props;

	// Memoize optionText to avoid recalculating on each render
	const optionText = useMemo( () => {
		return options
			.map( ( option ) => option.optionTitle.trim() )
			.join( '\n' );
	}, [ options ] );

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
		const newOptions = tempOptions.split( '\n' ).reduce( ( acc, item ) => {
			item = item.trim();

			// Skip empty items
			if ( item === '' ) {
				return acc;
			}

			// Avoid adding duplicates
			if ( acc.some( ( opt ) => opt.optionTitle.trim() === item ) ) {
				return acc;
			}

			// Check if item exists in the original options and merge data
			const existingOption = options.find(
				( option ) => option.optionTitle.trim() === item
			);

			if ( existingOption ) {
				acc.push( { ...existingOption } );
			} else {
				// If item doesn't exist in options, add as a new entry
				acc.push( { optionTitle: item } );
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
				<div className="srfm-modal-header-close-icon">{ wpXIcon }</div>
			</div>
			<div className="srfm-modal-body" style={ bodyStyle }>
				<div className="srfm-modal-body-content">
					<textarea
						onChange={ ( e ) => setOptions( e.target.value ) }
						value={ tempOptions }
						className="srfm-bulk-edit-textarea"
					></textarea>
					<div className="srfm-body-footer">
						<Button isSecondary onClick={ closeModal }>
							{ __( 'Close', 'sureforms' ) }
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

export default BulkInserter;
