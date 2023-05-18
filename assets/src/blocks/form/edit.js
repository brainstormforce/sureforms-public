/* eslint-disable react/no-unknown-property */
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
import Setup from './components/Setup';

export default function Edit( { clientId, attributes, setAttributes } ) {
	const { id } = attributes;
	const [ patterns, setPatterns ] = useState( [] );

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
	}, [ formId, id, setAttributes ] );

	useEffect( () => {
		getPatterns();
	}, [] );

	const getPatterns = async () => {
		const newPatterns = await apiFetch( {
			path: '/sureforms/v1/form-patterns',
		} );
		setPatterns( newPatterns );
	};

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
}
