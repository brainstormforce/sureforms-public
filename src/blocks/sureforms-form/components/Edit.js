/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useRef, useEffect, useState, useCallback } from '@wordpress/element';
import {
	Placeholder,
	TextControl,
	PanelBody,
	PanelRow,
	Spinner,
	Button,
	ToggleControl,
	SelectControl,
	ExternalLink,
} from '@wordpress/components';
import { useEntityProp, store as coreStore } from '@wordpress/core-data';
import {
	InspectorControls,
	useBlockProps,
	Warning,
} from '@wordpress/block-editor';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import {
	getColorControls,
	getLayoutControls,
	getFieldControls,
	getButtonControls,
} from './panel-controls';
import UpgradeModal from '@Components/upgrade-modal';

export default ( { attributes, setAttributes, clientId } ) => {
	const { id, showTitle, formTheme, blockId } = attributes;

	const iframeRef = useRef( null );
	const iframeContainerRef = useRef( null );
	const [ loading, setLoading ] = useState( false );
	const [ formIframeHeight, setFormIframeHeight ] = useState( 0 );
	const [ showUpgradeModal, setShowUpgradeModal ] = useState( false );

	// Generate unique blockId if not set (first render).
	useEffect( () => {
		if ( ! blockId ) {
			setAttributes( { blockId: clientId.substring( 0, 8 ) } );
		}
	}, [ blockId, clientId, setAttributes ] );

	// Check if Pro is active by looking for Pro-specific localized data.
	const isProActive = Boolean( window.srfm_block_data?.is_pro_active );

	// eslint-disable-next-line no-unused-vars
	const [ formUrl, setFormUrl ] = useEntityProp(
		'postType',
		'sureforms_form',
		'link',
		id
	);

	const blockProps = useBlockProps();

	const [ title, setTitle ] = useEntityProp(
		'postType',
		'sureforms_form',
		'title',
		id
	);

	const status = useSelect(
		( select ) => {
			const record = select( coreStore ).getEntityRecord(
				'postType',
				'sureforms_form',
				id
			);
			return record ? record.status : undefined;
		},
		[ id ]
	);

	const { isMissing, hasResolved } = useSelect(
		( select ) => {
			const hasResolvedValue = select( coreStore ).hasFinishedResolution(
				'getEntityRecord',
				[ 'postType', 'sureforms_form', id ]
			);
			const form = select( coreStore ).getEntityRecord(
				'postType',
				'sureforms_form',
				id
			);
			const canEdit =
				select( coreStore ).canUserEditEntityRecord( 'sureforms_form' );
			return {
				canEdit,
				isMissing: hasResolvedValue && ! form,
				hasResolved: hasResolvedValue,
				form,
			};
		},
		[ id ]
	);

	// Remove unwanted elements from the iframe and add styling for the form
	const modifyIframeContent = () => {
		const iframeDocument = iframeRef.current.contentDocument;

		if ( ! iframeDocument ) {
			return;
		}

		const formOuterContainerSelector = iframeDocument.querySelector(
			'.srfm-form-container'
		);

		if ( formOuterContainerSelector ) {
			const getHeight = formOuterContainerSelector.offsetHeight;

			if ( getHeight && 0 !== getHeight ) {
				// set height of iframe if form is not empty.
				setFormIframeHeight( getHeight );
				iframeRef.current.height = getHeight;
			}
		}

		setLoading( false );
	};

	useEffect( () => {
		// Ensure the iframe container reference is valid
		if ( ! iframeContainerRef?.current ) {
			return;
		}

		const formElement = iframeContainerRef?.current; // Reference to the element being observed

		const options = {
			root: null, // Observe within the viewport
			rootMargin: '0px', // No additional margins
			threshold: 0.1, // Trigger when 10% of the element is visible
		};

		const observerCallback = ( entries ) => {
			entries.forEach( ( entry ) => {
				// Check if form is visible and iframe height hasn't been set
				if ( ! formIframeHeight && entry.isIntersecting ) {
					// Access the iframe's content
					const iframeDocument = iframeRef.current.contentDocument;

					// Find the form container inside the iframe
					const formOuterContainerSelector =
						iframeDocument.querySelector( '.srfm-form-container' );

					if ( formOuterContainerSelector ) {
						const getHeight =
							formOuterContainerSelector.offsetHeight;

						// Set iframe height if a valid height is found
						if ( getHeight && getHeight !== 0 ) {
							setFormIframeHeight( getHeight );
							iframeRef.current.height = getHeight;
						}
					}
				}
			} );
		};

		// Initialize Intersection Observer
		const observer = new IntersectionObserver( observerCallback, options );

		// Start observing the form element
		observer.observe( formElement );

		// Clean up the observer when the component unmounts or dependencies change
		return () => observer.disconnect();
	}, [] );

	useEffect( () => {
		if ( iframeRef && iframeRef.current ) {
			setLoading( true );

			iframeRef.current.onload = () => {
				modifyIframeContent();
			};
		}
	}, [ id, iframeRef, hasResolved, attributes.formTheme ] );

	// Send styling updates to iframe via PostMessage
	const sendStylingToIframe = useCallback( () => {
		if ( ! iframeRef.current?.contentWindow || ! formUrl ) {
			return;
		}

		const iframeOrigin = new URL( formUrl ).origin;

		// If inheriting styling, send reset message to reload iframe with original styles
		if ( 'inherit' === attributes.formTheme ) {
			iframeRef.current.contentWindow.postMessage(
				{
					type: 'srfm-reset-styling',
				},
				iframeOrigin
			);
			return;
		}

		// Base styling object for free version
		const baseStyling = {
			formTheme: attributes.formTheme,
			// Colors
			primaryColor: attributes.primaryColor,
			textColor: attributes.textColor,
			textOnPrimaryColor: attributes.textOnPrimaryColor,
			// Background
			bgType: attributes.bgType,
			bgColor: attributes.bgColor,
			bgGradient: attributes.bgGradient,
			bgImage: attributes.bgImage,
			bgImagePosition: attributes.bgImagePosition,
			bgImageSize: attributes.bgImageSize,
			bgImageRepeat: attributes.bgImageRepeat,
			bgImageAttachment: attributes.bgImageAttachment,
			// Layout
			formPaddingTop: attributes.formPaddingTop,
			formPaddingRight: attributes.formPaddingRight,
			formPaddingBottom: attributes.formPaddingBottom,
			formPaddingLeft: attributes.formPaddingLeft,
			formPaddingUnit: attributes.formPaddingUnit,
			formBorderRadiusTop: attributes.formBorderRadiusTop,
			formBorderRadiusRight: attributes.formBorderRadiusRight,
			formBorderRadiusBottom: attributes.formBorderRadiusBottom,
			formBorderRadiusLeft: attributes.formBorderRadiusLeft,
			formBorderRadiusUnit: attributes.formBorderRadiusUnit,
			// Fields
			fieldSpacing: attributes.fieldSpacing,
			// Button
			buttonAlignment: attributes.buttonAlignment,
		};

		// Allow Pro to extend the styling object
		const styling = applyFilters(
			'srfm.embed.previewStyling',
			baseStyling,
			attributes
		);

		iframeRef.current.contentWindow.postMessage(
			{
				type: 'srfm-update-styling',
				styling,
			},
			iframeOrigin
		);
	}, [ attributes, formUrl ] );

	// Trigger styling update when attributes change
	useEffect( () => {
		sendStylingToIframe();
	}, [ sendStylingToIframe ] );

	// Re-send styling after iframe loads
	useEffect( () => {
		if ( ! loading && iframeRef.current?.contentWindow ) {
			const timer = setTimeout( () => {
				sendStylingToIframe();
			}, 100 );
			return () => clearTimeout( timer );
		}
	}, [ loading, sendStylingToIframe ] );

	// Handle image selection for background
	const onSelectImage = ( label, media ) => {
		if ( ! media || ! media.url ) {
			setAttributes( { [ label ]: '' } );
			return;
		}
		setAttributes( {
			[ label ]: media.url,
			bgImageId: media.id,
		} );
	};

	// Get panel controls from separate file
	// Pro can filter these arrays to add, remove, or modify controls
	const colorControls = getColorControls( {
		attributes,
		setAttributes,
		onSelectImage,
	} );
	const layoutControls = getLayoutControls( { attributes, setAttributes } );
	const fieldControls = getFieldControls( { attributes, setAttributes } );
	const buttonControls = getButtonControls( { attributes, setAttributes } );

	// If the form is not published or is missing, show a warning and allow the user to change the form.
	if ( isMissing || ( status && 'publish' !== status ) ) {
		return (
			<>
				<InspectorControls>
					<PanelBody>
						<PanelRow>
							<Button
								variant="secondary"
								text={ __( 'Change Form', 'sureforms' ) }
								onClick={ () => {
									setAttributes( { id: undefined } );
								} }
								className="srfm-change-form-btn"
							/>
						</PanelRow>
					</PanelBody>
				</InspectorControls>
				<div { ...blockProps }>
					<Warning>
						{ __(
							'This form has been deleted or is unavailable.',
							'sureforms'
						) }
					</Warning>
				</div>
			</>
		);
	}

	return (
		<>
			<InspectorControls>
				{ /* 1. Form Settings (General) */ }
				<PanelBody title={ __( 'Form Settings', 'sureforms' ) }>
					<PanelRow>
						<ToggleControl
							label={ __(
								'Show Form Title on this Page',
								'sureforms'
							) }
							checked={ showTitle }
							onChange={ ( value ) => {
								setAttributes( { showTitle: value } );
							} }
							className="srfm-form-page-title-toggle"
						/>
					</PanelRow>
					{ showTitle && (
						<PanelRow>
							<TextControl
								label={ __( 'Form Title', 'sureforms' ) }
								value={ title }
								onChange={ ( value ) => {
									setTitle( value );
								} }
								className="srfm-form-page-title-input-wrapper"
							/>
						</PanelRow>
					) }
					<SelectControl
						label={ __( 'Form Theme', 'sureforms' ) }
						value={ formTheme }
						options={ applyFilters( 'srfm.embed.formThemeOptions', [
							{
								label: __(
									"Inherit Form's Original Style",
									'sureforms'
								),
								value: 'inherit',
							},
							{
								label: __( 'Default', 'sureforms' ),
								value: 'default',
							},
							{
								label: __( 'Custom (Premium)', 'sureforms' ),
								value: 'custom',
							},
						] ) }
						onChange={ ( value ) => {
							// If Custom is selected and Pro is not active, show upgrade modal.
							if ( 'custom' === value && ! isProActive ) {
								setShowUpgradeModal( true );
								return;
							}
							setAttributes( { formTheme: value } );
						} }
						help={ __(
							'Select a theme style for this form embed.',
							'sureforms'
						) }
					/>
					{ srfm_block_data.is_admin_user && (
						<PanelRow>
							<p className="srfm-form-notice">
								{ __(
									'Note: For editing SureForms, please refer to the SureForms Editor - ',
									'sureforms'
								) }
								<ExternalLink
									href={ `${ srfm_block_data.post_url }?post=${ id }&action=edit` }
								>
									{ __( 'Edit Form', 'sureforms' ) }
								</ExternalLink>
							</p>
						</PanelRow>
					) }
					<PanelRow>
						<Button
							variant="secondary"
							text={ __( 'Change Form', 'sureforms' ) }
							onClick={ () => {
								setAttributes( { id: undefined } );
							} }
							className="srfm-change-form-btn"
						/>
					</PanelRow>
				</PanelBody>

				{ /* Styling panels - only show when not inheriting */ }
				{ 'inherit' !== formTheme && (
					<>
						{ /* 2. Colors */ }
						<PanelBody
							title={ __( 'Colors', 'sureforms' ) }
							initialOpen={ false }
						>
							{ colorControls.map( ( control ) => (
								<div
									className="components-base-control"
									key={ control.id }
								>
									{ control.component }
								</div>
							) ) }
						</PanelBody>

						{ /* 3. Layout */ }
						<PanelBody
							title={ __( 'Layout', 'sureforms' ) }
							initialOpen={ false }
							className="srfm-layout-panel"
						>
							{ layoutControls.map( ( control ) => (
								<div
									className="components-base-control"
									key={ control.id }
								>
									{ control.component }
								</div>
							) ) }
						</PanelBody>

						{ /* 4. Fields */ }
						<PanelBody
							title={ __( 'Fields', 'sureforms' ) }
							initialOpen={ false }
						>
							{ fieldControls.map( ( control ) => (
								<div
									className="components-base-control"
									key={ control.id }
								>
									{ control.component }
								</div>
							) ) }
						</PanelBody>

						{ /* 5. Button */ }
						<PanelBody
							title={ __( 'Button', 'sureforms' ) }
							initialOpen={ false }
						>
							{ buttonControls.map( ( control ) => (
								<div
									className="components-base-control"
									key={ control.id }
								>
									{ control.component }
								</div>
							) ) }
						</PanelBody>

						{ /* Pro can add additional panels via filter */ }
						{ applyFilters( 'srfm.embed.additionalPanels', null, {
							attributes,
							setAttributes,
						} ) }
					</>
				) }
			</InspectorControls>
			{ hasResolved ? (
				<div { ...blockProps }>
					<div
						className="srfm-iframe-container"
						ref={ iframeContainerRef }
					>
						{ loading && (
							<div className="srfm-iframe-loader">
								<Spinner />
							</div>
						) }
						{ showTitle && title && (
							<h2 className="srfm-form-title">{ title }</h2>
						) }
						<iframe
							loading={ 'eager' }
							ref={ iframeRef }
							title="srfm-iframe"
							src={
								formUrl +
								`?preview_id=${ id }&preview=true&form_preview=true&formTheme=${ encodeURIComponent( attributes.formTheme || 'inherit' ) }`
							}
							width={ '100%' }
						/>
					</div>
				</div>
			) : (
				<div { ...blockProps }>
					<Placeholder>
						<Spinner />
					</Placeholder>
				</div>
			) }
			<UpgradeModal
				isOpen={ showUpgradeModal }
				onClose={ () => setShowUpgradeModal( false ) }
				title={ __( 'Advanced Styling', 'sureforms' ) }
				heading={ __( 'Unlock Custom Styling', 'sureforms' ) }
				description={ __(
					"Switch to Custom Mode to take full control of your form's design and spacing.",
					'sureforms'
				) }
				features={ [
					__(
						'Full color control (buttons, fields, text)',
						'sureforms'
					),
					__( 'Row and column gap control', 'sureforms' ),
					__( 'Field spacing and layout precision', 'sureforms' ),
					__( 'Complete button styling', 'sureforms' ),
				] }
			/>
		</>
	);
};
