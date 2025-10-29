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
	onClose,
	features = [],
} ) => {
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
				className="bg-background-primary gap-4 p-5 rounded-lg max-w-md shadow-lg"
			>
				<Container.Item className="relative pt-2">
					<Label
						variant="neutral"
						className="text-xs font-semibold flex gap-3 text-brand-800"
					>
						<span className="pt-1">{ <Zap className="size-4 text-brand-800" /> }</span>
						{ title }
						<span
							className="absolute top-[-10px] right-[-15px] cursor-pointer"
							onClick={ onClose }
						>
							{ ICONS.close }
						</span>
					</Label>
				</Container.Item>

				<Container className="gap-1 w-full" direction="column">
					{ /* Title */ }
					<Label
						size="md"
						className="text-text-primary font-semibold text-lg"
					>
						{ paraOne }
					</Label>

					{ /* Paragraph */ }
					<Label
						size="sm"
						className="text-text-secondary text-sm font-normal leading-relaxed w-full"
					>
						{ paraTwo }
					</Label>
				</Container>

				<Container className="flex-col w-full gap-2">
					{ features.map( ( item, index ) => (
						<Container.Item
							key={ index }
							className="flex items-start gap-2 text-base text-text-primary"
						>
							<Check className="w-4 h-4 text-brand-800 mt-0.5" />
							<Label size="sm" className="font-normal">
								{ item }
							</Label>
						</Container.Item>
					) ) }
				</Container>

				<hr className="border-b-5 border-x-0 border-t-0 w-full border-solid border-border-subtle" />

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
