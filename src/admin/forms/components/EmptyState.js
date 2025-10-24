import { __ } from '@wordpress/i18n';
import { Button, Text, Container } from '@bsf/force-ui';
import { Plus, Search } from 'lucide-react';

/**
 * EmptyState Component
 * Displays different empty states based on context
 */
const EmptyState = ( { hasActiveFilters = false, onClearFilters } ) => {
	const handleAddNew = () => {
		window.location.href = 'admin.php?page=add-new-form';
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
						{ __( 'No forms match your current filters. Try adjusting your search terms or clearing the filters.', 'sureforms' ) }
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
		<Container className="flex items-center justify-center p-8 bg-background-primary rounded-lg">
			<div className="flex flex-col md:flex-row items-center gap-8 max-w-4xl">
				{/* Icon/Visual */}
				<div className="flex-shrink-0">
					<div className="w-32 h-32 bg-background-secondary rounded-full flex items-center justify-center">
						<Plus className="w-12 h-12 text-text-tertiary" />
					</div>
				</div>

				{/* Content */}
				<div className="flex-1 text-center md:text-left">
					<Text
						size={ 24 }
						lineHeight={ 32 }
						weight={ 600 }
						letterSpacing={ -0.5 }
						color="primary"
						className="mb-3"
					>
						{ __( 'Create your first form', 'sureforms' ) }
					</Text>
					
					<Text
						size={ 16 }
						lineHeight={ 24 }
						color="secondary"
						className="mb-6"
					>
						{ __( 'Get started by creating your first form. SureForms makes it easy to build beautiful, responsive forms with drag-and-drop blocks.', 'sureforms' ) }
					</Text>

					<div className="space-y-3">
						<Text
							size={ 14 }
							lineHeight={ 20 }
							color="secondary"
							className="mb-4"
						>
							{ __( 'With SureForms you can:', 'sureforms' ) }
						</Text>
						
						<ul className="text-left space-y-2">
							{ [
								__( 'Build forms with native WordPress blocks', 'sureforms' ),
								__( 'Collect and manage form submissions', 'sureforms' ),
								__( 'Customize styling and behavior', 'sureforms' ),
								__( 'Export data for further analysis', 'sureforms' ),
							].map( ( feature, index ) => (
								<li key={ index } className="flex items-start gap-2">
									<span className="text-accent-primary mt-1">•</span>
									<Text size={ 14 } lineHeight={ 20 } color="secondary">
										{ feature }
									</Text>
								</li>
							) ) }
						</ul>
					</div>

					<Button
						variant="primary"
						size="md"
						icon={ <Plus className="w-4 h-4" /> }
						iconPosition="left"
						onClick={ handleAddNew }
						className="mt-6"
					>
						{ __( 'Create Your First Form', 'sureforms' ) }
					</Button>
				</div>
			</div>
		</Container>
	);
};

export default EmptyState;