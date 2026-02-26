import { __ } from '@wordpress/i18n';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import { Button } from '@bsf/force-ui';
import { useNavigate } from 'react-router-dom';

/**
 * EntryNavigation Component
 * Provides Previous/Next navigation buttons for browsing entries
 *
 * @param {Object}      props
 * @param {number|null} props.previousEntryId - ID of the previous entry (null if none)
 * @param {number|null} props.nextEntryId     - ID of the next entry (null if none)
 * @return {JSX.Element} EntryNavigation component
 */
const EntryNavigation = ( { previousEntryId, nextEntryId } ) => {
	const navigate = useNavigate();

	/**
	 * Handle navigation to previous entry
	 * Uses replace: true to avoid polluting browser history
	 */
	const handlePrevious = () => {
		if ( previousEntryId ) {
			navigate( `/entry/${ previousEntryId }`, { replace: true } );
		}
	};

	/**
	 * Handle navigation to next entry
	 * Uses replace: true to avoid polluting browser history
	 */
	const handleNext = () => {
		if ( nextEntryId ) {
			navigate( `/entry/${ nextEntryId }`, { replace: true } );
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
		<div className="flex justify-end gap-2">
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
				className="text-text-primary hover:text-text-primary outline-0 disabled:opacity-50 disabled:cursor-not-allowed border-0.5 border-solid border-badge-color-disabled shadow-sm"
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
				className="text-text-primary hover:text-text-primary outline-0 disabled:opacity-50 disabled:cursor-not-allowed border-0.5 border-solid border-badge-color-disabled shadow-sm"
			>
				{ __( 'Next', 'sureforms' ) }
			</Button>
		</div>
	);
};

export default EntryNavigation;
