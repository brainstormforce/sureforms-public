import { __ } from '@wordpress/i18n';
import { useRef, useEffect, useMemo, useState } from '@wordpress/element';
import { Search, X, Trash2, Plus, Calendar, Upload, Download } from 'lucide-react';
import { Input, Button, Container, Select, DatePicker } from '@bsf/force-ui';
import { getDatePlaceholder, getSelectedDate, getLastNDays } from '@Utils/Helpers';

/**
 * FormsHeader Component
 * Combined header with title, add new button, and all filters
 */
const FormsHeader = ( {
	searchQuery,
	onSearchChange,
	selectedForms = [],
	onBulkTrash,
	onBulkExport,
	statusFilter,
	onStatusFilterChange,
	statusCounts = {},
	selectedDates = { from: null, to: null },
	onDateChange,
} ) => {
	const searchInputRef = useRef( null );
	const containerRef = useRef( null );
	const [ isDatePickerOpen, setIsDatePickerOpen ] = useState( false );

	// Check if any forms are selected
	const hasSelectedForms = useMemo(
		() => selectedForms.length > 0,
		[ selectedForms ]
	);

	useEffect( () => {
		if ( searchInputRef.current ) {
			searchInputRef.current.value = searchQuery;
		}
	}, [ searchQuery ] );

	// Handle search functionality
	const handleSearchChange = ( value ) => {
		onSearchChange( value );
	};

	const handleSearchKeyDown = ( event ) => {
		if ( event.key === 'Enter' ) {
			event.preventDefault();
			onSearchChange( event.target.value );
		}
	};

	const clearSearch = () => {
		if ( searchInputRef.current ) {
			searchInputRef.current.value = '';
		}
		onSearchChange( '' );
	};

	const handleAddNew = () => {
		window.location.href = 'admin.php?page=add-new-form';
	};

	const handleImportForm = () => {
		// TODO: Implement import form functionality
		console.log( 'Import form clicked' );
	};

	// Status options with counts
	const statusOptions = [
		{
			key: 'any',
			label: __( 'All Forms', 'sureforms' ),
			count: statusCounts.total || 0,
		},
		{
			key: 'publish',
			label: __( 'Published', 'sureforms' ),
			count: statusCounts.publish || 0,
		},
		{
			key: 'draft',
			label: __( 'Drafts', 'sureforms' ),
			count: statusCounts.draft || 0,
		},
		{
			key: 'trash',
			label: __( 'Trash', 'sureforms' ),
			count: statusCounts.trash || 0,
		},
	];

	const getStatusLabel = ( key ) => {
		const option = statusOptions.find( opt => opt.key === key );
		return option ? option.label : __( 'All Forms', 'sureforms' );
	};

	// Handle date picker
	const handleDateApply = ( dates ) => {
		const { from, to } = dates;

		if ( from && to ) {
			const fromDate = new Date( from );
			const toDate = new Date( to );

			if ( fromDate > toDate ) {
				onDateChange( { from: to, to: from } );
			} else {
				onDateChange( dates );
			}
		} else if ( from && ! to ) {
			onDateChange( { from, to: from } );
		} else {
			onDateChange( { from: null, to: null } );
		}
		setIsDatePickerOpen( false );
	};

	const handleDateCancel = () => {
		setIsDatePickerOpen( false );
	};

	// Click Outside Handler for date picker
	useEffect( () => {
		function handleClickOutside( event ) {
			if (
				isDatePickerOpen &&
				containerRef.current &&
				! containerRef.current.contains( event.target )
			) {
				setIsDatePickerOpen( false );
			}
		}

		document.addEventListener( 'mousedown', handleClickOutside );
		return () => {
			document.removeEventListener( 'mousedown', handleClickOutside );
		};
	}, [ isDatePickerOpen ] );

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
						<h1 className="text-3xl font-semibold text-text-primary leading-9">
							{ __( 'All Forms', 'sureforms' ) }
						</h1>
					</Container.Item>
					<Container.Item>
						<Button
							variant="primary"
							size="md"
							icon={ <Plus className="w-4 h-4" /> }
							iconPosition="left"
							onClick={ handleAddNew }
						>
							{ __( 'Add New Form', 'sureforms' ) }
						</Button>
					</Container.Item>
				</Container>
			</Container.Item>

			{/* Right side - Filters or Bulk actions */}
			<Container.Item>
				{ hasSelectedForms ? (
					<Container direction="row" align="center" className="gap-3">
						<Container.Item>
							<Button
								variant="ghost"
								size="md"
								icon={ <Trash2 className="w-4 h-4" /> }
								iconPosition="left"
								onClick={ onBulkTrash }
								destructive
							>
								{ __( 'Trash', 'sureforms' ) }
							</Button>
						</Container.Item>
						<Container.Item>
							<Button
								variant="secondary"
								size="md"
								icon={ <Download className="w-4 h-4" /> }
								iconPosition="left"
								onClick={ onBulkExport }
							>
								{ __( 'Export', 'sureforms' ) }
							</Button>
						</Container.Item>
					</Container>
				) : (
					<Container direction="row" align="center" className="gap-3">
						{/* Status Dropdown */}
						<Container.Item>
							<div className="min-w-[160px]">
								<Select
									value={ statusFilter }
									onChange={ onStatusFilterChange }
									size="md"
								>
									<Select.Button>
										{ getStatusLabel( statusFilter ) }
									</Select.Button>
									<Select.Options className="z-999999">
										{ statusOptions.map( ( option ) => (
											<Select.Option
												key={ option.key }
												value={ option.key }
											>
												{ option.label } ({ option.count })
											</Select.Option>
										) ) }
									</Select.Options>
								</Select>
							</div>
						</Container.Item>

						{/* Date Picker */}
						<Container.Item>
							<div
								className="relative"
								ref={ containerRef }
							>
								<Input
									type="text"
									size="md"
									value={ getSelectedDate( selectedDates ) }
									suffix={
										<Calendar className="text-icon-secondary" />
									}
									onClick={ () =>
										setIsDatePickerOpen( ( prev ) => ! prev )
									}
									placeholder={ getDatePlaceholder() }
									className="min-w-[200px]"
									readOnly
									aria-label={ __(
										'Select Date Range',
										'sureforms'
									) }
								/>
								{ isDatePickerOpen && (
									<div className="absolute z-999999 mt-2 rounded-lg shadow-lg right-0 bg-background-primary">
										<DatePicker
											applyButtonText={ __(
												'Apply',
												'sureforms'
											) }
											cancelButtonText={ __(
												'Cancel',
												'sureforms'
											) }
											selectionType="range"
											showOutsideDays={ false }
											variant="presets"
											onApply={ handleDateApply }
											onCancel={ handleDateCancel }
											selected={ getLastNDays(
												selectedDates
											) }
										/>
									</div>
								) }
							</div>
						</Container.Item>

						{/* Search */}
						<Container.Item>
							<div className="relative min-w-[280px]">
								<Input
									ref={ searchInputRef }
									placeholder={ __( 'Search forms...', 'sureforms' ) }
									size="md"
									onKeyDown={ handleSearchKeyDown }
									onChange={ ( e ) => handleSearchChange( e.target.value ) }
									icon={ <Search className="w-4 h-4" /> }
									iconPosition="left"
									className="w-full"
								/>
								{ searchQuery && (
									<Button
										variant="ghost"
										size="xs"
										icon={ <X className="w-3 h-3" /> }
										onClick={ clearSearch }
										className="absolute right-2 top-1/2 transform -translate-y-1/2 p-1"
									/>
								) }
							</div>
						</Container.Item>

						{/* Import Form Button */}
						<Container.Item>
							<Button
								variant="secondary"
								size="md"
								icon={ <Upload className="w-4 h-4" /> }
								iconPosition="left"
								onClick={ handleImportForm }
							>
								{ __( 'Import Form', 'sureforms' ) }
							</Button>
						</Container.Item>
					</Container>
				) }
			</Container.Item>
		</Container>
	);
};

export default FormsHeader;