import { __ } from '@wordpress/i18n';
import { Select, Pagination } from '@bsf/force-ui';
import { ENTRIES_PER_PAGE_OPTIONS } from '../constants';
import { getPaginationRange } from '../utils/entryHelpers';
import { cn } from '@Utils/Helpers';

/**
 * EntriesPagination Component
 * Displays pagination controls and entries per page selector
 *
 * @param {Object}   props
 * @param {number}   props.currentPage            - Current page number
 * @param {number}   props.totalPages             - Total number of pages
 * @param {number}   props.entriesPerPage         - Number of entries per page
 * @param {Function} props.onPageChange           - Handler for page change
 * @param {Function} props.onEntriesPerPageChange - Handler for entries per page change
 * @param {Function} props.onNextPage             - Handler for next page
 * @param {Function} props.onPreviousPage         - Handler for previous page
 */
const EntriesPagination = ( {
	currentPage,
	totalPages,
	entriesPerPage,
	onPageChange,
	onEntriesPerPageChange,
	onNextPage,
	onPreviousPage,
} ) => {
	const paginationItems = getPaginationRange( currentPage, totalPages );

	return (
		<>
			<div className="flex items-center gap-3 w-full justify-center md:justify-start md:w-auto">
				<span className="text-sm text-text-secondary">
					{ __( 'Page', 'sureforms' ) } { currentPage }{ ' ' }
					{ __( 'out of', 'sureforms' ) } { totalPages }
				</span>
				<div className="w-16">
					<Select
						value={ entriesPerPage }
						onChange={ onEntriesPerPageChange }
						size="sm"
					>
						<Select.Button render={ ( value ) => value } />
						<Select.Options className="z-999999">
							{ ENTRIES_PER_PAGE_OPTIONS.map( ( option ) => (
								<Select.Option key={ option } value={ option }>
									{ option }
								</Select.Option>
							) ) }
						</Select.Options>
					</Select>
				</div>
			</div>

			<Pagination className="w-full md:!w-fit">
				<Pagination.Content>
					<Pagination.Previous
						onClick={ onPreviousPage }
						disabled={ currentPage === 1 }
						className={ cn(
							currentPage === 1 && 'opacity-50 text-text-tertiary'
						) }
					/>
					{ paginationItems.map( ( item, index ) => {
						if ( item === 'ellipsis' ) {
							return (
								<Pagination.Ellipsis
									key={ `ellipsis-${ index }` }
								/>
							);
						}
						return (
							<Pagination.Item
								key={ item }
								isActive={ currentPage === item }
								onClick={ () => onPageChange( item ) }
							>
								{ item }
							</Pagination.Item>
						);
					} ) }
					<Pagination.Next
						onClick={ onNextPage }
						disabled={ currentPage === totalPages }
						className={ cn(
							currentPage === totalPages &&
								'opacity-50 text-text-tertiary'
						) }
					/>
				</Pagination.Content>
			</Pagination>
		</>
	);
};

export default EntriesPagination;
