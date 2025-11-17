/**
 * Empty State component for entries listing page.
 *
 * @since 2.0.0
 */

import { __ } from '@wordpress/i18n';
import { Button, Text } from '@bsf/force-ui';
import EmptyStateImage from '@Image/empty-state-header.svg';

const FEATURES = [
	__( 'Track submission for all your forms', 'sureforms' ),
	__( 'View, filter, and analyze submissions in real time', 'sureforms' ),
	__( 'Export data for further processing', 'sureforms' ),
	__( 'Edit and manage your entries with ease', 'sureforms' ),
];

const handleClickViewAllForms = () => {
	window.open(
		srfm_admin.site_url + '/wp-admin/edit.php?post_type=sureforms_form',
		'_self'
	);
};

const EmptyState = () => {
	return (
		<div className="flex items-center justify-center p-2 bg-background-secondary rounded-lg">
			<div className="flex flex-col md:flex-row items-center gap-6 bg-background-primary rounded-md shadow-sm-blur-1 p-6 w-full border-0.5 border-solid border-border-subtle">
				{ /* Image Section */ }
				<div className="flex gap-4">
					<img
						src={ EmptyStateImage }
						alt={ __( 'No entries yet', 'sureforms' ) }
						className="w-[320px] h-[320px]"
					/>
				</div>

				{ /* Content Section */ }
				<div className="flex flex-col gap-2 max-w-[884px]">
					{ /* Title */ }
					<Text
						size={ 20 }
						lineHeight={ 30 }
						weight={ 600 }
						letterSpacing={ -0.5 }
						color="primary"
					>
						{ __(
							'No entries? No worries! This page will be flooded soon!',
							'sureforms'
						) }
					</Text>

					<div className="flex flex-col">
						{ /* Description */ }
						<Text
							size={ 16 }
							lineHeight={ 24 }
							weight={ 400 }
							color="secondary"
						>
							{ __(
								'Once you publish and share your form, this space will turn into a powerful insights hub where you can:',
								'sureforms'
							) }
						</Text>

						{ /* Bullet Points */ }
						<ul className="flex flex-col list-disc list-inside leading-7 ml-2.5 mt-2 mb-0 mr-0">
							{ FEATURES.map( ( point, index ) => (
								<li key={ index } className="m-0">
									<Text
										className="inline-block"
										size={ 16 }
										lineHeight={ 28 }
										weight={ 400 }
										color="secondary"
									>
										{ point }
									</Text>
								</li>
							) ) }
						</ul>
					</div>

					<Button
						className="w-fit mt-2"
						variant="primary"
						size="md"
						onClick={ handleClickViewAllForms }
					>
						{ __( 'Go to Forms', 'sureforms' ) }
					</Button>
				</div>
			</div>
		</div>
	);
};

export default EmptyState;
