/** @jsx jsx */
import { css, jsx } from '@emotion/react';
import { ScFlex, ScStackedListRow } from '@surecart/components-react';

export default ( {
	icon,
	title,
	descriptions,
	statusText,
	statusColor,
	handlePluginActionTrigger,
	dataSlug,
	init,
	redirectURL,
	action,
} ) => {
	return (
		<ScStackedListRow
			style={ {
				'--columns': '1',
				overflow: 'hidden',
			} }
		>
			<ScFlex
				justify-content="space-between"
				alignItems="center"
				css={ css`
					padding: var( --sc-spacing-small );
				` }
				style={ { '--sc-flex-column-gap': '1em' } }
			>
				<ScFlex
					alignItems="center"
				>
					<span>
						<img width="40px" src={ icon } alt="logo" />
					</span>

					<div>
						<p
							css={ css`
							font-weight: 700;
							font-size: 16px;
							line-height: 28px;
							color: #334155;
							margin: 0;
						` }
						>
							{ title }
						</p>
						<p
							css={ css`
							font-weight: 400;
							font-size: 16px;
							line-height: 28px;
							color: #334155;
							margin: 0;
						` }
						>
							{ descriptions }
						</p>
					</div>
				</ScFlex>
				<button
					css={ css`
						webkit-appearance: button;
						background-color: transparent;
						border:none;
						font-weight: 400;
						font-size: 16px;
						line-height: 28px;
						cursor: pointer;
						color: ${ statusColor };
						margin: 0;
					` }
					onClick={ handlePluginActionTrigger }
					data-slug={ dataSlug }
					data-init={ init }
					data-redirection={ redirectURL }
					data-action={ action }
				>
					{ statusText }
				</button>
			</ScFlex>
		</ScStackedListRow>
	);
};
