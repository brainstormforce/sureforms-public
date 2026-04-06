/**
 * Upgrade Modal Component
 *
 * A reusable modal for Pro feature upgrade prompts.
 *
 * @package
 * @since x.x.x
 */

import { Modal, Button } from '@wordpress/components';
import { useLayoutEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import styles from './editor.lazy.scss';

/**
 * Lightning bolt icon for the header.
 *
 * @return {JSX.Element} SVG icon element.
 */
const LightningIcon = () => (
	<svg
		width="16"
		height="16"
		viewBox="0 0 16 16"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
	>
		<path
			d="M8.66667 1.33334L2 9.33334H8L7.33333 14.6667L14 6.66668H8L8.66667 1.33334Z"
			stroke="currentColor"
			strokeWidth="1.5"
			strokeLinecap="round"
			strokeLinejoin="round"
		/>
	</svg>
);

/**
 * Checkmark icon for feature list items.
 *
 * @return {JSX.Element} SVG icon element.
 */
const CheckIcon = () => (
	<svg
		width="16"
		height="16"
		viewBox="0 0 16 16"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
	>
		<path
			d="M13.3333 4L6 11.3333L2.66667 8"
			stroke="currentColor"
			strokeWidth="1.5"
			strokeLinecap="round"
			strokeLinejoin="round"
		/>
	</svg>
);

/**
 * Close icon for the header.
 *
 * @return {JSX.Element} SVG icon element.
 */
const CloseIcon = () => (
	<svg
		width="20"
		height="20"
		viewBox="0 0 20 20"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
	>
		<path
			d="M15 5L5 15M5 5L15 15"
			stroke="currentColor"
			strokeWidth="1.5"
			strokeLinecap="round"
			strokeLinejoin="round"
		/>
	</svg>
);

/**
 * Image placeholder icon.
 *
 * @return {JSX.Element} SVG icon element.
 */
const ImagePlaceholderIcon = () => (
	<svg
		width="48"
		height="48"
		viewBox="0 0 48 48"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
	>
		<rect
			x="6"
			y="10"
			width="36"
			height="28"
			rx="2"
			stroke="currentColor"
			strokeWidth="2"
		/>
		<circle cx="15" cy="19" r="3" stroke="currentColor" strokeWidth="2" />
		<path
			d="M6 32L16 22L26 32"
			stroke="currentColor"
			strokeWidth="2"
			strokeLinecap="round"
			strokeLinejoin="round"
		/>
		<path
			d="M24 28L32 20L42 30"
			stroke="currentColor"
			strokeWidth="2"
			strokeLinecap="round"
			strokeLinejoin="round"
		/>
	</svg>
);

/**
 * Upgrade Modal Component.
 *
 * @param {Object}   props             Component props.
 * @param {boolean}  props.isOpen      Whether the modal is open.
 * @param {Function} props.onClose     Function to close the modal.
 * @param {string}   props.title       Header title (e.g., "Advanced Styling").
 * @param {string}   props.heading     Main heading (e.g., "Unlock Custom Styling").
 * @param {string}   props.description Description text.
 * @param {Array}    props.features    Array of feature strings.
 * @param {string}   props.buttonText  Button text (default: "Upgrade to Unlock").
 * @param {string}   props.buttonUrl   URL for the upgrade button.
 * @param {string}   props.image       Optional image URL.
 * @return {JSX.Element|null} Modal component or null if not open.
 */
const UpgradeModal = ( {
	isOpen,
	onClose,
	title,
	heading,
	description,
	features = [],
	buttonText = __( 'Upgrade to Unlock', 'sureforms' ),
	buttonUrl = '',
	image = '',
} ) => {
	// Add and remove the CSS on mount/unmount.
	useLayoutEffect( () => {
		styles.use();
		return () => {
			styles.unuse();
		};
	}, [] );

	if ( ! isOpen ) {
		return null;
	}

	const upgradeUrl =
		buttonUrl ||
		window.srfm_block_data?.upgrade_url ||
		'https://sureforms.com/pricing/';

	return (
		<Modal
			onRequestClose={ onClose }
			className="srfm-upgrade-modal-wrapper"
			overlayClassName="srfm-upgrade-modal-overlay"
		>
			<div className="srfm-upgrade-modal-header">
				<div className="srfm-upgrade-modal-header-title">
					<LightningIcon />
					<span>{ title }</span>
				</div>
				<button
					type="button"
					className="srfm-upgrade-modal-close"
					onClick={ onClose }
					aria-label={ __( 'Close', 'sureforms' ) }
				>
					<CloseIcon />
				</button>
			</div>

			<div className="srfm-upgrade-modal-body">
				<div className="srfm-upgrade-modal-image">
					{ image ? (
						<img src={ image } alt={ heading } />
					) : (
						<div className="srfm-upgrade-modal-image-placeholder">
							<ImagePlaceholderIcon />
						</div>
					) }
				</div>

				<div className="srfm-upgrade-modal-content">
					<h2 className="srfm-upgrade-modal-heading">{ heading }</h2>

					{ description && (
						<p className="srfm-upgrade-modal-description">
							{ description }
						</p>
					) }

					{ features.length > 0 && (
						<ul className="srfm-upgrade-modal-features">
							{ features.map( ( feature, index ) => (
								<li key={ index }>
									<CheckIcon />
									<span>{ feature }</span>
								</li>
							) ) }
						</ul>
					) }

					<Button
						variant="primary"
						className="srfm-upgrade-modal-button"
						href={ upgradeUrl }
						target="_blank"
					>
						{ buttonText }
					</Button>
				</div>
			</div>
		</Modal>
	);
};

export default UpgradeModal;
