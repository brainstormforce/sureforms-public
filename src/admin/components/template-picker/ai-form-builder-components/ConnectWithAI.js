import { __ } from '@wordpress/i18n';
import { Button, Container, Label } from '@bsf/force-ui';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import aiAuthPlaceholder from '@Image/ai-auth.svg';

export default () => {
	const examplePrompts =
		[
			{
				title: __( '1. Create a free account to connect with our AI.', 'sureforms' ),
			},
			{
				title: __( '2. Describe the form you want to create in words.', 'sureforms' ),
			},
			{
				title: __( '3. Watch as our AI crafts your form instantly.', 'sureforms' ),
			},
			{
				title: __( '4. Refine the form with our easy drag & drop.', 'sureforms' ),
			},
			{
				title: __( '5. Launch.', 'sureforms' ),
			},
		];

	return (
		<Container
			className="p-8 gap-6 bg-background-secondary w-[90%] ml-auto mr-auto"
			containerType="flex"
			direction="column"
		>
			<Container.Item className="gap-8"
				containerType="flex"
				direction="column"
			>
				<Container
					className="px-2 gap-12"
					containerType="grid"
					cols={ 12 }
				>
					<Container.Item
						className=""
						colSpan={ 6 }
					>
						<Container
							className="gap-2"
							containerType="flex"
							direction="column"
						>
							<Container.Item className="flex flex-col p-1 gap-1">
								<Label
									tag="h3"
									variant="neutral"
									className="font-semibold text-2xl text-text-primary"
								>
									{ __( 'Building forms with AI', 'sureforms' ) }
								</Label>
								<Label
									variant="neutral"
									className="font-normal text-text-secondary text-base"
								>
									{ __( 'SureForms AI, helps you build your forms 10x faster.', 'sureforms' ) }
								</Label>
							</Container.Item>
							<Container.Item className="p-1">
								<Label
									variant="neutral"
									className="font-normal text-text-secondary text-base"
								>
									{ __( 'Here is how the AI Form Builder Works:', 'sureforms' ) }
								</Label>
							</Container.Item>
							<Container.Item>
								<Container
									containerType="flex"
									direction="column"
									className="gap-1.5"
								>
									<Container.Item>
										{ examplePrompts.map( ( prompt, index ) => (
											<Label
												key={ index }
												variant="neutral"
												className="font-medium text-field-input text-sm"
											>
												{ prompt.title }
											</Label>
										) ) }
									</Container.Item>
								</Container>
							</Container.Item>
							<Container.Item>
								<Container
									containerType="flex"
									direction="row"
									className="gap-1 p-1"
								>
									<Container.Item>
										<Button
											className="text-text-tertiary font-semibold text-sm"
											icon={ <ArrowLeft size={ 20 } strokeWidth={ 1.25 } /> }
											iconPosition="left"
											size="md"
											variant="ghost"
										>
											{ __( 'Back', 'sureforms' ) }
										</Button>
									</Container.Item>
									<Container.Item>
										<Button
											className="bg-button-primary hover:bg-button-primary gap-1 border border-solid border-button-primary text-text-on-color hover:border-button-primary shadow-sm-blur-2"
											icon={ <ArrowRight size={ 20 } strokeWidth={ 1.25 } /> }
											iconPosition="right"
											size="md"
											variant="outline"
										>
											<Label
												variant="neutral"
												className="font-semibold text-text-on-color text-sm hover:cursor-pointer"
											>
												{ __( 'Get Started', 'sureforms' ) }
											</Label>
										</Button>
									</Container.Item>
								</Container>
							</Container.Item>
						</Container>
					</Container.Item>
					<Container.Item>
						<img
							src={ aiAuthPlaceholder }
							alt={ __( 'AI Form Builder', 'sureforms' ) }
						/>
					</Container.Item>
				</Container>
			</Container.Item>
		</Container >
	);
};
