import { __ } from '@wordpress/i18n';
import { Badge, Button, Container, Label, TextArea, Title, Switch } from '@bsf/force-ui';
import { ArrowRight, ChevronDown, MicOff } from 'lucide-react';

export default () => {
	return (
		<Container
			className="p-4 gap-1.5 bg-background-primary border-0.5 border-solid border-border-subtle shadow-sm-blur-2 rounded-xl w-[50%] ml-auto mr-auto"
			containerType="flex"
			direction="column"
		>
			<Container.Item className="p-2 gap-6">
				<Title
					tag="h4"
					size="md"
					className="text-text-primary font-semibold"
					title={ __( 'Please describe the form you want to create', 'sureforms' ) }
				/>
			</Container.Item>
			<Container.Item className="p-2 gap-6">
				<TextArea
					aria-label={ __( 'Describe the form you want to create', 'sureforms' ) }
					defaultValue=""
					placeholder={ __( "E.g. Form to gather feedback from our customer for our product functionality, usability , how much you will rate it and what you don't like about it.", 'sureforms' ) }
					id="textarea"
					size="lg"
					className="gap-1.5 w-full h-[124px] text-field-placeholder font-normal text-md"
				/>
			</Container.Item>
			<Container.Item className="p-2 flex flex-row items-center gap-3">
				<Switch
					aria-label={ __( 'Create Conversation Form', 'sureforms' ) }
					id="switch-element"
					onChange={ function Ki() { } }
					size="sm"
					className="border border-toggle-off-border shadow-sm-blur-2"
				/>
				<Label
					variant="neutral"
					className="font-medium text-field-label text-sm"
				>
					{ __( 'Create Conversation Form', 'sureforms' ) }
				</Label>
			</Container.Item>
			<Container.Item className="p-2 gap-6">
				<Container className="flex flex-row justify-between">
					<Container.Item className="p-1 gap-1 flex flex-row items-center">
						<Label
							variant="neutral"
							className="font-medium text-text-primary text-sm"
						>
							{ __( 'Some Form Ideas', 'sureforms' ) }
						</Label>
						<ChevronDown size={ 20 } className="text-icon-secondary" />
					</Container.Item>
					<Container.Item className="py-2 px-4 gap-2">
						<Badge
							icon={ <MicOff size={ 12 } /> }
							label={ __( 'Voice Input', 'sureforms' ) }
							size="xs"
							type="pill"
							variant="neutral"
							className="bg-badge-background-orange-10 border-0.5 border-badge-background-orange-30 text-brand-800 font-medium text-xs hover:bg-badge-background-orange-10 hover:cursor-pointer"
						/>
					</Container.Item>
				</Container>
			</Container.Item>
			<Container.Item className="py-1 px-2 gap-3 flex justify-end">
				<Button
					className="bg-button-primary hover:bg-button-primary gap-1 border border-solid border-button-primary text-text-on-color hover:border-button-primary shadow-sm-blur-2"
					icon={ <ArrowRight size={ 20 } strokeWidth={ 1.25 } /> }
					iconPosition="right"
					size="md"
					tag="button"
					type="button"
					variant="outline"
				>
					<Label
						variant="neutral"
						className="font-semibold text-text-on-color text-sm hover:cursor-pointer"
					>
						{ __( 'Generate Form', 'sureforms' ) }
					</Label>
				</Button>
			</Container.Item>
		</Container >
	);
};
