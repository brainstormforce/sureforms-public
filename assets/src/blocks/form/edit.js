/** @jsx jsx */
import { css, jsx } from '@emotion/react';
import apiFetch from '@wordpress/api-fetch';
import {
	InnerBlocks,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { createBlocksFromInnerBlocksTemplate, parse } from '@wordpress/blocks';
import { useDispatch, useSelect } from '@wordpress/data';
import { Fragment, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import Setup from './components/Setup';

export default function edit( { clientId, attributes, setAttributes } ) {
	const [ patterns, setPatterns ] = useState( [] );

	const {
		id,
	} = attributes;

	const blockCount = useSelect( ( select ) =>
		select( blockEditorStore ).getBlockCount( clientId )
	);
	const { replaceInnerBlocks, setTemplateValidity } =
		useDispatch( blockEditorStore );

	// set template to valid for our post type.
	// prevents template changed warnings.
	const postType = useSelect( ( select ) =>
		select( 'core/editor' ).getCurrentPostType()
	);
	useEffect( () => {
		if ( postType === 'sureforms_form' ) {
			setTemplateValidity( true );
		}
	}, [ postType ] );

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
		getPatterns();
	}, [] );

	const getPatterns = async () => {
		const patterns = await apiFetch( {
			path: '/sureforms/v1/form-patterns',
		} );
		setPatterns( patterns );
	};

	/**
	 * Maybe create the template for the form.
	 *
	 * @param root0
	 * @param root0.template
	 */
	const maybeCreateTemplate = async ( {
		template = 'contact-form',
	} ) => {
		const pattern = patterns.find(
			( pattern ) => pattern.name === `sureforms/${ template }`
		);

		if ( ! pattern ) {
			alert( 'Something went wrong' );
			return;
		}
		// parse blocks.
		const parsed = parse( pattern.content );

		return parsed;
	};

	const onCreate = async ( template ) => {
	const onCreate = async (template) => {
		const result = await maybeCreateTemplate( {
			template,
		} );

		replaceInnerBlocks(
			clientId,
			createBlocksFromInnerBlocksTemplate( result ),
			false
		);
	};

	return (
		<Fragment>
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
				>
					<InnerBlocks
						templateLock={ false }
						renderAppender={
							blockCount
								? undefined
								: InnerBlocks.ButtonBlockAppender
						}
					/>
				</div>
			) }
		</Fragment>
	);
}}
