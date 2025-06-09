import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import PremiumBadge from '../../../components/PremiumBadge';
import { Container, Label, Title } from '@bsf/force-ui';
import { cn } from '@Utils/Helpers';

const FormTypeSelector = ( { formType, setFormType, setformLayout } ) => {
	// filter to add more form types
	const formTypeOptions = applyFilters(
		'srfm.ai_form_builder.form_type_options',
		[
			{
				label: __( 'Simple', 'sureforms' ),
				slug: 'simple',
				isAvailable: true,
			},
			// this is added to show a preview of Calculator form type in free plugin
			{
				label: __( 'Calculator', 'sureforms' ),
				slug: 'calculator',
				isAvailable: false,
				upgradeTooltipContent: {
					tooltipHeadin: __( 'Unlock Calculations', 'sureforms' ),
					tooltipContent: __(
						'Upgrade to the SureForms Business Plan to create advanced forms with real-time calculations, such as project quote calculators, BMI calculators, loan calculators, and more.',
						'sureforms'
					),
					utmMedium: 'ai_builder_calculator',
					tooltipPosition: 'bottom',
				},
			},
		]
	);

	const handleSelection = ( type ) => {
		setFormType( type );
		// If the form type is not simple, reset the form layout object
		if ( type !== 'simple' ) {
			setformLayout( {} );
		}
	};

	return (
		<Container.Item className="flex flex-col p-2 gap-2">
			<Title
				tag="h4"
				size="md"
				className="text-text-primary"
				title={ __( 'Please select form type', 'sureforms' ) }
			/>
			<Container className="p-1 gap-1 bg-tab-background border-0.5 border-solid border-tab-border rounded-md">
				{ formTypeOptions.map( ( option, index ) =>
					option.isAvailable ? (
						<div
							key={ index }
							className={ cn(
								'flex p-1.5 items-center justify-center w-full gap-1',
								formType === option.slug &&
									'rounded-md bg-background-primary shadow-sm-blur-2'
							) }
							onClick={ () => handleSelection( option.slug ) }
						>
							<Title
								icon={ option.icon && option.icon }
								iconPosition="right"
								className={ cn(
									'text-text-secondary',
									formType === option.slug &&
										'text-text-primary'
								) }
								size="xs"
								title={ option.label }
							/>
						</div>
					) : (
						<div
							key={ index }
							className="flex p-1.5 items-center justify-center w-full gap-1"
						>
							<Label size="md" variant="disabled">
								{ option.label }
							</Label>
							<PremiumBadge
								tooltipHeading={
									option?.upgradeTooltipContent?.tooltipHeadin
								}
								tooltipContent={
									option?.upgradeTooltipContent
										?.tooltipContent
								}
								utmMedium={
									option?.upgradeTooltipContent?.utmMedium
								}
								tooltipPosition={
									option?.upgradeTooltipContent
										?.tooltipPosition
								}
							/>
						</div>
					)
				) }
			</Container>
		</Container.Item>
	);
};

export default FormTypeSelector;
