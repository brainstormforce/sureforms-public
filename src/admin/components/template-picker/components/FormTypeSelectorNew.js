import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import PremiumBadge from '../../../components/PremiumBadge';
import { Container, Title, Select, Label, Badge } from '@bsf/force-ui';

const FormTypeSelectorNew = ({ formTypeObj, setFormTypeObj, formType, setFormType, setformLayout }) => {
	const formTypeOptions = applyFilters(
		'srfm.ai_form_builder.form_type_options',
		[
			{ label: __('Simple', 'sureforms'), slug: 'simple', isAvailable: true },
			{
				label: __('Calculator', 'sureforms'),
				slug: 'calculator',
				isAvailable: false,
				upgradeTooltipContent: {
					tooltipHeadin: __('Unlock Calculations', 'sureforms'),
					tooltipContent: __(
						'Upgrade to the SureForms Business Plan to create advanced forms with real-time calculations, such as project quote calculators, BMI calculators, loan calculators, and more.',
						'sureforms'
					),
					utmMedium: 'ai_builder_calculator',
					tooltipPosition: 'bottom',
				},
			},
			{
				label: __('Conversational Form', 'sureforms'),
				slug: 'conversational',
				isAvailable: false,
				upgradeTooltipContent: {
					tooltipHeadin: __('Unlock Conversational Forms', 'sureforms'),
					tooltipContent: __(
						'Upgrade to the SureForms Business Plan to create advanced forms with real-time conversations, such as project quote calculators, BMI calculators, loan calculators, and more.',
						'sureforms'
					),
					utmMedium: 'ai_builder_conversational',
					tooltipPosition: 'bottom',
				},
			},
		]
	);

	const handleSelection = (option) => {
		if (!option.isAvailable) return;

		setFormType(option.slug);

		if (option.slug === 'simple') {
			console.log('Simple form selected');
			// Reset to default layout
			setformLayout({});
		} else if (option.slug === 'conversational') {
			// Enable conversational form layout automatically (same behaviour as toggle)
			setFormTypeObj({
				...formTypeObj,
				isConversationalForm: true
			})
		} else {
			// calculator / others
			setformLayout({});
		}
	};


	const getFormTypeLabel = (slug) => {
		const match = formTypeOptions.find(opt => opt.slug === slug);
		return match ? match.label : __('Select…', 'sureforms');
	};

	return (
		<Container.Item className="flex flex-col gap-2">
			<Select
				value={formTypeOptions.find(opt => opt.slug === formType)}
				onChange={(selectedOption) => handleSelection(selectedOption)}
				size="sm"
			>
				<Select.Button placeholder={__('Select…', 'sureforms')}
					className="rounded-full px-1.5 !py-1 border-0.5 bg-badge-background-gray"
				>
					<Badge
						label={getFormTypeLabel(formType)}
						size="md"
						type="pill"
						variant="neutral"
						className="border-0 p-0"
					/>
				</Select.Button>

				<Select.Options className="z-50 !max-w-[300px]">
					{formTypeOptions.map((option, index) =>
						option.isAvailable ? (
							<Select.Option
								key={index}
								value={option}
								selected={formType === option.slug}
							>
								<span className="whitespace-normal break-words">{option.label}</span>
							</Select.Option>
						) : (
							<div
								key={index}
								className="flex items-center justify-between px-2 py-1"
							>
								<Label size="xs" variant="disabled">
									{option.label}
								</Label>
								<PremiumBadge
									tooltipHeading={option?.upgradeTooltipContent?.tooltipHeadin}
									tooltipContent={option?.upgradeTooltipContent?.tooltipContent}
									utmMedium={option?.upgradeTooltipContent?.utmMedium}
									tooltipPosition={option?.upgradeTooltipContent?.tooltipPosition}
								/>
							</div>
						)
					)}
				</Select.Options>
			</Select>
		</Container.Item>
	);
};

export default FormTypeSelectorNew;
