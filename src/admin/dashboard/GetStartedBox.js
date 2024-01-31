/** @jsx jsx */
import { css, jsx } from '@emotion/react';
import { ScTag, ScButton } from '@surecart/components-react';

export default ( {
	infoType,
	infoText,
	title,
	description,
	buttonLabel,
	buttonUrl,
	btnFilled,
} ) => {
	return (

		<div
			className="sc-col-33"
			css={ css`
				max-width: 341px;

				sc-button {
					margin-top: auto;
				}

				@media screen and ( max-width: 480px ) {
					margin-bottom: 20px;
				}
			` }>
			<div className="sc-top-section">
				<ScTag
					type={ infoType }
					style={ {
						'--sc-tag-primary-background-color': '#f3e8ff',
						'--sc-tag-primary-color': '#6b21a8',
					} }
				>
					{ infoText }
				</ScTag>
				<p
					css={ css`
						font-weight: 600;
						font-size: 20px;
						line-height: 28px;
						color: #334155;
						margin: 0.8em 0 .4em 0;
					` }
				>
					{ title }
				</p>
				<p
					css={ css`
						font-weight: 400;
						font-size: 14px;
						line-height: 28px;
						color: #334155;
						margin: 0.4em 0 1em 0;
					` }
				>
					{ description }
				</p>
			</div>
			<ScButton

				type="primary"
				href={ buttonUrl }
				style={ {
					'--primary-background': btnFilled ? '#125D9F' : '#F5F3FF',
					'--primary-color': btnFilled ? '#fff' : '#125D9F',
					'--sc-focus-ring-color-primary': '#125D9F',
				} }
			>
				{ buttonLabel } &#8594;
			</ScButton>
		</div>
	);
};
