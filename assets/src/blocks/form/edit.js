/** @jsx jsx */
import { styles } from '../../../admin/styles/admin';
import ColorPopup from '../../components/ColorPopup';
import Cart from './components/Cart';
import Mode from './components/Mode';
import Setup from './components/Setup';
import { css, jsx } from '@emotion/core';
import { ScCheckout, ScIcon } from '@surecart/components-react';
import apiFetch from '@wordpress/api-fetch';
import {
	InnerBlocks,
	InspectorControls,
	store as blockEditorStore,
	__experimentalLinkControl as LinkControl,
} from '@wordpress/block-editor';
import { parse } from '@wordpress/blocks';
import { createBlocksFromInnerBlocksTemplate } from '@wordpress/blocks';
import {
	PanelRow,
	PanelBody,
	Button,
	BaseControl,
	ToggleControl,
	UnitControl as __stableUnitControl,
	__experimentalUnitControl,
	TextControl,
} from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { Fragment, useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

export default function edit({ clientId, attributes, setAttributes }) {
	const [patterns, setPatterns] = useState([]);
	const UnitControl = __stableUnitControl
		? __stableUnitControl
		: __experimentalUnitControl;

	const {
		align,
		className,
		prices,
		font_size,
		loading_text,
		choice_type,
		mode,
		gap,
		color,
		success_url,
	} = attributes;

	const [custom_success_url, setCustomSuccessUrl] = useState(!!success_url);
	useEffect(() => {
		if (!custom_success_url) {
			setAttributes({ success_url: '' });
		}
	}, [custom_success_url]);

	const [tab, setTab] = useState('');
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
		if (postType === 'sc_form') {
			setTemplateValidity(true);
		}
	}, [postType]);

	const changeTemplate = async () => {
		const r = confirm(
			__(
				'Are you sure you want to change the template? This will completely replace your current form.',
				'surecart'
			)
		);
		if (!r) return;
		replaceInnerBlocks(clientId, [], false);
	};

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
		getPatterns();
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

		parsed = populateChoicesBlock(parsed, choices, choice_type);
		parsed = populateBlock(parsed, choices, 'surecart/donation');
		parsed = populateBlock(parsed, choices, 'surecart/name-your-price');

		return parsed;
	};

	/**
	 * Maybe populated the donation block with the correct price.
	 */
	const populateChoicesBlock = (blocks, choices, choice_type) => {
		const remove =
			!choices?.length || !['checkbox', 'radio'].includes(choice_type);

		// look through nested blocks and add or remove prices.
		blocks.forEach(function iter(block, index, blocks) {
			if (block.name === 'surecart/price-selector') {
				if (remove) {
					blocks.splice(index, 1);
				} else {
					blocks[index].attributes.type = choice_type;
					blocks[index].innerBlocks = choices.map((choice, index) => {
						return [
							'surecart/price-choice',
							{
								price_id: choice?.id,
								quantity: choice?.quantity || 1,
								type: choice_type,
								checked: index === 0 && choice_type === 'radio',
							},
						];
					});
				}
			}
			Array.isArray(block.innerBlocks) && block.innerBlocks.forEach(iter);
		});

		return blocks;
	};

	/**
	 * Maybe populated the donation block with the correct price.
	 */
	const populateBlock = (blocks, choices, name) => {
		const remove = !choices?.length;

		// look through nested blocks and add or remove prices.
		blocks.forEach(function iter(block, index, blocks) {
			if (block.name === name) {
				if (remove) {
					blocks.splice(index, 1);
				} else {
					blocks[index].attributes.price_id = choices[0].id;
				}
			}
			Array.isArray(block.innerBlocks) && block.innerBlocks.forEach(iter);
		});

		return blocks;
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
			<InspectorControls>
				<PanelBody title={__('Form Template', 'surecart')}>
					<PanelRow>
						<div>
							<Button isPrimary onClick={changeTemplate}>
								{__('Change Template', 'surecart')}
							</Button>
						</div>
					</PanelRow>
				</PanelBody>
				<PanelBody title={__('Style', 'surecart')}>
					<PanelRow>
						<BaseControl.VisualLabel>
							{__('Form Highlight Color', 'surecart')}
						</BaseControl.VisualLabel>
						<ColorPopup
							color={color}
							setColor={(color) => {
								setAttributes({ color: color?.hex });
							}}
						/>
					</PanelRow>
					<PanelRow>
						<UnitControl
							label={__('Row Gap')}
							onChange={(gap) => setAttributes({ gap })}
							value={gap}
							help={__(
								'The this is the space between the rows of form elements.',
								'surecart'
							)}
							units={[
								{ value: 'px', label: 'px', default: 0 },
								{ value: 'em', label: 'em', default: 0 },
							]}
						/>
					</PanelRow>
				</PanelBody>
				<PanelBody title={__('Thank You Page', 'surecart')}>
					<PanelRow>
						<ToggleControl
							label={__('Custom Thank You Page', 'surecart')}
							checked={custom_success_url}
							onChange={(custom_success_url) =>
								setCustomSuccessUrl(custom_success_url)
							}
						/>
					</PanelRow>
					{custom_success_url && (
						<PanelRow>
							<div
								css={css`
									border: 1px solid #ddd;
									box-sizing: border-box;
									.block-editor-link-control {
										min-width: 248px;
										max-width: 248px;
										overflow: hidden;
									}

									.block-editor-link-control__search-item-header {
										white-space: normal;
										overflow-wrap: anywhere;
									}
								`}
							>
								<LinkControl
									value={{ url: success_url }}
									settings={{}}
									shownUnlinkControl={true}
									noURLSuggestion
									showInitialSuggestions
									onChange={(nextValue) => {
										setAttributes({
											success_url: nextValue.url,
										});
									}}
								/>
							</div>
						</PanelRow>
					)}
				</PanelBody>
				<PanelBody
					title={__('Loading Text', 'surecart')}
					initialOpen={false}
				>
					<PanelRow>
						<TextControl
							label={__('Submitting Order', 'surecart')}
							value={loading_text?.finalizing}
							placeholder={__('Submitting Order...', 'surecart')}
							onChange={(finalizing) =>
								setAttributes({
									loading_text: {
										...loading_text,
										finalizing,
									},
								})
							}
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={__('Processing Payment', 'surecart')}
							value={loading_text?.paying}
							placeholder={__(
								'Processing payment...',
								'surecart'
							)}
							onChange={(paying) =>
								setAttributes({
									loading_text: {
										...loading_text,
										paying,
									},
								})
							}
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={__('Confirming Payment', 'surecart')}
							value={loading_text?.confirming}
							placeholder={__('Finalizing order...', 'surecart')}
							onChange={(confirming) =>
								setAttributes({
									loading_text: {
										...loading_text,
										confirming,
									},
								})
							}
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={__('Success Text', 'surecart')}
							value={loading_text?.confirmed}
							placeholder={__(
								'Success! Redirecting...',
								'surecart'
							)}
							onChange={(confirmed) =>
								setAttributes({
									loading_text: {
										...loading_text,
										confirmed,
									},
								})
							}
						/>
					</PanelRow>
				</PanelBody>
			</InspectorControls>

			{blockCount === 0 ? (
				<Setup
					templates={patterns}
					onCreate={onCreate}
					clientId={clientId}
				/>
			) : (
				<div
					css={css`
						max-width: var(--ast-content-width-size);
						margin-left: auto !important;
						margin-right: auto !important;
					`}
				>
					<div
						style={styles}
						css={css`
							padding: 10px 16px;
							border-radius: 8px;
							display: grid;
							gap: 0.5em;
							border: 1px solid transparent;
							background: var(
								--sc-input-background-color-disabled
							);
						`}
					>
						<div
							css={css`
								display: flex;
								justify-content: space-between;
								align-items: center;
								font-size: 15px;
							`}
						>
							<div
								css={css`
									cursor: pointer;
									flex: 1;
									user-select: none;
									display: inline-block;
									color: var(--sc-input-label-color);
									font-weight: var(
										--sc-input-label-font-weight
									);
									text-transform: var(
										--sc-input-label-text-transform,
										none
									);
									letter-spacing: var(
										--sc-input-label-letter-spacing,
										0
									);
								`}
							>
								{__('Form', 'surecart')}
							</div>
							<div
								css={css`
									display: flex;
									align-items: center;
								`}
							>
								<Mode
									attributes={attributes}
									setAttributes={setAttributes}
								/>
								<div
									css={css`
										display: flex;
										align-items: center;
									`}
								>
									<Button
										onClick={() =>
											setTab(tab === 'cart' ? '' : 'cart')
										}
									>
										<span
											css={css`
												display: inline-block;
												vertical-align: top;
												box-sizing: border-box;
												margin: 1px 0 -1px 2px;
												padding: 0 5px;
												min-width: 18px;
												height: 18px;
												border-radius: 9px;
												background-color: currentColor;
												font-size: 11px;
												line-height: 1.6;
												text-align: center;
												z-index: 26;
											`}
										>
											<span
												css={css`
													color: #fff;
												`}
											>
												{
													(prices || []).filter(
														(p) => p?.id
													)?.length
												}
											</span>
										</span>

										<ScIcon
											name="shopping-bag"
											style={{
												fontSize: '18px',
												color: 'var(--sc-input-label-color)',
											}}
										/>
									</Button>
								</div>
							</div>
						</div>

						{tab === 'cart' && (
							<Cart
								attributes={attributes}
								setAttributes={setAttributes}
							/>
						)}
					</div>
					<ScCheckout
						mode="test"
						formId={formId}
						processors={scBlockData?.processors}
						stripePaymentElement={
							scBlockData?.beta?.stripe_payment_element
						}
						css={css`
							margin-top: 2em;
							font-size: ${font_size}px;
						`}
						className={className}
						style={{
							...(color
								? {
										'--sc-color-primary-500': color,
										'--sc-focus-ring-color-primary': color,
										'--sc-input-border-color-focus': color,
								  }
								: {}),
						}}
						disableComponentsValidation={true}
						persistSession={false}
						alignment={align}
						currencyCode={scBlockData.currency || scData?.currency}
						choiceType={choice_type}
						prices={prices}
					>
						<div
							css={css`
								*
									> *
									> .wp-block:not(sc-choice):not(sc-column):not(sc-radio):not(:last-child) {
									margin-bottom: ${gap} !important;
								}
								// prevents issues with our shadow dom.
								[data-type*='surecart/'] {
									pointer-events: all !important;
								}
								.wp-block,
								.block-editor-inserter {
									pointer-events: all !important;
								}
							`}
						>
							<InnerBlocks
								templateLock={false}
								renderAppender={
									blockCount
										? undefined
										: InnerBlocks.ButtonBlockAppender
								}
							/>
						</div>
					</ScCheckout>
				</div>
			)}
		</Fragment>
	);
}
