/** @jsx jsx */
import { jsx } from '@emotion/react';
import apiFetch from '@wordpress/api-fetch';
import {
	store as blockEditorStore
} from '@wordpress/block-editor';
import { createBlocksFromInnerBlocksTemplate, parse } from '@wordpress/blocks';
import { useDispatch, useSelect } from '@wordpress/data';
import { Fragment, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import Setup from './components/setup';

export default function edit({ clientId, attributes, setAttributes }) {
	const [patterns, setPatterns] = useState([]);

	const {
		id
	} = attributes;

	const blockCount = useSelect((select) =>
		select(blockEditorStore).getBlockCount(clientId)
	);
	const { replaceInnerBlocks, setTemplateValidity } =
		useDispatch(blockEditorStore);

	// set template to valid for our post type.
	// prevents template changed warnings.
	const postType = useSelect((select) =>
		select('core/editor').getCurrentPostType()
	);
	useEffect(() => {
		if (postType === 'sureforms_form') {
			setTemplateValidity(true);
		}
	}, [postType]);

	const formId = useSelect((select) => {
		// parent block id attribute.
		const parents = select(blockEditorStore).getBlockParents(clientId);
		const parentBlock = select(blockEditorStore).getBlocksByClientId(
			parents?.[0]
		);
		// current post id.
		const post_id = select('core/editor').getCurrentPostId();
		return parentBlock?.[0]?.attributes?.id || post_id;
	});

	useEffect(() => {
		// getPatterns();
	}, []);

	const getPatterns = async () => {
		const patterns = await apiFetch({
			path: '/surecart/v1/form-patterns',
		});
		setPatterns(patterns);
	};

	/**
	 * Maybe create the template for the form.
	 */
	const maybeCreateTemplate = async ({
		template = 'default',
		choices,
		choice_type,
	}) => {
		const pattern = patterns.find(
			(pattern) => pattern.name === `surecart/${template}`
		);

		if (!pattern) {
			alert('Something went wrong');
			return;
		}
		// parse blocks.
		let parsed = parse(pattern.content);

		return parsed;
	};

	const onCreate = async ({
		choices,
		choice_type,
		template,
		custom_success_url,
		success_url,
	}) => {
		// form attributes.
		setAttributes({
			prices: choice_type === 'all' ? choices : [],
			redirect: custom_success_url && success_url ? success_url : '',
		});

		const result = await maybeCreateTemplate({
			template,
			choices,
			choice_type,
		});

		replaceInnerBlocks(
			clientId,
			createBlocksFromInnerBlocksTemplate(result),
			false
		);
	};

	return (
		<Fragment>
			{blockCount === 0 ? (
				<Setup
					templates={patterns}
					onCreate={onCreate}
					clientId={clientId}
				/>
			) : (
				__( 'Form Should be here' )
			)}
		</Fragment>
	);
}
