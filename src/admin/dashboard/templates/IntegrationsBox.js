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
				padding: '3px',
			} }
		>
			<ScFlex
				justify-content="space-between"
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
							font-weight: 500;
							font-size: 14px;
							line-height: 22px;
							color: #1E293B;
							margin: 0 0 4px 0;
						` }
						>
							{ title }
						</p>
						<p
							css={ css`
							font-weight: 400;
							font-size: 14px;
							line-height: 1.6em;
							color: #AAA;
							width: 95%;
							margin: 0;
							@media screen and ( max-width: 480px ) {
								width: 100%;
							}
						` }
						>
							{ descriptions }
						</p>
					</div>
					<button
						css={ css`
						webkit-appearance: button;
						background-color: transparent;
						border:none;
						font-weight: 500;
						font-size: 14px;
						line-height: 20px;
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
			</ScFlex>
		</ScStackedListRow>
	);
};
