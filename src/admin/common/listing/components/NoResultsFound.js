import { __ } from '@wordpress/i18n';
import { Container, Button, Text } from '@bsf/force-ui';

const NoResultsFound = ( { handleClearFilters } ) => {
	return (
		<div className="p-6">
			<Container className="flex items-center justify-center p-8 bg-background-primary rounded-lg">
				<div className="text-center max-w-md">
					<Text
						size={ 18 }
						lineHeight={ 28 }
						weight={ 600 }
						color="primary"
						className="mb-2"
					>
						{ __( 'No results found', 'sureforms' ) }
					</Text>
					<Text
						size={ 16 }
						lineHeight={ 24 }
						weight={ 400 }
						color="secondary"
						className="mb-4"
					>
						{ __(
							"We couldn't find any records matching your filters. Try adjusting the filters or resetting them to see all results.",
							'sureforms'
						) }
					</Text>
					<Button
						variant="outline"
						size="sm"
						onClick={ handleClearFilters }
					>
						{ __( 'Clear Filter', 'sureforms' ) }
					</Button>
				</div>
			</Container>
		</div>
	);
};

export default NoResultsFound;
