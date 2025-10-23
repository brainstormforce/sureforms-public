import { Container, Label, Text, Button, Tooltip } from '@bsf/force-ui';
import { Trash2, ChevronLeft, ChevronRight } from 'lucide-react';
import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';

const PaymentLogs = ( {
	logs,
	handleDeleteLog,
	deleteLogMutation,
	formatLogTimestamp,
} ) => {
	const [ showDeletePopup, setShowDeletePopup ] = useState( null );
	const [ currentPage, setCurrentPage ] = useState( 1 );
	const itemsPerPage = 3;

	// Reverse logs to show latest first
	const reversedLogs = logs ? [ ...logs ].reverse() : [];

	// Calculate pagination
	const totalPages = Math.ceil( ( reversedLogs?.length || 0 ) / itemsPerPage );
	const startIndex = ( currentPage - 1 ) * itemsPerPage;
	const endIndex = startIndex + itemsPerPage;
	const paginatedLogs = reversedLogs?.slice( startIndex, endIndex ) || [];

	// Adjust current page if it exceeds total pages after deletion
	useEffect( () => {
		if ( reversedLogs?.length > 0 ) {
			const maxPossiblePage = Math.ceil( reversedLogs.length / itemsPerPage );
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

	return (
		<Container
			className="w-full bg-background-primary border-0.5 border-solid rounded-xl border-border-subtle p-3 gap-2 shadow-sm"
			direction="column"
		>
			<Container className="p-1 gap-2" align="center" justify="between">
				<Label size="md" className="font-semibold">
					{ __( 'Payment Logs', 'sureforms' ) }
				</Label>
			</Container>
			<Container className="flex flex-col items-center justify-center bg-background-secondary gap-1 p-1 rounded-lg min-h-[89px]">
				{ logs && logs.length > 0 ? (
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

						return (
							<div
								key={ index }
								className="w-full flex flex-col gap-2 p-3 bg-background-primary rounded-lg border border-border-subtle"
							>
								<div className="flex justify-between items-start gap-2">
									<div className="flex-1">
										<Text className="text-base font-semibold">
											{ sprintf(
												/* translators: 1: Log title, 2: Timestamp */
												__(
													'%1$s at %2$s',
													'sureforms'
												),
												logTitle,
												formatLogTimestamp(
													log.timestamp
												)
											) }
										</Text>
									</div>
									<Tooltip
										arrow
										offset={ 20 }
										content={
											<Container
												direction="column"
												className="gap-2"
											>
												<p className="text-[13px] font-normal">
													{ __(
														'Are you sure to delete this?',
														'sureforms'
													) }
												</p>
												<Container className="gap-3">
													<Button
														variant="outline"
														size="xs"
														onClick={ () =>
															setShowDeletePopup(
																null
															)
														}
														className="px-3"
													>
														{ __(
															'Cancel',
															'sureforms'
														) }
													</Button>
													<Button
														variant="primary"
														size="xs"
														onClick={ () => {
															handleDeleteLog(
																actualIndex
															);
															setShowDeletePopup(
																null
															);
														} }
														className="px-2 ml-2 bg-red-600 hover:bg-red-700 border-red-600 hover:border-red-700"
													>
														{ __(
															'Confirm',
															'sureforms'
														) }
													</Button>
												</Container>
											</Container>
										}
										placement="top"
										triggers={ [ 'click', 'focus' ] }
										tooltipPortalId="srfm-settings-container"
										interactive
										className="z-999999"
										variant="light"
										open={ showDeletePopup === actualIndex }
										setOpen={ () =>
											setShowDeletePopup( actualIndex )
										}
									>
										<Button
											variant="ghost"
											size="xs"
											icon={
												<Trash2 className="!size-4" />
											}
											onClick={ () =>
												setShowDeletePopup( actualIndex )
											}
											disabled={
												deleteLogMutation.isPending
											}
											className="text-icon-secondary hover:text-red-700"
										/>
									</Tooltip>
								</div>
								{ logMessages.length > 0 && (
									<div className="flex flex-col gap-1 mt-1">
										{ logMessages.map(
											( message, msgIndex ) => (
												<Text
													key={ msgIndex }
													className="text-xs text-text-secondary"
												>
													{ message || '' }
												</Text>
											)
										) }
									</div>
								) }
							</div>
						);
					} )
				) : (
					<Text className="text-sm text-text-secondary p-3 text-center">
						{ __( 'No payment logs available', 'sureforms' ) }
					</Text>
				) }
			</Container>

			{ /* Pagination Controls - Show only if more than 3 logs */ }
			{ logs && logs.length > itemsPerPage && (
				<Container className="flex items-center justify-between px-2 py-1">
					<Text className="text-xs text-text-secondary">
						{ __( 'Page', 'sureforms' ) } { currentPage } { __( 'of', 'sureforms' ) } { totalPages }
					</Text>
					<Container className="gap-1">
						<Button
							variant="ghost"
							size="xs"
							icon={ <ChevronLeft className="!size-4" /> }
							onClick={ handlePreviousPage }
							disabled={ currentPage === 1 }
							className="text-icon-secondary hover:text-icon-primary"
						/>
						<Button
							variant="ghost"
							size="xs"
							icon={ <ChevronRight className="!size-4" /> }
							onClick={ handleNextPage }
							disabled={ currentPage === totalPages }
							className="text-icon-secondary hover:text-icon-primary"
						/>
					</Container>
				</Container>
			) }
		</Container>
	);
};

export default PaymentLogs;
