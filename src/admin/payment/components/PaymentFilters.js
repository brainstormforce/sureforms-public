import { __ } from '@wordpress/i18n';
import { useRef, useEffect, useState } from '@wordpress/element';
import { Search, X, Trash } from 'lucide-react';
import { Input, Button, Select } from '@bsf/force-ui';
import DateRangePicker from './DateRangePicker';

/**
 * PaymentFilters Component - Displays all filters and bulk actions
 *
 * @param {Object}   props
 * @param {string}   props.searchTerm           - Current search term
 * @param {Function} props.onSearchChange       - Search change handler
 * @param {Array}    props.selectedPayments     - Array of selected payment IDs
 * @param {Function} props.onBulkDelete         - Bulk delete handler
 * @param {string}   props.statusFilter         - Current status filter
 * @param {Function} props.onStatusFilterChange - Status filter change handler
 * @param {string}   props.formFilter           - Current form filter
 * @param {Function} props.onFormFilterChange   - Form filter change handler
 * @param {string}   props.paymentMode          - Current payment mode filter
 * @param {Function} props.onPaymentModeChange  - Payment mode change handler
 * @param {Object}   props.selectedDates        - Selected date range {from, to}
 * @param {Function} props.onDateChange         - Date range change handler
 * @param {Array}    props.formsList            - List of available forms
 * @param {Function} props.onClearFilters       - Clear all filters handler
 * @param {boolean}  props.hasActiveFilters     - Whether any filters are active
 */
const PaymentFilters = ( {
	searchTerm,
	onSearchChange,
	selectedPayments = [],
	onBulkDelete,
	statusFilter,
	onStatusFilterChange,
	formFilter,
	onFormFilterChange,
	paymentMode,
	onPaymentModeChange,
	selectedDates = { from: null, to: null },
	onDateChange,
	formsList = [],
	onClearFilters,
	hasActiveFilters = false,
} ) => {
	const searchInputRef = useRef( null );
	const [ localSearchValue, setLocalSearchValue ] = useState( searchTerm );

	// Check if any payments are selected
	const hasSelectedPayments = selectedPayments.length > 0;

	// Sync local state when parent state changes
	useEffect( () => {
		setLocalSearchValue( searchTerm );
	}, [ searchTerm ] );

	// Handle search input change (instant visual feedback)
	const handleSearchChange = ( value ) => {
		setLocalSearchValue( value );
	};

	// Handle Enter key press (trigger actual search)
	const handleSearchKeyDown = ( event ) => {
		if ( event.key === 'Enter' ) {
			event.preventDefault();
			onSearchChange( localSearchValue );
		}
	};

	// Status filter options
	const STATUS_FILTERS = [
		{ value: 'succeeded', label: __( 'Paid', 'sureforms' ) },
		{
			value: 'partially_refunded',
			label: __( 'Partially Refunded', 'sureforms' ),
		},
		{ value: 'pending', label: __( 'Pending', 'sureforms' ) },
		{ value: 'failed', label: __( 'Failed', 'sureforms' ) },
		{ value: 'refunded', label: __( 'Refunded', 'sureforms' ) },
	];

	// Get status label for display
	const getStatusLabel = ( value ) => {
		const option = STATUS_FILTERS.find( ( opt ) => opt.value === value );
		return option ? option.label : __( 'Select Status', 'sureforms' );
	};

	// Render bulk actions when payments are selected
	if ( hasSelectedPayments ) {
		return (
			<div className="flex flex-wrap lg:flex-nowrap items-center justify-end gap-2 sm:gap-3 lg:gap-4 mt-4 lg:!mt-0">
				<Button
					variant="outline"
					icon={ <Trash /> }
					size="sm"
					onClick={ onBulkDelete }
					destructive
				>
					{ __( 'Delete', 'sureforms' ) }
				</Button>
			</div>
		);
	}

	// Render regular filters
	return (
		<div className="flex flex-wrap lg:flex-nowrap items-center justify-space-between gap-2 sm:gap-3 lg:gap-4 mt-4 lg:!mt-0">
			{ /* Clear Filters Button */ }
			{ hasActiveFilters && (
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
			) }

			{ /* Payment Mode Filter */ }
			<div>
				<Select
					value={ paymentMode }
					onChange={ onPaymentModeChange }
					size="sm"
				>
					<Select.Button
						className="w-36 h-[2rem] [&_div]:text-xs"
						placeholder={ __( 'Payment Mode', 'sureforms' ) }
					>
						{ ( { value: renderValue } ) => {
							if ( renderValue === 'test' ) {
								return __( 'Test Mode', 'sureforms' );
							}
							if ( renderValue === 'live' ) {
								return __( 'Live Mode', 'sureforms' );
							}
						} }
					</Select.Button>
					<Select.Options>
						<Select.Option value="test">
							{ __( 'Test Mode', 'sureforms' ) }
						</Select.Option>
						<Select.Option value="live">
							{ __( 'Live Mode', 'sureforms' ) }
						</Select.Option>
					</Select.Options>
				</Select>
			</div>

			{ /* Status Filter */ }
			<div>
				<Select
					value={ statusFilter }
					onChange={ onStatusFilterChange }
					size="sm"
				>
					<Select.Button
						className="w-52 h-[2rem] [&_div]:text-xs"
						placeholder={ __( 'Status', 'sureforms' ) }
					>
						{ ( { value: renderValue } ) =>
							renderValue
								? getStatusLabel( renderValue )
								: __( 'Select Status', 'sureforms' )
						}
					</Select.Button>
					<Select.Options>
						{ STATUS_FILTERS.map( ( option ) => (
							<Select.Option
								key={ option.value }
								value={ option.value }
								className="text-xs"
							>
								<span>{ option.label }</span>
							</Select.Option>
						) ) }
					</Select.Options>
				</Select>
			</div>

			{ /* Form Filter */ }
			<div>
				<Select
					value={ formFilter }
					onChange={ onFormFilterChange }
					size="sm"
				>
					<Select.Button
						className="w-52 h-[2rem] [&_div]:text-xs"
						placeholder={ __( 'Form', 'sureforms' ) }
					>
						{ ( { value: renderValue } ) => {
							if ( ! renderValue ) {
								return __( 'Form', 'sureforms' );
							}
							const selectedForm = formsList.find(
								( form ) => form.id === parseInt( renderValue )
							);
							return selectedForm
								? selectedForm.title
								: __( 'Form', 'sureforms' );
						} }
					</Select.Button>
					<Select.Options>
						{ formsList.map( ( form ) => (
							<Select.Option
								key={ form.id }
								value={ form.id }
								className="text-xs"
							>
								<span>{ form.title }</span>
							</Select.Option>
						) ) }
					</Select.Options>
				</Select>
			</div>

			{ /* Date Range Picker */ }
			<div className="">
				<DateRangePicker
					selectedDates={ selectedDates }
					onApply={ onDateChange }
				/>
			</div>
			{ /* Search Input */ }
			<div>
				<Input
					ref={ searchInputRef }
					type="text"
					size="sm"
					onChange={ handleSearchChange }
					onKeyDown={ handleSearchKeyDown }
					value={ localSearchValue }
					placeholder={ __( 'Search…', 'sureforms' ) }
					prefix={ <Search className="text-icon-secondary" /> }
				/>
			</div>
		</div>
	);
};

export default PaymentFilters;
