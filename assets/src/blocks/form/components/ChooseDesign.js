/** @jsx jsx */
import { css, jsx } from '@emotion/react';
import { __ } from '@wordpress/i18n';
import Thumbnail from './Thumbnail';
import { ScButton } from '@surecart/components-react';
import { useState } from '@wordpress/element';
import PlaceholderTemplate from './PlaceholderTemplate';

export default ({ templates, template, setTemplate }) => {
	const [choice, setChoice] = useState(template);

	const imageCSS = css`
		margin: auto;
		height: 300px !important;
		object-fit: contain;
	`;

	return (
		<PlaceholderTemplate
			header={__('Choose A Starting Template', 'sureforms')}
			footerRight={
				<ScButton
					type="primary"
					disabled={!choice}
					onClick={() => setTemplate(choice)}
				>
					{__('Select Template', 'sureforms')}
				</ScButton>
			}
			maxHeight={'300px'}
			minHeight={'32rem'}
		>
			<div
				css={css`
					display: grid;
					padding: 32px;
					flex: 1 1 0%;
					grid-gap: 32px;
					@media (min-width: 768px) {
						grid-template-columns: repeat(2, 1fr);
					}
					@media (min-width: 960px) {
						grid-template-columns: repeat(2, 1fr);
					}
					overflow-y: scroll;
					overflow-x: visible;
				`}
			>
				{templates.map((template) => {
					const url = sfBlockData?.plugin_url;
					const name = template.name.replace('sureforms/', '');
					return (
						<Thumbnail
							label={template?.title}
							selected={choice === name}
							onSelect={() => setChoice(name)}
						>
							<img
								css={imageCSS}
								src={`${url}/templates/forms/${name}.png`}
							/>
						</Thumbnail>
					);
				})}
			</div>
		</PlaceholderTemplate>
	);
};
