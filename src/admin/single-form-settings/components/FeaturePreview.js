import { __ } from '@wordpress/i18n';
import { Button, Container, Label, Title } from '@bsf/force-ui';
import { Dot } from 'lucide-react';
import { addQueryParam, cn } from '@Utils/Helpers';

const FeaturePreview = ( {
	featureName = '',
	icon = null,
	title = '',
	subtitle = '',
	featureList = [],
	utmMedium = '',
	actionLeftContent,
	shouldShowFeatureHelpText = true,
	featureHelpText = '',
} ) => {
	return (
		<div className="space-y-7 pb-8">
			{ /* Header */ }
			<Container align="center" justify="between">
				<Container className="gap-0" direction="column">
					<Container.Item className="flex items-center gap-2">
						<Title tag="h4" title={ featureName } size="md" />
					</Container.Item>
					<Container.Item>
						{ shouldShowFeatureHelpText && (
							<Label
								size="sm"
								variant="help"
								className="text-text-on-button-disabled font-normal"
							>
								{ featureHelpText }
							</Label>
						) }
					</Container.Item>
				</Container>
				<Container align="center" className="gap-3">
					{ actionLeftContent }
				</Container>
			</Container>

			{ /* Content */ }
			<div
				className={ cn(
					'bg-background-primary rounded-xl p-4 shadow-sm',
					'shadow-sm border-[0.5px] border-[#E5E7EB]'
				) }
			>
				<div className="bg-background-secondary p-2 min-h-1 rounded-lg">
					<Container
						direction="column"
						className="bg-white p-6 rounded-md shadow-sm gap-3"
					>
						{ /* Icon */ }
						<Container.Item>{ icon && icon }</Container.Item>

						{ /* Title */ }
						<Container.Item>
							<Label
								as="h3"
								size="lg"
								className="text-text-primary font-semibold text-xl"
							>
								{ title }
							</Label>
						</Container.Item>

						{ /* Description */ }
						<Container.Item>
							<Label
								size="sm"
								className="text-[#4B5563] text-base font-normal leading-relaxed"
							>
								{ subtitle }
							</Label>
						</Container.Item>

						{ /* Features List */ }
						<Container direction="column" className="gap-3">
							{ featureList.map( ( point, index ) => (
								<Container.Item
									key={ index }
									className="flex items-start"
								>
									<Dot
										className="w-5 h-5 text-[#4B5563]"
										strokeWidth={ 2 }
									/>
									<Label
										size="sm"
										className="text-[#4B5563] font-normal"
									>
										{ point }
									</Label>
								</Container.Item>
							) ) }
						</Container>

						{ /* Upgrade Button */ }
						<Container.Item className='mt-2'>
							<Button
								className="w-fit p-2.5"
								size="md"
								variant="primary"
								onClick={ () => {
									const url =
										addQueryParam(
											srfm_admin?.pricing_page_url ||
												srfm_admin?.sureforms_pricing_page,
											utmMedium
										) || '#';
									window.open( url, '_blank', 'noreferrer' );
								} }
							>
								{ __( 'Upgrade Now', 'sureforms' ) }
							</Button>
						</Container.Item>
					</Container>
				</div>
			</div>
		</div>
	);
};

export default FeaturePreview;
