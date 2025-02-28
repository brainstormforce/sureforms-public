import { cn } from '@Utils/Helpers';
import { Title } from '@bsf/force-ui';

const IntegrationCard = ( { className, children } ) => {
	return (
		<div className={ cn(
			'bg-background-primary rounded-md shadow-sm p-4',
			className
		) }>
			<div className="space-y-4">
				{ children }
			</div>
		</div>
	);
};

const IntegrationHeader = ( { className, children } ) => {
	return (
		<div className={ cn(
			'flex justify-between items-center',
			className
		) }>
			{ children }
		</div>
	);
};

const IntegrationContent = ( { className, children } ) => {
	return (
		<div className={ cn(
			className
		) }>
			{ children }
		</div>
	);
};

const IntegrationTitle = ( { className, title } ) => {
	return (
		<Title
			className={ cn(
				'[&_*]:font-medium',
				className
			) }
			title={ title }
			size="xs"
		/>
	);
};

const IntegrationDescription = ( { className, description } ) => {
	return (
		<p
			className={ cn(
				'mt-0.5',
				'text-sm text-text-tertiary',
				className
			) }
		>
			{ description }
		</p>
	);
};

const IntegrationCTA = ( { className, children } ) => {
	return (
		<div className={ cn(
			'mt-2',
			className
		) }>
			{ children }
		</div>
	);
};

Object.assign( IntegrationCard, {
	Header: IntegrationHeader,
	Content: IntegrationContent,
	Title: IntegrationTitle,
	Description: IntegrationDescription,
	CTA: IntegrationCTA,
} );

export default IntegrationCard;
