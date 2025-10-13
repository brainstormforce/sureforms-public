import { __ } from '@wordpress/i18n';
import { ChevronsUpDown, ChevronUp, ChevronDown } from 'lucide-react';
import { Container, Table, Skeleton } from '@bsf/force-ui';
import { TABLE_HEADERS } from '../constants';
import EntriesTableRow from './EntriesTableRow';
import { cn } from '@Utils/Helpers';

/**
 * EntriesTable Component
 * Displays the entries table with headers and rows
 *
 * @param {Object}   props
 * @param {Array}    props.entries              - Array of entry objects
 * @param {Array}    props.selectedEntries      - Array of selected entry IDs
 * @param {Function} props.onToggleAll          - Handler for toggle all checkbox
 * @param {Function} props.onChangeRowSelection - Handler for row selection change
 * @param {boolean}  props.indeterminate        - Whether the header checkbox is indeterminate
 * @param {Function} props.onEdit               - Handler for edit action
 * @param {Function} props.onDelete             - Handler for delete action
 * @param {Function} props.onRestore            - Handler for restore action
 * @param {boolean}  props.isLoading            - Whether data is loading
 * @param {Function} props.onSort               - Handler for sort column change
 * @param {Function} props.getSortDirection     - Function to get sort direction for a column
 * @param {Node}     props.children             - Child components (typically pagination)
 */
const EntriesTable = ( {
	entries = [],
	selectedEntries = [],
	onToggleAll,
	onChangeRowSelection,
	indeterminate = false,
	onEdit,
	onDelete,
	onRestore,
	isLoading = false,
	onSort,
	getSortDirection,
	children,
} ) => {
	return (
		<Table className="rounded-md" checkboxSelection>
			<Table.Head
				selected={ !! selectedEntries.length }
				onChangeSelection={ onToggleAll }
				indeterminate={ indeterminate }
			>
				{ TABLE_HEADERS.map( ( header, index ) => {
					const sortDirection = header.sortable
						? getSortDirection?.( header.key )
						: null;
					const SortIcon = sortDirection === 'asc'
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
						<Table.HeadCell key={ index }>
							{ header.sortable ? (
								<div
									role="button"
									tabIndex={ 0 }
									className="cursor-pointer select-none"
									onClick={ () => onSort?.( header.key ) }
									onKeyDown={ ( e ) => {
										if ( e.key === 'Enter' || e.key === ' ' ) {
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
				{ isLoading ? (
					Array.from( { length: 10 }, ( _, index ) => (
						<Table.Row key={ index } className="[&_div:has(label)]:invisible relative">
							{ TABLE_HEADERS.map( ( header, headerIndex ) => (
								<Table.Cell key={ headerIndex }>
									<Skeleton className="absolute left-3.5 size-4 rounded-md" />
									<Skeleton
										variant="rectangular"
										className={ cn( 'h-4 rounded-md w-28', header.align === 'right' && 'ml-auto' ) }
									/>
								</Table.Cell>
							) ) }
						</Table.Row>
					) )
				) : entries.length === 0 ? (
					<Table.Row className="[&_div:has(label)]:invisible">
						<Table.Cell colSpan={ TABLE_HEADERS.length }>
							<div className="text-center py-8 text-text-secondary">
								{ __( 'No entries found', 'sureforms' ) }
							</div>
						</Table.Cell>
					</Table.Row>
				) : (
					entries.map( ( entry ) => (
						<EntriesTableRow
							key={ entry.id }
							entry={ entry }
							isSelected={ selectedEntries.includes( entry.id ) }
							onChangeSelection={ onChangeRowSelection }
							onEdit={ onEdit }
							onDelete={ onDelete }
							onRestore={ onRestore }
						/>
					) )
				) }
			</Table.Body>
			<Table.Footer className="flex flex-col lg:flex-row lg:justify-between">
				{ isLoading ? (
					<div className="w-full flex items-center justify-between">
						<Skeleton className="h-8 w-24 rounded-md" />
						<Skeleton className="h-8 w-32 rounded-md" />
					</div>
				) : children }
			</Table.Footer>
		</Table>
	);
};

export default EntriesTable;
