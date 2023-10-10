/* eslint-disable react/no-unknown-property */
/** @jsx jsx */
import { css, jsx } from '@emotion/react';
import apiFetch from '@wordpress/api-fetch';
import { applyFilters } from '@wordpress/hooks';
import {
	InnerBlocks,
	RichText,
	store as blockEditorStore,
	InspectorControls,
} from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { createBlocksFromInnerBlocksTemplate, parse } from '@wordpress/blocks';
import { useDispatch, useSelect } from '@wordpress/data';
import { Fragment, useEffect, useState } from '@wordpress/element';
import Setup from './components/Setup';
import { store as editorStore } from '@wordpress/editor';
import { TextControl, PanelBody, PanelRow } from '@wordpress/components';
import { useEntityProp, store as coreStore } from '@wordpress/core-data';

export default function Edit( { clientId, attributes, setAttributes } ) {
	const { id, submitButtonText, block_count } = attributes;
	const { editPost } = useDispatch( editorStore );
	// Get all registered block types
	const allBlocks = wp.blocks.getBlockTypes();
	const ALLOWED_BLOCKS = allBlocks
		.filter(
			( block ) =>
				block.name.includes( 'sureforms/' ) &&
				block.name !== 'sureforms/form' &&
				block.name !== 'sureforms/sf-form'
		)
		.map( ( block ) => block.name );

	const filteredAllowedBlocks = applyFilters(
		'sureforms/form/allowedBlocks',
		ALLOWED_BLOCKS
	);

	const [ patterns, setPatterns ] = useState( [] );

	const blockCount = useSelect( ( select ) =>
		select( blockEditorStore ).getBlockCount( clientId )
	);

	const { replaceInnerBlocks, setTemplateValidity } =
		useDispatch( blockEditorStore );

	const [ title, setTitle ] = useEntityProp(
		'postType',
		'sureforms_form',
		'title',
		id
	);

	// set template to valid for our post type.
	// prevents template changed warnings.
	const postType = useSelect( ( select ) =>
		select( 'core/editor' ).getCurrentPostType()
	);
	useEffect( () => {
		if ( postType === 'sureforms_form' ) {
			setTemplateValidity( true );
		}
	}, [ postType, setTemplateValidity ] );

	// Not sure can be used later.
	const formId = useSelect( ( select ) => {
		// parent block id attribute.
		const parents = select( blockEditorStore ).getBlockParents( clientId );
		const parentBlock = select( blockEditorStore ).getBlocksByClientId(
			parents?.[ 0 ]
		);
		// current post id.
		const post_id = select( 'core/editor' ).getCurrentPostId();
		return parentBlock?.[ 0 ]?.attributes?.id || post_id;
	} );

	useEffect( () => {
		if ( id !== formId ) {
			setAttributes( { id: formId } );
		}
		if ( block_count !== blockCount ) {
			setAttributes( { block_count: blockCount } );
		}
	}, [ formId, id, setAttributes, blockCount ] );

	useEffect( () => {
		getPatterns();
	}, [] );

	const getPatterns = async () => {
		const newPatterns = await apiFetch( {
			path: '/sureforms/v1/form-patterns',
		} );
		setPatterns( newPatterns );
	};

	const sureforms_keys = useSelect( ( select ) => {
		if ( 'sureforms_form' === postType ) {
			return select( 'core/editor' ).getEditedPostAttribute( 'meta' );
		}
		const form = select( coreStore ).getEntityRecord(
			'postType',
			'sureforms_form',
			formId
		);
		const postMeta = form?.meta;
		return postMeta;
	} );

	// Used to detect the FSE Theme
	const siteEditor = document.querySelector( '.site-editor-php' );

	useEffect( () => {
		if ( ! siteEditor ) {
			function updateMeta( option, value ) {
				const option_array = {};
				option_array[ option ] = value;
				editPost( {
					meta: option_array,
				} );
			}
			console.log( 'cls', attributes.className );
			updateMeta( '_sureforms_form_class_name', attributes.className );
		}
	}, [ siteEditor, attributes ] );

	/**
	 * Maybe create the template for the form.
	 *
	 * @param {Object} root0
	 * @param {string} root0.template
	 */
	const maybeCreateTemplate = async ( { template = 'contact-form' } ) => {
		const newPattern = patterns.find(
			( singlePattern ) =>
				singlePattern.name === `sureforms/${ template }`
		);

		if ( ! newPattern ) {
			alert( 'Something went wrong' );
			return;
		}
		// parse blocks.
		const parsed = parse( newPattern.content );

		return parsed;
	};

	const onCreate = async ( template ) => {
		const result = await maybeCreateTemplate( {
			template,
		} );

		replaceInnerBlocks(
			clientId,
			createBlocksFromInnerBlocksTemplate( result ),
			false
		);
	};

	const renderButtonHtml = () => {
		return (
			<button
				className={
					'sureform-submit-button ' +
					( sureforms_keys?._sureforms_submit_styling_inherit_from_theme &&
					'' === sureforms_keys?._sureforms_color1
						? 'wp-block-button__link'
						: 'sureforms-button' ) +
					( ! sureforms_keys?._sureforms_color1
						? ' sureforms-default-colors'
						: '' )
				}
			>
				<RichText
					tagName="div"
					placeholder={ __( 'Submit', 'sureforms' ) }
					value={ submitButtonText?.replace(
						/<(?!br\s*V?)[^>]+>/g,
						''
					) }
					onChange={ ( value ) =>
						setAttributes( {
							submitButtonText: value,
						} )
					}
					multiline={ false }
					style={ { textAlign: 'center' } }
				/>
			</button>
		);
	};

	return (
		<Fragment>
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
					{ 'sureforms_form' !== postType && (
						<PanelRow>
							<p className="sureform-form-notice">
								{ __(
									'Note: For Editing the stylings, please check the SureForms styling - ',
									'sureforms'
								) }
								<a
									href={ `${ sfBlockData.post_url }?post=${ id }&action=edit` }
									target="_blank"
									rel="noreferrer"
								>
									{ __( 'Edit Form Settings ', 'sureforms' ) }
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
					) }
				</PanelBody>
			</InspectorControls>
			{ blockCount === 0 ? (
				<Setup
					templates={ patterns }
					onCreate={ onCreate }
					clientId={ clientId }
				/>
			) : (
				<div
					css={ css`
						padding: 32px;
					` }
					className={
						'classic' === sureforms_keys?._sureforms_form_styling &&
						'sf-form-style-classic'
					}
				>
					<InnerBlocks
						allowedBlocks={ filteredAllowedBlocks }
						templateLock={ false }
						renderAppender={
							blockCount
								? undefined
								: InnerBlocks.ButtonBlockAppender
						}
					/>
					<div
						className={
							'sureform-submit-button' +
							( sureforms_keys?._sureforms_submit_styling_inherit_from_theme &&
							'' === sureforms_keys?._sureforms_color1
								? ' wp-block-button'
								: '' )
						}
					>
						{ renderButtonHtml() }
					</div>
				</div>
			) }
		</Fragment>
	);
}
