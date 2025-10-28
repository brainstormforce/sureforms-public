import { __ } from '@wordpress/i18n';
import ICONS from '../components/icons.js';
import { Button, Container, Label } from '@bsf/force-ui';
import { applyFilters } from '@wordpress/hooks';
import { Zap, Check } from 'lucide-react';

const UpgradePopup = ( {
	title = '',
	paraOne,
	paraTwo,
	buttonText,
	onclick,
	deactivatedLicense = false,
	onClose,
	features = [],
} ) => {
	// Get filter values
	const {
		paraOne: filteredParaOne,
		paraTwo: filteredParaTwo,
		title: filteredTitle,
	} = applyFilters( 'srfm.aiFormScreen.freeCreds.expired', null ) || {};

	const finalTitle = deactivatedLicense ? filteredTitle : title;
	const finalParaOne = deactivatedLicense ? filteredParaOne : paraOne;
	const finalParaTwo = deactivatedLicense ? filteredParaTwo : paraTwo;
	const FinalButton = (
		<Button size="md" variant="primary" onClick={ onclick }>
			{ buttonText ?? __( 'Connect Now', 'sureforms' ) }
		</Button>
	);

	const renderLimitReachedContent = () => (
		<Container
			direction="column"
			justify="center"
			align="center"
			className="fixed inset-0 bg-overlay-background z-[99999999]"
		>
			<Container
				direction="column"
				className="bg-background-primary gap-6 py-4 px-5 rounded-lg max-w-md shadow-lg"
			>
				<Container.Item className="relative pt-2">
					<Label
						variant="neutral"
						className="text-lg font-bold flex gap-3"
					>
						<span className="pt-1">{ <Zap /> }</span>
						{ finalTitle }
						<span
							className="absolute top-[-10px] right-[-15px] cursor-pointer"
							onClick={ onClose }
						>
							{ ICONS.close }
						</span>
					</Label>
				</Container.Item>

				<Container className="gap-1 p-2 w-full" direction="column">
					{ /* Title */ }
					<Label
						as="h2"
						size="lg"
						className="text-[#141338] font-semibold text-lg"
					>
						{ __(
							'Build Better Forms with SureForms',
							'sureforms'
						) }
						{ finalParaOne }
					</Label>

					{ /* Paragraph */ }
					<Label
						size="sm"
						className="text-[#4F4E7C] text-sm font-normal leading-relaxed w-full"
					>
						{ finalParaTwo }
					</Label>
				</Container>

				<Container className="flex-col w-full p-2">
					{ features.map( ( item, index ) => (
						<Container.Item
							key={ index }
							className="flex items-start gap-2 text-base text-[#141338]"
						>
							<Check className="w-4 h-4 text-brand-800 mt-0.5" />
							<Label size="sm" className="font-normal">
								{ item }
							</Label>
						</Container.Item>
					) ) }
				</Container>

				<Container.Item className="flex flex-col w-full gap-3 pb-2">
					{ FinalButton }
				</Container.Item>
			</Container>
		</Container>
	);

	return (
		<>
			{ renderLimitReachedContent() }
		</>
	);
};

export default UpgradePopup;
