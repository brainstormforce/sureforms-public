import { Container, Label, Text, Button, Tooltip } from '@bsf/force-ui';
import { Trash2 } from 'lucide-react';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

const PaymentLogs = ( {
	logs,
	handleDeleteLog,
	deleteLogMutation,
	formatLogTimestamp,
} ) => {
	const [ showDeletePopup, setShowDeletePopup ] = useState( null );

	return (
		<Container
			className="w-full bg-background-primary border-0.5 border-solid rounded-xl border-border-subtle p-3 gap-2 shadow-sm"
			direction="column"
		>
			<Container className="p-1 gap-2" align="center" justify="between">
				<Label size="sm" className="font-semibold">
					{ __( 'Payment Log', 'sureforms' ) }
				</Label>
			</Container>
			<Container className="flex flex-col items-center justify-center bg-background-secondary gap-1 p-1 rounded-lg min-h-[89px]">
				{ logs && logs.length > 0 ? (
					[ ...logs ].reverse().map( ( log, index ) => {
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
										<Text className="text-sm font-semibold">
											{ logTitle }
										</Text>
										<Text className="text-xs text-text-tertiary mt-1">
											{ formatLogTimestamp(
												log.timestamp
											) }
										</Text>
									</div>
									<Tooltip
										arrow
										offset={ 20 }
										content={
											<Container direction="column" className="gap-2">
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
														onClick={ () => setShowDeletePopup( null ) }
														className="px-3"
													>
														{ __( 'Cancel', 'sureforms' ) }
													</Button>
													<Button
														variant="primary"
														size="xs"
														onClick={ () => {
															handleDeleteLog( index );
															setShowDeletePopup( null );
														} }
														className="px-2 ml-2 bg-red-600 hover:bg-red-700 border-red-600 hover:border-red-700"
													>
														{ __( 'Confirm', 'sureforms' ) }
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
										open={ showDeletePopup === index }
										setOpen={ () => setShowDeletePopup( index ) }
									>
										<Button
											variant="ghost"
											size="xs"
											icon={ <Trash2 className="!size-4" /> }
											onClick={ () => setShowDeletePopup( index ) }
											disabled={ deleteLogMutation.isPending }
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
		</Container>
	);
};

export default PaymentLogs;
