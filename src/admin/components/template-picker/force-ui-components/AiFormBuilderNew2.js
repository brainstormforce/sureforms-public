import { __ } from '@wordpress/i18n';
import { Badge, Button, Container, Label, TextArea, Title, Switch } from '@bsf/force-ui';
import { ArrowRight, ChevronDown, Info, Mic } from 'lucide-react';

export default () => {
	return (
		<Container
			className="p-4 gap-1.5 bg-background-primary border-0.5 border-solid border-border-subtle shadow-sm-blur-2 rounded-xl w-[50%] ml-auto mr-auto"
			containerType="flex"
			direction="column"
		>
			<Container.Item className="flex flex-col p-2 gap-2">
				<Title
					tag="h4"
					size="md"
					className="text-text-primary font-semibold"
					title={ __( 'Please select form type', 'sureforms' ) }
				/>
				<Container
					className="p-1 gap-1 bg-tab-background border-0.5 border-solid border-tab-border rounded-md"
					containerType="grid"
					cols={ 12 }
				>
					<Container.Item
						className="p-1.5 flex items-center justify-center"
						colSpan={ 6 }
					>
						<Label
							className="font-medium text-text-secondary text-base"
						>
							{ __( 'Simple', 'sureforms' ) }
						</Label>
					</Container.Item>
					<Container.Item
						className="flex flex-row items-center justify-center p-1.5 rounded-md bg-background-primary shadow-sm-blur-2 gap-1"
						colSpan={ 6 }>
						<Label
							className="font-medium text-text-primary text-base"
						>
							{ __( 'Calculations', 'sureforms' ) }
						</Label>
						<Info size={ 12 } className="text-badge-color-gray" />
					</Container.Item>
				</Container>
			</Container.Item>
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
				<Container
					containerType="flex"
					direction="column"
				>
					<Container.Item>
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
									icon={ <Mic size={ 12 } /> }
									label={ __( 'Voice Input', 'sureforms' ) }
									size="xs"
									type="pill"
									variant="neutral"
									className="bg-badge-background-green border-0.5 border-badge-border-green text-badge-color-green font-medium text-xs hover:bg-badge-background-green hover:cursor-pointer"
								/>
							</Container.Item>
						</Container>
					</Container.Item>
					<Container.Item>
						<Container className="gap-2 flex-wrap"
							containerType="flex"
							direction="row"
						>
							<Badge
								label="Generate user feedback form"
								size="md"
								type="pill"
								variant="neutral"
								className="border-0.5 border-solid border-badge-border-gray bg-badge-background-gray hover:bg-badge-background-gray hover:cursor-pointer text-badge-color-gray font-medium text-sm"
							/>
							<Badge
								label="Create a job application form"
								size="md"
								type="pill"
								variant="neutral"
								className="border-0.5 border-solid border-badge-border-gray bg-badge-background-gray hover:bg-badge-background-gray hover:cursor-pointer text-badge-color-gray font-medium text-sm"
							/>
							<Badge
								label="Create simple contact form"
								size="md"
								type="pill"
								variant="neutral"
								className="border-0.5 border-solid border-badge-border-gray bg-badge-background-gray hover:bg-badge-background-gray hover:cursor-pointer text-badge-color-gray font-medium text-sm"
							/>
							<Badge
								label="Make an event registration form"
								size="md"
								type="pill"
								variant="neutral"

							/>
							<Badge
								label="Create market research survey form for..."
								size="md"
								type="pill"
								variant="neutral"
								className="border-0.5 border-solid border-badge-border-gray bg-badge-background-gray hover:bg-badge-background-gray hover:cursor-pointer text-badge-color-gray font-medium text-sm"
							/>
						</Container>
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
