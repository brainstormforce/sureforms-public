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
	SelectControl,
	Button,
	Icon,
} from '@wordpress/components';
import {
	useEntityBlockEditor,
	useEntityProp,
	store as coreStore,
} from '@wordpress/core-data';
import {
	// useInnerBlocksProps as __stableUseInnerBlocksProps,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	// __experimentalUseInnerBlocksProps,
	// __experimentalBlockContentOverlay as BlockContentOverlay, // TODO when gutenberg releases it: https://github.com/WordPress/gutenberg/blob/afee31ee020b8965e811f5d68a5ca8001780af9d/packages/block-editor/src/components/block-content-overlay/index.js#L17
	InspectorControls,
	useBlockProps,
	Warning,
} from '@wordpress/block-editor';
import SelectForm from './SelectForm';
import apiFetch from '@wordpress/api-fetch';

export default ( { attributes, setAttributes } ) => {
	// TODO: Let's store a unique hash in both meta and attribute to find.
	const { id } = attributes;

	const iframeRef = useRef( null );
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

	const { hasResolved } = useSelect( ( select ) => {
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
			hasResolved: hasResolvedValue,
			form,
		};
	} );

	// form is missing
	if ( 'trash' === status[ 0 ] || 'draft' === status[ 0 ] ) {
		return (
			<div { ...blockProps }>
				<Warning>
					{ __(
						'This form has been deleted or is unavailable.',
						'sureforms'
					) }
				</Warning>
			</div>
		);
	}

	// Remove unwanted elements from the iframe and add styling for the form
	const removeContentFromIframe = () => {
		const iframeDocument = iframeRef.current.contentDocument;

		if ( iframeDocument ) {
			const wpAdminBar = iframeDocument.getElementById( 'wpadminbar' );
			const siteDesktopHeader =
				iframeDocument.querySelector( '.site-header' );
			const srfmSinglePageBanner =
				iframeDocument.querySelector( '.srfm-page-banner' );
			const srfmSingleForm =
				iframeDocument.querySelector( '.srfm-single-form' );
			const srfmSuccessMsg =
				iframeDocument.querySelector( '.srfm-success-box' );
			const siteFooter = iframeDocument.getElementById( 'colophon' );

			if ( iframeDocument && iframeDocument.body ) {
				iframeDocument.querySelector( 'html' ).style.backgroundColor =
					'transparent';
				iframeDocument.body.style.pointerEvents = 'none';
				iframeDocument.body.style.backgroundColor = 'transparent';
				const iframeHalfScrollHeight =
					iframeDocument.body.scrollHeight / 2;
				iframeRef.current.height = iframeHalfScrollHeight + 'px';
				if ( 300 > iframeHalfScrollHeight ) {
					iframeDocument.body.style.overflow = 'hidden';
				}
			}
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
			if ( srfmSingleForm ) {
				srfmSingleForm.style.boxShadow = 'none';
				srfmSingleForm.style.backgroundColor = 'transparent';
				srfmSingleForm.style.width = '100%';
			}
		}
	};

	useEffect( () => {
		if ( iframeRef && iframeRef.current ) {
			iframeRef.current.onload = () => {
				removeContentFromIframe();
			};
		}
	}, [ id, iframeRef, hasResolved ] );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Form Settings', 'sureforms' ) }>
					<PanelRow>
						<TextControl
							label={ __( 'Form Title', 'sureforms' ) }
							value={ title }
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
				</PanelBody>
			</InspectorControls>
			{ hasResolved ? (
				<div { ...blockProps }>
					<div
						style={ {
							display: 'flex',
							justifyContent: 'space-between',
							padding: ' 12px 32px 12px 32px',
							alignItems: 'center',
							boxShadow: '0 8px 8px -10px rgba(0, 0, 0, 0.1)',
						} }
					>
						<button
							style={ {
								background: 'none',
								border: 'none',
								display: 'flex',
								alignItems: 'center',
								cursor: 'pointer',
								height: '44px',
								gap: '10px',
								color: '#9CA3AF',
							} }
							onClick={ () => {
								setAttributes( { id: undefined } );
							} }
						>
							<svg
								xmlns="http://www.w3.org/2000/svg"
								width="24"
								height="24"
								viewBox="0 0 24 24"
								fill="none"
							>
								<path
									fill-rule="evenodd"
									clip-rule="evenodd"
									d="M18 11.9992C18 12.4963 17.5971 12.8992 17.1 12.8992H9.1345L11.7238 15.2505C12.0821 15.595 12.0933 16.1647 11.7487 16.523C11.4042 16.8813 10.8345 16.8925 10.4762 16.548L6.2762 12.648C6.09973 12.4783 6 12.244 6 11.9992C6 11.7544 6.09973 11.5202 6.2762 11.3505L10.4762 7.45047C10.8345 7.10596 11.4042 7.11713 11.7487 7.47542C12.0933 7.83372 12.0821 8.40346 11.7238 8.74797L9.1345 11.0992L17.1 11.0992C17.5971 11.0992 18 11.5022 18 11.9992Z"
									fill="#9CA3AF"
								/>
							</svg>
							{ __( 'Back', 'sureforms' ) }
						</button>
						<span
							style={ {
								color: '#030712',
								fontFamily: 'Inter',
								fontSize: '20px',
								fontStyle: 'normal',
								fontWeight: '600',
								lineHeight: '140%',
							} }
						>
							{ title }
						</span>
					</div>
					<div className="srfm-iframe-container">
						<iframe
							loading={ 'eager' }
							ref={ iframeRef }
							title="srfm-iframe"
							src={ formUrl }
							style={ {
								minWidth: '-webkit-fill-available',
							} }
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
