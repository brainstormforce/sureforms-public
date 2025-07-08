/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useRef, useEffect, useState } from '@wordpress/element';
import {
	Placeholder,
	TextControl,
	PanelBody,
	PanelRow,
	Spinner,
	Button,
	ToggleControl,
	ExternalLink,
} from '@wordpress/components';
import { useEntityProp, store as coreStore } from '@wordpress/core-data';
import {
	InspectorControls,
	useBlockProps,
	Warning,
} from '@wordpress/block-editor';

export default ( { attributes, setAttributes } ) => {
	const { id, showTitle } = attributes;
	const iframeRef = useRef( null );
	const iframeContainerRef = useRef( null );
	const [ loading, setLoading ] = useState( false );
	const [ formIframeHeight, setFormIframeHeight ] = useState( 0 );

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

	const { isMissing, hasResolved } = useSelect( ( select ) => {
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
	} );

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
	}, [ iframeContainerRef?.current ] ); // Re-run if iframe container reference changes

	useEffect( () => {
		if ( iframeRef && iframeRef.current ) {
			setLoading( true );

			iframeRef.current.onload = () => {
				modifyIframeContent();
			};
		}
	}, [ id, iframeRef, hasResolved ] );

	console.log( status );

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
								`?preview_id=${ id }&preview=true&form_preview=true`
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
		</>
	);
};
