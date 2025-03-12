/** @jsx jsx */
import { __ } from '@wordpress/i18n';
import { css, jsx } from '@emotion/react';
import { ScCard, ScDashboardModule } from '@surecart/components-react';
import ChartSummary from './charts/ChartSummary';
export default ( {} ) => {
	return (
		<ScDashboardModule
			css={ css`
				width: calc(65% - 2em );
				margin-top:2em;
				@media screen and ( max-width: 1260px ) {
					width: 100%;
				}
				.dashboard-module{
					display:none;
				}
			` }
			style={ { '--sc-flex-column-gap': '1em' } }
		>
			<ScCard
				style={ { '--sc-card-padding': 'var(--sc-spacing-xx-large)' } }
			>
				<article
					css={ css`
				display: flex;
				align-items:center;
				` }>
					{ __( 'Entries', 'sureforms' ) }
					<span
						css={ css`
				width: 16px;
				height: 3px;
				border-radius: 2px;
				background: #F06434;
				margin-left: 8px;
				margin-right:20px;
				` }>
					</span>
				</article>
				<ChartSummary loading={ false } className="" />
			</ScCard>
		</ScDashboardModule>
	);
};
