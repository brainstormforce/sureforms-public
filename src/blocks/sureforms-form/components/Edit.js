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
} from '@wordpress/components';
import { useEntityProp, store as coreStore } from '@wordpress/core-data';
import {
	InspectorControls,
	useBlockProps,
	Warning,
} from '@wordpress/block-editor';

export default ( { attributes, setAttributes } ) => {
	const { id, hideTitle } = attributes;
	const iframeRef = useRef( null );
	const [ loading, setLoading ] = useState( false );

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
	const removeContentFromIframe = () => {
		const iframeDocument = iframeRef.current.contentDocument;

		if ( ! iframeDocument ) {
			return;
		}

		const wpAdminBar = iframeDocument.getElementById( 'wpadminbar' );
		const siteDesktopHeader =
			iframeDocument.querySelector( '.site-header' );
		const srfmSinglePageBanner =
			iframeDocument.querySelector( '.srfm-page-banner' );
		const srfmSingleForm =
			iframeDocument.querySelector( '.srfm-single-form' );
		const srfmSuccessMsg =
			iframeDocument.querySelector( '.srfm-success-box' );
		const formContainer = iframeDocument.querySelector(
			'.srfm-form-container'
		);
		const formContainerWrapper =
			iframeDocument.querySelector( '.srfm-form-wrapper' );

		const formOuterContainerSelector = iframeDocument.querySelector(
			'.srfm-single-page-container'
		);

		const siteFooter = iframeDocument.getElementById( 'colophon' );
		const iframeHtml = iframeDocument.querySelector(
			'html.srfm-html.hydrated'
		);

		if ( formContainerWrapper ) {
			formContainerWrapper.style.setProperty( 'padding', '0' );
		}

		if ( formContainer ) {
			formContainer.style.setProperty( 'margin-top', '0' );
			formContainer.style.setProperty( 'box-shadow', 'none' );
			formContainer.style.setProperty( 'max-width', '100%' );
			formContainer.style.setProperty( 'padding', '0' );
			formContainer.style.setProperty(
				'background-color',
				'transparent'
			);
			formContainer.style.setProperty( 'background-image', 'none' );
		}

		if ( iframeHtml ) {
			iframeHtml.style.setProperty(
				'background',
				'transparent',
				'important'
			);
			iframeHtml.style.setProperty( 'margin-top', '0', 'important' );
		}
		if ( iframeDocument.body ) {
			const bodyStyle = iframeDocument.body.style;
			bodyStyle.pointerEvents = 'none';
			bodyStyle.backgroundColor = 'transparent';
			bodyStyle.overflow = 'hidden';
		}

		// Combine style changes
		if ( wpAdminBar ) {
			wpAdminBar.remove();
		}
		if ( siteFooter ) {
			siteFooter.remove();
		}
		if ( siteDesktopHeader ) {
			siteDesktopHeader.remove();
		}
		if ( srfmSinglePageBanner ) {
			srfmSinglePageBanner.remove();
		}
		if ( srfmSuccessMsg ) {
			srfmSuccessMsg.remove();
		}

		if ( formOuterContainerSelector ) {
			const iframeHalfScrollHeight =
				formOuterContainerSelector.offsetHeight;
			iframeRef.current.height = iframeHalfScrollHeight + 'px';
		}

		// Combine element removal
		if ( srfmSingleForm ) {
			srfmSingleForm.style.boxShadow = 'none';
			srfmSingleForm.style.backgroundColor = 'transparent';
			srfmSingleForm.style.width = '100%';
		}

		setLoading( false );
	};

	// useEffect( () => {
	// 	if ( iframeRef && iframeRef.current ) {
	// 		setLoading( true );
	// 		iframeRef.current.onload = () => {
	// 			removeContentFromIframe();
	// 		};
	// 	}
	// }, [ id, iframeRef, hasResolved ] );

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
								'Hide title on this page',
								'sureforms'
							) }
							checked={ hideTitle }
							onChange={ ( value ) => {
								setAttributes( { hideTitle: value } );
							} }
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={ __( 'Form Title', 'sureforms' ) }
							value={ title }
							disabled={ hideTitle }
							onChange={ ( value ) => {
								setTitle( value );
							} }
						/>
					</PanelRow>
					<PanelRow>
						<p className="srfm-form-notice">
							{ __(
								'Note: For Editing the SureForm, please check the SureForms Editor - ',
								'sureforms'
							) }
							<a
								href={ `${ sfBlockData.post_url }?post=${ id }&action=edit` }
								target="_blank"
								rel="noreferrer"
							>
								{ __( 'Edit Form', 'sureforms' ) }
								<svg
									xmlns="http://www.w3.org/2000/svg"
									viewBox="0 0 24 24"
									width="16"
									height="16"
									aria-hidden="true"
									focusable="false"
								>
									<path d="M19.5 4.5h-7V6h4.44l-5.97 5.97 1.06 1.06L18 7.06v4.44h1.5v-7Zm-13 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-3H17v3a.5.5 0 0 1-.5.5h-10a.5.5 0 0 1-.5-.5v-10a.5.5 0 0 1 .5-.5h3V5.5h-3Z"></path>
								</svg>
							</a>
						</p>
					</PanelRow>
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
					<div className="srfm-iframe-container">
						{ loading && (
							<div className="srfm-iframe-loader">
								<Spinner />
							</div>
						) }
						<iframe
							loading={ 'eager' }
							ref={ iframeRef }
							title="srfm-iframe"
							src={ formUrl + '?form_preview=true' }
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
