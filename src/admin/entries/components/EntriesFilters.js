import { __ } from '@wordpress/i18n';
import {
	Search,
	Calendar,
	X,
	Trash2,
	MoreVertical,
	RotateCcw,
	ArchiveRestore,
} from 'lucide-react';
import { Select, Input, Button, DropdownMenu } from '@bsf/force-ui';
import { useRef, useEffect, useMemo } from '@wordpress/element';
import DatePicker from '@Admin/components/DatePicker';
import { STATUS_OPTIONS } from '../constants';

/**
 * EntriesFilters Component
 * Displays all filter controls for entries or bulk action buttons when items are selected
 *
 * @param {Object}   props                      - Component props
 * @param {string}   props.statusFilter         - Current status filter value
 * @param {Function} props.onStatusFilterChange - Handler for status filter change
 * @param {string}   props.formFilter           - Current form filter value
 * @param {Function} props.onFormFilterChange   - Handler for form filter change
 * @param {string}   props.searchQuery          - Current search query
 * @param {Function} props.onSearchChange       - Handler for search input change
 * @param {Object}   props.dateRange            - Current date range
 * @param {Function} props.onDateRangeChange    - Handler for date range change
 * @param {Array}    props.formOptions          - Array of form options
 * @param {Array}    props.selectedEntries      - Array of selected entry IDs
 * @param {Function} props.onBulkDelete         - Handler for bulk delete action
 * @param {Function} props.onBulkExport         - Handler for bulk export action
 * @param {Function} props.onMarkAsRead         - Handler for mark as read action
 * @param {Function} props.onMarkAsUnread       - Handler for mark as unread action
 * @param {Function} props.onBulkRestore        - Handler for bulk restore action
 */
