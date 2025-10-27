import { __ } from '@wordpress/i18n';
import { Button, Container } from '@bsf/force-ui';
import { Plus } from 'lucide-react';
import FormsFilters from './FormsFilters';

/**
 * FormsHeader Component
 * Combined header with title, add new button, and filters component
 */
const FormsHeader = ( {
	searchQuery,
	onSearchChange,
	selectedForms = [],
	onBulkTrash,
	onBulkExport,
	statusFilter,
	onStatusFilterChange,
	selectedDates = { from: null, to: null },
	onDateChange,
} ) => {
	const handleAddNew = () => {
		window.location.href = 'admin.php?page=add-new-form';
	};

	return (
		<Container
			direction="row"
			align="center" 
			justify="between"
			className="w-full"
		>
			{/* Left side - Title and Add New button */}
			<Container.Item>
				<Container direction="row" align="center" className="gap-6">
					<Container.Item>
						<h1 className="text-xl whitespace-nowrap font-semibold text-text-primary leading-[30px] tracking-[-0.005em]">
							{ __( 'All Forms', 'sureforms' ) }
						</h1>
					</Container.Item>
					<Container.Item>
						<Button
							variant="primary"
							size="sm"
							icon={ <Plus className="w-4 h-4" /> }
							iconPosition="left"
							onClick={ handleAddNew }
						>
							{ __( 'Add New Form', 'sureforms' ) }
						</Button>
					</Container.Item>
				</Container>
			</Container.Item>

			{/* Right side - Filters component */}
			<Container.Item>
				<FormsFilters
					searchQuery={ searchQuery }
					onSearchChange={ onSearchChange }
					selectedForms={ selectedForms }
					onBulkTrash={ onBulkTrash }
					onBulkExport={ onBulkExport }
					statusFilter={ statusFilter }
					onStatusFilterChange={ onStatusFilterChange }
					selectedDates={ selectedDates }
					onDateChange={ onDateChange }
				/>
			</Container.Item>
		</Container>
	);
};

export default FormsHeader;