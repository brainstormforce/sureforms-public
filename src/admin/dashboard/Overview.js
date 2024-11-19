/** @jsx jsx */
import { __ } from '@wordpress/i18n';
import { css, jsx } from '@emotion/react';
import { ScButton, ScFlex } from '@surecart/components-react';
import EntriesView from './EntriesView';
import Integrations from './Integrations';

export default ( {} ) => {
	const siteUrl = srfm_admin.site_url;
	return (
		<div
			css={ css`
				width: 100%;
			` }
		>
			<div
				css={ css`
					display: flex;
					align-items: center;
					justify-content: space-between;
					width: 100%;
				` }
			>
				<h2
					css={ css`
						color: #1e293b;
						font-size: 30px;
						font-weight: 600;
						line-height: 36px;
						margin: 0;
					` }
				>
					{ __( 'Overview', 'sureforms' ) }
				</h2>
				<ScButton
					type="primary"
					href={ `${ siteUrl }/wp-admin/admin.php?page=add-new-form` }
					style={ {
						'--primary-background': '#D54407',
						'--sc-focus-ring-color-primary': '#D54407',
					} }
				>
					{ __( 'Create New Form', 'sureforms' ) } &#8594;
				</ScButton>
			</div>
			<ScFlex
				style={ { '--sc-flex-column-gap': '2em' } }
				stack="tablet"
				flexWrap="wrap"
			>
				<EntriesView />
				<Integrations />
			</ScFlex>
		</div>
	);
};
