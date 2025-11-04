import { __ } from '@wordpress/i18n';
import { useRef, useEffect, useMemo, useState } from '@wordpress/element';
import {
	Trash,
	Calendar,
	Import,
	ArchiveRestore,
	Search,
	X,
	RotateCcw,
} from 'lucide-react';
import { Input, Button, Container, Select, DatePicker } from '@bsf/force-ui';
import {
	getDatePlaceholder,
	getSelectedDate,
	getLastNDays,
} from '@Utils/Helpers';
import ImportForm from './ImportForm';

// FormsFilters Component - Displays all filters: status dropdown, date picker, search, and import button
// Shows bulk actions when forms are selected
const FormsFilters = ( {
	searchQuery,
	onSearchChange,
	selectedForms = [],
	onBulkTrash,
	onBulkExport,
	onBulkRestore,
	onBulkDelete,
	statusFilter,
	onStatusFilterChange,
	selectedDates = { from: null, to: null },
	onDateChange,
	onImportSuccess,
	onClearFilters,
	hasActiveFilters = false,
} ) => {
	const searchInputRef = useRef( null );
	const containerRef = useRef( null );
	const datePickerRef = useRef( null );
	const [ isDatePickerOpen, setIsDatePickerOpen ] = useState( false );
	const [ isImportDialogOpen, setIsImportDialogOpen ] = useState( false );
	const [ localSearchValue, setLocalSearchValue ] = useState( searchQuery );
	const [ datePickerPosition, setDatePickerPosition ] = useState( {
		right: 0,
	} );

	// Check if any forms are selected
	const hasSelectedForms = useMemo(
		() => selectedForms.length > 0,
		[ selectedForms ]
	);

	useEffect( () => {
		setLocalSearchValue( searchQuery );
	}, [ searchQuery ] );

	// Handle search functionality
	const handleSearchChange = ( value ) => {
		setLocalSearchValue( value );
	};

	const handleSearchKeyDown = ( event ) => {
		if ( event.key === 'Enter' ) {
			event.preventDefault();
			onSearchChange( localSearchValue );
		}
	};

	const handleImportForm = () => {
		setIsImportDialogOpen( true );
	};

	// Handle import success
	const handleImportSuccess = ( response ) => {
		onImportSuccess?.( response );
		setIsImportDialogOpen( false );
	};

	// Status options
	const statusOptions = [
		{
			key: 'any',
			label: __( 'All Forms', 'sureforms' ),
		},
		{
			key: 'publish',
			label: __( 'Published', 'sureforms' ),
		},
		{
			key: 'draft',
			label: __( 'Drafts', 'sureforms' ),
		},
		{
			key: 'trash',
			label: __( 'Trash', 'sureforms' ),
		},
	];

	const getStatusLabel = ( key ) => {
		const option = statusOptions.find( ( opt ) => opt.key === key );
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
				! containerRef.current.contains( event.target ) &&
				datePickerRef.current &&
				! datePickerRef.current.contains( event.target )
			) {
				setIsDatePickerOpen( false );
			}
		}

		document.addEventListener( 'mousedown', handleClickOutside );
		return () => {
			document.removeEventListener( 'mousedown', handleClickOutside );
		};
	}, [ isDatePickerOpen ] );

	// Render bulk actions when forms are selected
	if ( hasSelectedForms ) {
		return (
			<Container direction="row" align="center" className="gap-3">
				{ statusFilter === 'trash' && (
					<Container.Item>
						<Button
							variant="outline"
							size="sm"
							icon={ <RotateCcw className="w-4 h-4" /> }
							iconPosition="left"
							onClick={ onBulkRestore }
						>
							{ __( 'Restore', 'sureforms' ) }
						</Button>
					</Container.Item>
				) }
				{ statusFilter === 'trash' ? (
					<Container.Item>
						<Button
							variant="outline"
							size="sm"
							icon={ <Trash className="w-4 h-4" /> }
							iconPosition="left"
							onClick={ onBulkDelete }
							destructive
						>
							{ __( 'Delete', 'sureforms' ) }
						</Button>
					</Container.Item>
				) : (
					<Container.Item>
						<Button
							variant="outline"
							size="sm"
							icon={ <Trash className="w-4 h-4" /> }
							iconPosition="left"
							onClick={ onBulkTrash }
							destructive
						>
							{ __( 'Trash', 'sureforms' ) }
						</Button>
					</Container.Item>
				) }
				<Container.Item>
					<Button
						variant="outline"
						size="sm"
						icon={ <ArchiveRestore className="w-4 h-4" /> }
						iconPosition="left"
						onClick={ onBulkExport }
					>
						{ __( 'Export', 'sureforms' ) }
					</Button>
				</Container.Item>
			</Container>
		);
	}

	// Render regular filters
	return (
		<Container direction="row" align="center" className="gap-3">
			{ /* Clear Filters button - shown when filters are active */ }
			{ hasActiveFilters && ! hasSelectedForms && (
				<Container.Item>
					<Button
						variant="outline"
						size="sm"
						onClick={ onClearFilters }
						icon={ <X className="w-4 h-4" /> }
						iconPosition="left"
						className="min-w-fit"
						destructive
					>
						{ __( 'Clear Filters', 'sureforms' ) }
					</Button>
				</Container.Item>
			) }

			{ /* Status Dropdown */ }
			<Container.Item>
				<div className="min-w-[160px]">
					<Select
						value={ statusFilter }
						onChange={ onStatusFilterChange }
						size="sm"
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
									{ option.label }
								</Select.Option>
							) ) }
						</Select.Options>
					</Select>
				</div>
			</Container.Item>

			{ /* Date Picker */ }
			<Container.Item>
				<div className="relative" ref={ containerRef }>
					<Input
						type="text"
						size="sm"
						value={ getSelectedDate( selectedDates ) }
						suffix={ <Calendar className="text-icon-secondary" /> }
						onClick={ () => {
							if ( ! isDatePickerOpen && containerRef.current ) {
								const rect =
									containerRef.current.getBoundingClientRect();
								setDatePickerPosition( {
									right: window.innerWidth - rect.right,
								} );
							}
							setIsDatePickerOpen( ( prev ) => ! prev );
						} }
						placeholder={ getDatePlaceholder() }
						className="min-w-[200px]"
						readOnly
						aria-label={ __( 'Select Date Range', 'sureforms' ) }
					/>
				</div>
				{ /* Date Picker inside Container.Item but outside relative div */ }
				{ isDatePickerOpen && (
					<div
						ref={ datePickerRef }
						className="absolute z-[9999] mt-2 rounded-md shadow-soft-shadow-md bg-background-primary"
						style={ {
							right: datePickerPosition.right,
						} }
					>
						<DatePicker
							applyButtonText={ __( 'Apply', 'sureforms' ) }
							cancelButtonText={ __( 'Cancel', 'sureforms' ) }
							selectionType="range"
							showOutsideDays={ false }
							variant="presets"
							onApply={ handleDateApply }
							onCancel={ handleDateCancel }
							selected={ getLastNDays( selectedDates ) }
						/>
					</div>
				) }
			</Container.Item>

			{ /* Search */ }
			<Container.Item>
				<Input
					ref={ searchInputRef }
					type="text"
					size="sm"
					placeholder={ __( 'Search forms…', 'sureforms' ) }
					value={ localSearchValue }
					onChange={ handleSearchChange }
					onKeyDown={ handleSearchKeyDown }
					prefix={
						<Search className="w-4 h-4 text-icon-secondary" />
					}
					className="min-w-[200px]"
				/>
			</Container.Item>

			{ /* Import Form Button */ }
			<Container.Item>
				<Button
					variant="outline"
					size="sm"
					icon={ <Import className="w-4 h-4" /> }
					iconPosition="left"
					onClick={ handleImportForm }
				>
					{ __( 'Import Form', 'sureforms' ) }
				</Button>
			</Container.Item>
			{ /* Import Form */ }
			<ImportForm
				open={ isImportDialogOpen }
				setOpen={ setIsImportDialogOpen }
				onImportSuccess={ handleImportSuccess }
			/>
		</Container>
	);
};

export default FormsFilters;
