import { __ } from '@wordpress/i18n';
import { Select, Pagination as FUIPagination, Text } from '@bsf/force-ui';
import { cn } from '@Utils/Helpers';

/**
 * Default pagination options
 */
const DEFAULT_PER_PAGE_OPTIONS = [ 10, 20, 50, 100 ];

/**
 * Generate pagination range with ellipsis
 *
 * @param {number} currentPage  - Current active page
 * @param {number} totalPages   - Total number of pages
 * @param {number} siblingCount - Number of pages to show on each side of current page
 * @return {Array} Array containing page numbers and 'ellipsis' strings
 */
const getPaginationRange = ( currentPage, totalPages, siblingCount = 1 ) => {
	// Calculate common values
	const siblingFactor = siblingCount * 2; // Sibling count multiplied by 2
	const totalPageNumbers = siblingFactor + 5; // Total numbers including ellipses and edges

	if ( totalPageNumbers >= totalPages ) {
		// If all pages can fit within the range
		return Array.from( { length: totalPages }, ( _, i ) => i + 1 );
	}

	// Calculate indices
	const leftSiblingIndex = Math.max( currentPage - siblingCount, 1 ); // Left sibling index
	const rightSiblingIndex = Math.min(
		currentPage + siblingCount,
		totalPages
	);

	const showLeftEllipsis = leftSiblingIndex > 2;
	const showRightEllipsis = rightSiblingIndex < totalPages - 1;

	// Constants for the first and last pages
	const firstPage = 1;
	const lastPage = totalPages;

	const pages = [];

	if ( ! showLeftEllipsis && showRightEllipsis ) {
		// Calculate range for the left side
		const leftItemCount = 3 + siblingFactor; // Number of items on the left
		const leftRange = Array.from(
			{ length: leftItemCount },
			( _, i ) => i + 1
		);
		pages.push( ...leftRange, 'ellipsis', lastPage );
	} else if ( showLeftEllipsis && ! showRightEllipsis ) {
		// Calculate range for the right side
		const rightItemCount = 3 + siblingFactor; // Number of items on the right
		const rightRange = Array.from(
			{ length: rightItemCount },
			( _, i ) => totalPages - rightItemCount + i + 1
		);
		pages.push( firstPage, 'ellipsis', ...rightRange );
	} else if ( showLeftEllipsis && showRightEllipsis ) {
		// Calculate middle range
		const middleRange = Array.from(
			{ length: siblingFactor + 1 },
			( _, i ) => currentPage - siblingCount + i
		);
		pages.push(
			firstPage,
			'ellipsis',
			...middleRange,
			'ellipsis',
			lastPage
		);
	}

	return pages;
};

/**
 * Unified Pagination Component
 * Displays pagination controls and items per page selector
 *
 * @param {Object}   props
 * @param {number}   props.currentPage     - Current page number
 * @param {number}   props.totalPages      - Total number of pages
 * @param {number}   props.perPage         - Number of items per page
 * @param {Function} props.onPageChange    - Handler for page change
 * @param {Function} props.onPerPageChange - Handler for items per page change
 * @param {Function} props.onNextPage      - Handler for next page
 * @param {Function} props.onPreviousPage  - Handler for previous page
 * @param {Array}    props.perPageOptions  - Array of per page options
 * @param {string}   props.className       - Additional CSS classes
 * @param {boolean}  props.responsive      - Enable responsive design
 */
const Pagination = ( {
	currentPage,
	totalPages,
	perPage,
	onPageChange,
	onPerPageChange,
	onNextPage,
	onPreviousPage,
	perPageOptions = DEFAULT_PER_PAGE_OPTIONS,
	className = '',
	responsive = true,
} ) => {
	const paginationItems = getPaginationRange( currentPage, totalPages );

	// Page info component
	const PageInfo = () => (
		<div
			className={ cn(
				'flex items-center gap-3',
				responsive && 'w-full justify-center md:justify-start md:w-auto'
			) }
		>
			<Text size={ 14 } color="secondary">
				{ __( 'Page', 'sureforms' ) } { currentPage }{ ' ' }
				{ __( 'out of', 'sureforms' ) } { totalPages }
			</Text>
			<div className="w-20">
				<Select
					value={ perPage }
					onChange={ onPerPageChange }
					size="sm"
				>
					<Select.Button render={ ( value ) => value } />
					<Select.Options className="z-999999">
						{ perPageOptions.map( ( option ) => (
							<Select.Option key={ option } value={ option }>
								{ option }
							</Select.Option>
						) ) }
					</Select.Options>
				</Select>
			</div>
		</div>
	);

	// Pagination controls component
	const PaginationControls = () => (
		<FUIPagination className="w-full md:!w-fit">
			<FUIPagination.Content>
				<FUIPagination.Previous
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
							<FUIPagination.Ellipsis
								key={ `ellipsis-${ index }` }
							/>
						);
					}
					return (
						<FUIPagination.Item
							key={ item }
							isActive={ currentPage === item }
							onClick={ () => onPageChange( item ) }
						>
							{ item }
						</FUIPagination.Item>
					);
				} ) }
				<FUIPagination.Next
					onClick={ onNextPage }
					disabled={ currentPage === totalPages }
					className={ cn(
						currentPage === totalPages &&
							'opacity-50 text-text-tertiary cursor-not-allowed'
					) }
				/>
			</FUIPagination.Content>
		</FUIPagination>
	);

	return (
		<div
			className={ cn(
				'flex items-center justify-between flex-wrap gap-4 w-full',
				className
			) }
		>
			<PageInfo />
			<PaginationControls />
		</div>
	);
};

export default Pagination;

// Export utilities for external use
export { getPaginationRange, DEFAULT_PER_PAGE_OPTIONS };
