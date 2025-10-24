import { __ } from '@wordpress/i18n';
import { useRef, useEffect, useMemo } from '@wordpress/element';
import { Search, X, Trash2 } from 'lucide-react';
import { Input, Button, Container, Badge } from '@bsf/force-ui';

/**
 * FormsFilters Component
 * Displays status filters, search, and bulk actions
 */
const FormsFilters = ( {
	searchQuery,
	onSearchChange,
	selectedForms = [],
	onBulkTrash,
} ) => {
	const searchInputRef = useRef( null );

	// Check if any forms are selected
	const hasSelectedForms = useMemo(
		() => selectedForms.length > 0,
		[ selectedForms ]
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

	const clearSearch = () => {
		if ( searchInputRef.current ) {
			searchInputRef.current.value = '';
		}
		onSearchChange( '' );
	};

	// Render bulk actions when forms are selected
	if ( hasSelectedForms ) {
		return (
			<Container
				direction="row"
				align="center"
				justify="between"
				className="w-full p-4 gap-4 rounded-xl bg-background-primary border-0.5 border-solid border-border-subtle shadow-sm-blur-2"
			>
				<Container.Item>
					<span className="text-sm text-text-secondary">
						{ selectedForms.length } { __( 'selected', 'sureforms' ) }
					</span>
				</Container.Item>

				<Container.Item>
					<Button
						variant="ghost"
						size="sm"
						icon={ <Trash2 className="w-4 h-4" /> }
						iconPosition="left"
						onClick={ onBulkTrash }
						destructive
					>
						{ __( 'Move to Trash', 'sureforms' ) }
					</Button>
				</Container.Item>
			</Container>
		);
	}

	// Render regular filters - only search, no status filters
	return (
		<Container direction="column" className="gap-4">
			{/* Search Card */}
			<Container
				direction="row"
				align="center"
				justify="end"
				className="w-full p-4 gap-4 rounded-xl bg-background-primary border-0.5 border-solid border-border-subtle shadow-sm-blur-2"
			>
				<Container.Item>
					<div className="relative min-w-[320px]">
						<Input
							ref={ searchInputRef }
							placeholder={ __( 'Search forms...', 'sureforms' ) }
							size="md"
							onKeyDown={ handleSearchKeyDown }
							onChange={ ( e ) => handleSearchChange( e.target.value ) }
							icon={ <Search className="w-4 h-4" /> }
							iconPosition="left"
							className="w-full"
						/>
						{ searchQuery && (
							<Button
								variant="ghost"
								size="xs"
								icon={ <X className="w-3 h-3" /> }
								onClick={ clearSearch }
								className="absolute right-2 top-1/2 transform -translate-y-1/2 p-1"
							/>
						) }
					</div>
				</Container.Item>
			</Container>

			{/* Active Search Filter Display */}
			{ searchQuery && (
				<Container
					direction="row"
					align="center"
					className="w-full p-4 gap-2 rounded-xl bg-background-primary border-0.5 border-solid border-border-subtle shadow-sm-blur-2"
				>
					<Container.Item>
						<span className="text-sm text-text-secondary font-medium">
							{ __( 'Active filters:', 'sureforms' ) }
						</span>
					</Container.Item>
					
					<Container.Item>
						<Badge
							variant="neutral"
							size="sm"
							label={ `${ __( 'Search:', 'sureforms' ) } ${ searchQuery }` }
							onRemove={ clearSearch }
						/>
					</Container.Item>
				</Container>
			) }
		</Container>
	);
};

export default FormsFilters;