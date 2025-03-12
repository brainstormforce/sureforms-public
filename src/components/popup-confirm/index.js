import { Button, Modal } from '@wordpress/components';
import { useLayoutEffect, useState } from '@wordpress/element';
import styles from './editor.lazy.scss';
import { __ } from '@wordpress/i18n';
import renderSVG from '@Controls/renderIcon';

const SRFMConfirmPopup = ( props ) => {
	const {
		isOpen,
		setOpen,
		onConfirm,
		title,
		description,
		confirmLabel = __( 'Confirm', 'sureforms' ),
		cancelLabel = __( 'Cancel', 'sureforms' ),
		processingLabel = __( 'Processing…', 'sureforms' ),
		icon = '',
		executable = false,
	} = props;

	// Add and remove the CSS on the drop and remove of the component.
	useLayoutEffect( () => {
		styles.use();
		return () => {
			styles.unuse();
		};
	}, [] );

	const [ isProcessing, setProcessing ] = useState( false );
	const closeModal = () => {
		setOpen( false );
		setProcessing( false );
	};

	const handleConfirmation = () => {
		setProcessing( true );
		// If a custom function needs to be processed or a parameter needs to be passed on confirmation can be passed here.
		if ( executable ) {
			onConfirm( executable );
		} else {
			onConfirm();
		}
	};

	return (
		<>
			{ isOpen && (
				<Modal
					onRequestClose={ closeModal }
					className="srfm-confirm-popup-wrapper"
					overlayClassName="srfm-confirm-popup-overlay"
				>
					<div className="srfm-confirm-popup-icon">
						{ icon !== '' ? (
							renderSVG( icon )
						) : (
							<svg
								xmlns="http://www.w3.org/2000/svg"
								fill="none"
								viewBox="0 0 24 24"
								strokeWidth="1.5"
								stroke="currentColor"
							>
								<path
									strokeLinecap="round"
									strokeLinejoin="round"
									d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"
								/>
							</svg>
						) }
					</div>
					<div className="srfm-confirm-popup-content">
						<div className="srfm-confirm-popup-text">
							<span className="srfm-confirm-popup-title">
								{ title }
							</span>
							<span className="srfm-confirm-popup-description">
								{ description }
							</span>
						</div>
						<div className="srfm-confirm-popup-controls">
							{ isProcessing === false ? (
								<>
									<Button
										className="srfm-confirm-popup-buttons srfm-popup-confirmation-button"
										onClick={ handleConfirmation }
									>
										{ confirmLabel }
									</Button>
									<Button
										className="srfm-confirm-popup-buttons srfm-popup-cancellation-button"
										onClick={ closeModal }
									>
										{ cancelLabel }
									</Button>
								</>
							) : (
								<Button
									isBusy={ true }
									className="srfm-confirm-popup-buttons srfm-popup-confirmation-button"
								>
									{ processingLabel }
								</Button>
							) }
						</div>
					</div>
				</Modal>
			) }
		</>
	);
};

SRFMConfirmPopup.defaultProps = {};

export default SRFMConfirmPopup;
