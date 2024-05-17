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
	const modifyIframeContent = () => {
		const iframeDocument = iframeRef.current.contentDocument;

		if ( ! iframeDocument ) {
			return;
		}

		const formOuterContainerSelector = iframeDocument.querySelector(
			'.srfm-form-container'
		);

		if ( formOuterContainerSelector ) {
			iframeRef.current.height = formOuterContainerSelector.offsetHeight;
		}

		setLoading( false );
	};

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
							/>
						</PanelRow>
					) }
					{ srfm_block_data.is_admin_user && (
						<PanelRow>
							<p className="srfm-form-notice">
								{ __(
									'Note: For Editing the SureForm, please check the SureForms Editor - ',
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
					<div className="srfm-iframe-container">
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
