import { __ } from '@wordpress/i18n';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import { Button } from '@bsf/force-ui';
import { useNavigate } from 'react-router-dom';

/**
 * EntryNavigation Component
 * Provides Previous/Next navigation buttons for browsing entries
 *
 * @param {Object}      props
 * @param {number|null} props.previousEntryId   - ID of the previous entry (null if none)
 * @param {number|null} props.nextEntryId       - ID of the next entry (null if none)
 * @param {Object}      props.navigationContext - Filter/sort context to preserve during navigation
 * @return {JSX.Element} EntryNavigation component
 */
const EntryNavigation = ( {
	previousEntryId,
	nextEntryId,
	navigationContext,
} ) => {
	const navigate = useNavigate();

	/**
	 * Build URL with navigation context parameters
	 *
	 * @param {number} entryId - Entry ID to navigate to
	 * @return {string} URL with query parameters
	 */
	const buildNavigationUrl = ( entryId ) => {
		const params = new URLSearchParams();

		// Add filter/sort context if provided
		if ( navigationContext ) {
			if ( navigationContext.form_id ) {
				params.set( 'form_id', navigationContext.form_id );
			}
			if ( navigationContext.status ) {
				params.set( 'status', navigationContext.status );
			}
			if ( navigationContext.search ) {
				params.set( 'search', navigationContext.search );
			}
			if ( navigationContext.date_from ) {
				params.set( 'date_from', navigationContext.date_from );
			}
			if ( navigationContext.date_to ) {
				params.set( 'date_to', navigationContext.date_to );
			}
			if ( navigationContext.orderby ) {
				params.set( 'orderby', navigationContext.orderby );
			}
			if ( navigationContext.order ) {
				params.set( 'order', navigationContext.order );
			}
		}

		const queryString = params.toString();
		return `/entry/${ entryId }${ queryString ? `?${ queryString }` : '' }`;
	};

	/**
	 * Handle navigation to previous entry
	 */
	const handlePrevious = () => {
		if ( previousEntryId ) {
			navigate( buildNavigationUrl( previousEntryId ) );
		}
	};

	/**
	 * Handle navigation to next entry
	 */
	const handleNext = () => {
		if ( nextEntryId ) {
			navigate( buildNavigationUrl( nextEntryId ) );
		}
	};

	/**
	 * Handle keyboard navigation
	 *
	 * @param {KeyboardEvent} event   - Keyboard event
	 * @param {Function}      handler - Navigation handler function
	 */
	const handleKeyDown = ( event, handler ) => {
		if ( event.key === 'Enter' || event.key === ' ' ) {
			event.preventDefault();
			handler();
		}
	};

	// Don't render if both navigation directions are unavailable
	if ( ! previousEntryId && ! nextEntryId ) {
		return null;
	}

	return (
		<div className="absolute -top-14 right-0 z-10 flex justify-end gap-2">
			<Button
				variant="outline"
				size="sm"
				icon={ <ArrowLeft className="w-4 h-4" /> }
				iconPosition="left"
				onClick={ handlePrevious }
				onKeyDown={ ( e ) => handleKeyDown( e, handlePrevious ) }
				disabled={ ! previousEntryId }
				aria-label={ __( 'Previous entry', 'sureforms' ) }
				aria-disabled={ ! previousEntryId }
				className="text-text-primary hover:text-text-primary disabled:opacity-50 disabled:cursor-not-allowed border border-solid border-badge-color-disabled shadow-sm"
			>
				{ __( 'Previous', 'sureforms' ) }
			</Button>
			<Button
				variant="outline"
				size="sm"
				icon={ <ArrowRight className="w-4 h-4" /> }
				iconPosition="right"
				onClick={ handleNext }
				onKeyDown={ ( e ) => handleKeyDown( e, handleNext ) }
				disabled={ ! nextEntryId }
				aria-label={ __( 'Next entry', 'sureforms' ) }
				aria-disabled={ ! nextEntryId }
				className="text-text-primary hover:text-text-primary disabled:opacity-50 disabled:cursor-not-allowed border border-solid border-badge-color-disabled shadow-sm"
			>
				{ __( 'Next', 'sureforms' ) }
			</Button>
		</div>
	);
};

export default EntryNavigation;
