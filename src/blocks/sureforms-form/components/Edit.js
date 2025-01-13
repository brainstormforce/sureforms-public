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

	const status = useEntityProp( 'postType', 'sureforms_form', 'status', id );

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

			if( getHeight && 0 !== getHeight ) {
				// set height of iframe if form is not empty.
				setFormIframeHeight( getHeight );
				iframeRef.current.height = getHeight;
			}
		}

		setLoading( false );
	};

	useEffect( () => {
		if ( ! iframeContainerRef?.current ) {
			return;
		}

		const formElement = iframeContainerRef?.current; // Replace with your form's ID or selector

		// Define the observer options
		const options = {
			root: null, // Use the viewport as the container
			rootMargin: '0px', // No margin around the root
			threshold: 0.1 // Trigger when at least 10% of the form is visible
		};

		// Callback function when visibility changes
		const observerCallback = (entries, observer) => {
			entries.forEach(entry => {

				console.log( {entry} );

				if ( ! formIframeHeight && entry.isIntersecting) {
					console.log('Form is in the viewport.');
					// Add any logic when the form is visible
					const iframeDocument = iframeRef.current.contentDocument;

					const formOuterContainerSelector = iframeDocument.querySelector(
						'.srfm-form-container'
					);
			
					if ( formOuterContainerSelector ) {
						const getHeight = formOuterContainerSelector.offsetHeight;
						if(  getHeight && 0 !== getHeight ) {
							// set height of iframe if form is not empty.
							setFormIframeHeight( getHeight );
							iframeRef.current.height = getHeight;
						}
					}
				}
			});
		};

		// Create an Intersection Observer
		const observer = new IntersectionObserver(observerCallback, options);

		// Start observing the form
		observer.observe(formElement);

		// Clean up
		return () => observer.disconnect();
	}, [iframeContainerRef?.current]);

	useEffect( () => {
		if ( iframeRef && iframeRef.current ) {
			setLoading( true );

			iframeRef.current.onload = () => {
				modifyIframeContent();
			};
		}
	}, [ id, iframeRef, hasResolved ] );

	// If form is in draft or trash then show the warning.
	if ( isMissing || 'trash' === status[ 0 ] || 'draft' === status[ 0 ] ) {
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
					<div className="srfm-iframe-container" ref={ iframeContainerRef }>
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
