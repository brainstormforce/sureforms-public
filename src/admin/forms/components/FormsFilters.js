import { __ } from '@wordpress/i18n';
import { useRef, useEffect, useMemo, useState } from '@wordpress/element';
import { Trash, Calendar, Import, ArchiveRestore } from 'lucide-react';
import {
	Input,
	Button,
	Container,
	Select,
	DatePicker,
	SearchBox,
} from '@bsf/force-ui';
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
	statusFilter,
	onStatusFilterChange,
	selectedDates = { from: null, to: null },
	onDateChange,
	onImportSuccess,
} ) => {
	const searchInputRef = useRef( null );
	const containerRef = useRef( null );
	const [ isDatePickerOpen, setIsDatePickerOpen ] = useState( false );
	const [ isImportDialogOpen, setIsImportDialogOpen ] = useState( false );
	const [ localSearchValue, setLocalSearchValue ] = useState( searchQuery );

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

	// Render bulk actions when forms are selected
	if ( hasSelectedForms ) {
		return (
			<Container direction="row" align="center" className="gap-3">
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
						onClick={ () =>
							setIsDatePickerOpen( ( prev ) => ! prev )
						}
						placeholder={ getDatePlaceholder() }
						className="min-w-[200px]"
						readOnly
						aria-label={ __( 'Select Date Range', 'sureforms' ) }
					/>
					{ isDatePickerOpen && (
						<div className="absolute z-999999 mt-2 rounded-lg shadow-lg right-0 bg-background-primary">
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
				</div>
			</Container.Item>

			{ /* Search */ }
			<Container.Item>
				<SearchBox
					variant="secondary"
					size="sm"
					open={ false }
					className="w-full"
					filter={ false }
				>
					<SearchBox.Input
						ref={ searchInputRef }
						placeholder={ __( 'Search forms…', 'sureforms' ) }
						value={ localSearchValue }
						onChange={ handleSearchChange }
						onKeyDown={ handleSearchKeyDown }
					/>
				</SearchBox>
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
