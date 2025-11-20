import { Text, Button } from '@bsf/force-ui';
import { Trash, ArrowLeft, ArrowRight } from 'lucide-react';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { formatDateTime } from './utils';

const PaymentLogs = ( {
	logs,
	handleDeleteLog,
	deleteLogMutation,
	onConfirmation,
} ) => {
	const [ currentPage, setCurrentPage ] = useState( 1 );
	const itemsPerPage = 3;

	// Reverse logs to show latest first
	const reversedLogs = logs ? [ ...logs ].reverse() : [];

	// Calculate pagination
	const totalPages = Math.ceil(
		( reversedLogs?.length || 0 ) / itemsPerPage
	);
	const startIndex = ( currentPage - 1 ) * itemsPerPage;
	const endIndex = startIndex + itemsPerPage;
	const paginatedLogs = reversedLogs?.slice( startIndex, endIndex ) || [];

	// Adjust current page if it exceeds total pages after deletion
	useEffect( () => {
		if ( reversedLogs?.length > 0 ) {
			const maxPossiblePage = Math.ceil(
				reversedLogs.length / itemsPerPage
			);
			if ( currentPage > maxPossiblePage ) {
				setCurrentPage( maxPossiblePage );
			}
		}
	}, [ reversedLogs?.length, currentPage, itemsPerPage ] );

	const handlePreviousPage = () => {
		setCurrentPage( ( prev ) => Math.max( prev - 1, 1 ) );
	};

	const handleNextPage = () => {
		setCurrentPage( ( prev ) => Math.min( prev + 1, totalPages ) );
	};

	const handleDeleteLogConfirmation = ( logIndex ) => {
		onConfirmation( {
			title: __( 'Delete Log?', 'sureforms' ),
			description: __(
				'This action cannot be undone. The log will be permanently deleted.',
				'sureforms'
			),
			confirmLabel: __( 'Delete Log', 'sureforms' ),
			onConfirm: () => handleDeleteLog( logIndex ),
			isLoading: deleteLogMutation.isPending,
			destructive: true,
		} );
	};

	const isLogsAvailable = logs && logs.length > 0;

	return (
		<div className="bg-background-primary border-0.5 border-solid border-border-subtle rounded-lg shadow-sm">
			<div className="pb-0 px-4 pt-4">
				<h3 className="text-base font-semibold text-text-primary">
					{ __( 'Payment Logs', 'sureforms' ) }
				</h3>
			</div>
			<div className="p-4 space-y-1 relative before:content-[''] before:block before:absolute before:inset-3 before:bg-background-secondary before:rounded-lg">
				{ isLogsAvailable ? (
					paginatedLogs.map( ( log, index ) => {
						// Calculate the actual index in the original logs array
						// Since we reversed the logs, we need to map back to original index
						const reversedIndex = startIndex + index;
						const actualIndex = logs.length - 1 - reversedIndex;
						// Defensive checks for log data
						if ( ! log || typeof log !== 'object' ) {
							return null;
						}

						const logTitle =
							log.title || __( 'Untitled Log', 'sureforms' );
						const logMessages = Array.isArray( log.messages )
							? log.messages
							: [];

						const deleteButton = (
							<Button
								variant="ghost"
								size="xs"
								icon={ <Trash className="!size-4" /> }
								onClick={ () =>
									handleDeleteLogConfirmation( actualIndex )
								}
								disabled={ deleteLogMutation.isPending }
								className="text-icon-secondary hover:text-icon-primary"
								aria-label={ __( 'Delete log', 'sureforms' ) }
							/>
						);

						return (
							<div
								key={ index }
								className="bg-background-primary rounded-md p-3 relative shadow-sm flex items-start justify-between gap-3"
							>
								<div className="flex-1 space-y-2">
									<Text className="text-sm font-semibold text-text-secondary">
										{ logTitle }
										{ log?.created_at &&
											` at ${ formatDateTime(
												log.created_at
											) }` }
									</Text>
									{ logMessages.length > 0 && (
										<div className="flex flex-col gap-2">
											{ logMessages.map(
												( message, msgIndex ) => (
													<Text
														key={ msgIndex }
														size={ 14 }
														weight={ 400 }
														color="primary"
														className="[overflow-wrap:anywhere]"
													>
														{ message || '' }
													</Text>
												)
											) }
										</div>
									) }
								</div>
								{ deleteButton }
							</div>
						);
					} )
				) : (
					<div className="relative text-center py-4 text-text-secondary">
						{ __( 'No logs available.', 'sureforms' ) }
					</div>
				) }
			</div>
			{ /* Pagination */ }
			{ isLogsAvailable && (
				<div className="w-full flex items-center justify-end pb-3 pr-3 pl-3">
					<div className="flex items-center gap-2">
						<Button
							variant="ghost"
							size="xs"
							icon={ <ArrowLeft /> }
							iconPosition="left"
							onClick={ handlePreviousPage }
							disabled={ currentPage === 1 }
						>
							{ __( 'Previous', 'sureforms' ) }
						</Button>
						<Button
							variant="ghost"
							size="xs"
							icon={ <ArrowRight /> }
							iconPosition="right"
							onClick={ handleNextPage }
							disabled={ currentPage === totalPages }
						>
							{ __( 'Next', 'sureforms' ) }
						</Button>
					</div>
				</div>
			) }
		</div>
	);
};

export default PaymentLogs;
