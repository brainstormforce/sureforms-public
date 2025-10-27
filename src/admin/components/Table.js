import { __ } from '@wordpress/i18n';
import { ChevronsUpDown, ChevronUp, ChevronDown } from 'lucide-react';
import { Container, Table as FUITable, Skeleton } from '@bsf/force-ui';
import { cn } from '@Utils/Helpers';

/**
 * Table Component
 * Displays the entries table with headers and rows
 *
 * @param {Object}   props
 * @param {Array}    props.data                 - Array of data objects
 * @param {Array}    props.columns              - Array of column definitions
 * @param {Array}    props.selectedItems        - Array of selected item IDs
 * @param {Function} props.onToggleAll          - Handler for toggle all checkbox
 * @param {Function} props.onChangeRowSelection - Handler for row selection change
 * @param {boolean}  props.indeterminate        - Whether the header checkbox is indeterminate
 * @param {boolean}  props.isLoading            - Whether data is loading
 * @param {Function} props.onSort               - Handler for sort column change
 * @param {Function} props.getSortDirection     - Function to get sort direction for a column
 * @param {string}   props.emptyMessage         - Message to display when no data
 * @param {Node}     props.children             - Child components (typically pagination)
 */
const Table = ( {
	data = [],
	columns = [],
	selectedItems = [],
	onToggleAll,
	onChangeRowSelection,
	indeterminate = false,
	isLoading = false,
	onSort,
	getSortDirection,
	emptyMessage = __( 'No entries found', 'sureforms' ),
	children,
} ) => {
	return (
		<FUITable className="rounded-md" checkboxSelection>
			<FUITable.Head
				selected={ !! selectedItems.length }
				onChangeSelection={ onToggleAll }
				indeterminate={ indeterminate }
			>
				{ columns.map( ( header, index ) => {
					const sortDirection = header.sortable
						? getSortDirection?.( header?.sortBy ?? header.key )
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
							justify={ header.align === 'right' && 'end' }
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
						<FUITable.HeadCell
							key={ index }
							className={ cn(
								'whitespace-nowrap',
								header?.headerClassName
							) }
						>
							{ header.sortable ? (
								<div
									role="button"
									tabIndex={ 0 }
									className="cursor-pointer select-none"
									onClick={ () =>
										onSort?.( header?.sortBy ?? header.key )
									}
									onKeyDown={ ( e ) => {
										if (
											e.key === 'Enter' ||
											e.key === ' '
										) {
											e.preventDefault();
											onSort?.(
												header?.sortBy ?? header.key
											);
										}
									} }
								>
									{ content }
								</div>
							) : (
								content
							) }
						</FUITable.HeadCell>
					);
				} ) }
			</FUITable.Head>
			<FUITable.Body>
				{ isLoading ? (
					Array.from( { length: 10 }, ( _, index ) => (
						<FUITable.Row
							key={ index }
							className="[&_div:has(label)]:invisible relative"
						>
							{ columns.map( ( header, headerIndex ) => (
								<FUITable.Cell key={ headerIndex }>
									<Skeleton className="absolute left-3.5 size-4 rounded-md" />
									<Skeleton
										variant="rectangular"
										className={ cn(
											'h-4 rounded-md w-28',
											header.align === 'right' &&
												'ml-auto'
										) }
									/>
								</FUITable.Cell>
							) ) }
						</FUITable.Row>
					) )
				) : data.length === 0 ? (
					<FUITable.Row className="[&_div:has(label)]:invisible">
						<FUITable.Cell colSpan={ columns.length }>
							<div className="text-center py-8 text-text-secondary">
								{ emptyMessage }
							</div>
						</FUITable.Cell>
					</FUITable.Row>
				) : (
					data.map( ( item ) => (
						<FUITable.Row
							key={ item.id }
							selected={ selectedItems.includes( item.id ) }
							onChangeSelection={ onChangeRowSelection }
							className="hover:bg-background-primary"
							value={ item }
						>
							{ columns.map( ( col, colIndex ) => (
								<FUITable.Cell
									key={ colIndex }
									className={ cn( col?.columnsClassName ) }
								>
									{ col.render
										? col.render( item )
										: item[ col.key ] }
								</FUITable.Cell>
							) ) }
						</FUITable.Row>
					) )
				) }
			</FUITable.Body>
			<FUITable.Footer className="flex flex-col md:flex-row md:justify-between">
				{ isLoading ? (
					<div className="w-full flex items-center justify-between">
						<Skeleton className="h-8 w-24 rounded-md" />
						<Skeleton className="h-8 w-32 rounded-md" />
					</div>
				) : (
					children
				) }
			</FUITable.Footer>
		</FUITable>
	);
};

export default Table;
