import { Button, Container, Title, Label } from '@bsf/force-ui';
import { cn } from '@Utils/Helpers';
import { ArrowLeftIcon } from 'lucide-react';
import { __ } from '@wordpress/i18n';

const TabContentWrapper = ( {
	children,
	title,
	className,
	onClickBack,
	onClickAction,
	actionBtnText,
	actionBtnIcon,
	actionBtnVariant = 'primary',
	actionLeftContent,
	actionBtnDisabled = false,
	shouldShowAutoSaveText = false,
	autoSaveHelpText =__( 'All changes will be saved automatically when you press back.', 'sureforms' )
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
				<Container className="gap-0" direction="column">
					<Container.Item className="flex items-center gap-2">
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
					</Container.Item>
					<Container.Item className="pl-7">
						{ shouldShowAutoSaveText && (
							<Label size="sm" variant="help" className="text-text-on-button-disabled font-normal">
								{ autoSaveHelpText }
							</Label>
						) }
					</Container.Item>
				</Container>
				<Container align="center" className="gap-3">
					{ actionLeftContent }
					{ onClickAction && (
						<Button
							size="md"
							onClick={ handleClickAction }
							icon={ actionBtnIcon }
							variant={ actionBtnVariant }
							disabled={ actionBtnDisabled }
						>
							{ actionBtnText }
						</Button>
					) }
				</Container>
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
