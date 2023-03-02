/** @jsx jsx */
import { css, jsx } from '@emotion/react';

/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import {
	createBlock, serialize
} from '@wordpress/blocks';
import { Placeholder } from '@wordpress/components';
import { dispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { receipt as icon } from '@wordpress/icons';

/**
 * Components
 */
import { ScButton, ScInput } from '@surecart/components-react';
import PlaceholderTemplate from '../../form/components/PlaceholderTemplate';
import SelectForm from './SelectForm';

export default ({ attributes, setAttributes }) => {
	const { title, step } = attributes;
	const [form, setForm] = useState({});

	const blockProps = useBlockProps({
		css: css`
			.components-placeholder.components-placeholder {
				padding: 2em;
			}
		`,
	});

	// save the form block.
	const saveFormBlock = async () => {
		setAttributes({ loading: true });

		try {
			const updatedRecord = await dispatch('core').saveEntityRecord(
				'postType',
				'sureforms_form',
				{
					title: title || __('Untitled Form', 'sureforms'),
					content: serialize(
						createBlock(
							'sureforms/form', // name
							{},
							[]
						)
					),
					status: 'publish',
				}
			);
			setAttributes({ id: updatedRecord.id });
		} catch (e) {
			// TODO: Add notice here.
			console.error(e);
		} finally {
			setAttributes({ loading: false });
		}
	};

	if (step === 'new') {
		return (
			<div {...blockProps}>
				<PlaceholderTemplate
					header={__('Create a SureForms Form', 'sureforms')}
				>
					<div
						css={css`
							display: grid;
							gap: 0.5em;
							width: 100%;
						`}
					>
						<div>{__('Form Title', 'sureforms')}</div>
						<ScInput
							css={css`
								max-width: 400px;
							`}
							value={title}
							placeholder={__(
								'Enter a title for your form',
								'sureforms'
							)}
							onScChange={(e) =>
								setAttributes({ title: e.target.value })
							}
						/>
						<div>
							<ScButton
								type="primary"
								onClick={() => {
									saveFormBlock();
								}}
							>
								{__('Next', 'sureforms')}
								<sc-icon
									name="arrow-right"
									slot="suffix"
								></sc-icon>
							</ScButton>
							<ScButton
								type="text"
								onClick={() => setAttributes({ step: null })}
							>
								{__('Cancel', 'sureforms')}
							</ScButton>
						</div>
					</div>
				</PlaceholderTemplate>
			</div>
		);
	}

	if (step === 'select') {
		return (
			<div {...blockProps}>
				<PlaceholderTemplate
					header={__('Select a SureForms form', 'sureforms')}
				>
					<div
						css={css`
							display: grid;
							gap: 0.5em;
							width: 100%;
						`}
					>
						<SelectForm form={form} setForm={setForm} />
						<div>
							<ScButton
								type="primary"
								onClick={() => {
									setAttributes({ id: form?.id });
								}}
							>
								{__('Choose', 'sureforms')}
								<sc-icon
									name="arrow-right"
									slot="suffix"
								></sc-icon>
							</ScButton>
							<ScButton
								type="text"
								onClick={() => setAttributes({ step: null })}
							>
								{__('Cancel', 'sureforms')}
							</ScButton>
						</div>
					</div>
				</PlaceholderTemplate>
			</div>
		);
	}

	return (
		<div {...blockProps}>
			<Placeholder
				icon={icon}
				instructions={__(
					'Get started by selecting a form or start build a new form.',
					'sureforms'
				)}
				label={__('Add a sureforms form', 'sureforms')}
			>
				<div
					css={css`
						display: flex;
						gap: 0.5em;
					`}
				>
					<ScButton
						type="primary"
						onClick={() => setAttributes({ step: 'new' })}
					>
						{__('New Form', 'sureforms')}
					</ScButton>
					<ScButton
						type="default"
						onClick={() => setAttributes({ step: 'select' })}
					>
						{__('Select Form', 'sureforms')}
					</ScButton>
				</div>
			</Placeholder>
		</div>
	);
};
