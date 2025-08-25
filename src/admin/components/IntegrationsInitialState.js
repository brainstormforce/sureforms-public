import { __ } from '@wordpress/i18n';
import { Button, Container, Text, Title } from '@bsf/force-ui';
import { cn } from '@Utils/Helpers';

const IntegrationsInitialState = ( {
	title,
	description,
	buttonText,
	buttonIcon = null,
	buttonIconPosition = 'left',
	buttonVariant = 'primary',
	buttonSize = 'md',
	onButtonClick,
	className = '',
	imageClassName = '',
	titleClassName = '',
	descriptionClassName = '',
	containerPadding = true,
	// Allow image to be passed as prop.
	image,
} ) => {
	return (
		<Container
			className={ cn(
				'flex flex-col items-center justify-center bg-background-primary',
				containerPadding && 'py-16',
				className
			) }
		>
			{ image && (
				<img
					src={ image }
					alt={ __( 'No Integrations', 'sureforms' ) }
					className={ cn( 'h-20 w-20 mx-auto mb-6', imageClassName ) }
				/>
			) }

			{ title && (
				<Title
					tag="h3"
					title={ title }
					size="md"
					className={ cn(
						'text-center text-text-primary mb-4',
						titleClassName
					) }
				/>
			) }

			{ description && (
				<Text
					size={ 16 }
					weight={ 400 }
					color="secondary"
					className={ cn(
						'text-center max-w-2xl mb-6',
						descriptionClassName
					) }
				>
					{ description }
				</Text>
			) }

			{ buttonText && onButtonClick && (
				<Button
					variant={ buttonVariant }
					size={ buttonSize }
					icon={ buttonIcon }
					iconPosition={ buttonIconPosition }
					onClick={ onButtonClick }
				>
					{ buttonText }
				</Button>
			) }
		</Container>
	);
};

export default IntegrationsInitialState;
