import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import PremiumBadge from '../../../components/PremiumBadge';

const FormTypeSelector = ( { formType, setFormType, setformLayout } ) => {
	// filter to add more form types
	const formTypeOptions = applyFilters(
		'srfm.ai_form_builder.form_type_options',
		[
			{ label: __( 'Simple', 'sureforms' ), slug: 'simple', isAvailable: true },
			// this is added to show a preview of Calculator form type in free plugin
			{ label: __( 'Calculator', 'sureforms' ), slug: 'calculator', isAvailable: false, upgradeTooltipContent: {
				tooltipHeadin: __( 'Unlock Calculations', 'sureforms' ),
				tooltipContent: __( 'Upgrade to the SureForms Business Plan to create advanced forms with real-time calculations, such as project quote calculators, BMI calculators, loan calculators, and more.', 'sureforms' ),
				utmMedium: 'ai_builder_calculator',
				tooltipPosition: 'top',
			} },
		],
	);

	const handleSelection = ( type ) => {
		setFormType( type );
		// If the form type is not simple, reset the form layout object
		if ( type !== 'simple' ) {
			setformLayout( {} );
		}
	};

	return (
		<div className="srfm-ai-builder-form-type-selector">
			<p className="srfm-ai-builder-form-type-label">{ __( 'Please select form type', 'sureforms' ) }</p>
			<div className="srfm-ai-builder-form-type-selector">
				<div
					className="srfm-ai-builder-form-type-highlight"
					style={ { left: formType === 'simple' ? '4px' : '50%' } }
				/>
				{ formTypeOptions.map( ( option, index ) => (
					option.isAvailable ? (
						<div
							key={ index }
							className={ `srfm-ai-builder-form-type-option ${ formType === option.slug ? 'active' : '' }` }
							onClick={ () => handleSelection( option.slug ) }
						>
							<span className="srfm-ai-builder-form-type-text">{ option.label }{ option.icon && option.icon }</span>
						</div>
					) : (
						<div
							key={ index }
							className="srfm-ai-builder-form-type-option disabled"
						>
							{ option.label }
							<div style={ { lineHeight: 0 } }>
								<PremiumBadge
									tooltipHeading={ option?.upgradeTooltipContent?.tooltipHeading }
									tooltipContent={ option?.upgradeTooltipContent?.tooltipContent }
									utmMedium={ option?.upgradeTooltipContent?.utmMedium }
									tooltipPosition={ option?.upgradeTooltipContent?.tooltipPosition }
								/>
							</div>
						</div>
					)
				) ) }
			</div>
		</div>
	);
};

export default FormTypeSelector;
