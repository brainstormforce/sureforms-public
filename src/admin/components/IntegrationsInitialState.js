import { __ } from '@wordpress/i18n';
import { Button, Container, Text, Title } from '@bsf/force-ui';
import { cn } from '@Utils/Helpers';
import { Dot } from 'lucide-react';

const IntegrationsInitialState = ( {
	title,
	description,
	buttonText,
	buttonIcon = null,
	buttonIconPosition = 'left',
	buttonVariant = 'primary',
	buttonSize = 'md',
	onButtonClick,
	imageClassName = '',
	titleClassName = '',
	descriptionClassName = '',
	// Allow image to be passed as prop.
	image,
	// Additional props for OttoKit-style layout
	features = [],
} ) => {
	return (
		<Container className="flex bg-background-primary rounded-xl">
			<Container className="p-2 rounded-lg bg-background-secondary gap-2 w-full">
				<Container className="p-6 gap-6 rounded-md bg-background-primary w-full">
					<Container className="items-start">
						{ image && (
							<img
								src={ image }
								alt={
									title || __( 'Integration', 'sureforms' )
								}
								className={ cn(
									'w-[300px] h-[300px]',
									imageClassName
								) }
							/>
						) }
					</Container>
					<Container className="gap-8">
						<div className="space-y-2">
							{ title && (
								<Title
									tag="h3"
									title={ title }
									size="md"
									className={ titleClassName }
								/>
							) }
							{ description && (
								<Text
									size={ 16 }
									weight={ 400 }
									color="secondary"
									className={ descriptionClassName }
								>
									{ description }
								</Text>
							) }
							{ features.length > 0 &&
								features.map( ( feature, index ) => (
									<Container
										key={ index }
										className="flex items-center gap-1.5"
									>
										<Dot className="text-icon-secondary" />
										<Text
											size={ 16 }
											weight={ 400 }
											color="secondary"
										>
											{ feature }
										</Text>
									</Container>
								) ) }
							{ buttonText && onButtonClick && (
								<Container className="p-2 gap-3">
									<Button
										size={ buttonSize }
										variant={ buttonVariant }
										onClick={ onButtonClick }
										icon={ buttonIcon }
										iconPosition={ buttonIconPosition }
									>
										{ buttonText }
									</Button>
								</Container>
							) }
						</div>
					</Container>
				</Container>
			</Container>
		</Container>
	);
};

export default IntegrationsInitialState;
