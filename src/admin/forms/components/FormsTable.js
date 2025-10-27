import { __ } from '@wordpress/i18n';
import { ChevronsUpDown, ChevronUp, ChevronDown } from 'lucide-react';
import { Container, Table, Skeleton } from '@bsf/force-ui';
import { cn } from '@Utils/Helpers';
import FormsTableRow from './FormsTableRow';

/**
 * Table headers configuration
 */
const TABLE_HEADERS = [
	{
		key: 'title',
		label: __( 'Title', 'sureforms' ),
		sortable: true,
		align: 'left',
		width: 'auto', // Takes remaining space
	},
	{
		key: 'shortcode',
		label: __( 'Shortcode', 'sureforms' ),
		sortable: false,
		align: 'left',
		width: '15%', // 15% of table width
	},
	{
		key: 'entries_count',
		label: __( 'Entries', 'sureforms' ),
		sortable: false,
		align: 'left',
		width: '8%', // Small percentage for numbers
	},
	{
		key: 'author',
		label: __( 'Author', 'sureforms' ),
		sortable: false,
		align: 'left',
		width: '15%', // Moderate percentage for names
	},
	{
		key: 'datetime',
		label: __( 'Date & Time', 'sureforms' ),
		sortable: true,
		align: 'left',
		width: '18%', // Consistent percentage for dates
	},
	{
		key: 'actions',
		label: __( 'Action', 'sureforms' ),
		sortable: false,
		align: 'left',
		width: '160px', // Percentage for action buttons
	},
];

/**
 * FormsTable Component
 * Displays the forms table with headers and rows
 */
const FormsTable = ( {
	forms = [],
	selectedForms = [],
	onToggleAll,
	onChangeRowSelection,
	indeterminate = false,
	onEdit,
	onTrash,
	onRestore,
	onDelete,
	isLoading = false,
	onSort,
	getSortDirection,
} ) => {
	// Skeleton loading rows
	const renderSkeletonRows = () => (
		Array.from( { length: 5 }, ( _, index ) => (
			<Table.Row key={ `skeleton-${ index }` } className="hover:bg-background-primary">
				<Table.Cell><Skeleton className="h-4 w-8" /></Table.Cell>
				<Table.Cell><Skeleton className="h-4 w-32" /></Table.Cell>
				<Table.Cell><Skeleton className="h-6 w-16" /></Table.Cell>
				<Table.Cell><Skeleton className="h-4 w-12" /></Table.Cell>
				<Table.Cell><Skeleton className="h-4 w-24" /></Table.Cell>
				<Table.Cell><Skeleton className="h-4 w-20" /></Table.Cell>
				<Table.Cell><Skeleton className="h-8 w-20" /></Table.Cell>
			</Table.Row>
		) )
	);

	return (
		<Table className="w-full" checkboxSelection>
			<Table.Head
				selected={ !! selectedForms.length }
				onChangeSelection={ onToggleAll }
				indeterminate={ indeterminate }
				className="bg-background-tertiary"
			>
				{ TABLE_HEADERS.map( ( header, index ) => {
					const sortDirection = header.sortable
						? getSortDirection?.( header.key )
						: null;
					const SortIcon =
						sortDirection === 'asc'
							? ChevronUp
							: sortDirection === 'desc'
								? ChevronDown
								: ChevronsUpDown;

					const content = (
						<Container
							align="center"
							className="gap-2"
							justify={ header.align === 'right' ? 'end' : header.align === 'center' ? 'center' : 'start' }
						>
							{ header.label }
							{ header.sortable && (
								<SortIcon
									className={ cn(
										'w-4 h-4',
										sortDirection
											? 'text-text-primary'
											: 'text-text-tertiary'
									) }
								/>
							) }
						</Container>
					);

					return (
						<Table.HeadCell 
							key={ index } 
							style={ { width: header.width } }
						>
							{ header.sortable ? (
								<div
									role="button"
									tabIndex={ 0 }
									className="cursor-pointer select-none"
									onClick={ () => onSort?.( header.key ) }
									onKeyDown={ ( e ) => {
										if (
											e.key === 'Enter' ||
											e.key === ' '
										) {
											e.preventDefault();
											onSort?.( header.key );
										}
									} }
								>
									{ content }
								</div>
							) : (
								content
							) }
						</Table.HeadCell>
					);
				} ) }
			</Table.Head>

			<Table.Body>
				{ isLoading && forms.length === 0 ? (
					renderSkeletonRows()
				) : (
					forms.map( ( form ) => (
						<FormsTableRow
							key={ form.id }
							form={ form }
							isSelected={ selectedForms.includes( form.id ) }
							onChangeSelection={ ( selected ) =>
								onChangeRowSelection( form.id, selected )
							}
							onEdit={ onEdit }
							onTrash={ onTrash }
							onRestore={ onRestore }
							onDelete={ onDelete }
						/>
					) )
				) }
			</Table.Body>
		</Table>
	);
};

export default FormsTable;