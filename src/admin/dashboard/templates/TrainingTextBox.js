/** @jsx jsx */
import { css, jsx } from '@emotion/react';
import { ScCard } from '@surecart/components-react';

export default ( { title, description, urlText } ) => {
	return (
		<ScCard
			css={ css`
				margin-top: 1.5em;
			` }
		>
			<div
				css={ css`
					padding: var( --sc-spacing-large );
				` }
			>
				<h3
					css={ css`
						color: #0f172a;
						font-size: 18px;
						font-weight: 600;
						line-height: 28px;
						margin: 0;
						margin-bottom: 8px;
					` }
				>
					{ title }
				</h3>
				<article
					css={ css`
						color: 364748B;
					` }
				>
					{ description }
				</article>
				<article
					css={ css`
						color: #125d9f;
						margin-top: 15px;
						display: flex;
					` }
				>
					<a
						href={
							'https://wordpress.org/support/plugin/sureforms/reviews/?rate=5#new-post'
						}
						target="_blank"
						css={ css`
							text-decoration: none;
						` }
						rel="noreferrer"
					>
						{ urlText } &nbsp;&nbsp;
						<span>
							<svg
								xmlns="http://www.w3.org/2000/svg"
								width="16"
								height="16"
								viewBox="0 0 16 16"
								fill="none"
							>
								<path
									d="M6.66406 3.9974H3.9974C3.26102 3.9974 2.66406 4.59435 2.66406 5.33073V11.9974C2.66406 12.7338 3.26102 13.3307 3.9974 13.3307H10.6641C11.4004 13.3307 11.9974 12.7338 11.9974 11.9974V9.33073M9.33073 2.66406H13.3307M13.3307 2.66406V6.66406M13.3307 2.66406L6.66406 9.33073"
									stroke="#125D9F"
									strokeWidth="1.2"
									strokeLinecap="round"
									strokeLinejoin="round"
								/>
							</svg>
						</span>
					</a>
				</article>
			</div>
		</ScCard>
	);
};
