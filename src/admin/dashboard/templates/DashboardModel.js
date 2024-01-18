/** @jsx jsx */
import { css, jsx, Global } from '@emotion/react';
import { Fragment } from '@wordpress/element';
import Header from '../../components/Header';

export default ( { children } ) => {
	return (
		<Fragment>
			<Global
				styles={ css`
					:root {
						--sc-color-primary-500: var( --sc-color-brand-primary );
						--sc-focus-ring-color-primary: var(
							--sc-color-brand-primary
						);
						--sc-input-border-color-focus: var(
							--sc-color-brand-primary
						);
					}
					#wpwrap {
						background-color: var(
							--sc-color-brand-main-background
						);
					}
					#wpcontent {
						padding: 0;
					}
					@media screen and ( max-width: 782px ) {
						.auto-fold #wpcontent {
							padding-left: 0;
						}
					}
				` }
			/>
			<>
				<Header />
				<div
					css={ css`
						padding: 0 20px;
						display: grid;
						margin: auto;
						max-width: 95%;
						margin-top: var( --sc-spacing-xx-large );
					` }
				>
					<div
						css={ css`
							margin-bottom: 3em;
							> * ~ * {
								margin-top: var( --sc-spacing-xxx-large );
							}
						` }
					>
						{ children }
					</div>
					<div>
						<div
							css={ css`
								margin-bottom: 3em;
								> * ~ * {
									margin-top: 1em;
								}
							` }
						></div>
					</div>
				</div>
			</>
		</Fragment>
	);
};
