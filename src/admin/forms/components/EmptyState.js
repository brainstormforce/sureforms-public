import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Button, Text, Container } from '@bsf/force-ui';
import { Plus, Search, Dot } from 'lucide-react';
import noFormsImage from '../no-forms.svg';
import ImportForm from './ImportForm';

// EmptyState Component - Displays different empty states based on context
const EmptyState = ( {
	hasActiveFilters = false,
	onClearFilters,
	onImportSuccess,
} ) => {
	const [ isImportDialogOpen, setIsImportDialogOpen ] = useState( false );

	const handleAddNew = () => {
		window.location.href = 'admin.php?page=add-new-form';
	};

	const handleImportForm = () => {
		setIsImportDialogOpen( true );
	};

	// Handle import success
	const handleImportSuccess = ( response ) => {
		onImportSuccess?.( response );
		setIsImportDialogOpen( false );
	};

	// Empty state when filters are active but no results
	if ( hasActiveFilters ) {
		return (
			<Container className="flex items-center justify-center p-8 bg-background-primary rounded-lg">
				<div className="text-center max-w-md">
					<div className="mb-4">
						<Search className="w-12 h-12 mx-auto text-text-tertiary" />
					</div>
					<Text
						size={ 18 }
						lineHeight={ 26 }
						weight={ 600 }
						color="primary"
						className="mb-2"
					>
						{ __( 'No forms found', 'sureforms' ) }
					</Text>
					<Text
						size={ 14 }
						lineHeight={ 20 }
						color="secondary"
						className="mb-4"
					>
						{ __(
							'No forms match your current filters. Try adjusting your search terms or clearing the filters.',
							'sureforms'
						) }
					</Text>
					<Button
						variant="secondary"
						size="sm"
						onClick={ onClearFilters }
					>
						{ __( 'Clear Filters', 'sureforms' ) }
					</Button>
				</div>
			</Container>
		);
	}

	// Empty state when no forms exist at all
	return (
		<Container className="flex bg-background-primary rounded-xl">
			<Container className="p-2 rounded-lg bg-background-secondary gap-2 w-full">
				<Container className="p-6 gap-6 rounded-md bg-background-primary w-full">
					<Container>
						<img
							src={ noFormsImage }
							alt={ __( 'No Forms', 'sureforms' ) }
							className="w-[320px] h-[320px]"
						/>
					</Container>
					<Container className="gap-8 items-center">
						<div className="space-y-2 p-2">
							<Text
								size={ 20 }
								lineHeight={ 30 }
								weight={ 600 }
								letterSpacing={ -0.5 }
								color="primary"
								className="mb-3"
							>
								{ __(
									"Hi there, let's get you started",
									'sureforms'
								) }
							</Text>

							<Text
								size={ 16 }
								lineHeight={ 24 }
								color="secondary"
								className="mb-6"
							>
								{ __(
									"It looks like you haven't created any forms yet. Start building with SureForms and launch powerful forms in just a few clicks.",
									'sureforms'
								) }
							</Text>

							<div className="space-y-3">
								<div className="text-left space-y-2">
									{ [
										__(
											'Design forms with our Gutenberg-native builder.',
											'sureforms'
										),
										__(
											'Use AI to generate forms instantly from a simple prompt.',
											'sureforms'
										),
										__(
											'Build instant forms and share them with a link-no embedding needed.',
											'sureforms'
										),
										__(
											'Build engaging conversational, calculation, and multi-step forms.',
											'sureforms'
										),
									].map( ( feature, index ) => (
										<Container
											key={ index }
											className="flex items-center gap-1"
										>
											<Dot className="text-icon-secondary" />
											<Text size={ 16 } color="secondary">
												{ feature }
											</Text>
										</Container>
									) ) }
								</div>
							</div>

							<Container className="p-2 gap-3 flex-row">
								<Button
									variant="primary"
									size="md"
									icon={ <Plus className="w-4 h-4" /> }
									iconPosition="left"
									onClick={ handleAddNew }
								>
									{ __( 'Create Form', 'sureforms' ) }
								</Button>
								<Button
									variant="outline"
									size="md"
									onClick={ handleImportForm }
								>
									{ __( 'Import Form', 'sureforms' ) }
								</Button>
							</Container>
						</div>
					</Container>
				</Container>
			</Container>
			{ /* Import Form Dialog */ }
			<ImportForm
				open={ isImportDialogOpen }
				setOpen={ setIsImportDialogOpen }
				onImportSuccess={ handleImportSuccess }
			/>
		</Container>
	);
};

export default EmptyState;