const EntriesFilters = ( {
	statusFilter,
	onStatusFilterChange,
	formFilter,
	onFormFilterChange,
	searchQuery,
	onSearchChange,
	dateRange,
	onDateRangeChange,
	formOptions = [],
	selectedEntries = [],
	onBulkDelete,
	onBulkExport,
	onMarkAsRead,
	onMarkAsUnread,
	onBulkRestore,
} ) => {
	const searchInputRef = useRef( null );

	// Dropdown menu options for bulk actions
	const DROPDOWN_MENU_OPTIONS = [
		{
			label: __( 'Mark as Read', 'sureforms' ),
			onClick: onMarkAsRead,
		},
		{
			label: __( 'Mark as Unread', 'sureforms' ),
			onClick: onMarkAsUnread,
		},
	];

	// Check if any entries are selected
	const hasSelectedEntries = useMemo(
		() => selectedEntries.length > 0,
		[ selectedEntries ]
	);

	useEffect( () => {
		if ( searchInputRef.current ) {
			searchInputRef.current.value = searchQuery;
		}
	}, [ searchQuery ] );

	const handleSearchKeyDown = ( event ) => {
		if ( event.key === 'Enter' ) {
			onSearchChange( event.target.value );
		}
	};

	const handleSearchChange = ( value ) => {
		// Clear search query immediately when input becomes empty
		if ( value === '' ) {
			onSearchChange( '' );
		}
	};

	const formatDate = ( value ) => {
		if ( ! value ) {
			return '';
		}
		const date = new Date( value );
		if ( isNaN( date ) ) {
			return '';
		}
		const year = date.getFullYear();
		const month = String( date.getMonth() + 1 ).padStart( 2, '0' );
		const day = String( date.getDate() ).padStart( 2, '0' );
		return `${ day }/${ month }/${ year }`;
	};

	const dateFilterProps = dateRange &&
		dateRange?.from &&
		dateRange?.to && {
		value: `${ formatDate( dateRange?.from ) } - ${ formatDate(
			dateRange?.to
		) }`,
		suffix: (
			<Button
				type="button"
				onClick={ () => {
					onDateRangeChange( null );
				} }
				variant="ghost"
				size="xs"
				className="bg-transparent p-0 pointer-events-auto text-icon-secondary hover:text-icon-primary transition"
				icon={ <X /> }
			/>
		),
	};

	return (
		<div className="flex items-center gap-4">
			<Button
				variant="outline"
				size="sm"
				onClick={ onBulkExport }
				icon={ <ArchiveRestore className="w-4 h-4" /> }
				iconPosition="left"
				className="min-w-fit"
			>
				{ __( 'Export', 'sureforms' ) }
			</Button>

			{ /* Show filters when no items are selected */ }
			{ ! hasSelectedEntries && (
				<>
					<div className="w-[150px]">
						<Select
							value={ statusFilter }
							onChange={ onStatusFilterChange }
							size="sm"
						>
							<Select.Button
								placeholder={ __( 'Status', 'sureforms' ) }
							>
								{
									STATUS_OPTIONS.find(
										( option ) =>
											option.value === statusFilter
									)?.label
								}
							</Select.Button>
							<Select.Options className="z-999999">
								{ STATUS_OPTIONS.map( ( option ) => (
									<Select.Option
										key={ option.value }
										value={ option.value }
									>
										{ option.label }
									</Select.Option>
								) ) }
							</Select.Options>
						</Select>
					</div>

					<div className="w-[160px]">
						<Select
							value={ formFilter }
							onChange={ onFormFilterChange }
							size="sm"
						>
							<Select.Button
								placeholder={ __( 'All Forms', 'sureforms' ) }
							>
								{
									formOptions.find(
										( option ) =>
											option.value === formFilter
									)?.label
								}
							</Select.Button>
							<Select.Options className="z-999999">
								{ formOptions.map( ( option ) => (
									<Select.Option
										key={ option.value }
										value={ option.value }
									>
										{ option.label }
									</Select.Option>
								) ) }
							</Select.Options>
						</Select>
					</div>

					<div className="min-w-[210px]">
						<DatePicker
							value={ dateRange }
							onApply={ onDateRangeChange }
							trigger={ ( { setShow } ) => (
								<Input
									type="text"
									placeholder="dd/mm/yyyy - dd/mm/yyyy"
									readOnly
									onClick={ () =>
										setShow( ( prev ) => ! prev )
									}
									prefix={
										<Calendar className="w-4 h-4 text-icon-secondary" />
									}
									{ ...dateFilterProps }
								/>
							) }
						/>
					</div>
					{ /* Search box */ }
					<div className="w-48">
						<Input
							type="search"
							placeholder={ __(
								'Search your entry.',
								'sureforms'
							) }
							ref={ searchInputRef }
							onChange={ handleSearchChange }
							onKeyDown={ handleSearchKeyDown }
							prefix={
								<Search className="w-4 h-4 text-icon-secondary" />
							}
						/>
					</div>
				</>
			) }

			{ /* Show bulk action buttons when items are selected */ }
			{ hasSelectedEntries && (
				<>
					{ /* Show restore button when trash filter is active */ }
					{ statusFilter === 'trash' && (
						<Button
							variant="outline"
							size="sm"
							onClick={ onBulkRestore }
							icon={ <RotateCcw className="w-4 h-4" /> }
							iconPosition="left"
							className="min-w-fit"
						>
							{ __( 'Restore', 'sureforms' ) }
						</Button>
					) }

					<Button
						variant="outline"
						size="sm"
						onClick={ onBulkDelete }
						icon={ <Trash2 className="w-4 h-4" /> }
						iconPosition="left"
						className="min-w-fit"
						destructive
					>
						{ __( 'Delete', 'sureforms' ) }
					</Button>

					<DropdownMenu placement="bottom-end">
						<DropdownMenu.Trigger>
							<Button
								variant="outline"
								size="sm"
								icon={ <MoreVertical className="w-4 h-4" /> }
								className="min-w-fit px-2"
							/>
						</DropdownMenu.Trigger>
						<DropdownMenu.Portal id="srfm-dialog-root">
							<DropdownMenu.ContentWrapper>
								<DropdownMenu.Content className="w-48">
									<DropdownMenu.List>
										{ DROPDOWN_MENU_OPTIONS.map(
											( option, index ) => (
												<DropdownMenu.Item
													key={ index }
													onClick={ option.onClick }
													className="text-sm font-normal text-text-secondary hover:bg-background-secondary hover:text-text-primary focus:bg-background-secondary focus:text-text-primary cursor-pointer"
												>
													{ option.label }
												</DropdownMenu.Item>
											)
										) }
									</DropdownMenu.List>
								</DropdownMenu.Content>
							</DropdownMenu.ContentWrapper>
						</DropdownMenu.Portal>
					</DropdownMenu>
				</>
			) }
		</div>
	);
};

export default EntriesFilters;
