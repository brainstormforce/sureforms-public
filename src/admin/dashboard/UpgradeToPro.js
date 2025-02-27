import { __ } from '@wordpress/i18n';
import { Button, Container, Label, Title } from '@bsf/force-ui';
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
			className="bg-background-primary p-4 shadow-sm rounded-xl border border-solid border-border-subtle gap-1"
			containerType="grid"
			cols={ 12 }
		>
			<Container.Item className="flex flex-col p-2" colSpan={ 7 }>
				<Container className="flex flex-col gap-1">
					<Container.Item className="flex flex-row gap-1 items-center">
						<Zap className="w-12 text-brand-800 border-1.25" />
						<Label className="font-semibold cursor-pointer text-xs text-brand-800">
							{ __( 'Upgrade to Pro', 'sureforms' ) }
						</Label>
					</Container.Item>
					<Container.Item className="flex flex-col gap-1 py-1">
						<Title
							className="text-text-primary font-semibold text-lg"
							tag="h5"
							title={ __( 'Upgrade to Unlock SureForms Premium Features!', 'sureforms' ) }
						/>
						<Label className="text-sm text-text-secondary font-normal">
							{ __( 'Upgrade to SureForms Premium and access advanced fields and features that enhance your form-building experience:', 'sureforms' ) }
						</Label>
					</Container.Item>
					<Container.Item>
						<Container className="grid grid-cols-1 sm:grid-cols-2 py-1 gap-3 rounded-lg">
							{ featuresText.map( ( prompt, index ) => (
								<Container.Item className="flex flex-row items-center gap-2" key={ index }>
									<Check className="w-12 text-brand-800 border" />
									<Label className="text-field-label font-medium text-sm">
										{ prompt.title }
									</Label>
								</Container.Item>
							) ) }
						</Container>
					</Container.Item>
					<Container.Item className="p-2 gap-3">
						<Button
							variant="secondary"
							size="md"
							className="border border-solid border-button-secondary bg-button-secondary hover:bg-button-secondary gap-1 shadow-sm-blur-2 text-sm focus:[box-shadow:none]"
							onClick={ () => window.open( addQueryParam( srfm_admin?.pricing_page_url || srfm_admin?.sureforms_pricing_page, 'dashboard-cta' ), '_blank', 'noreferrer' ) }
						>
							{ __( 'Upgrade now', 'sureforms' ) }
						</Button>
					</Container.Item>
				</Container>
			</Container.Item >
			<Container.Item className="p-2 gap-1.5 flex justify-center items-center" colSpan={ 5 }>
				<img
					src={ upgradeToProPlaceholder }
					alt={ __( 'Upgrade To Pro', 'sureforms' ) }
					className="max-w-full h-auto"
				/>
			</Container.Item>
		</Container >
	);
};
