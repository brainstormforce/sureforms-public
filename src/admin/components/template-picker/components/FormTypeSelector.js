import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { Container, Button, Tooltip } from '@bsf/force-ui';
import { Calculator, MessagesSquare, CreditCard } from 'lucide-react';
import { useState } from '@wordpress/element';
import UpgradePopup from './UpgradePopup.js';
import { addQueryParam, cn } from '@Utils/Helpers';

const FormTypeSelector = ( {
	formTypeObj,
	setFormTypeObj,
	formType,
	setFormType,
	setformLayout,
} ) => {
	const [ showUpgradePopup, setShowUpgradePopup ] = useState( false );
	const [ selectedUpgradeOption, setSelectedUpgradeOption ] =
		useState( null );

	const formTypeOptions = applyFilters(
		'srfm.ai_form_builder.form_type_options',
		[
			{
				label: __( 'Payment', 'sureforms' ),
				slug: 'payment',
				isAvailable: true,
			},
			{
				label: __( 'Simple', 'sureforms' ),
				slug: 'simple',
				isAvailable: true,
			},
			{
				label: __( 'Calculations', 'sureforms' ),
				slug: 'calculator',
				isAvailable: false,
				tooltipPosition: 'bottom',
				upgradeTooltipContent: {
					tooltipHeading: __(
						'Unlock Calculator Forms',
						'sureforms'
					),
					tooltipContentTitle: __(
						'Build Smart Forms That Calculate Instantly',
						'sureforms'
					),
					tooltipContent: __(
						'Empower your forms with advanced calculations. From loan calculators to BMI checkers - create interactive forms that give real-time results.',
						'sureforms'
					),
					utmMedium: 'ai_builder_calculator',
					tooltipPosition: 'bottom',
					features: [
						__( 'Add complex formulas with ease', 'sureforms' ),
						__( 'Provide instant answers to users', 'sureforms' ),
						__(
							'Perfect for finance, health, and pricing forms',
							'sureforms'
						),
					],
				},
			},
			{
				label: __( 'Conversational', 'sureforms' ),
				slug: 'conversational',
				isAvailable: false,
				upgradeTooltipContent: {
					tooltipHeading: __(
						'Unlock Conversational Forms',
						'sureforms'
					),
					tooltipContentTitle: __(
						'Turn Simple Forms Into Conversations',
						'sureforms'
					),
					tooltipContent: __(
						'Transform boring forms into friendly, chat-like experiences. Ask one question at a time, just like a real conversation, and see higher engagement.',
						'sureforms'
					),
					utmMedium: 'ai_builder_conversational',
					tooltipPosition: 'bottom',
					features: [
						__(
							'One question at a time, in chat flow',
							'sureforms'
						),
						__(
							'More engaging than traditional forms',
							'sureforms'
						),
						__(
							'Boosts completion rates with a friendly interface',
							'sureforms'
						),
					],
				},
			},
		]
	);

	// Ensure default type is 'simple' if not set
	if ( ! formType ) {
		setFormType( 'simple' );
	}

	const handleSelection = ( option ) => {
		if ( ! option.isAvailable ) {
			setSelectedUpgradeOption( option );
			setShowUpgradePopup( true );
			return;
		}

		// Toggle behavior
		if ( formType === option.slug ) {
			setFormType( 'simple' );
			setFormTypeObj( {
				...formTypeObj,
				isConversationalForm: false,
			} );
			return;
		}

		setFormType( option.slug );

		if (
			option.slug === 'calculator' ||
			option.slug === 'conversational' ||
			option.slug === 'payment'
		) {
			setformLayout( {} );
		}

		setFormTypeObj( {
			...formTypeObj,
			isConversationalForm: option.slug === 'conversational',
		} );
	};

	const visibleFormTypes = formTypeOptions.filter(
		( option ) => option.slug !== 'simple'
	);

	return (
		<Container.Item className="flex gap-2">
			{ visibleFormTypes.map( ( option, index ) => (
				<div
					key={ index }
					className="flex items-center justify-between gap-2"
				>
					<Tooltip
						arrow
						content={
							option.slug === 'calculator'
								? __(
									'Select this if you need calculations in your form. For example: Loan interest calculator.',
									'sureforms'
								  )
								: option.slug === 'conversational'
								? __(
									'Select this if you want your form to display one question at a time, like a chat.',
									'sureforms'
								  )
								: __(
									'Select this if you want to collect payments through your form.',
									'sureforms'
								  )
						}
						placement="bottom"
						triggers={ [ 'hover' ] }
						variant="dark"
						tooltipPortalId="srfm-add-new-form-container"
						className="text-xs"
					>
						<Button
							className={ cn(
								'px-1.5 py-1 font-medium text-icon-secondary border border-solid border-border-strong flex-1 transition-colors duration-150 focus:[box-shadow:none] bg-background-secondary hover:bg-background-secondary',
								formType === option.slug &&
									'hover:bg-brand-background-hover-100 bg-brand-background-hover-100 border-0.5 border-solid border-border-ai-button text-icon-interactive'
							) }
							iconPosition="left"
							icon={
								option.slug === 'calculator' ? (
									<Calculator className="size-4" />
								) : option.slug === 'conversational' ? (
									<MessagesSquare className="size-4" />
								) : (
									<CreditCard className="size-4" />
								)
							}
							size="md"
							variant="outline"
							onClick={ () => handleSelection( option ) }
						>
							{ option.label }
						</Button>
					</Tooltip>
				</div>
			) ) }

			{ showUpgradePopup && selectedUpgradeOption && (
				<UpgradePopup
					title={
						selectedUpgradeOption.upgradeTooltipContent
							.tooltipHeading
					}
					paraOne={
						selectedUpgradeOption.upgradeTooltipContent
							.tooltipContentTitle
					}
					paraTwo={
						selectedUpgradeOption.upgradeTooltipContent
							.tooltipContent
					}
					features={
						selectedUpgradeOption.upgradeTooltipContent.features
					}
					buttonText={ __( 'Upgrade Now', 'sureforms' ) }
					onclick={ () =>
						window.open(
							addQueryParam(
								srfm_admin?.pricing_page_url ||
									srfm_admin?.sureforms_pricing_page,
								selectedUpgradeOption.upgradeTooltipContent
									.utmMedium
							),
							'_blank',
							'noreferrer'
						)
					}
					onClose={ () => setShowUpgradePopup( false ) }
				/>
			) }
		</Container.Item>
	);
};

export default FormTypeSelector;
