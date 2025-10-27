import { __ } from '@wordpress/i18n';
import { Select, Pagination, Text } from '@bsf/force-ui';
import { cn } from '@Utils/Helpers';

/**
 * Entries per page options
 */
const ENTRIES_PER_PAGE_OPTIONS = [ 10, 20, 50, 100 ];

// Generate pagination range
const getPaginationRange = ( currentPage, totalPages ) => {
	const range = [];
	const showPages = 5; // Number of page numbers to show

	if ( totalPages <= showPages ) {
		// Show all pages if total is small
		for ( let i = 1; i <= totalPages; i++ ) {
			range.push( i );
		}
	} else {
		// Show pages with ellipsis
		const start = Math.max( 1, currentPage - 2 );
		const end = Math.min( totalPages, currentPage + 2 );

		if ( start > 1 ) {
			range.push( 1 );
			if ( start > 2 ) {
				range.push( 'ellipsis' );
			}
		}

		for ( let i = start; i <= end; i++ ) {
			range.push( i );
		}

		if ( end < totalPages ) {
			if ( end < totalPages - 1 ) {
				range.push( 'ellipsis' );
			}
			range.push( totalPages );
		}
	}

	return range;
};

// FormsPagination Component - Displays pagination controls and entries per page selector
const FormsPagination = ( {
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
		<div className="flex items-center justify-between flex-wrap gap-4 w-full">
			{ /* Page info and per-page selector */ }
			<div className="flex items-center gap-3">
				<Text size={ 14 } color="secondary">
					{ __( 'Page', 'sureforms' ) } { currentPage }{ ' ' }
					{ __( 'of', 'sureforms' ) } { totalPages }
				</Text>
				<div className="w-20">
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
				<Text size={ 14 } color="secondary">
					{ __( 'per page', 'sureforms' ) }
				</Text>
			</div>

			{ /* Pagination controls */ }
			<Pagination className="w-auto">
				<Pagination.Content>
					<Pagination.Previous
						onClick={ onPreviousPage }
						disabled={ currentPage === 1 }
						className={ cn(
							currentPage === 1 &&
								'opacity-50 text-text-tertiary cursor-not-allowed'
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
								'opacity-50 text-text-tertiary cursor-not-allowed'
						) }
					/>
				</Pagination.Content>
			</Pagination>
		</div>
	);
};

export default FormsPagination;
