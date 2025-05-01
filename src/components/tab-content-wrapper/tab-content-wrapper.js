import { Button, Container, Title } from '@bsf/force-ui';
import { cn } from '@Utils/Helpers';
import { ArrowLeftIcon } from 'lucide-react';

const TabContentWrapper = ( {
	children,
	title,
	className,
	onClickBack,
	onClickAction,
	actionBtnText,
	actionBtnIcon,
	actionBtnVariant = 'primary',
} ) => {
	const handleBack = () => {
		if ( typeof onClickBack !== 'function' ) {
			return;
		}

		onClickBack();
	};

	const handleClickAction = ( data ) => {
		if ( typeof onClickAction !== 'function' ) {
			return;
		}

		onClickAction( data );
	};

	return (
		<div className="space-y-7 pb-8">
			<Container align="center" justify="between">
				<Container align="center" className="gap-2">
					{ onClickBack && (
						<Button
							className="p-0"
							size="md"
							variant="ghost"
							onClick={ handleBack }
							icon={ <ArrowLeftIcon /> }
						/>
					) }
					<Title tag="h4" title={ title } size="md" />
				</Container>
				{ onClickAction && (
					<Button
						size="md"
						onClick={ handleClickAction }
						icon={ actionBtnIcon }
						variant={ actionBtnVariant }
					>
						{ actionBtnText }
					</Button>
				) }
			</Container>
			<div
				className={ cn(
					'bg-background-primary rounded-xl p-4 shadow-sm',
					className
				) }
			>
				{ children }
			</div>
		</div>
	);
};

export default TabContentWrapper;
