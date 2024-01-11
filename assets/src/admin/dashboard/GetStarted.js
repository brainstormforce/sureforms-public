/** @jsx jsx */
import { __ } from '@wordpress/i18n';
import { css, jsx } from '@emotion/react';
import { ScCard, ScFlex } from '@surecart/components-react';

import GetStartedBox from './GetStartedBox';

export default () => {
	const siteUrl = sureforms_admin.site_url;

	return (
		<ScCard
			css={ css`
				position: relative;
				.sc-getstarted-inner-wrap {
					padding: 20px;
				}
				@media screen and ( max-width: 480px ) {
					.sc-getstarted-inner-wrap {
						padding: 5px;
					}
				}
				.sc-get-started-main-title {
					font-size: 28px;
					font-weight: 600;
					line-height: 28px;
					text-align: left;
					margin: 0px 0px 1.2em 0px;
				}
				.sc-getstarted-close-icon {
					position: absolute;
					right: 30px;
					top: 30px;
					cursor: pointer;
				}

				.sc-col-33 {
					width: 33%;
				}

				@media screen and ( max-width: 480px ) {
					.sc-col-33 {
						width: 100%;
					}
				}

			` }
		>
			<div className="sc-getstarted-inner-wrap">
				<h3 className="sc-get-started-main-title">
					{ __( 'Get started with SureForms', 'sureforms' ) }
				</h3>
				<ScFlex stack="mobile">
					<GetStartedBox
						infoType="info"
						infoText={ __( 'Setup', 'sureforms' ) }
						title={ __( 'Create a Form', 'sureforms' ) }
						description={ __(
							'Create visually stunning, user-friendly forms.',
							'sureforms'
						) }
						buttonLabel={ __( 'Create New Form', 'sureforms' ) }
						buttonUrl={ `${ siteUrl }/wp-admin/admin.php?page=add-new-form` }
						btnFilled={ true }
					/>
					<GetStartedBox
						infoType="success"
						infoText={ __( 'Form', 'sureforms' ) }
						title={ __( 'All Forms', 'sureforms' ) }
						description={ __(
							'Take a look at the forms you have created.',
							'sureforms'
						) }
						buttonLabel={ __( 'View All Forms', 'sureforms' ) }
						buttonUrl={ `${ siteUrl }/wp-admin/edit.php?post_type=sureforms_form` }
						btnFilled={ false }
					/>
					<GetStartedBox
						infoType="primary"
						infoText={ __( 'Entries', 'sureforms' ) }
						title={ __( 'View Entries', 'sureforms' ) }
						description={ __(
							'Access the complete list of form submissions.',
							'sureforms'
						) }
						buttonLabel={ __( 'View All Entries', 'sureforms' ) }
						buttonUrl={ `${ siteUrl }/wp-admin/edit.php?post_type=sureforms_entry` }
						btnFilled={ false }
					/>
				</ScFlex>
			</div>
		</ScCard>
	);
};
