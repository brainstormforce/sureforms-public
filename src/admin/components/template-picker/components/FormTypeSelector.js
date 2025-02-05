import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import PremiumBadge from '../../../components/PremiumBadge';

const FormTypeSelector = ( { formType, setFormType, setformLayout } ) => {
	const formTypeOptions = applyFilters(
		'srfm.ai_form_builder.form_type_options',
		[
			{ label: 'Simple', enabled: true },
			{ label: 'Calculations', enabled: false },
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
			<p className="srfm-ai-builder-form-type-label">Please select form type</p>
			<div className="srfm-ai-builder-form-type-selector">
				<div
					className="srfm-ai-builder-form-type-highlight"
					style={ { left: formType === 'simple' ? '4px' : 'calc(50% + 0px)' } }
				/>
				{ formTypeOptions.map( ( option, index ) => (
					option.enabled ? (
						<div
							key={ index }
							className={ `srfm-ai-builder-form-type-option ${ formType === option.label.toLowerCase() ? 'active' : '' }` }
							onClick={ () => handleSelection( option.label.toLowerCase() ) }
						>
							{ option.label }
						</div>
					) : (
						<div
							key={ index }
							className="srfm-ai-builder-form-type-option disabled"
						>
							{ option.label }
							<PremiumBadge
								badgeName={ __( 'Business', 'sureforms' ) }
								tooltipHeading={ __( 'Unlock Conversational Forms', 'sureforms' ) }
								tooltipContent={ __( 'With the SureForms Pro Plan, you can transform your forms into engaging conversational layouts for a seamless user experience.', 'sureforms' ) }
								utmMedium="ai_builder"
								tooltipPosition="top"
							/>
						</div>
					)
				) ) }
			</div>
		</div>
	);
};

export default FormTypeSelector;
