import { useState } from '@wordpress/element';
import { Button, Text } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import { ArrowLeft, ArrowRight, Trash2 } from 'lucide-react';
import { useEntryLogs, useDeleteEntryLog } from '../hooks/useEntriesQuery';

/**
 * EntryLogsSection Component
 * Displays entry logs and activities with pagination and delete functionality
 *
 * @param {Object}   props
 * @param {number}   props.entryId        - The entry ID for fetching logs
 * @param {Function} props.onConfirmation - Handler function for triggering confirmations
 * @return {JSX.Element} EntryLogsSection component
 */
const EntryLogsSection = ( { entryId, onConfirmation } ) => {
	const [ pagination, setPagination ] = useState( { page: 1, per_page: 3 } );

	// Fetch entry logs
	const { data: logsData, isLoading } = useEntryLogs( entryId, pagination );

	// Delete log mutation
	const { mutate: deleteLog, isPending: isDeleting } = useDeleteEntryLog();

	// Extract logs from data
	const logs = logsData?.logs || [];
	const totalPages = logsData?.total_pages || 1;
	const currentPage = pagination.page;

	/**
	 * Format timestamp to YYYY-MM-DD HH:MM:SS format
	 *
	 * @param {string|number} timestamp - The timestamp to format
	 * @return {string} Formatted date string
	 */
	const formatTimestamp = ( timestamp ) => {
		if ( ! timestamp ) {
			return '';
		}

		const date = new Date( timestamp );
		const year = date.getFullYear();
		const month = String( date.getMonth() + 1 ).padStart( 2, '0' );
		const day = String( date.getDate() ).padStart( 2, '0' );
		const hours = String( date.getHours() ).padStart( 2, '0' );
		const minutes = String( date.getMinutes() ).padStart( 2, '0' );
		const seconds = String( date.getSeconds() ).padStart( 2, '0' );

		return `${ year }-${ month }-${ day } ${ hours }:${ minutes }:${ seconds }`;
	};

	const handleDeleteLog = ( logId ) => {
		onConfirmation( {
			title: __( 'Delete Log Entry?', 'sureforms' ),
			description: __(
				'This action cannot be undone. The log entry will be permanently deleted.',
				'sureforms'
			),
			confirmLabel: __( 'Delete Log', 'sureforms' ),
			onConfirm: () => handleDeleteLogConfirm( logId ),
			isLoading: isDeleting,
			destructive: true,
		} );
	};

	const handleDeleteLogConfirm = ( logId ) => {
		deleteLog( { id: entryId, log_id: logId } );
	};

	const handlePreviousPage = () => {
		setPagination( ( prev ) => ( {
			...prev,
			page: Math.max( 1, prev.page - 1 ),
		} ) );
	};

	const handleNextPage = () => {
		setPagination( ( prev ) => ( {
			...prev,
			page: Math.min( totalPages, prev.page + 1 ),
		} ) );
	};

	return (
		<>
			<div className="bg-background-primary border-0.5 border-solid border-border-subtle rounded-lg shadow-sm">
				<div className="pb-0 px-4 pt-4">
					<h3 className="text-base font-semibold text-text-primary">
						{ __( 'Entry Logs', 'sureforms' ) }
					</h3>
				</div>
				<div className="p-4 space-y-1 relative before:content-[''] before:block before:absolute before:inset-3 before:bg-background-secondary before:rounded-lg">
					{ isLoading ? (
						<div className="relative text-center py-4 text-text-secondary">
							{ __( 'Loading logs…', 'sureforms' ) }
						</div>
					) : logs.length === 0 ? (
						<div className="relative text-center py-4 text-text-secondary">
							{ __( 'No logs available.', 'sureforms' ) }
						</div>
					) : (
						logs.map( ( log ) => (
							<div
								key={ log.id }
								className="bg-background-primary rounded-md p-3 relative shadow-sm flex items-start justify-between gap-3"
							>
								<div className="flex-1 space-y-2">
									<div className="text-sm font-semibold text-text-primary">
										{ log.title }{ ' ' }
										{ log.timestamp &&
											`at ${ formatTimestamp(
												log.timestamp
											) }` }
									</div>
									{ log.messages &&
										log.messages.map(
											( message, index ) => (
												<Text
													key={ index }
													size={ 14 }
													weight={ 400 }
													color="primary"
												>
													{ message }
												</Text>
											)
										) }
								</div>
								<Button
									className="text-icon-secondary hover:text-icon-primary"
									variant="ghost"
									size="xs"
									icon={ <Trash2 /> }
									onClick={ () => handleDeleteLog( log.id ) }
									disabled={ isDeleting }
									aria-label={ __(
										'Delete log',
										'sureforms'
									) }
								/>
							</div>
						) )
					) }
				</div>
				{ /* Pagination */ }
				<div className="w-full flex items-center justify-end pb-3 pr-3 pl-3">
					<div className="flex items-center gap-2">
						<Button
							variant="ghost"
							size="xs"
							icon={ <ArrowLeft /> }
							iconPosition="left"
							onClick={ handlePreviousPage }
							disabled={ currentPage === 1 || isLoading }
						>
							{ __( 'Previous', 'sureforms' ) }
						</Button>
						<Button
							variant="ghost"
							size="xs"
							icon={ <ArrowRight /> }
							iconPosition="right"
							onClick={ handleNextPage }
							disabled={ currentPage === totalPages || isLoading }
						>
							{ __( 'Next', 'sureforms' ) }
						</Button>
					</div>
				</div>
			</div>
		</>
	);
};

export default EntryLogsSection;
