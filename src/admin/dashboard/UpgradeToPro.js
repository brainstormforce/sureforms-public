import { __ } from '@wordpress/i18n';
import { Button, Container, Title } from '@bsf/force-ui';
import { Check, Zap } from 'lucide-react';
import upgradeToProPlaceholder from '@Image/upgrade-to-pro.svg';
import { addQueryParam } from '@Utils/Helpers';

export default () => {
	const featuresText = [
		{ title: __( 'FileUpload', 'sureforms' ) },
		{ title: __( 'Page Break', 'sureforms' ) },
		{ title: __( 'Rating Fields', 'sureforms' ) },
		{ title: __( 'Date & Time Pickers', 'sureforms' ) },
		{ title: __( 'Conditional Logic', 'sureforms' ) },
		{ title: __( 'And much more…', 'sureforms' ) },
	];

	return (
		<Container
			className="bg-background-primary p-4 shadow-sm-blur-1 rounded-xl border-0.5 border-solid border-border-subtle gap-2"
			containerType="grid"
			cols={ 12 }
		>
			<Container.Item className="flex flex-col px-2 gap-2 gap-6 col-span-12 md:col-span-6">
				<div className="flex flex-col gap-2">
					<div className="flex flex-row gap-1 items-center my-2">
						<Zap className="size-4 text-brand-800 border-1.25" />
						<div className="font-semibold text-xs text-brand-800">
							{ __( 'Upgrade to Pro', 'sureforms' ) }
						</div>
					</div>
					<div className="flex flex-col gap-1">
						<Title
							tag="h5"
							title={ __(
								'Upgrade to Unlock SureForms Premium Features!',
								'sureforms'
							) }
						/>
						<p className="text-sm text-text-secondary font-normal">
							{ __(
								'Upgrade to SureForms Premium and access advanced fields and features that enhance your form-building experience:',
								'sureforms'
							) }
						</p>
					</div>
					<Container.Item>
						<Container className="grid grid-cols-1 sm:grid-cols-2 gap-3 rounded-lg mt-2">
							{ featuresText.map( ( prompt, index ) => (
								<Container.Item
									className="flex flex-row items-center gap-2"
									key={ index }
								>
									<Check className="size-3.5 text-brand-800" />
									<span className="text-field-label font-medium text-sm">
										{ prompt.title }
									</span>
								</Container.Item>
							) ) }
						</Container>
					</Container.Item>
				</div>
				<div className="flex p-2 gap-3 mt-2">
					<Button
						variant="secondary"
						size="md"
						className="gap-1 shadow-sm-blur-2"
						onClick={ () =>
							window.open(
								addQueryParam(
									srfm_admin?.pricing_page_url ||
										srfm_admin?.sureforms_pricing_page,
									'dashboard-cta'
								),
								'_blank',
								'noreferrer'
							)
						}
					>
						{ __( 'Upgrade now', 'sureforms' ) }
					</Button>
				</div>
			</Container.Item>
			<Container.Item className="gap-1.5 flex justify-center items-center col-span-12 md:col-span-6">
				<img
					src={ upgradeToProPlaceholder }
					alt={ __( 'Upgrade To Pro', 'sureforms' ) }
					className="max-w-full h-auto"
				/>
			</Container.Item>
		</Container>
	);
};
