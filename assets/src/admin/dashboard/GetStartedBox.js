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

		<div>
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
					margin: 0.4em 0;
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
			<ScButton
				type="primary"
				href={ buttonUrl }
				style={ {
					'--primary-background': btnFilled ? '#125D9F' : '#F5F3FF',
					'--primary-color': btnFilled ? '#fff' : '#125D9F',
				} }
			>
				{ buttonLabel } &#8594;
			</ScButton>
		</div>
	);
};
