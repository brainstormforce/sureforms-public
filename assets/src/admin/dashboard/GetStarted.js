/** @jsx jsx */
import { __ } from '@wordpress/i18n';
import { css, jsx } from '@emotion/react';
import { ScCard, ScFlex } from '@surecart/components-react';

import GetStartedBox from './GetStartedBox';

export default () => {
	return (
		<ScCard
			css={ css`
				position: relative;
				.sc-getstarted-inner-wrap {
					padding: 20px;
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
				@media only screen and ( max-width: 768px ) {
					display: none;
				}
			` }
		>
			<div className="sc-getstarted-inner-wrap">
				<h3 className="sc-get-started-main-title">
					{ __( 'Get started with SureForms', 'sureforms' ) }
				</h3>
				<ScFlex>
					<GetStartedBox
						infoType="info"
						infoText={ __( 'Setup', 'sureforms' ) }
						title={ __( 'Create a form', 'sureforms' ) }
						description={ __(
							'Create visually stunning, user-friendly forms.',
							'sureforms'
						) }
						buttonLabel={ __( 'Create New Form', 'sureforms' ) }
						buttonUrl={ '/wp-admin/post-new.php?post_type=sureforms_form' }
						btnFilled={ true }
					/>
					<GetStartedBox
						infoType="success"
						infoText={ __( 'Form', 'sureforms' ) }
						title={ __( 'All forms', 'sureforms' ) }
						description={ __(
							'Take a look at the forms you have created.',
							'sureforms'
						) }
						buttonLabel={ __( 'View All Forms', 'sureforms' ) }
						buttonUrl={
							'/wp-admin/edit.php?post_type=sureforms_form'
						}
						btnFilled={ false }
					/>
					<GetStartedBox
						infoType="primary"
						infoText={ __( 'Entries', 'sureforms' ) }
						title={ __( 'View entries', 'sureforms' ) }
						description={ __(
							'Access the complete list of form submissions.',
							'sureforms'
						) }
						buttonLabel={ __( 'View All Entries', 'sureforms' ) }
						buttonUrl={ '/wp-admin/edit.php?post_type=sureforms_entry' }
						btnFilled={ false }
					/>
				</ScFlex>
			</div>
		</ScCard>
	);
};
