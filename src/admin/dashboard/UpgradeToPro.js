import { __ } from '@wordpress/i18n';
import { Button, Container, Title, Label } from '@bsf/force-ui';
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
			className="bg-background-primary p-4 gap-2 shadow-sm-blur-1 rounded-xl border-0.5 border-solid border-border-subtle"
			containerType="grid"
			cols={ 12 }
		>
			<Container.Item className="flex flex-col gap-2 col-span-12 md:col-span-6">
				<Container direction="column" className="gap-2 p-2">
					<Container align="center" className="gap-1">
						<Zap className="size-4 text-brand-800" />
						<Label size="xs" className="font-semibold text-brand-800">
							{ __( 'Upgrade to Pro', 'sureforms' ) }
						</Label>
					</Container>
					<Container direction="column" className="gap-1 py-1">
						<Title
							tag="h5"
							title={ __(
								'Upgrade to Unlock SureForms Premium Features!',
								'sureforms'
							) }
						/>
						<Label size="sm" className="text-text-secondary font-normal">
							{ __(
								'Upgrade to SureForms Premium and access advanced fields and features that enhance your form-building experience:',
								'sureforms'
							) }
						</Label>
					</Container>
					<Container.Item>
						<Container containerType="gird" className="grid-cols-1 sm:grid-cols-2 gap-1.5 rounded-lg py-1">
							{ featuresText.map( ( prompt, index ) => (
								<Container.Item
									className="flex flex-row items-center gap-2"
									key={ index }
								>
									<Check className="size-3.5 text-brand-800" />
									<Label size="sm">
										{ prompt.title }
									</Label>
								</Container.Item>
							) ) }
						</Container>
					</Container.Item>
				</Container>
				<Container className="p-2 gap-3">
					<Button
						variant="secondary"
						size="md"
						className="shadow-sm-blur-2"
						onClick={ () =>
							window.open(
								addQueryParam(
									srfm_admin?.pricing_page_url ||
										srfm_admin?.sureforms_pricing_page,
									'dashboard-upgrade-to-pro-banner-cta'
								),
								'_blank',
								'noreferrer'
							)
						}
					>
						{ __( 'Upgrade now', 'sureforms' ) }
					</Button>
				</Container>
			</Container.Item>
			<Container.Item className="gap-1.5 p-2 flex justify-center items-center col-span-12 md:col-span-6">
				<img
					src={ upgradeToProPlaceholder }
					alt={ __( 'Upgrade To Pro', 'sureforms' ) }
					className="max-w-full h-auto"
				/>
			</Container.Item>
		</Container>
	);
};
