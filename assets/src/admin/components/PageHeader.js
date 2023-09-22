import Header from './Header';
import { css, Global } from '@emotion/react';
import { Fragment, render } from '@wordpress/element';

const FormPageHeader = () => {
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
						--dashboard-heading: #1e293b;
						--dashboard-text: #64748b;
						overflow-x: hidden;
					}
					#wpwrap {
						background-color: var(
							--sc-color-brand-main-background
						);
					}
					#wpcontent {
						padding: 0;
					}
					.wrap {
						margin-right: 46px;
					}
					#wpbody-content {
						margin: 0 0 0 20px;
					}
					.wrap {
						margin-right: 46px;
					}
					@media screen and ( max-width: 782px ) {
						#wpbody {
							padding-top: 0;
						}
						.auto-fold #wpcontent {
							padding-left: 0;
						}
						#sureforms-form-page-header {
							padding-top: 46px;
						}
					}
					#show-settings-link {
						margin-right: 26px;
					}
				` }
			/>
			<Header />
		</Fragment>
	);
};

export default FormPageHeader;

( function () {
	const app = document.getElementById( 'sureforms-page-header' );

	function renderApp() {
		if ( null !== app ) {
			render( <FormPageHeader />, app );
		}
	}

	document.addEventListener( 'DOMContentLoaded', renderApp );
}() );
